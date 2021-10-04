<?php

require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot.'/enrol/cohort/lib.php');
require_once($CFG->dirroot.'/enrol/cohort/locallib.php');

const WEBINAR_COMMENT = '
<nolink>
    <p style="margin: 0cm 0cm 8pt; line-height: 107%; font-size: 11pt; font-family: Calibri, sans-serif;"><strong><span style="font-size: 13.5pt; line-height: 107%; font-family: Calibri, sans-serif; color: red; background: white; font-weight: normal;">Для входа в вебинарную комнату введите логин от единой учетной записи, укажите домен @spbstu.ru </span></strong>
        <span style="font-size: 13.5pt; line-height: 107%; color: red; background: white;">и пароль от единой учетной записи (пример: <span style="font-size: 13.5pt; line-height: 107%; color: black; background: white;">ivanov_aa@spbstu.ru</span>)
        <span
            style="font-size: 13.5pt; line-height: 107%; color: black; background: white;"><br></span><strong><span style="font-size: 13.5pt; line-height: 107%; font-family: Calibri, sans-serif; color: red; background: white;">Внимание: не забудьте включить запись вебинара! </span></strong></span>
    </p>
</nolink>
';

function spbstuapi_explode_category_path($path) {
    return explode('/', preg_replace('|^/|', '', $path));
}

function spbstuapi_path_divergency_index($root_category, $category, $extpath, $cats) {
    $root_path = $root_category->path;
    $cat_path_ids = spbstuapi_explode_category_path(preg_replace('|^' . $root_path . '|', '', $category->path));

    // Find divergence index
    foreach ($cat_path_ids as $i=>$cpi) {
        if (!array_key_exists($cpi, $cats) || $extpath[$i] != $cats[$cpi]->name) {
            return $i;
        }
    }
    // No divergence found
    return -1;
}

// Don't forget to set course and section!
function spbstuapi_create_url_modinfo($name, $url,$intro="") {
    global $DB;
    $url_module = new stdClass();
    $url_module->name = $name;
    $url_module->externalurl = $url;
    $url_module->display = 5;
    if ($intro) { $url_module->intro="Здесь вы найдете ответы на все вопросы";  $url_module->showdescription = 1;}
    $url_module->printintro = 0;
    $url_module->visible = 1;
    $url_module->visibleoncoursepage = 1;
    $url_module->cmidnumber = "";
    $url_module->coursemodule = 0;
    $url_module->module = $DB->get_record('modules', ['name' => 'url'])->id;
    $url_module->modulename = "url";
    $url_module->instance = 0;
    $url_module->add = "url";
    return $url_module;
}


function spbstuapi_create_teams_modinfo($name, $url,$intro="") {
    global $DB;
    $url_module = new stdClass();
    $url_module->name = $name;
    $url_module->link = $url;
    $url_module->intro = '-';
//    if ($intro) { $url_module->intro="Здесь вы найдете ответы на все вопросы";  $url_module->showdescription = 1;}
    $url_module->descripton = "-";
    $url_module->visible = 1;
    $url_module->visibleoncoursepage = 1;
    $url_module->cmidnumber = "";
    $url_module->coursemodule = 0;
    $url_module->module = $DB->get_record('modules', ['name' => 'teams'])->id;
    $url_module->modulename = "teams";
//    $url_module->instance = 0;
    $url_module->add = "teams";
    return $url_module;
}




function spbstuapi_add_modinfo_to_course($modinfo, $course, $section) {
    $modinfo->section = $section;
    $modinfo->course = $course->id;
    return add_moduleinfo($modinfo, $course, null);
}

function spbstuapi_clear_course($course_id) {
    $mods = get_course_mods($course_id);
    foreach ($mods as $cm) {
        course_delete_module($cm->id);
    }
}

