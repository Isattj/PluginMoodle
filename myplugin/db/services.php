<?php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_myplugin_core_grades_get_course_grades' => [
        'classname'   => 'local_myplugin\external\CoreGradesGetCourseGrades', 
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get course grades for users',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => ['mypluginservice'],
    ],
    
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
    'local_myplugin_get_users_roles' => [
        'classname'   => 'local_myplugin\external\GetUsersRoles',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get users roles in a course',
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
];

$services = [
    'mypluginservice' => [
        'functions'       => [
            'local_myplugin_core_grades_get_course_grades',
            'local_myplugin_get_students_informations',
            'local_myplugin_get_quiz_questions',
            'local_myplugin_get_users_roles',
            'local_myplugin_get_courses_informations_by_user',
            'local_myplugin_get_activities_by_user',
            'local_myplugin_get_activities_by_course'
        ],
        'restrictedusers' => 0,
        'enable'          => 1,
        'shortname'       => 'mypluginservice'
    ],
];
