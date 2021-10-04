<?php

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once(__DIR__ . '/utils.php');

class local_spbstuapi_external extends external_api
{
    public static function quiz_attempt_questions($attempts)
    {
        global $DB;

        self::validate_parameters(self::quiz_attempt_questions_parameters(), array('attempts' => $attempts));

        $result = [];
        foreach ($attempts as $attemptid) {
            $a = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*');

            if (!$a) {
                continue;
            }

            $qu = question_engine::load_questions_usage_by_activity($a->uniqueid);
            $context = $qu->get_owning_context()->get_course_context();

            self::validate_context($context);
            require_capability('mod/quiz:viewreports', $context);

            $slots = $qu->get_slots();

            $questions = [];

            foreach ($slots as $slot) {
                $q = $qu->get_question($slot);
                $category_id = $q->category;

                $category = $DB->get_record('question_categories', [
                    'id' => $category_id
                ]);

                $questions[] = [
                    'question_id' => $q->id,
                    'question_name' => $q->name,
                    'category_id' => $category_id,
                    'category_name' => $category->name,
                    'mark' => $qu->get_question_mark($slot),
                    'maxmark' => $qu->get_question_max_mark($slot)
                ];
            }

            $result[] = [
                'attempt' => $attemptid,
                'questions' => $questions
            ];
        }
        return $result;
    }











    public static function provide_course_external($external_id, $external_category_id = null, $course_name = null, $course_description = null, $sections = null)
    {
        global $DB;

        try {
            self::validate_parameters(self::provide_course_external_parameters(), array(
                'external_id' => $external_id,
                'external_category_id' => $external_category_id,
                'course_name' => $course_name
            ));
        } catch (invalid_parameter_exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        $existing_course = $DB->get_record('spbstuapi_ext_courses', ['external_id' => $external_id]);
        if ($existing_course !== false) {
            $internal_course = null;
            try {
                $internal_course = get_course($existing_course->internal_id);
            } catch (dml_exception $e) {
                return [
                    'status' => 'error',
                    'error' => 'internal course was deleted'
                ];
            }

            return [
                'status' => 'ok',
                'external_id' => $existing_course->external_id,
                'external_category_id' => $existing_course->external_catid,
                'internal_id' => $internal_course->id,
                'internal_category_id' => $internal_course->category
            ];
        } else if ($external_category_id === null || $course_name === null) {
            return [
                'status' => 'error',
                'error' => 'course with this id does not exists, and could not be created (required parameters not set)'
            ];
        }

        $external_category = $DB->get_record('spbstuapi_ext_cats', ['id' => $external_category_id]);
        if ($external_category === false) {
            return [
                'status' => 'error',
                'error' => 'external category does not exist'
            ];
        }

        $new_course = spbstuapi_create_covid_course(
            $external_category->category_id,
            $course_name,
            'cvd19-' .  crc32($external_id) . crc32($course_name),
            $course_description,
            $sections
        );

        $new_external_course = new stdClass();
        $new_external_course->external_id = $external_id;
        $new_external_course->external_catid = $external_category->id;
        $new_external_course->internal_id = $new_course->id;
        $DB->insert_record('spbstuapi_ext_courses', $new_external_course);

        return [
            'status' => 'ok',
            'external_id' => $new_external_course->external_id,
            'external_category_id' => $new_external_course->external_catid,
            'internal_id' => $new_course->id,
            'internal_category_id' => $new_course->category
        ];
    }

    public static function provide_course_external_parameters()
    {
        return new external_function_parameters([
            'external_id' => new external_value(PARAM_TEXT, 'ID for course in external system, no longer than 255 symbols', VALUE_REQUIRED),
            'external_category_id' => new external_value(PARAM_INT, 'Category to create external course in', VALUE_OPTIONAL),
            'course_name' => new external_value(PARAM_TEXT, 'Name of created course', VALUE_OPTIONAL),
            'course_description' => new external_value(PARAM_TEXT, 'Description of created course', VALUE_OPTIONAL),
            'sections' => new external_multiple_structure(new external_single_structure([
                'section_external_id' => new external_value(PARAM_TEXT, 'External value to identify thin section (not saved, used in output)'),

                'teacher_name' => new external_value(PARAM_TEXT, 'Teacher full name'),
                'teacher_tabno' => new external_value(PARAM_TEXT, 'Teacher table number (if exists)', VALUE_OPTIONAL),
                'teacher_spbstu_login' => new external_value(PARAM_TEXT, 'Teacher SPbSTU domain login (some_login@spbstu.ru)', VALUE_OPTIONAL),

                'section_type' => new external_value(PARAM_TEXT, 'Type of section'),
                'section_date' => new external_value(PARAM_TEXT, 'Section lesson date'),
                'section_timeperiod' => new external_value(PARAM_TEXT, 'Time start - end'),

                'webinar_link' => new external_value(PARAM_TEXT, 'Link to populate webinar with'),
                'groups' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Group name'), 'Participating groups',VALUE_OPTIONAL
                ),
                'subgroups' => new external_multiple_structure(
                    new external_single_structure([
                        'n' => new external_value(PARAM_TEXT, 'Subgroup name'),
                        'g' => new external_value(PARAM_TEXT, 'Group name')
                    ]), 'Participating subgroups', VALUE_OPTIONAL
                )
            ]), 'Course sections to create (for COVID-19)', VALUE_OPTIONAL)
        ]);
    }

