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
 * This file keeps track of upgrades to the wiki module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * @package report_zabbix
 * @copyright 2022
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
defined('MOODLE_INTERNAL') || die;

function xmldb_report_zabbix_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Moodle v2.3.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2022060700) {

        // Define table report_zabbix_plugins to be created.
        $table = new xmldb_table('report_zabbix_plugins');

        // Adding fields to table report_zabbix_plugins.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '32', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null);

        // Adding keys to table report_zabbix_plugins.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding keys to table report_zabbix_plugins.
        $table->add_index('ix_unique_plugin', XMLDB_INDEX_UNIQUE, array('type,name'));

        if (!$dbman->table_exists($table)) {
            // Launch create table for flashcard_card.
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022060700, 'report', 'zabbix');
    }

    if ($oldversion < 2023040700) {
        // Define table report_zabbix_custom to be created.
        $table = new xmldb_table('report_zabbix_custom');

        // Adding fields to table report_zabbix_custom.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sqlstatement', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('context', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('allow', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('deny', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('rate', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_zabbix_custom.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table report_zabbix_custom.
        $table->add_index('name_ix', XMLDB_INDEX_UNIQUE, ['name']);
        $table->add_index('shortname_ix', XMLDB_INDEX_UNIQUE, ['shortname']);
        $table->add_index('rate_ix', XMLDB_INDEX_NOTUNIQUE, ['rate']);

        // Conditionally launch create table for report_zabbix_custom.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2023040700, 'report', 'zabbix');
    }

    return true;
}
