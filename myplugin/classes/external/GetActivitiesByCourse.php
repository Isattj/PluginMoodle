<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

use context_course;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_url;

class GetActivitiesByCourse extends external_api {

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
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'realuserid' => new external_value(PARAM_INT, 'Real user ID', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'coursename' => new external_value(PARAM_RAW, 'Course name'),
                'activityid' => new external_value(PARAM_INT, 'Activity ID'),
                'activityname' => new external_value(PARAM_RAW, 'Activity name'),
                'intro' => new external_value(PARAM_RAW, 'Description of the activity', VALUE_OPTIONAL),
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
                'available'   => new external_value(PARAM_RAW, 'Available date', VALUE_OPTIONAL),
                'timelimit'   => new external_value(PARAM_RAW, 'Time limit (HH:MM:SS)', VALUE_OPTIONAL),
                'maxattempts' => new external_value(PARAM_INT, 'Maximum attempts', VALUE_OPTIONAL),
                'pages' => new external_multiple_structure(
                    new external_single_structure([
                        'pageid' => new external_value(PARAM_INT, 'Page id'),
                        'title' => new external_value(PARAM_RAW, 'Page title'),
                        'content' => new external_value(PARAM_RAW, 'Page content'),
                        'prevpageid' => new external_value(PARAM_INT, 'Previous page id', VALUE_OPTIONAL),
                        'nextpageid' => new external_value(PARAM_INT, 'Next page id', VALUE_OPTIONAL),
                        'answers' => new external_multiple_structure(
                            new external_single_structure([
                                'answerid' => new external_value(PARAM_INT, 'Answer id', VALUE_OPTIONAL),
                                'answer' => new external_value(PARAM_RAW, 'Answer text', VALUE_OPTIONAL),
                                'response' => new external_value (PARAM_RAW, 'Response text', VALUE_OPTIONAL),
                            ])
                        )
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
    
    public static function execute($courseid, $realuserid = 0) {
        global $DB, $CFG, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'realuserid' => $realuserid
        ]);

        $effectiveUser = $USER;
        if (!empty($params['realuserid'])) {
            $realuser = $DB->get_record('user', ['id' => $params['realuserid']], '*', IGNORE_MISSING);
            if ($realuser) {
                $effectiveUser = $realuser;
            }
        }

        error_log('GetActivitiesByCourse called for effective user id: ' . $effectiveUser->id);

        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);

        $canviewallgrades = has_capability('moodle/grade:viewall', $coursecontext, $effectiveUser);
        $roleuser = get_user_roles($coursecontext, $effectiveUser->id, true);
        $rolenames = array_map(fn($r) => $r->shortname, $roleuser);

        $teacher = in_array('editingteacher', $rolenames) || in_array('teacher', $rolenames)
            || in_array('manager', $rolenames) || is_siteadmin($effectiveUser);

        if (!is_enrolled($coursecontext, $effectiveUser->id)) {
            throw new \moodle_exception('not enrolled in course', 'local_myplugin');
        }

        require_once($CFG->dirroot . '/course/lib.php');

        $modinfo = get_fast_modinfo($params['courseid']);

        $result = [];

        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
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

            $competencies_data = [];
            $competencymodule = $DB->get_records('competency_modulecomp', ['cmid' => $cm->id]);
            foreach ($competencymodule as $comp) {
                $competencyid = $comp->competencyid ?? null;
                if ($competencyid) {
                    $competency = $DB->get_record('competency', ['id' => $competencyid]);
                    if ($competency) {
                        $competencies_data[] = [
                            'competencyid' => (int)$competency->id,
                            'competencyname' => $competency->shortname ?? $competency->name ?? 'Sem nome',
                            'competencydesc' => $competency->description ?? '',
                        ];
                    }
                }
            }

            $instance = $DB->get_record($cm->modname, ['id' => $cm->instance]);
            $duedate = ($instance && property_exists($instance, 'duedate')) ? date('d/m/Y H:i:s', $instance->duedate) : null;
            $maxgrade = ($instance && property_exists($instance, 'grade')) ? round((float)$instance->grade, 2) : null;
            $intro = ($instance && property_exists($instance, 'intro')) ? $instance->intro : null;

            $fs = get_file_storage();
            $modcontext = context_module::instance($cm->id);

            $available = null;
            $timelimit = null;
            $maxattempts = null;
            $pages_data = [];
            $time_data = [];
            $grades_data = [];

            $component = '';
            $fileareas = [];

            switch ($cm->modname) {
                case 'assign':
                    $component = 'mod_assign';
                    $fileareas = ['intro', 'introattachment'];
                    $assign = $DB->get_record('assign', ['id' => $cm->instance], 'intro', IGNORE_MISSING);
                    $intro = $assign ? $assign->intro : null;
                    break;
                case 'resource':
                    $component = 'mod_resource';
                    $fileareas = ['content'];
                    break;
                case 'page':
                    $component = 'mod_page';
                    $fileareas = ['content'];
                    break;
                case 'forum':
                    $component = 'mod_forum';
                    $fileareas = ['intro'];
                    break;
                case 'quiz':
                    $component = 'mod_quiz';
                    $fileareas = ['intro'];
                    break;
                case 'url':
                    $component = 'mod_url';
                    $fileareas = ['intro'];
                    break;
                case 'lesson':
                    $component = 'mod_lesson';
                    $fileareas = ['intro'];
                    if (file_exists($CFG->dirroot . '/local/myplugin/classes/external/GetModLesson.php')) {
                        require_once($CFG->dirroot . '/local/myplugin/classes/external/GetModLesson.php');
                        $lessoninfo = \local_myplugin\external\GetModLesson::execute($cm->instance, $params['courseid'], $effectiveUser->id);

                        $duedate = $lessoninfo['duedate'] ?? $duedate;
                        $available = $lessoninfo['available'] ?? null;
                        $timelimit = $lessoninfo['timelimit'] ?? null;
                        $maxattempts = $lessoninfo['maxattempts'] ?? null;

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

                            $answers = $DB->get_records('lesson_answers', ['pageid' => $page->id]);
                            $answers_data = [];

                            foreach ($answers as $answer) {
                                $answers_data[] = [
                                    'answerid' => (int)$answer->id,
                                    'answer' => $answer->answer,
                                    'response' => $answer->response,
                                ];
                            }

                            $pages_data[] = [
                                'pageid' => (int)$page->id,
                                'title' => $page->title,
                                'content' => trim($clean_content),
                                'answers' => $answers_data,
                                'prevpageid' => (int)$page->prevpageid ?? null,
                                'nextpageid' => (int)$page->nextpageid ?? null,
                            ];
                        }

                        $timers = $DB->get_records('lesson_timer', ['lessonid' =>$cm->instance]);
                        foreach ($timers as $timer){
                            $time_data[] = [
                                'userid' => (int) $timer->userid,
                                'starttime' => date('d/m/Y H:i:s', $timer->starttime),
                                'endtime' => date('d/m/Y H:i:s', $timer->endtime)
                            ];
                        }

                        if (!empty($lessoninfo['grades']) && !$canviewallgrades) {
                            $lessoninfo['grades'] = array_values(array_filter($lessoninfo['grades'], function($g) use ($effectiveUser) {
                                return (int)($g['userid'] ?? 0) === (int)$effectiveUser->id;
                            }));
                        }
                    }
                    break;
                default:
                    $component = 'mod_' . $cm->modname;
                    $fileareas = ['intro'];
                    break;
            }

            $files_data = [];
            foreach ($fileareas as $filearea) {
                $files = $fs->get_area_files($modcontext->id, $component, $filearea, false, 'filename', false);
                foreach ($files as $file) {
                    if ($file->is_directory()) {
                        continue;
                    }
                    $url = moodle_url::make_pluginfile_url(
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

            switch ($cm->modname) {
                case 'assign':
                    $grades = $DB->get_records_sql("
                        SELECT ag.userid, ag.grade
                        FROM {assign_grades} ag
                        WHERE ag.assignment = ?
                    ", [$cm->instance]);
                    foreach ($grades as $g) {
                        if (!$canviewallgrades && (int)$g->userid !== (int)$effectiveUser->id) {
                            continue;
                        }
                        $userrec = $DB->get_record('user', ['id' => $g->userid], 'id, firstname, lastname');
                        $grades_data[] = [
                            'userid' => (int)$g->userid,
                            'username' => $userrec ? fullname($userrec) : 'Usuário desconhecido',
                            'grade' => is_null($g->grade) ? null : round((float)$g->grade, 2)
                        ];
                    }
                    break;

                case 'quiz':
                    $grades = $DB->get_records_sql("
                        SELECT qg.userid, qg.grade
                        FROM {quiz_grades} qg
                        WHERE qg.quiz = ?
                    ", [$cm->instance]);
                    foreach ($grades as $g) {
                        if (!$canviewallgrades && (int)$g->userid !== (int)$effectiveUser->id) {
                            continue;
                        }
                        $userrec = $DB->get_record('user', ['id' => $g->userid], 'id, firstname, lastname');
                        $grades_data[] = [
                            'userid' => (int)$g->userid,
                            'username' => $userrec ? fullname($userrec) : 'Usuário desconhecido',
                            'grade' => is_null($g->grade) ? null : round((float)$g->grade, 2)
                        ];
                    }
                    break;

                default:
                    $grades = grade_get_grades($params['courseid'], 'mod', $cm->modname, $cm->instance);
                    if (!empty($grades->items)) {
                        $item = reset($grades->items);
                        if (!empty($item->grades)) {
                            foreach ($item->grades as $userid => $gradeinfo) {
                                if (!$canviewallgrades && (int)$userid !== (int)$effectiveUser->id) {
                                    continue;
                                }
                                $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
                                $grades_data[] = [
                                    'userid' => (int)$userid,
                                    'username' => $user ? fullname($user) : 'Usuário desconhecido',
                                    'grade' => is_null($gradeinfo->grade) ? null : round((float)$gradeinfo->grade, 2)
                                ];
                            }
                        }
                    }
                    break;
            }

            if (!empty($grades_data) && !$canviewallgrades) {
                $grades_data = array_values(array_filter($grades_data, function($g) use ($effectiveUser) {
                    return (int)($g['userid'] ?? 0) === (int)$effectiveUser->id;
                }));
            }

            $coursename = $DB->get_field('course', 'fullname', ['id' => $params['courseid']]) ?: '';

            $result[] = [
                'courseid' => $params['courseid'],
                'coursename' => $coursename,
                'activityid' => $cm->id,
                'activityname' => $cm->get_formatted_name(),
                'intro' => $intro,
                'moduletype' => $cm->modname,
                'maxgrade' => $maxgrade,
                'duedate' => $duedate,
                'available' => $available,
                'timelimit' => $timelimit,
                'maxattempts' => $maxattempts,
                'pages' => $pages_data,
                'time' => $time_data,
                'link' => $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id,
                'files' => $files_data,
                'tags' => $tags_data,
                'competencies' => $competencies_data,
                'grades' => $grades_data
            ];
        }

        $cleaned = array_map([self::class, 'remove_null_informations'], $result);
        return $cleaned;
    }
}
