<?php

function xmldb_local_spbstuapi_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019100510) {
        // Define table spbstuapi_ext_courses to be created.
        $table = new xmldb_table('spbstuapi_ext_courses');

        // Adding fields to table spbstuapi_ext_courses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('external_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('external_catid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('internal_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table spbstuapi_ext_courses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('spbstuapi_ext_courses_uq', XMLDB_KEY_UNIQUE, ['external_id']);
        $table->add_key('spbstuapi_ext_course_fk', XMLDB_KEY_FOREIGN, ['internal_id'], 'course', ['id']);
        $table->add_key('spbstuapi_ext_courses_fc', XMLDB_KEY_FOREIGN, ['external_catid'], 'spbstuapi_ext_cats', ['id']);

        // Conditionally launch create table for spbstuapi_ext_courses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table spbstuapi_ext_cats to be created.
        $table = new xmldb_table('spbstuapi_ext_cats');

        // Adding fields to table spbstuapi_ext_cats.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('category_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table spbstuapi_ext_cats.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('spbstuapi_ext_cats_fu', XMLDB_KEY_FOREIGN_UNIQUE, ['category_id'], 'course_categories', ['id']);

        // Conditionally launch create table for spbstuapi_ext_cats.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Spbstuapi savepoint reached.
        upgrade_plugin_savepoint(true, 2019100510, 'local', 'spbstuapi');
    }

    if ($oldversion < 2019100513) {

        // Define field allowed_root to be added to spbstuapi_ext_cats.
        $table = new xmldb_table('spbstuapi_ext_cats');
        $field = new xmldb_field('allowed_root', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'category_id');

        // Conditionally launch add field allowed_root.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Spbstuapi savepoint reached.
        upgrade_plugin_savepoint(true, 2019100513, 'local', 'spbstuapi');
    }

    return true;
}