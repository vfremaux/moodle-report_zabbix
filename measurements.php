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
 * @author Valery Fremaux valery@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package report_zabbix
 * @category report
 */

require('../../config.php');

$action = optional_param('what', '', PARAM_TEXT);

$context = context_system::instance();

$url = new moodle_url('/report/zabbix/measurements.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

require_login();
require_capability('report/zabbix:managecustom', $context);

if ($action) {
    include_once($CFG->dirroot.'/report/zabbix/measurements.controller.php');
    $controller = new \report_zabbix\controllers\measurements();
    $controller->receive($action);
    $controller->process($action);
}

$renderer = $PAGE->get_renderer('report_zabbix');
$measurements = $DB->get_records('report_zabbix_custom', []);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('custommeasurements', 'report_zabbix'));

echo $renderer->measurements($measurements);

echo '<center>';
$addmeasurementurl = new moodle_url('/report/zabbix/edit_measurement.php', []);
echo $OUTPUT->single_button($addmeasurementurl, get_string('addmeasurement', 'report_zabbix'));
echo '</center>';

echo $OUTPUT->footer();
