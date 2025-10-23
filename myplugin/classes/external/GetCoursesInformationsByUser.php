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
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_RAW, 'Full course name'),
                'shortname' => new external_value(PARAM_RAW, 'Short course name'),
                'startdate' => new external_value(PARAM_RAW, 'Course start date', VALUE_OPTIONAL),
                'enddate' => new external_value(PARAM_RAW, 'Course end date', VALUE_OPTIONAL),
                'timemodified' => new external_value(PARAM_RAW, 'Last modification time', VALUE_OPTIONAL),
                'tags' => new external_multiple_structure(
                    new external_single_structure([
                        'tagid' => new external_value(PARAM_INT, 'tag ID from course'),
                        'tagname' => new external_value(PARAM_RAW, 'tag name from course'),
                    ])
                ),
                'competencies' => new external_multiple_structure(
                    new external_single_structure([
                        'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                        'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                        'competencydesc' => new external_value(PARAM_RAW, 'Competency description', VALUE_OPTIONAL),
                    ])
                ),
                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'User ID'),
                        'username' => new external_value(PARAM_RAW, 'Username'),
                        'firstname' => new external_value(PARAM_RAW, 'First name'),
                        'lastname' => new external_value(PARAM_RAW, 'Last name'),
                        'email' => new external_value(PARAM_RAW, 'User email'),
                        'profileimage' => new external_value(PARAM_RAW, 'User profile image'),
                        'lastlogin' => new external_value(PARAM_RAW, 'User last login', VALUE_OPTIONAL),
                        'currentlogin' => new external_value(PARAM_RAW, 'User current login', VALUE_OPTIONAL),
                        'firstaccess' => new external_value(PARAM_RAW, 'User first access', VALUE_OPTIONAL),
                        'lastcourseaccess' => new external_value(PARAM_RAW, 'User last course access', VALUE_OPTIONAL),
                        'tags' => new external_multiple_structure(
                            new external_single_structure([
                                'tagid' => new external_value(PARAM_INT, 'tag ID from user'),
                                'tagname' => new external_value(PARAM_RAW, 'tag name from user'),
                            ])
                        ),
                        'roles' => new external_multiple_structure(
                            new external_single_structure([
                                'roleid' => new external_value(PARAM_INT, 'Role ID'),
                                'rolename' => new external_value(PARAM_RAW, 'Role name'),
                            ])
                        ),
                        'competencies' => new external_multiple_structure(
                            new external_single_structure([
                                'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                                'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                                'percentage' => new external_value(PARAM_FLOAT, 'Competency percentage', VALUE_OPTIONAL),
                            ])
                        ),
                    ])
                ),
            ])
        );
    }

public static function execute($userid) {
    global $DB, $USER, $CFG;

    $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);
    $usercontext = context_user::instance($params['userid']);
    self::validate_context($usercontext);

    $courses = enrol_get_users_courses($params['userid'], true, 'id, fullname, shortname, startdate, enddate, timemodified');
    $result = [];

    foreach ($courses as $course) {
        $context = context_course::instance($course->id);

        $roleuser = get_user_roles($context, $params['userid'], true);
        $rolenames = array_map(fn($r) => $r->shortname, $roleuser);

        $canviewall = false;
        foreach ($rolenames as $rolename) {
            if (in_array($rolename, ['editingteacher', 'teacher', 'manager', 'admin'])) {
                $canviewall = true;
                break;
            }
        }

        $tags = \core_tag_tag::get_item_tags('core', 'course', $course->id);
        $tags_data = [];
        foreach ($tags as $tag) {
            $tags_data[] = [
                'tagid' => $tag->id,
                'tagname' => $tag->get_display_name(),
            ];
        }

        $competencies_data = [];
        $competencymodule = $DB->get_records('competency_coursecomp', ['courseid' => $course->id]);
        foreach ($competencymodule as $comp) {
            $competency = $DB->get_record('competency', ['id' => $comp->competencyid]);
            if ($competency) {
                $competencies_data[] = [
                    'competencyid' => (int)$competency->id,
                    'competencyname' => $competency->shortname ?? $competency->name ?? 'Sem nome',
                    'competencydesc' => $competency->description ?? '',
                ];
            }
        }

        $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email, u.lastlogin, u.currentlogin, u.firstaccess');
        $users_data = [];

        foreach ($enrolled_users as $u) {
            if (!$canviewall && $u->id != $params['userid']) {
                continue;
            }

            $roles = get_user_roles($context, $u->id, true);
            $tags_users = \core_tag_tag::get_item_tags('core', 'user', $u->id);

            $roles_data = [];
            foreach ($roles as $r) {
                $roles_data[] = [
                    'roleid' => $r->roleid,
                    'rolename' => $r->shortname,
                ];
            }

            $tags_data_user = [];
            foreach ($tags_users as $t) {
                $tags_data_user[] = [
                    'tagid' => $t->id,
                    'tagname' => $t->get_display_name(),
                ];
            }

            $usercompetencies_data = [];
            foreach ($competencies_data as $comp) {
                $usercomp = $DB->get_record('competency_usercompcourse', [
                    'userid' => $u->id,
                    'courseid' => $course->id,
                    'competencyid' => $comp['competencyid'],
                ]);

                $percentage = null;
                if ($usercomp && isset($usercomp->grade)) {
                    $percentage = round($usercomp->grade * 100, 2);
                }

                $usercompetencies_data[] = [
                    'competencyid' => $comp['competencyid'],
                    'competencyname' => $comp['competencyname'],
                    'percentage' => $percentage,
                ];
            }

            $lastcourseaccess = (int)$DB->get_field('user_lastaccess', 'timeaccess', [
                'userid' => $u->id,
                'courseid' => $course->id
            ]) ?: 0;

            $user_data = [
                'id' => $u->id,
                'username' => $u->username,
                'firstname' => $u->firstname,
                'lastname' => $u->lastname,
                'email' => $u->email,
                'profileimage' => $CFG->wwwroot . '/user/pix.php/' . $u->id . '/f1.jpg',
                'tags' => $tags_data_user,
                'roles' => $roles_data,
                'competencies' => $usercompetencies_data,
            ];

            if ($canviewall) {
                $user_data['lastlogin'] = date('d/m/Y H:i:s', $u->lastlogin);
                $user_data['currentlogin'] = date('d/m/Y H:i:s', $u->currentlogin);
                $user_data['firstaccess'] = date('d/m/Y H:i:s', $u->firstaccess);
                $user_data['lastcourseaccess'] = date('d/m/Y H:i:s', $lastcourseaccess);
            }

            $users_data[] = $user_data;
        }

        $course_data = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'tags' => $tags_data,
            'competencies' => $competencies_data,
            'users' => $users_data,
        ];

        if ($canviewall) {
            $course_data['startdate'] = date('d/m/Y H:i:s', $course->startdate);
            $course_data['enddate'] = date('d/m/Y H:i:s', $course->enddate);
            $course_data['timemodified'] = date('d/m/Y H:i:s', $course->timemodified);
        }

        $result[] = $course_data;
    }

    return $result;
}
}