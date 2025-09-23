<?php
// local/myplugin/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Hook para fornecer parâmetros customizados para o LTI.
 * 
 * @param array $toolproxies
 * @param int $courseid
 * @param stdClass $user
 * @return array
 */
function local_myplugin_lti_get_launch_data($toolproxies, $courseid, $user) {
    global $DB;

    $course = get_course($courseid);
    $context = context_course::instance($courseid);

    // Usuários inscritos
    $users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email');
    $userlist = [];
    foreach ($users as $u) {
        $userlist[] = [
            'id' => $u->id,
            'username' => $u->username,
            'firstname' => $u->firstname,
            'lastname' => $u->lastname,
            'email' => $u->email,
        ];
    }

    // Notas
    $grades = [];
    foreach ($users as $u) {
        $gradesobj = grade_get_course_grades($courseid, $u->id);
        foreach ($gradesobj->grades as $item) {
            $grades[] = [
                'userid' => $u->id,
                'itemname' => $item->itemname,
                'finalgrade' => $item->grade,
                'courseid' => $courseid,
            ];
        }
    }

    // Monta JSON
    $custom_data = json_encode([
        'course' => [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
        ],
        'users' => $userlist,
        'grades' => $grades,
    ]);

    // Retorna para o Moodle injetar
    return [
        'custom_data' => $custom_data,
    ];
}