function spbstuapi_create_covid_course($category_id, $name, $shortname, $description=null, $sections=null, $exam=false) {
    global $DB;
    $new_course = new stdClass();
    $new_course->fullname = $name;
    $new_course->shortname = $shortname;
    $new_course->category = $category_id;
    $new_course->visible = 1;
    $new_course->startdate = time();
    $new_course->enddate = 0;
    $new_course->automaticenddate = 0;
    $new_course->idnumber = "";
    $new_course->mform_isexpanded_id_descriptionhdr = 1;
    $new_course->summary = $description;
    $new_course->summaryformat = 1;
    $new_course->format = 'topics';
    $new_course->numsections = 0;
    $new_course->hiddensections = 0;
    $new_course->coursedisplay = 0;
    $new_course->addcourseformatoptionshere = 0;
    $new_course->lang = "";
    $new_course->newsitems = 5;
    $new_course->showgrades = 1;
    $new_course->showreports = 0;
    $new_course->maxbytes = 0;
    $new_course->enablecompletion = 1;
    $new_course->groupmode = 2;
    $new_course->groupmodeforce = 1;
    $new_course->defaultgroupingid = 0;
    $new_course->id = 0;

    $new_course = create_course($new_course);
    try {
        // In case something has been created, remove all modules


        // Because nobody will ever understand what these elements are without the comment
        $lol = new stdClass();
        $lol->name = "Общая информация"; // Dammit, communism in mah Moodlez!
    
    course_update_section(
            $new_course, $DB->get_record('course_sections', ['course' => $new_course->id, 'section' => 0]), $lol
        );

    
        
	if ($sections !== null) {
            if (!$exam) {
                $created_sections = spbstuapi_populate_covid_sections($new_course, $sections);
            } else {
                $created_sections = spbstuapi_populate_covid_exam_sections($new_course, $sections);
            }
            $new_course->new_sections = $created_sections;
        }

    } catch (Exception $e) {
        // Delete course with broken state
        delete_course($new_course, false);
        throw $e;
    }

    return $new_course;
}

const SURVEY_TEMPLATE_NAME = 'Опрос_по_проведенному_занятию';






function spbstuapi_enrol_participants($course, $sections) {
    global $DB;
    // Get teacher role
    $teacher_role = $DB->get_record('role', ["shortname" => "editingteacher"]);

    // Extract group (and subgroups) names from sections
    // Also ensure that all the teachers have accounts (and are enrolled)
    $group_names = [];
    $subgroup_names = [];
    foreach ($sections as $s) {
        if (array_key_exists("groups", $s)) {
            foreach ($s["groups"] as $gn) {
                if (!in_array($gn, $group_names)) {
                    $group_names[] = $gn;
                }
            }
        }

        if (array_key_exists("subgroups", $s)) {
            foreach ($s["subgroups"] as $sg) {
                if (!in_array($sg["n"], $subgroup_names)) {
                    $subgroup_names[] = $sg["n"];
                }
                if (!in_array($sg["g"], $group_names)) {
                    $group_names[] = $sg["g"];
                }
            }
        }

        // Try to provide (and enrol) user
        if (array_key_exists("teacher_name", $s)) {
            $tabno = array_key_exists("teacher_tabno", $s) ? $s["teacher_tabno"] : null;
            $spbstu_login = array_key_exists("teacher_spbstu_login", $s) ? $s["teacher_spbstu_login"] : null;

            $teacher_user = false;
            if ($tabno !== null || $spbstu_login !== null) {
                $teacher_user = spbstuapi_provide_teacher_account($s["teacher_name"], $tabno, $spbstu_login);
            }

            if ($teacher_user !== false) {
                spbstuapi_manually_enrol($course, $teacher_user, $teacher_role);
            }
        }
    }

    // Get student role
    $student_role = $DB->get_record('role', array('shortname' => 'student'));

    // Provide cohorts first
    $course_groups = [];
    if (!empty($group_names)) {
        $cohorts = spbstuapi_provide_cohorts($group_names);

        // Enrol those cohorts (will create course group if necessary) and get group ids
        foreach ($cohorts as $c) {
            $course_groups[$c->idnumber] = spbstuapi_enrol_cohortsync($course, $c, $student_role)->customint2;
        }
    }

    // Provide subgroups
    $subgroups_groups = [];
    if (!empty($subgroup_names)) {
        foreach ($subgroup_names as $sg) {
            $subgroups_groups[$sg] = spbstuapi_provide_course_group($course->id, $sg);
        }
    }

    return [$course_groups, $subgroups_groups];
}

