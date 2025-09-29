<?php
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../config.php');

global $DB;

$courseid = optional_param('courseid', 0, PARAM_INT);

// se não veio pela URL, tenta pergar pelo POST LTI
if (!$courseid && isset($_POST['context_id'])) {
    $courseid = (int) $_POST['context_id'];
}

if (!$courseid) {
    echo json_encode(['error' => 'courseid não fornecido']);
    exit;
}

$course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname, startdate, enddate, timemodified', MUST_EXIST);

$course->startdate = date('d/m/Y H:i:s', $course->startdate);
$course->enddate = date('d/m/Y H:i:s', $course->enddate);
$course->timemodified = date('d/m/Y H:i:s', $course->timemodified);

$sql_users = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, u.lastlogin, u.currentlogin, u.firstaccess
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid = :courseid";
$users = $DB->get_records_sql($sql_users, ['courseid' => $courseid]);

foreach($users as $user){
    $user->lastlogin = date('d/m/Y H:i:s', $user->lastlogin);
    $user->currentlogin = date('d/m/Y H:i:s', $user->currentlogin);
    $user->firstaccess = date('d/m/Y H:i:s', $user->firstaccess);
    $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
        'userid' => $user->id, 'courseid' => $course->id
    ]);

    $user->lastcourseaccess = $lastaccess ? date('d/m/Y H:i:s', $lastaccess) : null;

    $user->profileimage = $CFG->wwwroot . '/user/pix.php/' . $user->id . '/f1.jpg';
}
unset($user);

$sql_grades = "SELECT g.id, g.userid, g.finalgrade, gi.itemname, gi.courseid
               FROM {grade_grades} g
               JOIN {grade_items} gi ON g.itemid = gi.id
               WHERE gi.courseid = :courseid";
$grades = $DB->get_records_sql($sql_grades, ['courseid' => $courseid]);

$sql_activities = "SELECT cm.id as cmid, m.name as modulename, cm.visible
                   FROM {course_modules} cm
                   JOIN {modules} m ON m.id = cm.module
                   WHERE cm.course = :courseid";
$raw_activities = $DB->get_records_sql($sql_activities, ['courseid' => $course->id]);

$activities = [];
foreach ($raw_activities as $activity) {
    $grade_item = $DB->get_record('grade_items', [
        'iteminstance' => $activity->instance,
        'itemmodule'   => $activity->modulename,
        'courseid'     => $activity->course
    ], 'grademax', IGNORE_MISSING);

    $activity_data = [
        'courseid'   => $course->id,
        'cmid'       => $activity->cmid,
        'modulename' => $activity->modulename,
        'grade'      => $grade_item ? $grade_item->grademax : null,
        'visible'    => $activity->visible,
        'due_date'   => null
    ];

    if ($activity->modulename === 'assign') {
        $assign = $DB->get_record('assign', ['id' => $activity->instance], 'duedate', IGNORE_MISSING);
        if ($assign && $assign->duedate) {
            $activity_data['due_date'] = date('d/m/Y H:i:s', $assign->duedate);
        }
    }

    if ($activity->modulename === 'quiz') {
        $quiz = $DB->get_record('quiz', ['id' => $activity->instance], 'timeclose', IGNORE_MISSING);
        if ($quiz && $quiz->timeclose) {
            $activity_data['due_date'] = date('d/m/Y H:i:s', $quiz->timeclose);
        }
    }

    $activities[] = $activity_data;
}

$data = [
    'course' => $course,
    'users' => array_values($users),
    'grades' => array_values($grades),
    'activities' => array_values($activities),
    'timestamp' => time(),
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
