<?php
define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__ . '/../../../config.php');
global $DB;

$userid = required_param('userid', PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid], 'id, username, firstname, lastname, email', MUST_EXIST);

$courseid = optional_param('courseid', 0, PARAM_INT);

if (!$courseid && isset($_POST['context_id'])) {
    $courseid = (int) $_POST['context_id'];
}

if (!$courseid) {
    echo json_encode(['error' => 'courseid não fornecido']);
    exit;
}

$course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname, startdate, enddate, timemodified', MUST_EXIST);

$context = context_course::instance($courseid);
$roles = get_user_roles($context, $userid, true);

$usertype = 'student';

foreach($roles as $role){
    if ($role->shortname === 'admin') {
        $usertype = 'admin';
        break;
    } elseif (in_array($role->shortname, ['editingteacher','teacher'])) {
        $usertype = 'teacher';
    }
}

$logs = [];
if($usertype === 'admin'|| $usertype === 'teacher'){
    $sql_logs = "SELECT id, userid, timecreated, action, target, contextinstanceid, crud, eventname
                 FROM {logstore_standard_log}
                 WHERE courseid = :courseid
                 ORDER BY timecreated DESC
                 LIMIT 10";
    $logs = $DB->get_records_sql($sql_logs, ['courseid' => $courseid]);

} else {
    $sql_logs = "SELECT id, timecreated, action, target, contextinstanceid, crud, eventname
                FROM {logstore_standard_log}
                WHERE userid = :userid AND courseid = :courseid
                ORDER BY timecreated DESC
                LIMIT 10";
    
    $logs = $DB->get_records_sql($sql_logs, [
        'userid' => $userid,
        'courseid' => $courseid
    ]);
}

foreach ($logs as $log) {
    $log->timecreated = date('d/m/Y H:i:s', $log->timecreated);
}

$course_data = [
    'id'           => $course->id,
    'fullname'     => $course->fullname,
    'shortname'    => $course->shortname,
    'startdate'    => date('d/m/Y H:i:s', $course->startdate),
    'enddate'      => date('d/m/Y H:i:s', $course->enddate),
    'timemodified' => date('d/m/Y H:i:s', $course->timemodified),
    'logs'         => array_values($logs)
];

$data = [
    'user'      => $user,
    'usertype'  => $usertype,
    'course'    => $course_data,
    'timestamp' => time(),
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
