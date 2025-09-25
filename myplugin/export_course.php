<?php
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../config.php');

global $DB;

$courseid = optional_param('courseid', 0, PARAM_INT);

if (!$courseid && isset($_POST['context_id'])) {
    $courseid = (int) $_POST['context_id'];
}

if (!$courseid) {
    echo json_encode(['error' => 'courseid não fornecido']);
    exit;
}

$course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname, startdate, enddate', MUST_EXIST);

$course->startdate = date('d/m/Y H:i:s', $course->startdate);
$course->enddate = date('d/m/Y H:i:s', $course->enddate);

$sql_users = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, u.lastlogin, u.currentlogin
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid = :courseid";
$users = $DB->get_records_sql($sql_users, ['courseid' => $courseid]);

foreach($users as $user){
    $user->lastlogin = date('d/m/Y H:i:s', $user->lastlogin);
    $user->currentlogin = date('d/m/Y H:i:s', $user->currentlogin);
}
unset($user);

$sql_grades = "SELECT g.id, g.userid, g.finalgrade, gi.itemname, gi.courseid
               FROM {grade_grades} g
               JOIN {grade_items} gi ON g.itemid = gi.id
               WHERE gi.courseid = :courseid";
$grades = $DB->get_records_sql($sql_grades, ['courseid' => $courseid]);

$data = [
    'course' => $course,
    'users' => array_values($users),
    'grades' => array_values($grades),
    'timestamp' => time(),
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
