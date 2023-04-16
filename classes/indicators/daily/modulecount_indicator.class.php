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

class modulecount_indicator extends zabbix_indicator {

    static $submodes = 'all,assign.all,forum.all,hvp.all,quiz.all,resource.all,mplayer.all';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.modules';
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

            case 'all': {
                // Avoid those being deleted or those stealth
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules}
                    WHERE
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
                break;
            }

            case 'assign.all': {
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules} cm,
                        {modules} m
                    WHERE
                        m.id = cm.module AND
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1 AND
                        m.name = 'assign'
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
                break;
            }

            case 'forum.all': {
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules} cm,
                        {modules} m
                    WHERE
                        m.id = cm.module AND
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1 AND
                        m.name = 'forum'
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
                break;
            }

            case 'quiz.all': {
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules} cm,
                        {modules} m
                    WHERE
                        m.id = cm.module AND
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1 AND
                        m.name = 'quiz'
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
                break;
            }

            case 'hvp.all': {
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules} cm,
                        {modules} m
                    WHERE
                        m.id = cm.module AND
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1 AND
                        m.name = 'hvp'
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
                break;
            }

            case 'resource.all': {
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules} cm,
                        {modules} m
                    WHERE
                        m.id = cm.module AND
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1 AND
                        (m.name = 'file' OR m.name = 'url')
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
                break;
            }

            case 'mplayer.all': {
                $sql = "
                    SELECT 
                        COUNT(*)
                    FROM
                        {course_modules} cm,
                        {modules} m
                    WHERE
                        m.id = cm.module AND
                        (deletioninprogress IS NULL or deletioninprogress = 0) AND
                        visibleoncoursepage = 1 AND
                        (m.name = 'mplayer')
                ";

                $modules = $DB->count_records_sql($sql);
                $this->value->$submode = $modules;
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