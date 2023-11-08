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

class daily_coursecount_indicator extends zabbix_indicator {

    static $submodes = 'dailycreated,dailyvisited';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.courses';
        $this->datatype = 'numeric';
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
        global $DB;

        if (!isset($this->value)) {
            $this->value = new StdClass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        switch ($submode) {

            case 'dailyvisited': {
                $sql = "
                    SELECT 
                        COUNT(DISTINCT courseid)
                    FROM
                        {user_lastaccess}
                    WHERE
                        timeaccess  > ?
                ";

                $activityhorizon = time() - DAYSECS;

                $accessed = $DB->count_records_sql($sql, [$activityhorizon]);
                $this->value->$submode = $accessed;
                break;
            }

            case 'dailyinactive': {
                // Simplest way : TODO : add category visibility information.
                $allcourses = $DB->count_records('course', []);
                $sql = "
                    SELECT 
                        COUNT(DISTINCT courseid)
                    FROM
                        {user_lastaccess}
                    WHERE
                        timeaccess  > ?
                ";

                $activityhorizon = time() - DAYSECS;

                $accessed = $DB->count_records_sql($sql, [$activityhorizon]);
                $this->value->$submode = $allcourses - $accessed;
                break;
            }

            case 'dailycreated': {
                $allcourses = $DB->count_records('course', []);
                $sql = "
                    SELECT 
                        COUNT(DISTINCT c.id)
                    FROM
                        {course} c
                    WHERE
                        timecreated  > ?
                ";

                $activityhorizon = time() - DAYSECS;

                $this->value->$submode = 0 + $DB->count_records_sql($sql, [$activityhorizon]);
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