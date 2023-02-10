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
 * This script is to be used from PHP command line and will create a set
 * of Virtual VMoodle automatically from a CSV nodelist description.
 * Template names can be used to feed initial data of new VMoodles.
 * The standard structure of the nodelist is given by the nodelist-dest.csv file.
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php'); // Cli only functions.
require_once($CFG->dirroot.'/local/vmoodle/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/cli/clilib.php'); // Vmoodle cli only functions.
require_once($CFG->libdir.'/adminlib.php'); // Various admin-only functions.

// Fakes an admin identity for all the process.
$USER = get_admin();

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'debugging'         => false,
        'fullstop'          => false,
        'with-disabled'     => false,
        'with-master'       => false,
        'nodes'             => '',
        'lint'              => false,
        'verbose'           => false,
        'rate'              => false,
        'silent'            => false,
    ),
    array(
        'h' => 'help',
        'n' => 'nodes',
        'd' => 'with-disabled',
        'm' => 'with-master',
        'D' => 'debugging',
        'f' => 'fullstop',
        'l' => 'lint',
        'v' => 'verbose',
        'r' => 'rate',
        'S' => 'silent'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line VMoodle Zabbix Report Installer.
Please note you must execute this script with the same uid as apache!

Options:
    -h, --help            Print out this help
    -n, --nodes           A node descriptor CSV file (optional)
    -d, --with-disabled   Include also disabled nodes
    -m, --with-master     Include also master moodle.
    -l, --lint            Decodes node file and give a report on nodes to be created.
    -r, --rate            Sending rate (required).
    -f, --fullstop        Stops on first error.
    -D, --debugging       Turns on debug mode.
    -v, --verbose         Turns verbose mode on.
    -S, --silent          Do not send any progress mail.

Example:
\$sudo -u www-data /usr/bin/php report/zabbix/cli/bulkinstall.php
"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

// Get all options from config file.

if (empty($options['nodes'])) {
    $enabled = [];
    if (empty($options['with-disabled'])) {
        $enabled = array('enabled' => 1);
    }
    $nodes = $DB->get_records('local_vmoodle', $enabled);
} else {
    $nodes = vmoodle_parse_csv_nodelist($options['nodes']);
}

$debug = '';
if (!empty($options['debugging'])) {
    $CFG->debug = E_ALL;
    $debug = ' --debugging ';
}

if (!empty($options['lint'])) {
    var_dump($nodes);
    die;
}

if (empty($nodes)) {
    cli_error(get_string('cliemptynodelist', 'local_vmoodle'));
}

mtrace(get_string('clistart', 'local_vmoodle'));

$rate = '';
if (empty($options['rate'])) {
    mtrace("Rate must be provided\n");
    exit(1);
}
$rate = ' --rate='.$options['rate'];

if (!empty($options['with-master'])) {
    $workercmd = "php {$CFG->dirroot}/report/zabbix/cli/zabbix_send_data.php {$rate} {$debug} ";

    mtrace("Executing $workercmd\n######################################################\n");

    $output = array();
    exec($workercmd, $output, $return);

    if ($return) {
        if (empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            if (empty($options['silent'])) {
                vmoodle_cli_notify_admin("Bulk Zabbix Send Data : Sending for {$CFG->wwwroot} (master) ended with error");
            }
            die("Worker ended with error\n");
        }
        echo "Worker ended with error:\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }
}

$i = 0;
$numhosts = count($nodes);
foreach ($nodes as $n) {

    mtrace(get_string('climakenode', 'local_vmoodle', $n->vhostname));

    $workercmd = "php {$CFG->dirroot}/report/zabbix/cli/zabbix_send_data.php --host=\"{$n->vhostname}\" {$rate} {$debug} ";

    mtrace("Executing $workercmd\n######################################################\n");

    $output = array();
    exec($workercmd, $output, $return);

    if ($return) {
        if (empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            if (empty($options['silent'])) {
                vmoodle_cli_notify_admin("Bulk Zabbix Send Data : {$n->vhostname} ended with error");
            }
            die("Worker ended with error\n");
        }
        echo "Worker ended with error:\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }

    $i++;
    if (empty($options['silent'])) {
        vmoodle_send_cli_progress($numhosts, $i, 'bulkzabbixinstall');
    }
}

if (empty($options['silent'])) {
    vmoodle_cli_notify_admin("[$SITE->shortname] Bulk Zabbix Install done.");
}
echo "All done.\n";
exit(0);
