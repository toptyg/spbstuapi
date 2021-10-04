<?php

$functions = [
    'spbstuapi_quiz_attempt_questions' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'quiz_attempt_questions',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Returns question information for mod_quiz attempt',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/quiz:viewreports'
    ],
    'spbstuapi_quiz_attempts' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'quiz_attempts',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Returns user and attempt ids with provided status for specific quiz',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/quiz:viewreports'
    ],
    'spbstuapi_course_modules_by_type' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'course_modules_by_type',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Returns user and attempt ids with provided status for specific quiz',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/course:ignoreavailabilityrestrictions, moodle/course:viewhiddenactivities'
    ],
    'spbstuapi_provide_course_external' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'provide_course_external',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Adds new course and track it as external or returns existing external course if any',
        'type' => 'write',
        'ajax' => false,
        'capabilities' => 'moodle/course:create, moodle/course:viewhiddenactivities, moodle/course:ignoreavailabilityrestrictions'
    ],
    'spbstuapi_provide_category_external' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'provide_category_external',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Creates new external category for specified path or returns existing category if any',
        'type' => 'write',
        'ajax' => false,
        'capabilities' => 'moodle/category:manage'
    ],
    'sbpstuapi_repopulate_subgroups_external' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'repopulate_subgroups_external',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Repopulates subgroups with all students of parent group',
        'type' => 'write',
        'ajax' => false,
        'capabilities' => ''
    ],
    'spbstuapi_provide_course_exam_external' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'provide_course_exam_external',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Populates new or existing course with exam sections. For new courses, track it as external. Returns internal course id.',
        'type' => 'write',
        'ajax' => false,
        'capabilities' => 'moodle/course:create, moodle/course:viewhiddenactivities, moodle/course:ignoreavailabilityrestrictions'
    ],

'spbstuapi_provide_course_vkr_external' => [
        'classname' => 'local_spbstuapi_external',
        'methodname' => 'provide_course_vkr_external',
        'classpath' => 'local/spbstuapi/externallib.php',
        'description' => 'Populates new or existing course with exam sections. For new courses, track it as external. Returns internal course id.',
        'type' => 'write',
        'ajax' => false,
        'capabilities' => 'moodle/course:create, moodle/course:viewhiddenactivities, moodle/course:ignoreavailabilityrestrictions'
    ]

];
