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
                'id' => new external_value(PARAM_INT, 'Id do curso'),
                'fullname' => new external_value(PARAM_RAW, 'Nome completo do curso'),
                'shortname' => new external_value(PARAM_RAW, 'Nome curto do curso'),
                'startdate' => new external_value(PARAM_INT, 'Data de início do curso (timestamp)'),
                'enddate' => new external_value(PARAM_INT, 'Data de término do curso (timestamp)'),
                'timemodified' => new external_value(PARAM_INT, 'Última modificação (timestamp)'),

                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Id do usuário'),
                        'fullname' => new external_value(PARAM_RAW, 'Nome completo do usuário'),
                        'email' => new external_value(PARAM_RAW, 'Email do usuário'),
                        'roles' => new external_multiple_structure(
                            new external_single_structure([
                                'roleid' => new external_value(PARAM_INT, 'Id da função'),
                                'rolename' => new external_value(PARAM_RAW, 'Nome da função'),
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
            $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');

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

                $users_data[] = [
                    'id' => $u->id,
                    'fullname' => fullname($u),
                    'email' => $u->email,
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