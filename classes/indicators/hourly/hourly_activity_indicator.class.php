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
namespace report_zabbix\indicators;

use moodle_exception;
use coding_exception;
use StdClass;

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');

class hourly_activity_indicator extends zabbix_indicator {

    static $submodes = 'cronlateness,hourlyslowpages,hourlyneedsupgrading,adhocqueuesize';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.general';
        $this->donotsendzeros = true;
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        return explode(',', self::$submodes);
    }

    /**
     * the function that contains the logic to acquire the indicator instant value.
     * @param string $submode to target an aquisition to an explicit submode, elsewhere 
     */
    public function acquire_submode($submode) {
        global $DB, $CFG;

        if(!isset($this->value)) {
            $this->value = new Stdclass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        switch ($submode) {
            case 'cronlateness': {
                // $lastcron = get_config('tool_task', 'lastcronstart');
                $lastcron = $DB->get_field('config_plugins', 'value', ['plugin' => 'tool_task', 'name' => 'lastcronstart']);
                $this->value->$submode = time() - $lastcron;
                break;
            }

            case 'hourlyslowpages': {
                if (is_dir($CFG->dirroot.'/local/advancedperfs')) {
                    $activityhorizon = time() - HOURSECS;
                    $slowpages = $DB->count_records_select('local_advancedperfs_slowp', 'timecreated > ?', [$activityhorizon]);
                    $this->value->$submode = $slowpages;
                }
                break;
            }

            case 'hourlyneedsupgrading': {
                $this->value->$submode = (moodle_needs_upgrading()) ? 1 : 0;
                break;
            }

            case 'adhocqueuesize': {
                $this->value->$submode = $DB->count_records('task_adhoc', []);
                break;
            }

            default: {
                if ($CFG->debug == DEBUG_DEVELOPER) {
                    throw new coding_exception("Indicator has a submode that is not handled in aquire_submode().");
                }
            }
        }
    }
}