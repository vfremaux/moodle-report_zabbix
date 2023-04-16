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
 * this is a CLI alternative to moodle internal task. Use it f.e. if cron is
 * disabled on qualification environments.
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
    'rate' => false,
    'host' => false,
    'help' => false,
    'debugging' => false,
    ), 
    array(
        'r' => 'rate',
        'h' => 'help',
        'H' => 'host',
        'd' => 'debugging'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized.' is not a known command line option');
}

if (!empty($options['help'])) {
    $help = "
Run senders for a specific rate. Note that this is done WITHOUT passing through the
moodle core start system. So that internal locks should NOT affect senders.

Options:
    --host=URL                  Host to proceeed for.
    -r, --rate                  The sender rate to activate : instant,hourly,daily,weekly,monthly
    -h, --help                  Print out this help.
    -d, --debugging             Enables debug option.

Example:
\$sudo -u www-data /usr/bin/php report/zabbix/cli/zabbix_send_data.php --rate=instant/\n
\$sudo -u www-data /usr/bin/php report/zabbix/cli/zabbix_send_data.php --host=my.moodle.domain --rate=hourly/\n

Cron setup:
Adequate cron setup should assign a launch rate consistant with the rate parameter, i.e:
* * * * *  --rate=instant
0 * * * *  --rate=hourly
0 0 * * *  --rate=daily
0 0 1 * *  --rate=monthly
0 0 * * 0  --rate=weekly

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
if (empty($options['rate'])) {
    mtrace("Empty rate.\n");
    exit(1);
}

$rate = $options['rate'];

if (!in_array($rate, ['instant', 'hourly', 'daily', 'weekly', 'monthly'])) {
    mtrace("Invalid rate.\n");
    exit(1);
}

include_once($CFG->dirroot.'/report/zabbix/classes/task/'.$rate.'_task.php');
$taskclass = 'report_zabbix\\task\\'.$rate.'_task';
$task = new $taskclass();

if (!empty($options['debugging'])) {
    $task->set_verbose(true);
}

if (!empty($options['debugging'])) {
    mtrace("Executing sender task : ".$taskclass."\n");
}
$task->execute();

mtrace("Data sent\n");
exit(0);