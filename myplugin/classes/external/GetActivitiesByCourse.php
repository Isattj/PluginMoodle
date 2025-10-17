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
                'duedate' => new external_value(PARAM_RAW, 'Due date (if applicable)', VALUE_OPTIONAL),
                'link' => new external_value(PARAM_RAW, 'Link to activiy'),
                'tags' => new external_multiple_structure(
                    new external_single_structure([
                        'tagid' => new external_value(PARAM_INT, 'tag ID from activity'),
                        'tagname' => new external_value(PARAM_RAW, 'tag name from activity'),
                    ]),
                ),
            ])
        );
    }

    public static function execute($courseid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        $modinfo = get_fast_modinfo($params['courseid']);
        $course = $modinfo->get_course();
        $result = [];

        foreach ($modinfo->get_cms() as $cm) {
            if(!$cm->uservisible){
                continue;
            }

            $tags = \core_tag_tag::get_item_tags('core', 'course_modules', $cm->id);
            $tags_data = [];
            foreach ($tags as $tag) {
                $tags_data[] = [
                    'tagid' => $tag->id,
                    'tagname' => $tag->get_display_name(),
                ];
            }

            $activityname = $cm->get_formatted_name();
            $duedate = null;
            $maxgrade = null;

            $instance = null;

            switch ($cm->modname) {
                case 'assign':
                    $instance = $DB->get_record('assign', ['id' => $cm->instance]);
                    break;
                case 'quiz':
                    $instance = $DB->get_record('quiz', ['id' => $cm->instance]);
                    break;
                case 'forum':
                    $instance = $DB->get_record('forum', ['id' => $cm->instance]);
                    break;
                case 'lti':
                    $instance = $DB->get_record('lti', ['id' => $cm->instance]);
                    break;
            }

            $duedate = ($instance && property_exists($instance, 'duedate')) ? date('d/m/Y H:i:s', $instance->duedate) : null;
            $maxgrade = ($instance && property_exists($instance, 'grade')) ? round((float)$instance->grade, 2) : null;

            $result[] = [
                'courseid' => $course->id,
                'coursename' => $course->fullname,
                'activityid' => $cm->id,
                'activityname' => $activityname ?: 'A atividade não possui nome',
                'moduletype' => $cm->modname,
                'maxgrade' => $maxgrade,
                'duedate' => $duedate,
                'link' => $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id,
                'tags' => $tags_data
            ];
        }
        return $result;
    }
}
