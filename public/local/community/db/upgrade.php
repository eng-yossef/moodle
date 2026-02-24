<?php
/**
 * Upgrade script for local_community plugin.
 *
 * @package    local_community
 */

function xmldb_local_community_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // 1️⃣ Add votes field to posts table
    if ($oldversion < 2026022401) {
        $table = new xmldb_table('local_community_posts');
        $field = new xmldb_field('votes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint after posts table upgrade
        upgrade_plugin_savepoint(true, 2026022401, 'local', 'community');
    }

    // 2️⃣ Add votes field to answers table
    if ($oldversion < 2026022402) {
        $table = new xmldb_table('local_community_answers');
        $field = new xmldb_field('votes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint after answers table upgrade
        upgrade_plugin_savepoint(true, 2026022402, 'local', 'community');
    }

    return true;
}
