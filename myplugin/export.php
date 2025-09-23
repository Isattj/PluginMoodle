<?php
// arquivo export.php público
define('NO_DEBUG_DISPLAY', true); // opcional para evitar warnings
require_once(__DIR__ . '/../../config.php'); // carrega Moodle

global $DB, $SITE;

// coleta todos os cursos
$courses = $DB->get_records('course', [], '', 'id, fullname, shortname, startdate, enddate');

// coleta todos os usuários matriculados em algum curso
$sql_users = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid";
$users = $DB->get_records_sql($sql_users);

// coleta todas as notas
$sql_grades = "SELECT g.id, g.userid, g.finalgrade, gi.itemname, gi.courseid
               FROM {grade_grades} g
               JOIN {grade_items} gi ON g.itemid = gi.id";
$grades = $DB->get_records_sql($sql_grades);

// monta payload
$data = [
    'site' => isset($SITE->shortname) ? $SITE->shortname : 'unknown',
    'timestamp' => time(),
    'courses' => array_values($courses),
    'users' => array_values($users),
    'grades' => array_values($grades),
];

// exibe JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
