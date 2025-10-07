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
                        ),
                    ]),
                ),
            ])
        );
    }

public static function execute($userid) {
    global $DB;

    $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

    $usercontext = context_user::instance($params['userid']);
    self::validate_context($usercontext);


    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname, startdate, enddate, timemodified');

    $result = [];

    foreach ($courses as $course){
        $context = context_course::instance($course->id);
        $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email, u.lastlogin, u.currentlogin, u.firstaccess');

        $users_data = [];
        foreach ($enrolled_users as $u) {
            $roles = get_user_roles($context, $u->id, true);
            $roles_data = [];

            foreach ($roles as $r) {
                $roles_data[] = [
                    'roleid' => $r->roleid,
                    'rolename' => $r->shortname,
                ];
            }

            $lastcourseaccess = (int)$DB->get_field('user_lastaccess', 'timeaccess', [
                'userid' => $u->id,
                'courseid' => $course->id
            ]) ?: 0;

            $u->profileimage = $CFG->wwwroot . '/user/pix.php/' . $u->id . '/f1.jpg';

            $users_data[] = [
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

        $result[] = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
            'timemodified' => $course->timemodified,
            'users' => $users_data,
        ];
    }

        return $result;
    }
}