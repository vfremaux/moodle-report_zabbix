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
 * This file contains functions called from any other plugins in moodle.
 *
 * @package    report_zabbix
 * @category   report
 * @copyright  2012 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/report/zabbix/lib.php');

/**
 * Passes the registration call to the "Pro" zone.
 */
function report_zabbix_register_plugin($type, $name) {
    global $CFG;

    if (report_zabbix_supports_feature('extension/plugins')) {
        include_once($CFG->dirroot.'/report/zabbix/pro/lib.php');
        __report_zabbix_register_plugin($type, $name);
    }

}