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
require_once($CFG->dirroot.'/report/zabbix/forms/form_custom_measurement.php');
require_once($CFG->dirroot.'/report/zabbix/lib.php');
require_once($CFG->dirroot.'/report/zabbix/measurements.controller.php');

$measid = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/report/zabbix/edit_measurement.php', ['id' => $measid]);
$PAGE->set_url($url);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('report/zabbix:managecustom', $context);

$PAGE->set_heading(get_string('editcustommeasurement', 'report_zabbix'));
$PAGE->set_pagelayout('admin');

$mform = new form_custom_measurement();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/report/zabbix/measurements.php'));
}

if ($data = $mform->get_data()) {
    $controller = new \report_zabbix\controllers\measurements();
    if ($measid) {
        $cmd = 'update';
    } else {
        $cmd = 'add';
    }
    $controller->receive($cmd, $data);
    $resulturl = $controller->process($cmd);

    if (!empty($resulturl)) {
        redirect($resulturl);
    }
}

if ($measid) {
    $measurement = $DB->get_record('report_zabbix_custom', ['id' => $measid]);
    $mform->set_data($measurement);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();