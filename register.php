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

$register = optional_param('register', false, PARAM_BOOL);

$systemcontext = context_system::instance();
require_login();
require_capability('moodle/site:config', $systemcontext);

$url = new moodle_url('/report/zabbix/register.php', ['register' => $register]);

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_heading(get_string('register', 'report_zabbix'));

echo $OUTPUT->header();

$config = get_config('report_zabbix');

if ($register) {
    $options = [];
    $hostname = preg_replace('#https?://#', '', $CFG->wwwroot);
    if (!empty($config->zabbixhostname)) {
        $hostname = $config->zabbixhostname;
    }
    $options['hostname'] = $hostname;
    $api = \report_zabbix\api::instance($options);

    if (!$api->is_logged_in()) {
        echo $OUTPUT->notification(get_string('errornoremotelogin', 'report_zabbix'), 'error');
    } else {
        echo $OUTPUT->notification(get_string('loginok', 'report_zabbix'), 'success');

        try {
            $check = $api->check_host_exists();
            if (!$check) {
                $str = get_string('creating', 'report_zabbix', $hostname);
                $api->create_me();
                $str .= " ...Created";
            } else {
                $str = "Host {$hostname} already registered in Zabbix. Updating... ";
                $api->update_me();
                $str .= " ...Updated";
            }
            echo $OUTPUT->notification($str, 'success');
        } catch (Exception $ex) {
            $str = '<pre>';
            $str .= "Exception : ".$ex->getMessage();
            $str .= '</pre>';
            echo $OUTPUT->notification($str, 'error');
        }
    }
}

if (!empty($config->zabbixserver)) {
    $table = new html_table();
    $table->caption = get_string('zabbixserversettings', 'report_zabbix');
    $table->width = '70%';
    $table->size = ['50%', '50%'];
    $table->align = ['left', 'left'];
    $table->data = [];
    $table->data[] = [get_string('configzabbixprotocol', 'report_zabbix'), $config->zabbixprotocol];
    $table->data[] = [get_string('configzabbixserver', 'report_zabbix'), $config->zabbixserver];
    if (!empty($config->zabbixhostname)) {
        $table->data[] = [get_string('configzabbixhostname', 'report_zabbix'), $config->zabbixhostname];
    }
    $table->data[] = [get_string('configzabbixgroups', 'report_zabbix'), $config->zabbixgroups];
    $table->data[] = [get_string('configzabbixtellithasstarted', 'report_zabbix'), $config->tellithasstarted];

    echo html_writer::table($table);

    echo '<br/><center>';
    $label = get_string('register', 'report_zabbix');
    $buttonurl = new moodle_url('/report/zabbix/register.php', ['register' => true]);
    echo $OUTPUT->single_button($buttonurl, $label);
    echo '</center>';
} else {
    echo $OUTPUT->notification(get_string('notconfigured', 'report_zabbix'), 'warning');
    $configureurl = new moodle_url('report/zabbix/admin/settiongs.php', ['section' => 'reportzabbix']);
    echo $OUTPUT->continue_button(get_string('configure', 'report_zabbix'), $configureurl);
}

echo $OUTPUT->footer();