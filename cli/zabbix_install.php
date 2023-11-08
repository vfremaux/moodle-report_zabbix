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
 * This script installs the zabbix Moodle monitoring model in Zabbix using Zabbix API.
 *
 * @package    report/zabbix
 * @subpackage cli
 * @copyright  2022 valery.fremaux@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // force first config to be minimal

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/lib/clilib.php');

echo "Starting with ".((!empty($CFG->zabbixusetesttarget)) ? 'test' : 'server')."\n";

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'host' => false,
    'zabbix-server' => false,
    'zabbix-protocol' => false,
    'help' => false,
    'debugging' => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host',
        'd' => 'debugging')
    );

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized.' is not a known command line option');
}

if (!empty($options['help'])) {
    $help = "
Installs the Moodle Monitoring model in Zabbix and configure the host.
This needs the report configuraiton be settled conveniently with a
valid Zabbix API administrator user account.

Options:
    --host=URL                  Host to proceeed for.
    --zabbix-server=hostname    Zabbix server, overriding current config.
    --zabbix-protocol=http|https Zabbix ppotocol, overriding current config.
    -h, --help                  Print out this help.
    -d, --debugging             Enables debug option.

Example:
\$sudo -u www-data /usr/bin/php report/zabbix/cli/zabbix_install.php/\n
\$sudo -u www-data /usr/bin/php report/zabbix/cli/zabbix_install.php --host=my.moodle.domain/\n
";

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

if (!empty($options['debugging'])) {
    $CFG->debug = E_ALL;
}

require_once($CFG->dirroot . '/report/zabbix/lib/zabbixapilib.php');

$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found");
    die;
}

$hostname = preg_replace('#https?://#', '', $CFG->wwwroot);

// Execution.
if (!empty($options['debugging'])) {
    mtrace("Building API instance.\n");
}

if (!empty($options['zabbix-server'])) {
    $options['zabbixserver'] = $options['zabbix-server'];
}

if (!empty($options['zabbix-protocol'])) {
    $options['zabbixprotocol'] = $options['zabbix-protocol'];
}

$config = get_config('report_zabbix');
if (!empty($config->zabbixhostname)) {
    $hostname = $config->zabbixhostname;
    $options['hostname'] = $hostname;
}

$cli = \report_zabbix\api::instance($options);

if (!$cli->is_logged_in()) {
    mtrace("could not login in Zabbix. Aborting...\n");
    exit(1);
} else {
    if (!empty($options['debugging'])) {
        mtrace("Zabbix API Logged in.\n");
    }
}

try {
    if (!empty($options['debugging'])) {
        mtrace("Host checks it is here.\n");
    }
    $check = $cli->check_host_exists();
    if (!$check) {
        mtrace("Host {$hostname} new in Zabbix. Creating... ");
        $cli->create_me();
        mtrace(" ...Created\n");
    } else {
        mtrace("Host {$hostname} already registered in Zabbix. Updating... ");
        $cli->update_me();
        mtrace(" ...Updated\n");
    }
} catch (Exception $ex) {
    mtrace("Exception : ".$ex->getMessage());
}


exit(0);