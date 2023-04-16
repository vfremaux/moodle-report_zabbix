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

class daily_courses_indicator extends zabbix_indicator {

    static $submodes = '<courseid>requests,<courseid>distinctusers,<courseid>enrolled,<courseid>completed';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.course';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        global $CFG;

        if (!report_zabbix_supports_feature('discovery/coursesofinterest')) {
            return [];
        }

        $localpromanager = new \report_zabbix\local_pro_manager();
        $courses = $localpromanager->get_courses_of_interest();
        if (empty($courses)) {
            return []; // no courses to scan.
        }

        // Get for next steps.
        include_once($CFG->dirroot.'/report/zabbix/pro/localprolib.php');
        return explode(',', self::$submodes);
    }

    /**
     * Checks for existance of subsubmodes and generates a bucket for each.
     * @param string $submodekey the submode key radical, without any variable subpattern (<>)
     * @return an array of subsumodes for this submodekey
     */
    protected function get_sub_submodes($submodekey) {
        global $DB;

        $localpromanager = new \report_zabbix\local_pro_manager();
        $courses = $localpromanager->get_courses_of_interest();

        // Implement a variable addition with top category submodes (Zabbix Discovery)

        // Element prototype is : moodle.course.[{#COURSEID}.<subsubmode>]
        $subsubmodes = [];
        foreach ($courses as $c) {
            $subsubmodes[$c->id] = '['.$c->id.'.'.$submodekey.']';
        }

        return $subsubmodes;
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

        $localpromanager = new \report_zabbix\local_pro_manager();
        $courses = $localpromanager->get_courses_of_interest();
        $horizon = time() - DAYSECS;

        // We need scan logs for those courses.
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($courses));
        $dailyinparams = $inparams;
        $dailyinparams[] = $horizon;

        switch ($submode) {
            case '<courseid>requests' : {

                $coursebuckets = $this->get_sub_submodes('requests');

                $sql = "
                    SELECT
                        c.id as courseid,
                        COUNT(*) as rq
                    FROM
                        {logstore_standard_log} l,
                        {course} c
                    WHERE
                        l.courseid = c.id AND
                        c.id $insql AND
                        l.timecreated > ?
                    GROUP BY
                        c.id
                ";

                $courserequests = $DB->get_records_sql($sql, $dailyinparams);

                foreach ($coursebuckets as $courseid => $subsubmode) {
                    $this->value->$subsubmode = 0 + @$courserequests[$courseid]->rq;
                }
                break;
            }

            case '<courseid>distinctusers' : {

                $coursebuckets = $this->get_sub_submodes('distinctusers');

                $courseusers = [];
                $sql = "
                    SELECT
                        c.id as courseid,
                        COUNT(DISTINCT l.userid) as du
                    FROM
                        {logstore_standard_log} l,
                        {course} c
                    WHERE
                        l.courseid = c.id AND
                        c.id $insql AND
                        l.timecreated > ?
                    GROUP BY
                        c.id
                ";

                $courseusers = $DB->get_records_sql($sql, $dailyinparams);

                foreach ($coursebuckets as $courseid => $subsubmode) {
                    $this->value->$subsubmode = 0 + @$courseusers[$courseid]->du;
                }
                break;
            }

            case '<courseid>enrolled' : {

                $coursebuckets = $this->get_sub_submodes('enrolled');

                $sql = "
                    SELECT
                        c.id as courseid,
                        COUNT(DISTINCT ue.userid) as enr
                    FROM
                        {user_enrolments} ue,
                        {enrol} e,
                        {course} c
                    WHERE
                        ue.enrolid = e.id AND
                        ue.status = 0 AND
                        e.courseid = c.id AND
                        c.id $insql AND
                        e.status = 0
                    GROUP BY
                        c.id
                ";

                $courseenrolled = $DB->get_records_sql($sql, $inparams);

                foreach ($coursebuckets as $courseid => $subsubmode) {
                    $this->value->$subsubmode = 0 + @$courseenrolled[$courseid]->enr;
                }
                break;
            }

            case '<courseid>completed' : {

                $coursebuckets = $this->get_sub_submodes('completed');

                $sql = "
                    SELECT
                        cc.course as courseid,
                        COUNT(*) as comp
                    FROM
                        {course_completions} cc
                    WHERE
                        cc.course $insql AND
                        cc.timecompleted > 0
                    GROUP BY
                        cc.course
                ";

                $courseecompletions = $DB->get_records_sql($sql, $inparams);

                foreach ($coursebuckets as $courseid => $subsubmode) {
                    $this->value->$subsubmode = 0 + @$courseecompletions[$courseid]->comp;
                }
                break;
            }
        }
    }
}