    public static function provide_course_external_returns()
    {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'May be "ok" or "error"', VALUE_REQUIRED),
            'error' => new external_value(PARAM_TEXT, 'Error description (if applicable)', VALUE_OPTIONAL),
            'external_id' => new external_value(PARAM_TEXT, 'ID used to create external course with', VALUE_OPTIONAL),
            'external_category_id' => new external_value(PARAM_TEXT, 'Category ID used to create external course with', VALUE_OPTIONAL),
            'internal_id' => new external_value(PARAM_INT, 'Internal ID of created course (access via /course/view.php?id=<internal id>)', VALUE_OPTIONAL),
            'internal_category_id' => new external_value(PARAM_INT, 'Internal ID of category course was created in (access via /course/index.php?categoryid=<internal_category_id>)', VALUE_OPTIONAL),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'section_external_id' => new external_value(PARAM_TEXT, 'External value to identify thin section (not saved, used in output)'),
                    'section_internal_id' => new external_value(PARAM_INT, 'Internal ID that identifies this section')
                ]), 'Created course sections (if any)', VALUE_OPTIONAL
            )]);
    }

    // Sorry
    // TODO Optimize query-wise

    public static function provide_category_external_parameters()
    {
        return new external_function_parameters([
            'root' => new external_value(PARAM_INT, 'External category ID used as root', true),
            'path' => new external_value(PARAM_TEXT, 'Categories path with "|" as separator', true)
        ]);
    }

    public static function provide_category_external_returns()
    {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'May be "ok" or "error"', VALUE_REQUIRED),
            'error' => new external_value(PARAM_TEXT, 'Error description (if applicable)', VALUE_OPTIONAL),
            'external_category_id' => new external_value(PARAM_TEXT, 'Category ID used to create external course with', VALUE_OPTIONAL),
            'internal_category_id' => new external_value(PARAM_INT, 'Internal ID of category course was created in (access via /course/index.php?categoryid=<internal_category_id>)', VALUE_OPTIONAL),
        ]);
    }

    public static function repopulate_subgroups_external() {
        try {
            spbstuapi_populate_all_subgroups();
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => strval($e),
                'print_r' => print_r($e, true)
            ];
        }
        return [
            'status' => 'ok'
        ];
    }

    public static function repopulate_subgroups_external_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function repopulate_subgroups_external_returns()
    {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'May be "ok" or "error"', VALUE_REQUIRED),
            'error' => new external_value(PARAM_TEXT, 'Error description (if applicable)', VALUE_OPTIONAL),
            'print_r' => new external_value(PARAM_TEXT, 'print_r for exception', VALUE_OPTIONAL)
        ]);
    }



    public static function provide_course_exam_external_parameters() {
        return new external_function_parameters([
            'external_id' => new external_value(PARAM_TEXT, 'ID for course in external system, no longer than 255 symbols', VALUE_REQUIRED),
            'external_category_id' => new external_value(PARAM_INT, 'Category to create external course in (ignored if course exists)', VALUE_OPTIONAL),
            'course_name' => new external_value(PARAM_TEXT, 'Name of created course (ignored if course exists)', VALUE_OPTIONAL),
            'course_description' => new external_value(PARAM_TEXT, 'Description of created course (ignored if course exists)', VALUE_OPTIONAL),
            'sections' => new external_multiple_structure(new external_single_structure([
                'section_external_id' => new external_value(PARAM_TEXT, 'External value to identify thin section (not saved, used in output)'),

                'teacher_name' => new external_value(PARAM_TEXT, 'Teacher full name'),
                'teacher_tabno' => new external_value(PARAM_TEXT, 'Teacher table number (if exists)', VALUE_OPTIONAL),
                'teacher_spbstu_login' => new external_value(PARAM_TEXT, 'Teacher SPbSTU domain login (some_login@spbstu.ru)', VALUE_OPTIONAL),

                'section_type' => new external_value(PARAM_TEXT, 'Type of section'),
                'section_date' => new external_value(PARAM_TEXT, 'Section lesson date'),
                'section_timeperiod' => new external_value(PARAM_TEXT, 'Time start - end'),

                'webinar_link' => new external_value(PARAM_TEXT, 'Link to populate webinar with'),
                'groups' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Group name'), 'Participating groups',VALUE_OPTIONAL
                ),
                'subgroups' => new external_multiple_structure(
                    new external_single_structure([
                        'n' => new external_value(PARAM_TEXT, 'Subgroup name'),
                        'g' => new external_value(PARAM_TEXT, 'Group name')
                    ]), 'Participating subgroups', VALUE_OPTIONAL
                )
            ]), 'Exam sections to create (for COVID-19 course)', VALUE_OPTIONAL)
        ]);
    }

    public static function provide_course_exam_external_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'May be "ok" or "error"', VALUE_REQUIRED),
            'error' => new external_value(PARAM_TEXT, 'Error description (if applicable)', VALUE_OPTIONAL),
            'external_id' => new external_value(PARAM_TEXT, 'ID used to create external course with', VALUE_OPTIONAL),
            'external_category_id' => new external_value(PARAM_TEXT, 'Category ID used to create external course with', VALUE_OPTIONAL),
            'internal_id' => new external_value(PARAM_INT, 'Internal ID of created course (access via /course/view.php?id=<internal id>)', VALUE_OPTIONAL),
            'internal_category_id' => new external_value(PARAM_INT, 'Internal ID of category course was created in (access via /course/index.php?categoryid=<internal_category_id>)', VALUE_OPTIONAL),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'section_external_id' => new external_value(PARAM_TEXT, 'External value to identify thin section (not saved, used in output)'),
                    'section_internal_id' => new external_value(PARAM_INT, 'Internal ID that identifies this section')
                ]), 'Created course sections (if any)', VALUE_OPTIONAL
            )]);
    }

    public static function spbstuapi_transform_created_sections($new_sections)
    {
        $created_sections = [];
        foreach ($new_sections as $new_section) {
            $created_sections[] = [
                'section_external_id' => $new_section->external_id,
                'section_internal_id' => $new_section->id
            ];
        }
        return $created_sections;
    }
}