function spbstuapi_populate_covid_sections($course, $sections) {
    // Enrol teachers, provide groups, generate subgroups mentioned in sections info
    list($course_groups, $subgroups_groups) = spbstuapi_enrol_participants($course, $sections);

    // Get survey template
//    $template = spbstuapi_questionnaire_get_template(SURVEY_TEMPLATE_NAME);

    $created_sections = [];
    // Finally, create sections
/*    foreach ($sections as $s) {
        $section_groups = [];
        // Subgroups have priority over groups in terms of availability
        if (array_key_exists("subgroups", $s)) {
            foreach ($s["subgroups"] as $sg) {
                $section_groups[] = $subgroups_groups[$sg["n"]];
            }
        } else if (array_key_exists("groups", $s)) {
            foreach ($s["groups"] as $gn) {
                $section_groups[] = $course_groups[$gn];
            }
        }

*/

}

function spbstuapi_questionnaire_get_template($name) {
    global $DB;
    $template = $DB->get_record('questionnaire_survey', ['realm' => 'template', 'title' => $name]);
    if ($template === false) {
        $template = null;
    } else {
        $template = 'template-' . strval($template->id);
    }
    return $template;
}

const AVAILABILITY_MULTIPLE_TEMPLATE = '{"op":"&","c":[{"op":"|","c":[!VALUE!]}],"showc":[false]}';
const AVAILABILITY_SINGLE_TEMPLATE = '{"op":"&","c":[!VALUE!],"showc":[false]}';
const AVAILABILITY_GROUP_CONDITION = '{"type":"group","id":!VALUE!}';

function spbstuapi_get_availability_string($group_ids) {
    if (empty($group_ids)) {
        return "";
    }
    $values = [];
    foreach ($group_ids as $gid) {
        $values[] = str_replace('!VALUE!', strval($gid), AVAILABILITY_GROUP_CONDITION);
    }

    $template = sizeof($values) === 1 ? AVAILABILITY_SINGLE_TEMPLATE : AVAILABILITY_MULTIPLE_TEMPLATE;
    return str_replace('!VALUE!', implode(',', $values), $template);
}

function spbstuapi_create_empty_covid_section($course, $group_ids, $date, $time, $teacher_name, $what)
{
    $new_section = course_create_section($course->id);
    $new_section->name = $what;


    course_update_section($course, $new_section, $new_section);
    return $new_section;
}



function spbstuapi_create_covid_section($external_id, $course, $group_ids, $date, $time, $teacher_name, $link, $what, $survey_template=null) {
    $new_section = spbstuapi_create_empty_covid_section($course, $group_ids, $date, $time, $teacher_name, $what);


        $course, $new_section->section

    $new_section->external_id = $external_id;
    return $new_section;
}



const SEB_INSTRUCTION_NAME = "Инструкция по установке Safe Exam Browser";
const SEB_INSTRUCTION_NOTICE = "Программное обеспечение Safe Exam Browser устанавливается только в случае проведения промежуточной аттестации в форме компьютерного тестирования";
const SEB_INSTRUCTION_LOCATION = "/safe/howTo.pdf";
const SEB_INSTALL_NAME = "Установка Safe Exam Browser";
const SEB_INSTALL_LOCATION = "/safe/";
const VERBAL_NAME = "Лист контроля";
const QUIZ_NAME = "Зачёт";
const AGREEMENT_TEMPLATE_NAME = "Ознакомление с условиями проведения промежуточной аттестации";
const AGREEMENT_NAME = "Ознакомление с условиями проведения промежуточной аттестации";
const AGREEMENT_DISCLAIMER = "Обучающийся обязан ознакомиться с правилами проведения промежуточной аттестации, в том числе видеофиксации ее хода, до начала прохождения промежуточной аттестации";


