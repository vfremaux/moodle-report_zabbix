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
use StdClass;

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');

/**
 * Statefull indicators are instant indicators that need to send zero states
 * to maintain a statefull information to the zabbix server.
 * In general indicators included in this class are bound to zabbix triggers that
 * nead to be relased when the error condition is off.
 */
class statefull_indicator extends zabbix_indicator {

    static $submodes = 'instance,started,cronstate,backupsinprogress,cronfailed';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.general';
        $this->donotsendzeros = false; // Need have a constant refresh to trigger cron drops.
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
            case 'instance': {
                // This sends a 1 constant to mark the instance existance for moodle area statistics.
                $this->value->$submode = 1;
                break;
            }

            case 'started': {
                /*
                 * This sends a 1 constant from the moment moodle instance knows it has really started exploitation.
                 * This can be told explictely by configuration, or using a SQL heuristic detector.
                 */
                 // TODO : Add a moodle cache for this state that should NOT be recalculated again and again.
                // Other alternative is to move this indicator to a daily or slower rate.
                $started = 0;
                if (!empty(self::$config->tellithasstarted)) {
                    $started = 1;
                } else {
                    if (!empty(self::$config->tellithasstartedsql)) {
                        // The configured sql query to tell the moodle instance has really started should
                        // return a single "started" field.
                        $state = $DB->get_record_sql(self::$config->tellithasstartedsql, []);
                        $started = $state->started;
                    }
                }
                $this->value->$submode = $started;
                break;
            }

            case 'cronstate': {
                // $lastcron = get_config('tool_task', 'lastcronstart');
                $lastcron = $DB->get_field('config_plugins', 'value', ['plugin' => 'report_zabbix', 'name' => 'lastcron']);
                if (empty(self::$config->zabbixallowedcronperiod)) {
                    self::$config->zabbixallowedcronperiod = 60;
                }
                $latecronhorizon = time() - 60 * self::$config->zabbixallowedcronperiod;
                $cronoverdue = $lastcron < $latecronhorizon;
                $this->value->$submode = ($cronoverdue) ? 1 : 0;
                break;
            }

            case 'cronfailed': {
                $select = " faildelay > 0 ";
                $failed = $DB->count_records_select('task_scheduled', $select, []);
                $this->value->$submode = 0 + $failed;
                break;
            }

            case 'backupsinprogress': {
                // Just ocunt the /temp/backup dirs
                $backupentries = glob($CFG->tempdir.'/backup/*');
                $currentbackups = 0;
                foreach ($backupentries as $backupentry) {
                    if (is_dir($backupentry)) {
                        $currentbackups++;
                    }
                }
                $this->value->$submode = $currentbackups;
                break;
            }
        }
    }
}