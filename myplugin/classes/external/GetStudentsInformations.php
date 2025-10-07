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
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_RAW, 'Username'),
                    'firstname' => new external_value(PARAM_RAW, 'first name'),
                    'lastname' => new external_value(PARAM_RAW, 'last name'),
                    'email' => new external_value(PARAM_RAW, 'User email'),
                    'lastlogin' => new external_value(PARAM_INT, 'User last login time'),
                    'currentlogin' => new external_value(PARAM_INT, 'User current login time'),
                    'firstaccess' => new external_value(PARAM_INT, 'User first access time'),
                    'lastcourseaccess' => new external_value(PARAM_INT, 'User last access time in this course'),
                    'profileimage' => new external_value(PARAM_RAW, 'User profile image'),
                    'roles' => new external_multiple_structure(
                        new external_single_structure([
                            'roleid' => new external_value(PARAM_INT, 'Role ID'),
                            'rolename' => new external_value(PARAM_RAW, 'Role name'),
                        ]),
                    )
                ]
            )
        );
    }

    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'The course ID'),
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID'), 'An array of user IDs', VALUE_DEFAULT, []
                ),
            ]
        );
    }

    public static function execute($courseid, $userids = []) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(),
            ['courseid' => $courseid, 'userids' => $userids]);

        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);

        require_capability('moodle/course:viewparticipants', $coursecontext);

        $result = [];

        if (empty($params['userids'])) {
            $users = get_enrolled_users($coursecontext);
        } else {
            $fields = 'id, username, firstname, lastname, email, lastlogin, currentlogin, firstaccess';
            list($sql, $params_sql) = $DB->get_in_or_equal($params['userids'], SQL_PARAMS_NAMED, 'uid');
            $users = $DB->get_records_select('user', "id $sql", $params_sql, '', $fields);
        }

        $lastcourseaccess = (int)$DB->get_field('user_lastaccess', 'timeaccess', [
                'userid' => $u->id,
                'courseid' => $course->id
            ]) ?: 0;

        $u->profileimage = $CFG->wwwroot . '/user/pix.php/' . $u->id . '/f1.jpg';

        foreach ($users as $user) {
            $result[] = [
                'id' => $u->id,
                'username' => $u->username,
                'firstname' => $u->firstname,
                'lastname' => $u->lastname,
                'email' => $u->email,
                'lastlogin' => $u->lastlogin,
                'currentlogin' => $u->currentlogin,
                'firstaccess' => $u->firstaccess,
                'lastcourseaccess' => $lastcourseaccess,
                'profileimage' => $u->profileimage,
                'roles' => $roles_data,
            ];
        }
        return $result;
    }
}