function spbstuapi_provide_cohorts($names) {
    global $DB;

    $syscontext = context_system::instance();

    list($insql, $inparams) = $DB->get_in_or_equal($names);
    $existing_cohorts = $DB->get_records_sql("SELECT * FROM {cohort} WHERE idnumber " . $insql, $inparams);

    $result = [];

    $non_existing = $names;
    foreach($existing_cohorts as $ec) {
        $result[$ec->idnumber] = $ec;
        if (($key = array_search($ec->idnumber, $non_existing)) !== false) {
            unset($non_existing[$key]);
        }
    }

    foreach ($non_existing as $nc) {
        $new_cohort = new stdClass();
        $new_cohort->name = $nc;
        $new_cohort->idnumber = $nc;
        $new_cohort->contextid = $syscontext->id;
        $cohort_id = cohort_add_cohort($new_cohort);
        $result[$nc] = $DB->get_record('cohort', ['id' => $cohort_id]);
    }

    return $result;
}


function spbstuapi_enrol_cohortsync($course, $cohort, $role) {
    global $DB;
    $params = [
        'name' => $cohort->idnumber,
        'customint1' => $cohort->id,
        'roleid' => $role->id,
        'customint2' => spbstuapi_provide_course_group($course->id, $cohort->idnumber)
    ];

    $enrol = enrol_get_plugin("cohort");
    if ($instance = $DB->get_record('enrol', $params)) {
        // Do nothing, cohort is already synchronized
    } else if ($instanceid = $enrol->add_instance($course, $params)) {
        // Синхронизируем
        $trace = new null_progress_trace();
        enrol_cohort_sync($trace, $course->id);
        $trace->finished();
        $instance = $DB->get_record('enrol', $params);
    }

    return $instance;
}



function spbstuapi_fetch_teacher_username($tabno) {
    $serverName = "sqlt.spbstu.ru";
    $connectionInfo = array("Database" => 'Деканат', "CharacterSet" => "UTF-8", "UID" => 'local', "PWD" => '123');
    $connection = sqlsrv_connect($serverName, $connectionInfo);

    if (!$connection) {
        throw new dml_connection_exception("Unable to connect to dean's office database");
    }

    $tsql = "SELECT TAB_N, SUSER_SNAME(sid) as uid FROM [Деканат].[dataexchange].[ka_KADRY] where TAB_N = ".$tabno.";";
    $result = sqlsrv_query($connection, $tsql);

    $records = [];
    while( $row = sqlsrv_fetch_array( $result, SQLSRV_FETCH_ASSOC))
    {
        $records[] = str_replace('spbstu', '', $row['uid']);
    }

    return $records;
}

