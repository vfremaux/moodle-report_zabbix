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
 * A scheduled task for zabbix daily sender cron.
 *
 * @package    report_zabbix
 * @category   report
 * @copyright  2018 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_zabbix\task;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/report/zabbix/lib.php');

class daily_task extends \core\task\scheduled_task {

    protected $verbose;

    public function set_verbose($verbose) {
        $this->verbose = $verbose;
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('daily_task', 'report_zabbix');
    }

    /**
     * Run daily senders cron.
     */
    public function execute() {

        @raise_memory_limit('512M');
        @set_time_limit(1800);

        if ($this->verbose || defined('CLI_SCRIPT')) {
            mtrace("Starting daily send.\n");
        }

        // Load those indicators that provide daily period measurement explicitely or implicitely.
        $indicators = report_zabbix_load_indicators('daily');

        if (!empty($indicators)) {
            foreach ($indicators as $classname => $indicator) {
                if ($this->verbose || defined('CLI_SCRIPT')) {
                    mtrace("Starting $classname.\n");
                }
                $indicator->acquire();
                $indicator->send();
            }
        } else {
            if ($this->verbose || defined('CLI_SCRIPT')) {
                mtrace("Empty indicator set.\n");
            }
        }
    }

    /**
     * Note that structurally, reports are not supported to be enabled or disabled,
     * this may cause troubles in task elibility to run.
     */
    public function get_run_if_component_disabled() {
        return true;
    }
}
