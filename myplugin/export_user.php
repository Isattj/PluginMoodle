<?php
define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__ . '/../../config.php');
global $DB;

$userid = required_param('userid', PARAM_INT);

$user = $DB->get_record('user', ['id' => $userid], 'id, username, firstname, lastname, email, firstaccess, lastlogin, currentlogin', MUST_EXIST);

$sql_courses = "SELECT c.id, c.fullname, c.shortname, c.startdate, c.enddate, c.timemodified
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = :userid
                  AND e.status = 0"; // status = 0 indica enrolamento ativo
$courses = $DB->get_records_sql($sql_courses, ['userid' => $userid]);

foreach($courses as $course){
    $course->startdate = date('d/m/Y H:i:s', $course->startdate);
    $course->enddate = date('d/m/Y H:i:s', $course->enddate);

    $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
        'userid' => $userid, 'courseid' => $course->id
    ]);

    $course->lastcourseaccess = $lastaccess ? date('d/m/Y H:i:s', $lastaccess) : null;
    $course->timemodified = date('d/m/Y H:i:s', $course->timemodified);
}

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

$activities = [];
foreach($courses as $course){
    $sql_activities = "SELECT cm.id as cmid, m.name as modulename, cm.completion, cm.visible, cm.course
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    WHERE cm.course = :courseid";
    $raw_activities = $DB->get_records_sql($sql_activities, ['courseid' => $course->id]);

    $grade_item = $DB->get_record('grade_items', [
        'iteminstance' => $activity->instance,
        'itemmodule'   => $activity->modulename,
        'courseid'     => $activity->course
    ], 'grademax', IGNORE_MISSING);
    
    foreach ($raw_activities as $activity) {
        $activity_data = [
            'courseid'   => $course->id,
            'cmid'       => $activity->cmid,
            'modulename' => $activity->modulename,
            'completion' => $activity->completion,
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
}

$last_login = date('d/m/Y H:i:s', $user->lastlogin);
$current_login = date('d/m/Y H:i:s', $user->currentlogin);
$firstaccess= date('d/m/Y H:i:s', $user->firstaccess);
$user->profileimage = $CFG->wwwroot . '/user/pix.php/' . $user->id . '/f1.jpg';

$data = [
    'type'    => 'user',
    'userid'  => $user->id,
    'username'=> $user->username,
    'firstname' => $user->firstname,
    'lastname'  => $user->lastname,
    'email'     => $user->email,
    'profileimage' => $user->profileimage,
    'firstaccess' => $firstaccess,
    'lastlogin' => $last_login,
    'currentlogin' => $current_login,
    'courses'   => array_values($courses),
    'grades'    => $grades,
    'activities' => $activities,
    'timestamp' => time()
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);