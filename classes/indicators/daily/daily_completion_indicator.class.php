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

class daily_completion_indicator extends zabbix_indicator {

    static $submodes = 'dailymodulescompletions,dailydistinctmodulescompleted,dailycoursecompletions,dailyavgtimetocompletefromstart,dailyavgtimetocompletefromenrol';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.completion';
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

            case 'dailymodulescompletions': {

                $sql = "
                    SELECT
                        COUNT(*)
                    FROM
                        {course_modules_completion} ccm,
                        {course_modules} cm
                    WHERE
                        ccm.coursemoduleid = cm.id AND
                        (cm.deletioninprogress IS NULL OR cm.deletioninprogress = 0) AND
                        ccm.completionstate = 1 AND
                        ccm.timemodified >= ?
                ";

                $horizon = time() - DAYSECS;
                $modulescomp = 0 + $DB->count_records_sql($sql, [$horizon]);

                $this->value->$submode = $modulescomp;
                break;
            }

            case 'dailydistinctmodulescompleted': {
                $sql = "
                    SELECT
                        COUNT(DISTINCT(coursemoduleid))
                    FROM
                        {course_modules_completion} ccm,
                        {course_modules} cm
                    WHERE
                        ccm.coursemoduleid = cm.id AND
                        (cm.deletioninprogress IS NULL OR cm.deletioninprogress = 0) AND
                        ccm.completionstate = 1 AND
                        ccm.timemodified >= ?
                ";

                $horizon = time() - DAYSECS;
                $modulescomp = 0 + $DB->count_records_sql($sql, [$horizon]);

                $this->value->$submode = $modulescomp;
                break;
            }

            case 'dailycoursecompletions': {
                $horizon = time() - DAYSECS;
                $coursecompletions = $DB->count_records_select('course_completions', "timecompleted >= ?", [$horizon]);
                $this->value->$submode = $coursecompletions;
                break;
            }

            case 'dailyavgtimetocompletefromstart': {
                $horizon = time() - DAYSECS;
                $avgtime = 0 + $DB->get_field_select('course_completions', 'AVG(timecompleted - timestarted)', "timecompleted >= ?", [$horizon]);
                $this->value->$submode = $avgtime / DAYSECS;
                break;
            }

            case 'dailyavgtimetocompletefromenrol': {
                $horizon = time() - DAYSECS;
                $avgtime = 0 + $DB->get_field_select('course_completions', 'AVG(timecompleted - timeenrolled)', "timecompleted >= ?", [$horizon]);
                $this->value->$submode = $avgtime / DAYSECS;
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