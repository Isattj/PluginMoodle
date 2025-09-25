<?php
define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__ . '/../../config.php');
global $DB;

$userid = required_param('userid', PARAM_INT);

// Pega dados do usuário
$user = $DB->get_record('user', ['id' => $userid], 'id, username, firstname, lastname, email', MUST_EXIST);

// Pega cursos em que ele está matriculado
$sql_courses = "SELECT c.id, c.fullname, c.shortname, c.startdate, c.enddate
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = :userid";
$courses = $DB->get_records_sql($sql_courses, ['userid' => $userid]);

// Pega notas dele em cada curso
$grades = [];
foreach ($courses as $course) {
    $sql_grades = "SELECT g.id, g.finalgrade, gi.itemname, gi.courseid
                   FROM {grade_grades} g
                   JOIN {grade_items} gi ON gi.id = g.itemid
                   WHERE g.userid = :userid AND gi.courseid = :courseid";
    $user_grades = $DB->get_records_sql($sql_grades, [
        'userid' => $userid,
        'courseid' => $course->id
    ]);

    foreach ($user_grades as $grade) {
        $grades[] = [
            'courseid'   => $course->id,
            'itemname'   => $grade->itemname,
            'finalgrade' => $grade->finalgrade
        ];
    }
}

// Monta payload
$data = [
    'type'    => 'user',
    'userid'  => $user->id,
    'username'=> $user->username,
    'firstname' => $user->firstname,
    'lastname'  => $user->lastname,
    'email'     => $user->email,
    'courses'   => array_values($courses),
    'grades'    => $grades,
    'timestamp' => time(),
];

// Retorna JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
