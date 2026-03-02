<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for local_helpdesk.
 *
 * @package    local_helpdesk
 * @copyright  2026 Helpdesk Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_helpdesk plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_helpdesk_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026030101) {
        // Make local_helpdesk_feedback.supportuserid nullable so feedback can be
        // submitted even when a ticket has no assigned support user.
        //
        // Moodle creates an index for every foreign key definition. That index
        // blocks the column alteration, so we must drop the FK/index first,
        // change the field, then restore the FK.

        $table = new xmldb_table('local_helpdesk_feedback');
        $key   = new xmldb_key('supportuserid', XMLDB_KEY_FOREIGN, ['supportuserid'], 'user', ['id']);
        $field = new xmldb_field('supportuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');

        // Drop the FK (and its auto-generated index) if it exists.
        if ($dbman->find_key_name($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // Change the column to nullable.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        // Restore the foreign key (recreates the index too).
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2026030101, 'local', 'helpdesk');
    }

    if ($oldversion < 2026030102) {
        // The technical_support role was created without a system context level entry,
        // so it did not appear in "Assign system roles". Fix it for existing installs.
        $role = $DB->get_record('role', ['shortname' => 'technical_support']);
        if ($role) {
            set_role_contextlevels($role->id, [CONTEXT_SYSTEM]);
        }

        upgrade_plugin_savepoint(true, 2026030102, 'local', 'helpdesk');
    }

    if ($oldversion < 2026030103) {
        // Re-assign all required capabilities to the technical_support role.
        // The original install.php assign_capability() calls can be silently skipped
        // by Moodle when capability definitions have not yet been fully committed at
        // install time, leaving the role with no effective permissions.
        $role = $DB->get_record('role', ['shortname' => 'technical_support']);
        if ($role) {
            $systemcontext = context_system::instance();

            $caps = [
                'local/helpdesk:viewalltickets',
                'local/helpdesk:managetickets',
                'local/helpdesk:openchat',
            ];

            foreach ($caps as $cap) {
                // Unassign first to ensure a clean state, then re-assign.
                unassign_capability($cap, $role->id, $systemcontext->id);
                assign_capability($cap, CAP_ALLOW, $role->id, $systemcontext->id, true);
            }

            // Flush all cached capability data so the change is visible immediately
            // without requiring a manual cache purge or user re-login.
            accesslib_clear_all_caches(true);
        }

        upgrade_plugin_savepoint(true, 2026030103, 'local', 'helpdesk');
    }

    return true;
}
