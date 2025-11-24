<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/externallib.php");

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_url;
use core_files\file_storage;

class GetQuizQuestions extends external_api {

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
            'courseid' => new external_value(PARAM_INT, 'The course ID'),
        ]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
                'quizname' => new external_value(PARAM_RAW, 'Quiz name'),
                'quiztags' => new external_multiple_structure(
                    new external_single_structure([
                        'tagid' => new external_value(PARAM_INT, 'Tag ID'),
                        'tagname' => new external_value(PARAM_RAW, 'Tag name'),
                    ]),
                    'Quiz tags',
                    VALUE_OPTIONAL
                ),
                'quizcompetencies' => new external_multiple_structure(
                    new external_single_structure([
                        'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
                        'competencyname' => new external_value(PARAM_RAW, 'Competency name'),
                        'competencydesc' => new external_value(PARAM_RAW, 'Competency description', VALUE_OPTIONAL),
                    ]),
                    'Quiz competencies',
                    VALUE_OPTIONAL
                ),
                'questions' => new external_multiple_structure(
                    new external_single_structure([
                        'questionid' => new external_value(PARAM_INT, 'Question ID'),
                        'questionname' => new external_value(PARAM_RAW, 'Question name'),
                        'qtype' => new external_value(PARAM_RAW, 'Question type'),
                        'questiontext' => new external_value(PARAM_RAW, 'Question text'),
                        'calculated' => new external_multiple_structure(
                            new external_single_structure([
                                'variableid' => new external_value(PARAM_INT, 'Variable id'),
                                'itemcount' => new external_value(PARAM_INT, 'quantity of items'),
                                'variableName' => new external_value(PARAM_RAW, 'Variable name'),
                                'items' => new external_multiple_structure(
                                    new external_single_structure([
                                        'itemid' => new external_value(PARAM_INT, 'Item id'),
                                        'value' => new external_value(PARAM_FLOAT, 'Value item')
                                    ]),
                                    'Items',
                                    VALUE_OPTIONAL
                                ),
                            ]),
                            'Counted variables',
                            VALUE_OPTIONAL
                        ),
                        'answers' => new external_multiple_structure(
                            new external_single_structure([
                                'answerid' => new external_value(PARAM_INT, 'Answer ID'),
                                'options' => new external_multiple_structure(
                                    new external_single_structure([
                                        'answerOption' => new external_value(PARAM_RAW, 'Only one answer'),
                                        'answertext' => new external_value(PARAM_RAW, 'Correspondent answer', VALUE_OPTIONAL)
                                    ]),
                                    'Options',
                                    VALUE_OPTIONAL
                                ),
                                'fraction' => new external_value(PARAM_FLOAT, 'Fraction (1=correct, 0=incorrect)', VALUE_OPTIONAL),
                            ]),
                            'Possible answers',
                            VALUE_OPTIONAL
                        ),
                        'image' => new external_value(PARAM_URL, 'Image URL', VALUE_OPTIONAL),
                    ]),
                    'Questions',
                    VALUE_OPTIONAL
                ),
            ])
        );
    }

    public static function execute($courseid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/quiz:view', $context);

        $sql = "
            WITH quiz_tags AS (
                SELECT t.id AS tagid, t.name AS tagname, ti.itemid AS cmid
                FROM {tag_instance} ti
                JOIN {tag} t ON t.id = ti.tagid
                WHERE ti.itemtype = 'course_modules'
            )
            SELECT 
                q.id AS quizid,
                q.name AS quizname,
                qt.tagid,
                qt.tagname,
                c.id AS competencyid,
                c.shortname AS competencyname,
                c.description AS competencydesc,
                qu.id AS questionid,
                qu.name AS questionname,
                qu.qtype AS questiontype,
                qu.questiontext,
                qa.id AS answerid,
                qa.answer,
                qa.fraction
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
            JOIN {quiz} q ON q.id = cm.instance
            LEFT JOIN quiz_tags qt ON qt.cmid = cm.id
            LEFT JOIN {competency_modulecomp} mc ON mc.cmid = cm.id
            LEFT JOIN {competency} c ON c.id = mc.competencyid
            JOIN {quiz_slots} qs ON qs.quizid = q.id
            JOIN {question_references} qr ON qr.itemid = qs.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
            JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
            JOIN {question} qu ON qu.id = qv.questionid
            LEFT JOIN {question_answers} qa ON qa.question = qu.id
            WHERE cm.course = :courseid
            ORDER BY q.id, qs.slot, qu.id, qa.id
        ";

        $records = $DB->get_recordset_sql($sql, ['courseid' => $params['courseid']]);
        $quizzes_map = [];

        foreach ($records as $row) {
            if (empty($row->quizid)) {
                continue;
            }

            $quizid = (int)$row->quizid;
            $questionid = (int)$row->questionid;
            $answerid = isset($row->answerid) ? (int)$row->answerid : null;

            if (!isset($quizzes_map[$quizid])) {
                $quizzes_map[$quizid] = [
                    'quizid' => $quizid,
                    'quizname' => $row->quizname ?? '',
                    'quiztags' => [],
                    'quizcompetencies' => [],
                    'questions' => [],
                ];
            }

            if (!is_null($row->tagid)) {
                $quizzes_map[$quizid]['quiztags'][$row->tagid] = [
                    'tagid' => (int)$row->tagid,
                    'tagname' => $row->tagname ?? '',
                ];
            }

            if (!is_null($row->competencyid)) {
                $quizzes_map[$quizid]['quizcompetencies'][$row->competencyid] = [
                    'competencyid' => (int)$row->competencyid,
                    'competencyname' => $row->competencyname ?? '',
                    'competencydesc' => $row->competencydesc ?? '',
                ];
            }


            if (!isset($quizzes_map[$quizid]['questions'][$questionid])) {
                $variablesList = [];

                $datasets = $DB->get_records('question_datasets', ['question' => $questionid]);

                foreach ($datasets as $dataset) {
                    $definition = $DB->get_record('question_dataset_definitions', ['id' => $dataset->datasetdefinition]);
                    if (!$definition) {
                        continue;
                    }

                    $items = $DB->get_records('question_dataset_items', ['definition' => $definition->id]);

                    $itemsList = [];
                    foreach ($items as $item) {
                        $itemsList[] = [
                            'itemid' => (int)$item->id,
                            'value' => (float)$item->value
                        ];
                    }

                    $variable = [
                        'variableid' => (int)$definition->id,
                        'itemcount' => (int)$definition->itemcount,
                        'variableName' => $definition->name,
                        'items' => $itemsList,
                    ];

                    $variablesList[] = $variable;
                }

                $quizzes_map[$quizid]['questions'][$questionid] = [
                    'questionid' => $questionid,
                    'questionname' => $row->questionname ?? '',
                    'qtype' => $row->questiontype ?? '',
                    'questiontext' => $row->questiontext ?? '',
                    'calculated' => $variablesList ?? [],
                    'answers' => [],
                    '__addedanswers' => [],
                    'image' => null
                ];
            }


            if ($row->questiontype === 'match') {
                $subquestions = $DB->get_records('qtype_match_subquestions', ['questionid' => $questionid]);
                foreach ($subquestions as $sub) {
                    $answerdata = [
                        'answerid' => (int)$sub->id,
                        'options' => [
                            [
                                'answerOption' => $sub->questiontext ?? '',
                                'answertext' => $sub->answertext ?? '',
                            ]
                        ],
                        'fraction' => 1.0
                    ];
                    $quizzes_map[$quizid]['questions'][$questionid]['answers'][] = $answerdata;
                }

            }  else if($row->questiontype === 'ddmarker'){
                $answeroptions = $DB->get_records('qtype_ddmarker_drags', ['questionid' => $questionid]);

                foreach ($answeroptions as $option) {
                    if(in_array($option->id, $quizzes_map[$quizid]['questions'][$questionid]['__addedanswers'])){
                        continue;
                    }

                    $quizzes_map[$quizid]['questions'][$questionid]['answers'][] = [
                        'answerid' => (int)$option->id,
                        'options' => [
                            [
                                'answerOption' => $option->label ?? '',
                            ]
                        ],
                    ];
                    $quizzes_map[$quizid]['questions'][$questionid]['__addedanswers'][] = (int)$option->id;
                }

                if ($quizzes_map[$quizid]['questions'][$questionid]['image'] === null) {

                    $question = $DB->get_record('question', ['id'=>$questionid], '*', MUST_EXIST);

                    if (!empty($question->category)) {
                        $category = $DB->get_record('question_categories', ['id' => $question->category]);

                        if($category){
                            $context = context::instance_by_id($category->contextid);
        
                            $fs = get_file_storage();
        
                            $files = $fs->get_area_files(
                                $context->id,
                                'question',
                                'bgimage',
                                $questionid,
                                'itemid, filepath, filename',
                                false
                            );
        
                            foreach($files as $file){
                                if(!$file->is_directory()){
        
                                    $url = moodle_url::make_pluginfile_url(
                                        $file->get_contextid(),
                                        $file->get_component(),
                                        $file->get_filearea(),
                                        $file->get_itemid(),
                                        $file->get_filepath(),
                                        $file->get_filename()
                                    )->out(false);
        
                                    $quizzes_map[$quizid]['questions'][$questionid]['image'] = $url;
                                    break;
                                }
                            }
                        }
                    }
                }


            } else if (!is_null($answerid) && !in_array($answerid, $quizzes_map[$quizid]['questions'][$questionid]['__addedanswers'])) {
                $answerdata = [
                    'answerid' => $answerid,
                    'options' => [
                        [
                            'answerOption' => $row->answer ?? '',
                        ]
                    ],
                    'fraction' => (float)$row->fraction
                ];

                $quizzes_map[$quizid]['questions'][$questionid]['answers'][] = $answerdata;
                $quizzes_map[$quizid]['questions'][$questionid]['__addedanswers'][] = $answerid;
            }
        }

        $records->close();

        $result = [];
        foreach ($quizzes_map as $quiz) {
            $quiz['quiztags'] = array_values($quiz['quiztags']);
            $quiz['quizcompetencies'] = array_values($quiz['quizcompetencies']);
            foreach ($quiz['questions'] as &$question) {
                unset($question['__addedanswers']);
                $question['answers'] = array_values($question['answers']);
            }
            $quiz['questions'] = array_values($quiz['questions']);
            $result[] = $quiz;
        }

        return $result;
    }
}
