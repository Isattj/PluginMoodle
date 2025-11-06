<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

use context_user;
use context_course;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_url;

class GetActivitiesByUser extends external_api {

    private static function remove_null_informations(array $data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::remove_null_informations($value);
                if ($data[$key] === []) {
                    unset($data[$key]);
                }
            } else if (is_null($value)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    public static function execute_parameters() {
        return new external_function_parameters([
            'userid'     => new external_value(PARAM_INT, 'User ID'),
            'realuserid' => new external_value(PARAM_INT, 'Real user ID (from LTI)', VALUE_DEFAULT, 0),
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
                'link' => new external_value(PARAM_RAW, 'Link to activity'),

                'files' => new external_multiple_structure(
                    new external_single_structure([
                        'filename' => new external_value(PARAM_RAW, 'File name'),
                        'url' => new external_value(PARAM_RAW, 'Direct file URL'),
                        'filesize' => new external_value(PARAM_INT, 'File size (bytes)'),
                    ]),
                    'Files attached to the activity',
                    VALUE_OPTIONAL
                ),

                'tags' => new external_multiple_structure(
                    new external_single_structure([
                        'tagid' => new external_value(PARAM_INT, 'tag ID from activity'),
                        'tagname' => new external_value(PARAM_RAW, 'tag name from activity'),
                    ]),
                    'Tags from activity',
                    VALUE_OPTIONAL
                ),

                'competencies' => new external_multiple_structure(
                    new external_single_structure([
                        'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                        'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                        'competencydesc' => new external_value(PARAM_RAW, 'Competency description', VALUE_OPTIONAL),
                    ]),
                    'Competencies from activity',
                    VALUE_OPTIONAL
                ),

                'grades' => new external_multiple_structure(
                    new external_single_structure([
                        'userid' => new external_value(PARAM_INT, 'User ID'),
                        'username' => new external_value(PARAM_RAW, 'User name'),
                        'grade' => new external_value(PARAM_FLOAT, 'Grade', VALUE_OPTIONAL),
                    ]),
                    'Grades from enrolled users in this activity',
                    VALUE_OPTIONAL
                ),

                'available' => new external_value(PARAM_RAW, 'Available date', VALUE_OPTIONAL),
                'timelimit' => new external_value(PARAM_RAW, 'Time limit (HH:MM:SS)', VALUE_OPTIONAL),
                'retake' => new external_value(PARAM_BOOL, 'Whether the lesson can be retaken', VALUE_OPTIONAL),
                'maxattempts' => new external_value(PARAM_INT, 'Maximum attempts', VALUE_OPTIONAL),
                'modattempts' => new external_value(PARAM_BOOL, 'Whether multiple attempts per question are allowed', VALUE_OPTIONAL),
                'pages' => new external_multiple_structure(
                    new external_single_structure([
                        'pageid' => new external_value(PARAM_INT, 'Page id'),
                        'title' => new external_value(PARAM_RAW, 'Page title'),
                        'content' => new external_value(PARAM_RAW, 'Page content'),
                        'prevpageid' => new external_value(PARAM_INT, 'Previous page id', VALUE_OPTIONAL),
                        'nextpageid' => new external_value(PARAM_INT, 'Next page id', VALUE_OPTIONAL),
                    ]),
                    'Pages from lesson activity',
                    VALUE_OPTIONAL
                ),       
                'time' => new external_multiple_structure(
                    new external_single_structure([
                        'userid' => new external_value(PARAM_INT, 'User ID'),
                        'starttime' => new external_value(PARAM_RAW, 'Parameter start time'),
                        'endtime' => new external_value(PARAM_RAW, 'Parameter end time'),
                    ]),
                    'Time parameters from lesson activity',
                    VALUE_OPTIONAL
                ),
            ])
        );
    }

    public static function execute($userid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);
        $context = \context_user::instance($params['userid']);
        self::validate_context($context);

        require_once($CFG->dirroot . '/course/lib.php');

        $courses = enrol_get_users_courses($params['userid'], true, '*', 'visible DESC, fullname ASC');
        $result = [];

