<?php

namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->libdir/enrollib.php");

use context_user;
use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetLogsUsers extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'userid' => new external_value(PARAM_INT, 'User ID'),
            ]
        );
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_RAW, 'Full course name'),
                'shortname' => new external_value(PARAM_RAW, 'Short course name'),
                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'userid' => new external_value(PARAM_INT, 'User ID'),
                        'username' => new external_value(PARAM_RAW, 'Username'),
                        'firstname' => new external_value(PARAM_RAW, 'First name'),
                        'lastname' => new external_value(PARAM_RAW, 'Last name'),
                        'email' => new external_value(PARAM_RAW, 'User email'),
                        'logs' => new external_multiple_structure(
                            new external_single_structure([
                                'logid' => new external_value(PARAM_INT, 'Log ID'),
                                'eventname' => new external_value(PARAM_RAW, 'Event name'),
                                'component' => new external_value(PARAM_RAW, 'Component'),
                                'action' => new external_value(PARAM_RAW, 'Action'),
                                'target' => new external_value(PARAM_RAW, 'Target'),
                                'timecreated' => new external_value(PARAM_RAW, 'Timestamp of log creation'),
                            ])
                        ),
                    ])
                ),
            ])
        );
    }

    public static function execute($userid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);
        $usercontext = context_user::instance($params['userid']);
        self::validate_context($usercontext);

        $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname');
        $result = [];

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);

            $roles = get_user_roles($context, $params['userid']);
            $rolenames = array_map(fn($r) => $r->shortname, $roles);

            $canviewall = false;
            foreach($rolenames as $r){
                if(in_array($r, ['editingteacher', 'teacher', 'manager', 'admin'])){
                    $canviewall = true;
                    break;
                }
            }

            if($canviewall){
                $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email');
            } else{
                $enrolled_users = [
                    $DB->get_record('user', [
                        'id' => $params['userid']
                    ], 'id, username, firstname, lastname, email')
                ];
            }
           $users_data = [];

            foreach ($enrolled_users as $u) {
                $sql = "SELECT id, eventname, component, action, target, timecreated
                        FROM {logstore_standard_log}
                        WHERE courseid = :courseid AND userid = :userid
                        ORDER BY timecreated DESC";
                
                $logs = $DB->get_records_sql($sql, [
                    'courseid' => $course->id,
                    'userid' => $u->id
                ], 0, 10);

                $logs_data = [];
                foreach ($logs as $log) {
                    $logs_data[] = [
                        'logid' => (int)$log->id,
                        'eventname' => $log->eventname ?? '',
                        'component' => $log->component ?? '',
                        'action' => $log->action ?? '',
                        'target' => $log->target ?? '',
                        'timecreated' => date('d/m/Y H:i:s', $log->timecreated),
                    ];
                }

                $users_data[] = [
                    'userid' => $u->id,
                    'username' => $u->username,
                    'firstname' => $u->firstname,
                    'lastname' => $u->lastname,
                    'email' => $u->email,
                    'logs' => $logs_data,
                ];
            }

            $result[] = [
                'courseid' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'users' => $users_data,
            ];
        }

        return $result;
    }
}