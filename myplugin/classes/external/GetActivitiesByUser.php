<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

use context_user;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
global $DB, $USER;

class GetActivitiesByUser extends external_api {

    public static function execute_parameters() {
        return new \core_external\external_function_parameters([]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'coursename' => new external_value(PARAM_RAW, 'Course name'),
                'activityid' => new external_value(PARAM_INT, 'Activity ID'),
                'activityname' => new external_value(PARAM_RAW, 'Activity name'),
                'moduletype' => new external_value(PARAM_RAW, 'Module type (assign, quiz, etc)'),
                'grade' => new external_value(PARAM_FLOAT, 'User grade (if exists)', VALUE_OPTIONAL),
                'maxgrade' => new external_value(PARAM_FLOAT, 'Maximum grade', VALUE_OPTIONAL),
                'duedate' => new external_value(PARAM_RAW, 'Due date (if applicable)', VALUE_OPTIONAL)
            ])
        );
    }

    public static function execute() {
        global $DB, $USER;

        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        $sql = "
            SELECT 
                c.id AS courseid,
                c.fullname AS coursename,
                cm.id AS activityid,
                m.name AS moduletype,
                COALESCE(a.name, q.name) AS activityname,
                gi.id AS gradeitemid,
                gg.finalgrade AS grade,
                gi.grademax AS maxgrade,
                CASE 
                    WHEN m.name = 'assign' THEN a.duedate
                    WHEN m.name = 'quiz' THEN q.timeclose
                    ELSE 0
                END AS duedate
            FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
            JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :userid1
            JOIN {course_modules} cm ON cm.course = c.id
            JOIN {modules} m ON m.id = cm.module
            LEFT JOIN {assign} a ON a.id = cm.instance AND m.name = 'assign'
            LEFT JOIN {quiz} q ON q.id = cm.instance AND m.name = 'quiz'
            LEFT JOIN {grade_items} gi 
                ON (
                    (m.name = 'assign' AND gi.iteminstance = a.id AND gi.itemmodule = 'assign') OR
                    (m.name = 'quiz'   AND gi.iteminstance = q.id AND gi.itemmodule = 'quiz')
                )
                AND gi.courseid = c.id
            LEFT JOIN {grade_grades} gg
                ON gg.itemid = gi.id AND gg.userid = :userid2
            WHERE c.visible = 1
            ORDER BY c.fullname, m.name, cm.id
        ";

        $records = $DB->get_records_sql($sql, [
            'userid1' => $USER->id,
            'userid2' => $USER->id
        ]);

        $result = [];
        foreach ($records as $r) {
            $result[] = [
                'courseid' => $r->courseid,
                'coursename' => $r->coursename,
                'activityid' => $r->activityid,
                'activityname' => $r->activityname ?? 'Atividade sem nome',
                'moduletype' => $r->moduletype,
                'grade' => is_null($r->grade) ? null : round((float)$r->grade, 2),
                'maxgrade' => is_null($r->maxgrade) ? null : round((float)$r->maxgrade, 2),
                'duedate' => date('d/m/Y H:i:s', $r->duedate),
            ];
        }

        return $result;
    }
}
