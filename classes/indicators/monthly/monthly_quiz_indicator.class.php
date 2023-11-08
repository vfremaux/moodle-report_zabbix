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

class monthly_quiz_indicator extends zabbix_indicator {

    static $submodes = 'monthlysuccessratio,monthlyquizinstancesall';

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

        // Monthlys says the past full month calendar period preceding the moment it is
        //processed..
        $today = getdate(time());
        $monthstart = mktime(0, 0, 0, $today['mon'] -1, 1, $today['year']);
        $monthend = mktime(0, 0, 0, $today['mon'], 1, $today['year']);

        switch ($submode) {

            case 'monthlysuccessratio': {
                $goodones = $DB->count_records_select('quiz_attempts', "timefinish >= ? AND timefinish < ? AND state = 'graderight'", [$monthstart, $monthend]);
                $badones = $DB->count_records_select('quiz_attempts', "timefinish >= ? AND timefinish < ? AND state = 'gradewrong'", [$monthstart, $monthend]);
                if (!$badones) {
                    $this->value->$submode = 0;
                } else {
                    $this->value->$submode = sprintf('%.2f', $goodones / $goodones + $badones);
                }
                break;
            }

            case 'monthlyquizinstancesall': {
                // Count real instancces that are not in deletion.
                $sql = "
                    SELECT
                        COUNT(*)
                    FROM
                        {quiz} q,
                        {modules} m,
                        {course_modules} cm
                    WHERE
                        (cm.deletioninprogress IS NULL OR cm.deletioninprogress = 0) AND
                        q.id = cm.instance AND
                        cm.module = m.id AND
                        m.name = 'quiz'
                ";
                $quizzes = $DB->count_records_sql($sql, []);
                $this->value->$submode = $quizzes;
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