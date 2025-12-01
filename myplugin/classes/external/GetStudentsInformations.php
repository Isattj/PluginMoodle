<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/user/lib.php");

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetStudentsInformations extends \core_external\external_api {

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User ID'),
                'username' => new external_value(PARAM_RAW, 'Username'),
                'firstname' => new external_value(PARAM_RAW, 'First name'),
                'lastname' => new external_value(PARAM_RAW, 'Last name'),
                'email' => new external_value(PARAM_RAW, 'User email'),
                'lastlogin' => new external_value(PARAM_INT, 'User last login timestamp'),
                'currentlogin' => new external_value(PARAM_INT, 'User current login timestamp'),
                'firstaccess' => new external_value(PARAM_INT, 'User first access timestamp'),
                'lastcourseaccess' => new external_value(PARAM_INT, 'User last access time in this course', VALUE_OPTIONAL),
                'profileimage' => new external_value(PARAM_RAW, 'User profile image url', VALUE_OPTIONAL),
                'tags' => new external_multiple_structure(
                    new external_single_structure([
                        'tagid' => new external_value(PARAM_INT, 'Tag ID'),
                        'tagname' => new external_value(PARAM_RAW, 'Tag name')
                    ]),
                    'User tags',
                    VALUE_OPTIONAL
                ),
            ])
        );
    }

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course ID'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID'), 
                'An array of user IDs', 
                VALUE_DEFAULT, 
                []
            ),
            'realuserid' => new external_value(PARAM_INT, 'The actual logged-in user ID', VALUE_DEFAULT, 0),
        ]);
    }

    private static function remove_null_informations(array $data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::remove_null_informations($value);
                if ($data[$key] === []) {
                    unset($data[$key]);
                }
            } else if (is_null($value)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    public static function execute($courseid, $userids = [], $realuserid = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'userids' => $userids,
            'realuserid' => $realuserid
        ]);

        $effectiveUser = $USER;
        if (!empty($params['realuserid'])) {
            $realuser = $DB->get_record('user', ['id' => $params['realuserid']], '*', IGNORE_MISSING);
            if ($realuser) {
                $effectiveUser = $realuser;
            }
        }

        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);

        $roleuser = get_user_roles($coursecontext, $effectiveUser->id, true);
        $rolenames = array_map(fn($r) => $r->shortname, $roleuser);

        $teacher = in_array('editingteacher', $rolenames) || in_array('teacher', $rolenames)
            || in_array('manager', $rolenames) || is_siteadmin($effectiveUser);
        $student = in_array('student', $rolenames) && !$teacher;

        if (!is_enrolled($coursecontext, $effectiveUser->id)) {
            throw new \moodle_exception('not enrolled in course', 'local_myplugin');
        }

        if ($teacher) {
            if (empty($params['userids'])) {
                $users = get_enrolled_users($coursecontext);
            } else {
                list($sql, $params_sql) = $DB->get_in_or_equal($params['userids'], SQL_PARAMS_NAMED, 'uid');
                $fields = 'id, username, firstname, lastname, email, lastlogin, currentlogin, firstaccess';
                $users = $DB->get_records_select('user', "id $sql", $params_sql, '', $fields);
            }
        } else {
            $users = [$DB->get_record('user', ['id' => $effectiveUser->id], '*', MUST_EXIST)];
        }

        $result = [];
        foreach ($users as $u) {
            $lastcourseaccess = (int)$DB->get_field('user_lastaccess', 'timeaccess', [
                'userid' => $u->id,
                'courseid' => $params['courseid']
            ]) ?: 0;

            $profileimage = (string)(new \moodle_url('/user/pix.php', ['id' => $u->id, 'size' => 1]));

            $tags_users = \core_tag_tag::get_item_tags('core', 'user', $u->id);
            $tags_data_user = [];
            foreach ($tags_users as $tag_user) {
                $tags_data_user[] = [
                    'tagid' => $tag_user->id,
                    'tagname' => $tag_user->get_display_name(),
                ];
            }

            $result[] = [
                'id' => (int)$u->id,
                'username' => $u->username,
                'firstname' => $u->firstname,
                'lastname' => $u->lastname,
                'email' => $u->email,
                'lastlogin' => (int)($u->lastlogin ?? 0),
                'currentlogin' => (int)($u->currentlogin ?? 0),
                'firstaccess' => (int)($u->firstaccess ?? 0),
                'lastcourseaccess' => $lastcourseaccess,
                'profileimage' => $profileimage,
                'tags' => $tags_data_user
            ];
        }

        return array_map([self::class, 'remove_null_informations'], $result);
    }
}