        foreach ($courses as $course) {
            $coursecontext = \context_course::instance($course->id);
            $roles = get_user_roles($coursecontext, $params['userid']);
            $rolenames = array_map(fn($r) => $r->shortname, $roles);
            $teacher = in_array('editingteacher', $rolenames) || in_array('teacher', $rolenames) || in_array('manager', $rolenames);

            $modinfo = get_fast_modinfo($course->id);
            foreach ($modinfo->get_cms() as $cm) {
                if (!$cm->uservisible) continue;

                $tags_data = [];
                $tags = \core_tag_tag::get_item_tags('core', 'course_modules', $cm->id);
                foreach ($tags as $tag) {
                    $tags_data[] = [
                        'tagid' => $tag->id,
                        'tagname' => $tag->get_display_name(),
                    ];
                }

                $competencies_data = [];
                $competencymodule = $DB->get_records('competency_modulecomp', ['cmid' => $cm->id]);
                foreach ($competencymodule as $comp) {
                    $competency = $DB->get_record('competency', ['id' => $comp->competencyid]);
                    if ($competency) {
                        $competencies_data[] = [
                            'competencyid' => (int)$competency->id,
                            'competencyname' => $competency->shortname ?? $competency->name ?? 'Sem nome',
                            'competencydesc' => $competency->description ?? '',
                        ];
                    }
                }

                $fs = get_file_storage();
                $modcontext = \context_module::instance($cm->id);
                $files_data = [];

                $component = 'mod_' . $cm->modname;
                $fileareas = ['intro'];
                foreach ($fileareas as $filearea) {
                    $files = $fs->get_area_files($modcontext->id, $component, $filearea, false, 'filename', false);
                    foreach ($files as $file) {
                        if ($file->is_directory()) continue;
                        $url = \moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            $file->get_itemid(),
                            $file->get_filepath(),
                            $file->get_filename()
                        )->out(false);

                        $files_data[] = [
                            'filename' => $file->get_filename(),
                            'url' => $url,
                            'filesize' => $file->get_filesize(),
                        ];
                    }
                }

                $grades_data = [];
                $maxgrade = null;
                $duedate = null;

                if ($cm->modname === 'assign') {
                    $grades = $DB->get_records_sql("
                        SELECT ag.userid, ag.grade, u.firstname, u.lastname
                        FROM {assign_grades} ag
                        JOIN {user} u ON u.id = ag.userid
                        WHERE ag.assignment = ?
                    ", [$cm->instance]);

                    foreach ($grades as $g) {
                        if (!$teacher && $g->userid != $params['userid']) continue;
                        $grades_data[] = [
                            'userid' => (int)$g->userid,
                            'username' => fullname($g),
                            'grade' => is_null($g->grade) ? null : round((float)$g->grade, 2)
                        ];
                    }

                    $assign = $DB->get_record('assign', ['id' => $cm->instance]);
                    $maxgrade = $assign->grade ?? null;
                    $duedate = !empty($assign->duedate) ? date('d/m/Y H:i:s', $assign->duedate) : null;

                } elseif ($cm->modname === 'lesson') {
                    $available = $timelimit = $retake = $maxattempts = $modattempts = null;

                    if (file_exists($CFG->dirroot . '/local/myplugin/classes/external/GetModLesson.php')) {
                        require_once($CFG->dirroot . '/local/myplugin/classes/external/GetModLesson.php');

                        try {
                            $lessoninfo = \local_myplugin\external\GetModLesson::execute(
                                $cm->instance,
                                $course->id,
                                $params['userid']
                            );

                            $maxgrade = $lessoninfo['maxgrade'] ?? $maxgrade;
                            $duedate = $lessoninfo['duedate'] ?? $duedate;
                            $available = $lessoninfo['available'] ?? null;
                            $timelimit = $lessoninfo['timelimit'] ?? null;
                            $retake = $lessoninfo['retake'] ?? null;
                            $maxattempts = $lessoninfo['maxattempts'] ?? null;
                            $modattempts = $lessoninfo['modattempts'] ?? null;

                            $pages = $DB->get_records('lesson_pages', ['lessonid' => $cm->instance]);
                            $pages_data = [];

                            foreach ($pages as $page) {
                                $clean_content = str_replace(["\r", "\n"], '', $page->contents);
                                $clean_content = preg_replace('/\s+/', ' ', $clean_content);

                                $clean_content = html_entity_decode($clean_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $clean_content = str_replace(['\"', "\'"], ['"', "'"], $clean_content);

                                $clean_content = preg_replace('/style="[^"]*"/i', '', $clean_content);
                                $clean_content = preg_replace('/data-[^=]+="[^"]*"/i', '', $clean_content);
                                $clean_content = preg_replace('/\*].*?\[.*?\]/', '', $clean_content);
                                $clean_content = preg_replace('/\s+/', ' ', $clean_content);

                                $pages_data[] = [
                                    'pageid' => (int)$page->id,
                                    'title' => $page->title,
                                    'content' => trim($clean_content),
                                    'prevpageid' => (int)$page->prevpageid ?? null,
                                    'nextpageid' => (int)$page->nextpageid ?? null,
                                ];
                            }

                            $timers = $DB->get_records('lesson_timer', ['lessonid' =>$cm->instance]);
                            $time_data = [];
                            foreach ($timers as $timer){
                                $time_data[] = [
                                    'userid' => (int) $timer->userid,
                                    'starttime' => date('d/m/Y H:i:s', $timer->starttime),
                                    'endtime' => date('d/m/Y H:i:s', $timer->endtime)
                                ];
                            }

                            $grades_data = $lessoninfo['grades'] ?? [];
                            if (!$teacher && !empty($grades_data)) {
                                $grades_data = array_values(array_filter($grades_data, function($g) use ($params) {
                                    return (int)($g['userid'] ?? 0) === (int)$params['userid'];
                                }));
                            }

                        } catch (\Throwable $e) {
                            debugging("Erro ao obter informações da lição {$cm->instance}: " . $e->getMessage());
                        }
                    }
                }

                elseif ($cm->modname === 'quiz') {
                    $grades = $DB->get_records_sql("
                        SELECT qg.userid, qg.grade, u.firstname, u.lastname
                        FROM {quiz_grades} qg
                        JOIN {user} u ON u.id = qg.userid
                        WHERE qg.quiz = ?
                    ", [$cm->instance]);

                    foreach ($grades as $g) {
                        if (!$teacher && $g->userid != $params['userid']) continue;
                        $grades_data[] = [
                            'userid' => (int)$g->userid,
                            'username' => fullname($g),
                            'grade' => is_null($g->grade) ? null : round((float)$g->grade, 2)
                        ];
                    }

                    $quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
                    $maxgrade = $quiz->grade ?? null;
                    $duedate = !empty($quiz->timeclose) ? date('d/m/Y H:i:s', $quiz->timeclose) : null;

                } else {
                    $grades = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance);
                    if (!empty($grades->items)) {
                        $item = reset($grades->items);
                        if (!empty($item->grades)) {
                            foreach ($item->grades as $userid => $gradeinfo) {
                                if (!$teacher && $userid != $params['userid']) continue;
                                $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
                                $grades_data[] = [
                                    'userid' => (int)$userid,
                                    'username' => fullname($user),
                                    'grade' => is_null($gradeinfo->grade) ? null : round((float)$gradeinfo->grade, 2)
                                ];
                            }
                        }
                        $maxgrade = $item->grademax ?? null;
                    }
                }

                $activity = [
                    'courseid' => $course->id,
                    'coursename' => $course->fullname,
                    'activityid' => $cm->id,
                    'activityname' => $cm->get_formatted_name(),
                    'moduletype' => $cm->modname,
                    'maxgrade' => $maxgrade,
                    'duedate' => $duedate,
                    'link' => $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id,
                    'files' => $files_data,
                    'tags' => $tags_data,
                    'competencies' => $competencies_data,
                    'grades' => $grades_data,
                ];

                if ($cm->modname === 'lesson') {
                    $activity['available'] = $available;
                    $activity['timelimit'] = $timelimit;
                    $activity['retake'] = $retake;
                    $activity['maxattempts'] = $maxattempts;
                    $activity['modattempts'] = $modattempts;
                    $activity['pages'] = $pages_data;
                    $activity['time'] = $time_data;
                }

                $result[]= $activity;
            }
        }

        return array_map([self::class, 'remove_null_informations'], $result);
    }
}

