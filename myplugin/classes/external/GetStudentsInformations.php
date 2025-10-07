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
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_RAW, 'Username'),
                    'firstname' => new external_value(PARAM_RAW, 'First name'),
                    'lastname' => new external_value(PARAM_RAW, 'Last name'),
                    'email' => new external_value(PARAM_RAW, 'Email'),
                    'lastlogin' => new external_value(PARAM_INT, 'Last login timestamp'),
                    'currentlogin' => new external_value(PARAM_INT, 'Current login timestamp'),
                    'firstaccess' => new external_value(PARAM_INT, 'First access timestamp'),
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

        foreach ($users as $user) {
            $result[] = [
                'userid' => $user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'lastlogin' => $user->lastlogin,
                'currentlogin' => $user->currentlogin,
                'firstaccess' => $user->firstaccess,
            ];
        }
        return $result;
    }
}