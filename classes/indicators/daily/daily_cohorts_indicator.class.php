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

class daily_cohorts_indicator extends zabbix_indicator {

    static $submodes = 'cohorts,empty,unenroled,system';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.cohort';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        global $CFG;

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

        $horizon = time() - DAYSECS;

        // We need scan logs for those courses.
        switch ($submode) {
            case 'cohorts' : {

                $this->value->$submode = 0 + $DB->count_records('cohort', []);
                break;
            }

            case 'empty' : {

                $sql = "
                    SELECT
                        COUNT(c.id) as ecc
                    FROM
                        {cohort} c
                    LEFT JOIN
                        {cohort_members} cm
                    ON
                        cm.cohortid = c.id
                    WHERE
                        cm.cohortid IS NULL
                ";

                $result = $DB->get_record_sql($sql, []);
                $this->value->$submode = 0 + $result->ecc;
                break;
            }

            case 'unenroled' : {

                $sql = "
                    SELECT
                        COUNT(c.id) as uec
                    FROM
                        {cohort} c
                    LEFT JOIN
                        {enrol} e
                    ON
                        e.enrol = 'cohort' AND
                        e.customint1 = c.id
                    WHERE
                        e.customint1 IS NULL
                ";

                $result = $DB->get_record_sql($sql, []);
                $this->value->$submode = 0 + $result->uec;
                break;
            }

            case 'system' : {

                $this->value->$submode = 0 + @$DB->count_records('cohort', ['contextid' => 1]);
                break;
            }
        }
    }
}