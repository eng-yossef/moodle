<?php
/**
 * Upgrade script for local_community plugin.
 *
 * @package    local_community
 */

function xmldb_local_community_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // =============================
    // 1️⃣ Add votes field to posts
    // =============================
    if ($oldversion < 2026022401) {

        $table = new xmldb_table('local_community_posts');
        $field = new xmldb_field('votes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026022401, 'local', 'community');
    }

    // =============================
    // 2️⃣ Add votes field to answers
    // =============================
    if ($oldversion < 2026022402) {

        $table = new xmldb_table('local_community_answers');
        $field = new xmldb_field('votes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026022402, 'local', 'community');
    }

    // =============================
    // 3️⃣ Reputation table
    // =============================
    if ($oldversion < 2026022403) {

        $table = new xmldb_table('local_community_reputation');

        if (!$dbman->table_exists($table)) {

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('points', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid_unique', XMLDB_KEY_UNIQUE, ['userid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026022403, 'local', 'community');
    }

    // =============================
    // 4️⃣ Badges table
    // =============================
    if ($oldversion < 2026022404) {

        $table = new xmldb_table('local_community_badges');

        if (!$dbman->table_exists($table)) {

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('icon', XMLDB_TYPE_CHAR, '255', null, null);
            $table->add_field('rule', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
            $table->add_field('threshold', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026022404, 'local', 'community');
    }

    // =============================
    // 5️⃣ User badges table
    // =============================
    if ($oldversion < 2026022405) {

        $table = new xmldb_table('local_community_user_badges');

        if (!$dbman->table_exists($table)) {

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('badgeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userbadge_unique', XMLDB_KEY_UNIQUE, ['userid', 'badgeid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026022405, 'local', 'community');
    }

    // =============================
    // 6️⃣ Seed default badges
    // =============================
    if ($oldversion < 2026022406) {

        $badges = [

            ['First Question', 'Asked your first question', 'questions', 1],
            ['Curious Mind', 'Asked 10 questions', 'questions', 10],

            ['First Answer', 'Posted your first answer', 'answers', 1],
            ['Helper', 'Posted 25 answers', 'answers', 25],

            ['Popular Answer', 'Answer reached 10 votes', 'answer_votes', 10],

            ['Legend', 'Earned 1000 reputation', 'reputation', 1000],
        ];

        foreach ($badges as $b) {

            if (!$DB->record_exists('local_community_badges', ['name' => $b[0]])) {

                $record = new stdClass();
                $record->name = $b[0];
                $record->description = $b[1];
                $record->rule = $b[2];
                $record->threshold = $b[3];

                $DB->insert_record('local_community_badges', $record);
            }
        }

        upgrade_plugin_savepoint(true, 2026022406, 'local', 'community');
    }

    return true;
}