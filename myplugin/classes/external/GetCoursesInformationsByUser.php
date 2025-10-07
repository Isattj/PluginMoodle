<?php

namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/course/externallib.php");

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_exception;


class GetCoursesInformationsByUser extends external_api {

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
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_RAW, 'Full course name'),
                'shortname' => new external_value(PARAM_RAW, 'Short course name'),
                'startdate' => new external_value(PARAM_INT, 'Course start date (timestamp)'),
                'enddate' => new external_value(PARAM_INT, 'Course end date (timestamp)'),
                'timemodified' => new external_value(PARAM_INT, 'Last modification time (timestamp)'),

                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'User ID'),
                        'fullname' => new external_value(PARAM_RAW, 'Full name'),
                        'email' => new external_value(PARAM_RAW, 'User email'),
                        'roles' => new external_multiple_structure(
                            new external_single_structure([
                                'roleid' => new external_value(PARAM_INT, 'Role ID'),
                                'rolename' => new external_value(PARAM_RAW, 'Role name'),
                            ]),
                        ),
                    ]),
                ),
            ])
        );
    }


public static function execute($courseid, $userids = []) {
    global $USER, $DB;

    $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

    $context = context_system::instance();
    self::validate_context($context);


    $courses = enrol_get_users_courses($params['userid'], true, '*');

    $result = [];

    foreach ($courses as $course){
        $coursecontext = \context_course::instance($course->id);

        $enrolledusers = get_enrolled_users($coursecontext, '', 0, 'u.id, u.firstname, u.lastname, u.email');

        $userlist = [];

        foreach ($enrolledusers as $u){
            $roles = get_user_roles($coursecontext, $u->id);
            $roleslist = [];

            foreach ($roles as $r){
                $roleslist[] = [
                    'roleid' => $r->roleid,
                    'rolename' => $r->shortname,
                ];
            }

            $userlist[] = [
                'id' => $u->id,
                'fullname' => fullname($u),
                'email' => $u->email,
                'roles' => $roleslist,
            ];
        }

        $result[] = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
            'timemodified' => $course->timemodified,
            'users' => $userlist,
        ];
    }
    return $result;
}
}