function spbstuapi_provide_teacher_account($name, $tabno = null, $spbstu_email = null) {
    global $DB, $CFG;

    $spbstu_name = null;
    $search_sql = "SELECT ... ";
    $search_params = [];
    $search_conditions = [];

    if ($spbstu_email === null && $tabno !== null) {
        $login = spbstuapi_fetch_teacher_username($tabno);
        if (empty($login)) {
            throw new dml_missing_record_exception("No teacher found for tabno " . strval($tabno));
        }
        if (sizeof($login) > 1) {
            throw new dml_multiple_records_exception("Multiple usernames found for teacher with tabno " . strval($tabno));
        }

        $spbstu_name = $login[0];
    } else if ($spbstu_email !== null) {
        $spbstu_name = preg_match('|^(.*)\@|', $spbstu_email, $matches);
        if (sizeof($matches) >= 1) {
            $spbstu_name = $matches[1];
        }
        // Also search with email
        $search_conditions[] = "email = ?";
        $search_params[] = $spbstu_email;
    }
    $search_conditions[] = "username = ?";
    $search_params[] = mb_strtolower($spbstu_name);

    $search_sql = $search_sql . "(" . implode(' OR ', $search_conditions) . ")";

    $existing_teacher = $DB->get_record_sql($search_sql, $search_params, IGNORE_MISSING);

    if ($existing_teacher !== false) {
        return $existing_teacher;
    }

    if ($spbstu_email === null) {
        $spbstu_email = $spbstu_name . '@spbstu.ru';
    }

    $splitname = explode(' ', $name, 2);

    $new_user = new stdClass();
    $new_user->auth = 'ldap';
    $new_user->lastname = $splitname[0];
    $new_user->firstname = $splitname[1];
    $new_user->username = mb_strtolower($spbstu_name);
    $new_user->lang = $CFG->lang;
    $new_user->email = $spbstu_email;
    $new_user->confirmed = 1;
    $new_user->lastip = getremoteaddr();
    $new_user->timecreated = time();
    $new_user->timemodified = $new_user->timecreated;
    $new_user->mnethostid = $CFG->mnet_localhost_id;
    $new_user->id = user_create_user($new_user, false, false);
    profile_save_data($new_user);
    $user = get_complete_user_data('id', $new_user->id);
    update_internal_user_password($user, '');

    return $user;
}


function spbstuapi_manually_enrol($course, $user, $role) {
    // Get manual enrolment plugin
    $plugin = enrol_get_plugin('manual');
    if ($plugin === null) {
        throw new coding_exception("'manual' enrolment plugin is absent");
    }

    // Search for (maybe inactive) manual enrolment plugin
    $instances = enrol_get_instances($course->id, false);
    $instance = null;
    foreach ($instances as $i) {
        if ($i->enrol == 'manual') {
            $instance = $i;
            break;
        }
    }

    // Try to add manual enrolment plugin if not exists
    if ($instance === null) {
        $instance = $plugin->add_default_instance($course);
    }

    // Forcibly enable plugin for course
    if ($instance->status != "0") {
        $instance->status = "0";
    }

    // Finally, enrol
    $plugin->enrol_user($instance, $user->id, $role->id, 0, 0, null, false);

    return true;
}

function spbstuapi_populate_course_subgroups($course_id) {
    global $DB;
    // Get groups enrolled with cohort
    $enrols = $DB->get_records('enrol', ['enrol' => 'cohort', 'courseid' => $course_id]);

    // Skip empty enrols (no groups, how to populate subgroups?)
    if (empty($enrols)) {
        return;
    }

    // $group->customint2 === group id

    $enrol_groups = [];
    foreach ($enrols as $enrol) {
        $enrol_groups[$enrol->name] = $enrol->customint2;
    }

    // Get non-enrol groups
    list($insql, $inparams) = $DB->get_in_or_equal(array_values($enrol_groups));
    $inparams = array_merge([$course_id], $inparams);
    $subgroups = $DB->get_records_sql(
        "SELECT g.id, g.name from {groups} g 
           LEFT JOIN {groups_members} gm ON g.id = gm.groupid
           WHERE g.courseid = ? AND NOT (g.id " . $insql . ")", $inparams
    );

    // Ve-e-ery primitive algorithm: just check if enrol group name is at the very beginning of the group name
    // and if it is, clone all the members
    foreach ($subgroups as $eg) {
        foreach ($enrol_groups as $name => $group_id) {
            if (strpos($eg->name, $name) === 0) {
                // Clone group enrolments
                spbstuapi_clone_group_members($group_id, $eg->id);
            }
        }
    }
}


function spbstuapi_populate_all_subgroups() {
    global $DB;
    $courses = $DB->get_records('spbstuapi_ext_courses');
    foreach ($courses as $c) {
        spbstuapi_populate_course_subgroups($c->internal_id);
    }
}