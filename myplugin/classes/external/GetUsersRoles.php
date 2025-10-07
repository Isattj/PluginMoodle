<?php

namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/course/externallib.php");

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetUsersRoles extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'ID do curso'),
            ]
        );
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'userid' => new external_value(PARAM_INT, 'ID do usuário'),
                    'fullname' => new external_value(PARAM_TEXT, 'Nome completo do usuário'),
                    'roles' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Nome da role'),
                        'Lista de roles do usuário no curso',
                        VALUE_OPTIONAL
                    ),
                ]
            ),
            'Lista de usuários com suas roles no curso'
        );
    }


    public static function execute($courseid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);

        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);

        require_capability('moodle/course:view', $coursecontext);

        $users = get_enrolled_users($coursecontext);

        $result = [];
        foreach ($users as $user) {
            $user_roles = [];
            $roles = get_user_roles($coursecontext, $user->id, true);
            foreach ($roles as $role) {
                $user_roles[] = $role->shortname;
            }

            $result[] = [
                'userid' => $user->id,
                'fullname' => fullname($user),
                'roles' => $user_roles,
            ];
        }

        return $result;
    }
}
