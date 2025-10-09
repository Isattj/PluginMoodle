<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetActivitiesByCourse extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'coursename' => new external_value(PARAM_RAW, 'Course name'),
                'activityid' => new external_value(PARAM_INT, 'Activity ID'),
                'activityname' => new external_value(PARAM_RAW, 'Activity name'),
                'moduletype' => new external_value(PARAM_RAW, 'Module type'),
                'maxgrade' => new external_value(PARAM_FLOAT, 'Maximum grade', VALUE_OPTIONAL),
                'duedate' => new external_value(PARAM_RAW, 'Due date (if applicable)', VALUE_OPTIONAL)
            ])
        );
    }

    public static function execute($courseid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        $sql = "
            SELECT 
                c.id AS courseid,
                c.fullname AS coursename,
                cm.id AS activityid,
                cm.instance,
                m.name AS moduletype,
                COALESCE(a.name, q.name, f.name, l.name) AS activityname,
                gi.id AS gradeitemid,
                gg.finalgrade AS grade,
                gi.grademax AS maxgrade,
                CASE 
                    WHEN m.name = 'assign' THEN a.duedate
                    WHEN m.name = 'quiz' THEN q.timeclose
                    ELSE 0
                END AS duedate
            FROM {course} c
            JOIN {course_modules} cm ON cm.course = c.id
            JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
            LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
            LEFT JOIN {forum} f ON f.id = cm.instance AND m.name = 'forum'
            LEFT JOIN {lti} l ON l.id = cm.instance AND m.name = 'lti'
            LEFT JOIN {grade_items} gi
                ON gi.courseid = c.id
                AND gi.itemtype = 'mod'
                AND gi.itemmodule = m.name
                AND gi.iteminstance = cm.instance
            LEFT JOIN {grade_grades} gg
                ON gg.itemid = gi.id
                AND gg.userid = :userid
            WHERE c.id = :courseid
            ORDER BY m.name, cm.id
        ";

        $records = $DB->get_records_sql($sql, [
            'courseid' => $params['courseid'],
            'userid' => $USER->id
        ]);

        $result = [];
        foreach ($records as $r) {
            $result[] = [
                'courseid' => $r->courseid,
                'coursename' => $r->coursename,
                'activityid' => $r->activityid,
                'activityname' => $r->activityname ?? 'Atividade sem nome',
                'moduletype' => $r->moduletype,
                'maxgrade' => is_null($r->maxgrade) ? null : round((float)$r->maxgrade, 2),
                'duedate' => date('d/m/Y H:i:s', $r->duedate),
            ];
        }

        return $result;
    }
}
