<?php
// arquivo export.php público
define('NO_DEBUG_DISPLAY', true); // opcional para evitar warnings
require_once(__DIR__ . '/../../config.php'); // carrega Moodle

global $DB, $SITE;

<<<<<<< HEAD
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);

$courses = $DB->get_records('course', ['id' => $courseid], '', 'id, fullname, shortname, startdate, enddate');

$sql_users = "SELECT u.id, u.username, u.firstname, u.lastname, u.email
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid = :courseid";
$users = $DB->get_records_sql($sql_users, ['courseid' => $courseid]);

$sql_enrolments = "SELECT ue.id, u.id as userid, c.id as courseid, u.firstname, u.lastname, u.email
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON ue.enrolid = e.id
                   JOIN {user} u ON ue.userid = u.id
                   JOIN {course} c ON e.courseid = c.id
                   WHERE c.id = :courseid";
$enrolments = $DB->get_records_sql($sql_enrolments, ['courseid' => $courseid]);

=======
// coleta todos os cursos
$courses = $DB->get_records('course', [], '', 'id, fullname, shortname, startdate, enddate');

// coleta todos os usuários matriculados em algum curso
$sql_users = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid";
$users = $DB->get_records_sql($sql_users);

// coleta todas as notas
>>>>>>> 4b2e525 (Enviando informações de todos os cursos e usuários para a lTI)
$sql_grades = "SELECT g.id, g.userid, g.finalgrade, gi.itemname, gi.courseid
               FROM {grade_grades} g
               JOIN {grade_items} gi ON g.itemid = gi.id";
$grades = $DB->get_records_sql($sql_grades);

// monta payload
$data = [
<<<<<<< HEAD
    'site'       => $SITE->fullname,
    'timestamp'  => time(),
    'courseid'   => $courseid,
    'courses'    => array_values($courses),
    'users'      => array_values($users),
    'enrolments' => array_values($enrolments),
    'grades'     => array_values($grades),
];

header('Content-Type: application/json');
=======
    'site' => isset($SITE->shortname) ? $SITE->shortname : 'unknown',
    'timestamp' => time(),
    'courses' => array_values($courses),
    'users' => array_values($users),
    'grades' => array_values($grades),
];

// exibe JSON
header('Content-Type: application/json; charset=utf-8');
>>>>>>> 4b2e525 (Enviando informações de todos os cursos e usuários para a lTI)
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
