<?php
namespace local_myplugin\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/lib/externallib.php");

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class GetQuizQuestions extends external_api {

    public static function execute_returns() {
        return new external_single_structure(
            [
                'quizid' => new external_value(PARAM_INT, 'Quiz ID'),
                'quizname' => new external_value(PARAM_RAW, 'Quiz name'),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'questionid' => new external_value(PARAM_INT, 'Question ID'),
                            'questionname' => new external_value(PARAM_RAW, 'Question name'),
                            'qtype' => new external_value(PARAM_RAW, 'Question type'),
                            'questiontext' => new external_value(PARAM_RAW, 'Question text'),
                            'answers' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'answerid' => new external_value(PARAM_INT, 'Answer ID'),
                                        'answer' => new external_value(PARAM_RAW, 'Answer text'),
                                        'fraction' => new external_value(PARAM_FLOAT, 'Fraction (1=correct, 0=incorrect)'),
                                    ]
                                ), 'Possible answers', VALUE_OPTIONAL
                            ),
                        ]
                    ), 'Quiz questions'
                ),
            ]
        );
    }

    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'The course ID'),
                'quizid'   => new external_value(PARAM_INT, 'The quiz ID'),
            ]
        );
    }

    public static function execute($courseid, $quizid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(),
            ['courseid' => $courseid, 'quizid' => $quizid]);

        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);

        require_capability('mod/quiz:view', $coursecontext);

        // Busca o quiz
        $quiz = $DB->get_record('quiz', ['id' => $params['quizid']], '*', MUST_EXIST);

        $sql = "SELECT 
                    q.id AS quizid,
                    q.name AS quizname,
                    qu.id AS questionid,
                    qu.name AS questionname,
                    qu.qtype AS questiontype,
                    qu.questiontext,
                    qa.id AS answerid,
                    qa.answer,
                    qa.fraction
                FROM mdl_quiz q
                JOIN mdl_quiz_slots qs ON qs.quizid = q.id
                JOIN mdl_question_references qr 
                    ON qr.itemid = qs.id 
                AND qr.component = 'mod_quiz' 
                AND qr.questionarea = 'slot'
                JOIN mdl_question_versions qv ON qv.questionbankentryid = qr.questionbankentryid
                JOIN mdl_question qu ON qu.id = qv.questionid
                LEFT JOIN mdl_question_answers qa ON qa.question = qu.id
                WHERE q.id = :quizid
                ORDER BY qs.slot, qu.id, qa.id;";

        $records = $DB->get_recordset_sql($sql, ['quizid' => $params['quizid']]);

        $questions_map = [];
        foreach ($records as $row) {
            $qid = $row->questionid;
            if (!isset($questions_map[$qid])) {
                $questions_map[$qid] = [
                    'questionid' => $row->questionid,
                    'questionname' => $row->questionname,
                    'qtype' => $row->questiontype,
                    'questiontext' => $row->questiontext,
                    'answers' => [],
                ];
            }
            if (
                in_array($row->questiontype, ['multichoice', 'truefalse', 'shortanswer', 'numerical'])
                && !is_null($row->answerid)
            ) {
                $questions_map[$qid]['answers'][] = [
                    'answerid' => $row->answerid,
                    'answer' => $row->answer,
                    'fraction' => (float)$row->fraction,
                ];
            }
        }
        $records->close();

        foreach ($questions_map as &$q) {
            if (empty($q['answers'])) {
                unset($q['answers']);
            }
        }

        return [
            'quizid' => $quiz->id,
            'quizname' => $quiz->name,
            'questions' => array_values($questions_map),
        ];
    }
}