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

class daily_assign_indicator extends zabbix_indicator {

    static $submodes = 'dailysubmissions,activeassigns';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.assign';
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

            case 'dailysubmissions': {
                $sql = "
                    SELECT
                        COUNT(*)
                    FROM
                        {assign_submission} asu
                    WHERE
                        asu.timecreated > ? AND
                        asu.status = 'submitted'
                ";
                $activityhorizon = time() - DAYSECS;
                $this->value->$submode = $DB->count_records_sql($sql, [$activityhorizon]);
                break;
            }

            case 'activeassigns': {

                /**
                 * Assigns that are visible to users, independantely of local access rules.
                 */
                $sql = "
                    SELECT
                        COUNT(*)
                    FROM
                        {assign} a,
                        {course_modules} cm,
                        {modules} m,
                        {course} c,
                        {course_categories} cc1
                    LEFT JOIN
                        {course_categories} cc2
                    ON
                        cc1.parent = cc2.id
                    WHERE
                        c.visible = 1 AND
                        cm.visible > 0 AND
                        (cm.deletioninprogress IS NULL OR cm.deletioninprogress = 0) AND
                        a.id = cm.instance AND
                        cm.module = m.id AND
                        m.name = 'assign' AND
                        cm.course = c.id AND
                        c.category = cc1.id AND
                        c.startdate < ? AND
                        (c.enddate = 0 OR c.enddate > ?) AND
                        cc1.visible = 1 AND
                        (cc2.id IS NULL OR cc2.visible = 1) AND
                        a.allowsubmissionsfromdate < ? AND
                        (a.duedate = 0 OR a.duedate > ?)
                ";

                $timeupperhorizon = time();
                $timelowerhorizon = time() - DAYSECS;
                $params = [$timeupperhorizon,$timelowerhorizon,$timeupperhorizon,$timelowerhorizon];
                $assigns = $DB->count_records_sql($sql, $params);

                $this->value->$submode = $assigns;
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