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
            'duedate' => new external_value(PARAM_RAW, 'Deadline date', VALUE_OPTIONAL),
            'available' => new external_value(PARAM_RAW, 'Available date', VALUE_OPTIONAL),
            'timelimit' => new external_value(PARAM_RAW, 'Time limit', VALUE_OPTIONAL),
            'maxattempts' => new external_value(PARAM_INT, 'Maximum attempts', VALUE_OPTIONAL),
            'pages' => new external_multiple_structure(
                new external_single_structure([
                    'pageid' => new external_value(PARAM_INT, 'Page id'),
                    'title' => new external_value(PARAM_RAW, 'Page title'),
                    'paragraphs' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'Page text')
                    ),
                    'images' => new external_multiple_structure(
                        new external_structue([
                            'src' => new external_value(PARAM_RAW,' Image URL'),
                            'alt' => new external_value(PARAM_RAW,' Image alt text', VALUE_OPTIONAL),
                        ])
                    ),
                ])
            ),
            'time' => new external_multiple_structure(
                new external_single_structure([
                    'starttime' => new external_value(PARAM_RAW, 'Parameter start time'),
                    'endtime' => new external_value(PARAM_RAW, 'Parameter end time'),
                ])
            ),
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
        global $DB, $CFG;

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

        $pages_records = $DB->get_records('lesson_pages', ['lessonid' => $params['lessonid']], 'id ASC', 'id, title, contents');
        $pages_data = [];
        foreach ($pages_records as $page) {
            $content = format_text($page->contents, FORMAT_HTML);

            preg_match_all('/<img[^>]+src="([^">]+)"[^>]*alt="([^">]*)"[^>]*>/i', $content, $matches, PREG_SET_ORDER);
            $images_info = [];
            foreach($matches as $match){
                $src = $match[1];
                $alt = $match[2];

                if(strpos($src, '@@PLUGINFILE@@') !== false){
                    $context = context_module::instance($cm->id);
                    $component = 'mod_lesson';
                    $filearea = 'page_contents';
                    $itemid = $page->id;

                    $src = str_replace(
                        '@@PLUGINFILE@@',
                        $CFG->wwwroot . "/pluginfile.php/{$context->id}/{$component}/{$filearea}/{$itemid}",
                        $src
                    );
                }
                $images_info[] = [
                    'alt' => $alt,
                    'src' => $src
                ];
            }

            preg_match_all('/<p>(.*?)<\/p>/is', $content, $paragraphs);
            $paragraph_texts = array_map(fn($p) => trim(strip_tags($p)), $paragraphs[1]);
            $pages_data[] = [
                'pageid' => (int)$page->id,
                'title' => $page->title ?? '',
                'paragraphs' => $paragraph_texts,
                'images' => $images_info
            ];
        }

        $timer_records = $DB->get_records('lesson_timer', ['lessonid' => $params['lessonid'], 'userid' => $params['userid']], 'starttime DESC');
        $time_data = [];

        foreach ($timer_records as $t) {
            $time_data[] = [
                'starttime' => date('d/m/Y H:i:s', $t->starttime),
                'endtime' => date('d/m/Y H:i:s', $t->lessontime),
            ];
        }

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
            'duedate' => !empty($lesson->deadline) ? date('d/m/Y H:i:s', $lesson->deadline) : null,
            'available' => !empty($lesson->available) ? date('d/m/Y H:i:s', $lesson->available) : null,
            'timelimit' => !empty($lesson->timelimit) ? gmdate('H:i:s', $lesson->timelimit) : null,
            'maxattempts' => $lesson->maxattempts ?? null,
            'pages' => $pages_data,
            'time' => $time_data,
            'grades' => $grades_data
        ];
    }

}
