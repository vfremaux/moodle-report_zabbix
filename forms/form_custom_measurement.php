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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class form_custom_measurement extends moodleform {

    public function definition() {

        $mform = &$this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'report_zabbix'), 'size="150" maxlength="255"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('namerequired', 'report_zabbix'), 'required', '', 'server');

        $mform->addElement('text', 'shortname', get_string('shortname', 'report_zabbix'), 'size="40" ');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('shortnamerequired', 'report_zabbix'), 'required', '', 'server');
        $mform->addHelpButton('shortname', 'shortname', 'report_zabbix');

        $mform->addElement('text', 'units', get_string('units', 'report_zabbix'), 'size="20" ');
        $mform->setType('units', PARAM_TEXT);
        $mform->setDefault('units', '');
        $mform->addHelpButton('units', 'units', 'report_zabbix');

        $mform->addElement('checkbox', 'active', get_string('customactive', 'report_zabbix'));
        $mform->setDefault('active', true);
        $mform->setType('active', PARAM_BOOL);

        $props = 'wrap="virtual" rows="10" cols="120"';
        $mform->addElement('textarea', 'sqlstatement', get_string('customactive', 'report_zabbix'), $props);
        $mform->setType('sqlstatement', PARAM_TEXT);

        $contexts = report_zabbix_contexts();
        $mform->addElement('select', 'context', get_string('context', 'report_zabbix'), $contexts);
        $mform->setType('context', PARAM_TEXT);
        $mform->addHelpButton('context', 'context', 'report_zabbix');

        $mform->addElement('text', 'allow', get_string('allow', 'report_zabbix'), 'size="120"');
        $mform->setType('allow', PARAM_TEXT);

        $mform->addElement('text', 'deny', get_string('deny', 'report_zabbix'), 'size="120"');
        $mform->setType('deny', PARAM_TEXT);

        $contexts = report_zabbix_rates();
        $mform->addElement('select', 'rate', get_string('rate', 'report_zabbix'), $contexts);
        $mform->setDefault('rate', REPORT_ZABBIX_RATE_HOURLY);

        $this->add_action_buttons();
    }

    public function validation($data, $files = null) {
        global $DB;

        $errors = [];

        if (empty($data['id'])) {
            // New definition.
            if ($DB->get_record('report_zabbix_custom', ['name' => $data['name']])) {
                $errors['name'] = get_string('duplicatenameerror', 'report_zabbix');
            }

            // New definition.
            if ($DB->get_record('report_zabbix_custom', ['shortname' => $data['shortname']])) {
                $errors['name'] = get_string('duplicateshortnameerror', 'report_zabbix');
            }
        } else {
            // New definition.
            $params = ['id' => $data['id'], 'name' => $data['name']];
            if ($DB->get_record_select('report_zabbix_custom', 'id != :id AND name = :name ', $params)) {
                $errors['name'] = get_string('duplicatenameerror', 'report_zabbix');
            }

            // New definition.
            $params = ['id' => $data['id'], 'shortname' => $data['shortname']];
            if ($DB->get_record_select('report_zabbix_custom', 'id != :id AND shortname = :shortname ', $params)) {
                $errors['name'] = get_string('duplicateshortnameerror', 'report_zabbix');
            }
        }

        if (!empty($data['sqlstatement'])) {
            if (!preg_match('/select/i', $data['sqlstatement'])) {
                $error['sqlstatement'] = get_string('notselecterror', 'repprt_zabbix');
            }

            if (!preg_match('/as meas\\b/i', $data['sqlstatement'])) {
                $error['sqlstatement'] = get_string('notsqlmeaserror', 'repprt_zabbix');
            }
        }

        return $errors;
    }
}