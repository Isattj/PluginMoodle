<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use moodle_url;

class GetModLesson extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'lessonid' => new external_value(PARAM_INT, 'Lesson instance ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'userid' => new external_value(PARAM_INT, 'User ID')
        ]);
    }

    public static function execute_returns() {
        return new external_single_structure([
            'maxgrade' => new external_value(PARAM_FLOAT, 'Maximum grade', VALUE_OPTIONAL),
            'duedate' => new external_value(PARAM_RAW, 'Deadline date', VALUE_OPTIONAL),
            'available' => new external_value(PARAM_RAW, 'Available date', VALUE_OPTIONAL),
            'timelimit' => new external_value(PARAM_RAW, 'Time limit', VALUE_OPTIONAL),
            'retake' => new external_value(PARAM_BOOL, 'Whether the lesson can be retaken', VALUE_OPTIONAL),
            'maxattempts' => new external_value(PARAM_INT, 'Maximum attempts', VALUE_OPTIONAL),
            'usepassword' => new external_value(PARAM_BOOL, 'Whether the lesson requires a password', VALUE_OPTIONAL),
            'modattempts' => new external_value(PARAM_BOOL, 'Whether multiple attempts per question are allowed', VALUE_OPTIONAL),
            'grades' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_RAW, 'User name'),
                    'grade' => new external_value(PARAM_FLOAT, 'Grade', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function execute($lessonid, $courseid, $userid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'lessonid' => $lessonid,
            'courseid' => $courseid,
            'userid' => $userid
        ]);

        $cm = get_coursemodule_from_instance('lesson', $params['lessonid'], $params['courseid']);
        if (!$cm) {
            throw new \moodle_exception('invalidcoursemodule');
        }

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $lesson = $DB->get_record('lesson', ['id' => $params['lessonid']], '*', MUST_EXIST);

        $isstudent = !has_capability('mod/lesson:manage', $context);
        $grades = grade_get_grades($params['courseid'], 'mod', 'lesson', $params['lessonid']);

        $grades_data = [];
        if (!empty($grades->items)) {
            $item = reset($grades->items);
            foreach ($item->grades as $uid => $gradeinfo) {
                if ($isstudent && $uid != $params['userid']) {
                    continue;
                }
                $user = $DB->get_record('user', ['id' => $uid], 'id, firstname, lastname');
                $grades_data[] = [
                    'userid' => (int)$uid,
                    'username' => fullname($user),
                    'grade' => is_null($gradeinfo->grade) ? null : round((float)$gradeinfo->grade, 2)
                ];
            }
        }

        return [
            'maxgrade' => $lesson->grade ?? null,
            'duedate' => !empty($lesson->deadline) ? date('d/m/Y H:i:s', $lesson->deadline) : null,
            'available' => !empty($lesson->available) ? date('d/m/Y H:i:s', $lesson->available) : null,
            'timelimit' => !empty($lesson->timelimit) ? gmdate('H:i:s', $lesson->timelimit) : null,
            'retake' => !empty($lesson->retake),
            'maxattempts' => $lesson->maxattempts ?? null,
            'usepassword' => !empty($lesson->usepassword),
            'modattempts' => !empty($lesson->modattempts),
            'grades' => $grades_data
        ];
    }
}
