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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/report/zabbix/lib.php');
require_once($CFG->dirroot.'/report/zabbix/lib/zabbixapilib.php');

if ($hassiteconfig) {

    $key = 'report_zabbix/zabbixprotocol';
    $label = get_string('configzabbixprotocol', 'report_zabbix');
    $desc = get_string('configzabbixprotocol_desc', 'report_zabbix');
    $default = 'https';
    $protocols = [
        'http' => 'HTTP',
        'https' => 'HTTPS',
    ];
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $protocols));

    $key = 'report_zabbix/zabbixversion';
    $label = get_string('configzabbixversion', 'report_zabbix');
    $desc = get_string('configzabbixversion_desc', 'report_zabbix');
    $default = '6.4';
    $engineversions = [
        '6.0' => '< 6.2',
        '6.2' => '6.2',
        '6.4' => '6.4',
    ];
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $engineversions));

    $key = 'report_zabbix/zabbixserver';
    $label = get_string('configzabbixserver', 'report_zabbix');
    $desc = get_string('configzabbixserver_desc', 'report_zabbix');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'report_zabbix/zabbixapipath';
    $label = get_string('configzabbixapipath', 'report_zabbix');
    $desc = get_string('configzabbixapipath_desc', 'report_zabbix');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    // Better read : hostname of (this) for zabbix 
    $key = 'report_zabbix/zabbixhostname';
    $label = get_string('configzabbixhostname', 'report_zabbix');
    $desc = get_string('configzabbixhostname_desc', 'report_zabbix');
    $default = preg_replace('#https?://#', '', $CFG->wwwroot);
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'report_zabbix/zabbixsendercmd';
    $label = get_string('configzabbixsendercmd', 'report_zabbix');
    $desc = get_string('configzabbixsendercmd_desc', 'report_zabbix');
    $default = '/usr/bin/zabbix_sender';
    $settings->add(new admin_setting_configexecutable($key, $label, $desc, $default));

    $key = 'report_zabbix/userrolepolicy';
    $label = get_string('configuserrolepolicy', 'report_zabbix');
    $desc = get_string('configuserrolepolicy_desc', 'report_zabbix');
    $policies = [
        'standard' => get_string('standardpolicy', 'report_zabbix'),
        'ent' => get_string('entpolicy', 'report_zabbix'),
    ];
    $default = 'standard';
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $policies));

    // This is for schema injection using zabbix API.

    $key = 'report_zabbix/zabbixadminusername';
    $label = get_string('configzabbixadminusername', 'report_zabbix');
    $desc = get_string('configzabbixadminusername_desc', 'report_zabbix');
    $default = 'admin';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $key = 'report_zabbix/zabbixadminpassword';
    $label = get_string('configzabbixadminpassword', 'report_zabbix');
    $desc = get_string('configzabbixadminpassword_desc', 'report_zabbix');
    $default = 'admin';
    $settings->add(new admin_setting_configpasswordunmask($key, $label, $desc, $default));

    $key = 'report_zabbix/zabbixgroups';
    $label = get_string('configzabbixgroups', 'report_zabbix');
    $desc = get_string('configzabbixgroups_desc', 'report_zabbix');
    $default = '';
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default, PARAM_TEXT, 120));

    $key = 'report_zabbix/interfacedef';
    $label = get_string('configzabbixinterfacedef', 'report_zabbix');
    $desc = get_string('configzabbixinterfacedef_desc', 'report_zabbix');
    $default = DNS;
    $idoptions = [
        DNS => get_string('bydns', 'report_zabbix'),
        PUBLICIP => get_string('bypublicip', 'report_zabbix'),
        INTERNALIP => get_string('byinternalip', 'report_zabbix'),
    ];
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $idoptions));

    $key = 'report_zabbix/tellithasstarted';
    $label = get_string('configzabbixtellithasstarted', 'report_zabbix');
    $desc = get_string('configzabbixtellithasstarted_desc', 'report_zabbix');
    $default = 0;
    $startedoptions = [
        0 => get_string('notstartedyet', 'report_zabbix'),
        1 => get_string('started', 'report_zabbix')
    ];
    $settings->add(new admin_setting_configselect($key, $label, $desc, $default, $startedoptions));

    $key = 'report_zabbix/tellithasstartedsql';
    $label = get_string('configzabbixtellithasstartedsql', 'report_zabbix');
    $desc = get_string('configzabbixtellithasstartedsql_desc', 'report_zabbix');
    $default = '';
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, $default));

    $key = 'report_zabbix/allowedcronperiod';
    $label = get_string('configzabbixallowedcronperiod', 'report_zabbix');
    $desc = get_string('configzabbixallowedcronperiod_desc', 'report_zabbix');
    $default = 60; // in minutes, defaults to one hour.
    $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

    $url = new moodle_url('/report/zabbix/register.php');
    $html = get_string('registerinzabbix', 'report_zabbix');
    $settings->add(new admin_setting_heading('register', get_string('register', 'report_zabbix'), $html));

    $label = get_string('zabbixcustommeasurements', 'report_zabbix');
    $pageurl = new moodle_url('/report/zabbix/measurements.php');
    $ADMIN->add('reports', new admin_externalpage('customzabbixmeasurements', $label, $pageurl, 'report/zabbix:managecustom'));

    if (report_zabbix_supports_feature('emulate/community') == 'pro') {
        include_once($CFG->dirroot.'/report/zabbix/pro/prolib.php');
            $promanager = report_zabbix\pro_manager::instance();
            $promanager->add_settings($ADMIN, $settings);
    } else {
        $label = get_string('plugindist', 'report_zabbix');
        $desc = get_string('plugindist_desc', 'report_zabbix');
        $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
    }
}

$config = get_config('report_zabbix');
if (!empty($config->zabbixserver)) {
    $zabbixurl = ($config->zabbixprotocol == 'HTTP') ? 'http://'.$config->zabbixserver : 'https://'.$config->zabbixserver ;
    if (!empty($config->zabbixpipath)) {
        $zabbixurl .= '/'.$config->zabbixpipath;
    }
    $key = 'report_zabbix_access';
    $menuaccess = new admin_externalpage($key, get_string('zabbixserveraccess', 'report_zabbix'), $zabbixurl);
    $ADMIN->add('reports', $menuaccess);
}

