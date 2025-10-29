<?php

defined('MOODLE_INTERNAL') || die();

$functions = [
    
    'local_myplugin_get_students_informations' => [
        'classname'   => 'local_myplugin\external\GetStudentsInformations',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get students informations',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],

    'local_myplugin_get_quiz_questions' => [
        'classname'   => 'local_myplugin\external\GetQuizQuestions',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get quiz questions',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],
    
    'local_myplugin_get_courses_informations_by_user' => [
        'classname'   => 'local_myplugin\external\GetCoursesInformationsByUser',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get courses informations by user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],

    'local_myplugin_get_competencies_by_user' => [
        'classname'   => 'local_myplugin\external\GetCompetenciesByUser',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get competencies by user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],

    'local_myplugin_get_activities_by_user' => [
        'classname'   => 'local_myplugin\external\GetActivitiesByUser',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get activities by user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],

    'local_myplugin_get_activities_by_course' => [
        'classname'   => 'local_myplugin\external\GetActivitiesByCourse',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get activities by course',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],
    
    'local_myplugin_get_logs_users' => [
        'classname'   => 'local_myplugin\external\GetLogsUsers',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get logs by user',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],
];

$services = [
    'mypluginservice' => [
        'functions'       => [
            'local_myplugin_get_students_informations',
            'local_myplugin_get_quiz_questions',
            'local_myplugin_get_courses_informations_by_user',
            'local_myplugin_get_activities_by_user',
            'local_myplugin_get_activities_by_course',
            'local_myplugin_get_logs_users',
        ],
        'restrictedusers' => 0,
        'enable'          => 1,
        'shortname'       => 'mypluginservice'
    ],
];
