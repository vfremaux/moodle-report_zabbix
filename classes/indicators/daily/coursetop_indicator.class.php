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

class coursetop_indicator extends zabbix_indicator {

    static $submodes = 'top3';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.courses';
        $this->datatype = 'text';
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

        if(!isset($this->value)) {
            $this->value = new Stdclass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        switch ($submode) {

            case 'top3': {
                $sql = "
                    SELECT DISTINCT
                        c.id,
                        c.shortname,
                        c.fullname,
                        COUNT(*) as logs
                    FROM
                        {logstore_standard_log} l,
                        {course} c
                    WHERE
                        c.id = l.courseid AND
                        l.origin = 'web' AND
                        l.realuserid IS NULL AND
                        l.timecreated > ? AND
                        l.courseid > 1
                    GROUP BY
                        l.courseid
                    ORDER BY
                        logs DESC
                    LIMIT 0, 3
                ";

                // Take  30 days of max backscann.
                $activityhorizon = time() - DAYSECS * 30;

                $topcourses = $DB->get_records_sql($sql, [$activityhorizon]);
                $topcoursesarr = [];
                foreach ($topcourses as $course) {
                    $topcoursesarr[] = "{$course->id}-{$course->shortname} {$course->fullname}";
                }
                $this->value->$submode = implode(', ', $topcoursesarr);
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