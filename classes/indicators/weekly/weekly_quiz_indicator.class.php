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

class weekly_quiz_indicator extends zabbix_indicator {

    static $submodes = 'weeklyattempts,weeklyactivecourseswithattempts,weeklydistinctattemptingusers,weeklyaverageattemptduration,weeklyaveragequestionduration';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.quiz';
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

            case 'weeklyattempts': {
                $activityhorizon = time() - DAYSECS * 7;
                $queries = $DB->count_records_select('quiz_attempts', "timestart > ? AND state = 'finished'", [$activityhorizon]);
                $this->value->$submode = $queries / 60;
                break;
            }

            case 'weeklyactivecourseswithattempts': {
                $sql = "
                    SELECT
                        COUNT(DISTINCT q.course)
                    FROM
                        {quiz_attempts} qa,
                        {quiz} q
                    WHERE
                        qa.quiz = q.id AND
                        qa.state = 'finished' AND
                        qa.timestart > ?
                ";
                $activityhorizon = time() - DAYSECS * 7;
                $this->value->$submode = $DB->count_records_sql($sql, [$activityhorizon]);
                break;
            }

            case 'weeklydistinctattemptingusers': {
                $sql = "
                    SELECT
                        COUNT(DISTINCT qa.userid)
                    FROM
                        {quiz_attempts} qa
                    WHERE
                        qa.timestart > ? AND
                        state = 'finished'
                ";
                $activityhorizon = time() - DAYSECS * 7;
                $this->value->$submode = $DB->count_records_sql($sql, [$activityhorizon]);
                break;
            }

            case 'weeklyaverageattemptduration': {
                // Filtering out unresolved attempts that are closed lately.
                $sql = "
                    SELECT 
                        AVG(qa.timefinish - qa.timestart) as measurement
                    FROM
                        {quiz_attempts} qa
                    WHERE
                        qa.timestart > ? AND
                        qa.timefinish > 0 AND
                        state = 'finished' AND
                        qa.timefinish - qa.timestart < 3000
                ";
                $activityhorizon = time() - DAYSECS * 7;
                $avg = $DB->get_record_sql($sql, [$activityhorizon]);
                $this->value->$submode = 0 + $avg->measurement;
                break;
            }

            case 'weeklyaveragequestionduration': {
                // how to count the number of questions : 
                // Take the layout, and remove all ,0 patterns. this will
                // keep the non 0 question mappings. then remove 0, at start if any,
                // then remove all numeric chars and count the number of comas. 
                // the query eliminates unreal durations, quiz that may be finished by cron or
                // session closing.
                $sql = "
                    SELECT 
                        AVG((qa.timefinish - qa.timestart) /  CHARACTER_LENGTH(REGEXP_REPLACE(REPLACE(qa.layout, ',0', ''), '[0-9]+', ''))) as measurement
                    FROM
                        {quiz_attempts} qa
                    WHERE
                        qa.timestart > ? AND
                        qa.timefinish > 0 AND
                        state = 'finished' AND
                        qa.timefinish - qa.timestart < 3000
                ";
                $activityhorizon = time() - DAYSECS * 7;
                $avg = $DB->get_record_sql($sql, [$activityhorizon]);
                $this->value->$submode = 0 + $avg->measurement;
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