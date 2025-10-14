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

class GetQuizQuestions extends external_api {

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
                        'tagid' => new external_value(PARAM_INT, 'tag ID'),
                        'tagname' => new external_value(PARAM_RAW, 'tag name'),
                    ]),
                    "Tags associadas ao quiz",
                    VALUE_OPTIONAL
                ),
                'questions' => new external_multiple_structure(
                    new external_single_structure([
                        'questionid' => new external_value(PARAM_INT, 'Question ID'),
                        'questionname' => new external_value(PARAM_RAW, 'Question name'),
                        'qtype' => new external_value(PARAM_RAW, 'Question type'),
                        'questiontext' => new external_value(PARAM_RAW, 'Question text'),
                        'answers' => new external_multiple_structure(
                            new external_single_structure([
                                'answerid' => new external_value(PARAM_INT, 'Answer ID'),
                                'answer' => new external_value(PARAM_RAW, 'Answer text'),
                                'fraction' => new external_value(PARAM_FLOAT, 'Fraction (1=correct, 0=incorrect)'),
                            ]),
                            'Possible answers',
                            VALUE_OPTIONAL
                        ),
                    ])
                ),
            ])
        );
    }

    public static function execute($courseid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/quiz:view', $context);

        $sql = "
            WITH quiz_tags AS (
                SELECT 
                    t.id AS tagid,
                    t.name AS tagname,
                    ti.itemid AS cmid
                FROM {tag_instance} ti
                JOIN {tag} t ON t.id = ti.tagid
                WHERE ti.itemtype = 'course_modules'
            )
            SELECT 
                q.id AS quizid,
                q.name AS quizname,
                qt.tagid,
                qt.tagname,
                qu.id AS questionid,
                qu.name AS questionname,
                qu.qtype AS questiontype,
                qu.questiontext,
                qa.id AS answerid,
                qa.answer,
                qa.fraction
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module 
            AND m.name = 'quiz'
            JOIN {quiz} q ON q.id = cm.instance
            LEFT JOIN quiz_tags qt ON qt.cmid = cm.id
            JOIN {quiz_slots} qs ON qs.quizid = q.id
            JOIN {question_references} qr 
                ON qr.itemid = qs.id 
                AND qr.component = 'mod_quiz' 
                AND qr.questionarea = 'slot'
            JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
            JOIN {question} qu ON qu.id = qv.questionid
            LEFT JOIN {question_answers} qa ON qa.question = qu.id
            WHERE cm.course = :courseid
            ORDER BY q.id, qs.slot, qu.id, qa.id;
        ";

        $records = $DB->get_recordset_sql($sql, ['courseid' => $params['courseid']]);

        $quizzes_map = [];

        foreach ($records as $row) {
            if (empty($row->quizid)) {
                continue;
            }

            $quizid = (int)$row->quizid;
            $questionid = (int)$row->questionid;
            $answerid = (int)$row->answerid;

            if (!isset($quizzes_map[$quizid])) {
                $quizzes_map[$quizid] = [
                    'quizid' => $quizid,
                    'quizname' => $row->quizname ?? '',
                    'quiztags' => [],
                    'questions' => [],
                ];
            }

            if (!is_null($row->tagid)) {
                $quizzes_map[$quizid]['quiztags'][$row->tagid] = [
                    'tagid' => (int)$row->tagid,
                    'tagname' => $row->tagname ?? '',
                ];
            }

            if (!isset($quizzes_map[$quizid]['questions'][$questionid])) {
                $quizzes_map[$quizid]['questions'][$questionid] = [
                    'questionid' => $questionid,
                    'questionname' => $row->questionname ?? '',
                    'qtype' => $row->questiontype ?? '',
                    'questiontext' => $row->questiontext ?? '',
                    'answers' => [],
                    '__addedanswers' => []
                ];
            }

            if (!is_null($answerid) &&
                !in_array($answerid, $quizzes_map[$quizid]['questions'][$questionid]['__addedanswers'])) {

                $quizzes_map[$quizid]['questions'][$questionid]['answers'][] = [
                    'answerid' => $answerid,
                    'answer' => $row->answer ?? '',
                    'fraction' => (float)$row->fraction,
                ];
                $quizzes_map[$quizid]['questions'][$questionid]['__addedanswers'][] = $answerid;
            }
        }
        $records->close();

        $result = [];
        foreach ($quizzes_map as $quiz) {
            $quiz['quiztags'] = array_values($quiz['quiztags']);
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
