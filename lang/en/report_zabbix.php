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
 * @author Valery Fremaux valery.fremaux@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package report_zabbix
 * @category report
 */

// Privacy.
$string['privacy:metadata'] = "The Zabbix Report does not store any data belonging to users";
$string['zabbix:managecustom'] = "Can manage custom measurements";

$string['instant_task'] = 'Continuous Zabbix Emission';
$string['hourly_task'] = 'Hourly Zabbix Emission';
$string['daily_task'] = 'Daily Zabbix Emission';
$string['weekly_task'] = 'Weekly Zabbix Emission';
$string['monthly_task'] = 'Monthly Zabbix Emission';

$string['configzabbixapipath'] = 'Web path to Zabbix API';
$string['configzabbixapipath_desc'] = 'An absolute path (starting with slash) to append to server name to find the Zabbix web front end (often defaults to /zabbix).';
$string['configzabbixprotocol'] = 'Zabbix server protocol';
$string['configzabbixprotocol_desc'] = 'Protocol of zabbix server. Zabbix server should be addressed directly without protocol redirection';
$string['configzabbixversion'] = 'Zabbix server version';
$string['configzabbixversion_desc'] = 'Version of zabbix. This may affect how moodle connects to zabbix (fe. for registering).';
$string['configzabbixserver'] = 'Zabbix server IP or servername';
$string['configzabbixserver_desc'] = 'Direct IP of zabbix server or proxy. Can use dns names as known by the current environment';
$string['configzabbixhostname'] = 'Moodle registered hostname in zabbix';
$string['configzabbixhostname_desc'] = 'Host name associated to the MOODLE application template. Will default to local WWWROOT.';
$string['configzabbixsendercmd'] = 'Commande zabbix_sender';
$string['configzabbixsendercmd_desc'] = 'The zabbix_sender command location';
$string['configzabbixadminusername'] = 'Zabbix admin username';
$string['configzabbixadminusername_desc'] = 'Username of the Zabbix admin capable to operate the Zabbix API';
$string['configzabbixadminpassword'] = 'Zabbix admin password';
$string['configzabbixadminpassword_desc'] = 'Password of the Zabbix admin capable to operate the Zabbix API';
$string['configzabbixallowedcronperiod'] = 'Zabbix allowed cron period';
$string['configzabbixallowedcronperiod_desc'] = 'Usually period of cron should be very short (1m). Some massive installs of Moodle may allow longer delays between crons.';
$string['configzabbixgroups'] = 'Zabbix groups';
$string['configzabbixgroups_desc'] = 'Groups to link the current host';
$string['configzabbixinterfacedef'] = 'Zabbix interface definition';
$string['configzabbixinterfacedef_desc'] = 'The method to inform zabbix about the available interface for this hostname.';
$string['configzabbixtellithasstarted'] = 'Do tell it is started';
$string['configzabbixtellithasstarted_desc'] = 'When setting true, this explicitely tells this moodle instance has really started it\'s exploitation cycle.
You may alternatively use an SQL query as heuristic for determining the started state. Explicit activation wins.';
$string['configzabbixtellithasstartedsql'] = 'An SQL statement that tells it has started';
$string['configzabbixtellithasstartedsql_desc'] = 'If not empty, the result of this sql statement should give a $result->started
mark to 1 if it check is positive';

$string['configuserrolepolicy'] = 'User policy';
$string['configuserrolepolicy_desc'] = 'Policy to check user\'s main role in moodle';
$string['zabbixcustommeasurements'] = 'Custom Zabbix measurements';
$string['custommeasurements'] = 'Custom measurements';
$string['entpolicy'] = 'Policy for Academic moodles using user profile fields to tag users.';
$string['standardpolicy'] = 'Policy based on standard role assignements in moodle.';
$string['register'] = 'Register in zabbix';
$string['registerinzabbix'] = 'Follow this link to <a href="'.$CFG->wwwroot.'/report/zabbix/register.php">register this site in Zabbix</a>';
$string['errornoremotelogin'] = 'Could not login to Zabbix administration.';
$string['loginok'] = 'Zabbix API Logged in';
$string['creating'] = 'Host {$a} new in Zabbix. Creating... ';
$string['created'] = '... Created';
$string['updating'] = 'Host {$a} exists already in Zabbix. Updating... ';
$string['updated'] = '... Updated';
$string['notconfigured'] = 'Zabbix server is not or improperly configured.';
$string['configure'] = 'Configure zabbix server';
$string['zabbixserversettings'] = 'Zabbix server settings';
$string['units'] = 'Units';
$string['units_help'] = 'Units reported in Zabbix element';

$string['pluginname'] = 'Zabbix Sender';
$string['bypublicip'] = 'By public ip';
$string['byinternalip'] = 'By internal ip';
$string['bydns'] = 'By DNS';
$string['zabbixserveraccess'] = 'Access to Zabbix Server (only for registered accounts)';

$string['addmeasurement'] = 'Add a measurement';
$string['editcustommeasurement'] = 'Edit a customized measurement';
$string['name'] = 'Measurement name';
$string['duplicatenameerror'] = 'This name is already used';
$string['namerequired'] = 'Name is required';
$string['shortname'] = 'Measurement key';
$string['duplicateshortnameerror'] = 'This shortname is already used';
$string['shortnamerequired'] = 'Zabbic key is required';
$string['shortname_help'] = 'This shortname will appear as moodle.custom.&lt;shortname&gt; in zabbix if system context (one value), or moodle.custom.&lt;context&gt;.[&lt;id&gt;.&lt;shortname&gt;] if in other contexts.';
$string['rate'] = 'Capture rate';
$string['raterequired'] = 'Rate is required in form';
$string['allow'] = 'Context allowed instances';
$string['deny'] = 'Context excluded instances';
$string['customactive'] = 'Active';
$string['context'] = 'Context';
$string['context_help'] = 'If context is system, only one value will be sent. If other context, one value per allowed context instance will be sent.';
$string['sqlstatement'] = 'Sql statement (must provide a single numeric or text result as "meas", for a single context instance if using non system context)';
$string['contextsystem'] = 'System Context';
$string['contextcoursecat'] = 'Course Category Context';
$string['contextcourse'] = 'Course Context';
$string['contextmodule'] = 'Course Module Context';
$string['contextuser'] = 'User Context';
$string['contextcohort'] = 'Cohort Context';
$string['noselecterror'] = 'The statement is not a select statement. This is formbidden for security reasons.';
$string['nosqlmeaserror'] = 'There is no "meas" tagged output column in your query.';

$string['notstartedyet'] = 'Not started yet';
$string['started'] = 'Started';

include(__DIR__.'/pro_additional_strings.php');