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

class coursecount_indicator extends zabbix_indicator {

    static $submodes = 'all,visible,nonvisible,nostudents,noteachers,noaccessed,nostudentslist,noteacherslist';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.courses';
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

            case 'all': {
                $this->value->$submode = $DB->count_records('course', []);
                break;
            }

            case 'visible': {
                // Simplest way : TODO : add category visibility information.
                $this->value->$submode = $DB->count_records('course', ['visible' => 1]);
                break;
            }

            case 'nonvisible': {
                // Simplest way : TODO : add category visibility information.
                $this->value->$submode = $DB->count_records('course', ['visible' => 0]);
                break;
            }

            case 'nostudents': {
                // Simplest way : Having no role assignments to a student role
                $sql = "
                    SELECT 
                        COUNT(DISTINCT c.id)
                    FROM
                        {course} c,
                        {context} ctx,
                        {role_assignments} ra,
                        {role} r
                    WHERE
                        ra.roleid = r.id AND
                        r.archetype = 'student' AND
                        ra.contextid = ctx.id AND
                        ctx.contextlevel = ".CONTEXT_COURSE." AND
                        c.id != ".SITEID." AND
                        c.id = ctx.instanceid
                ";
                $havestudentscourses = $DB->count_records_sql($sql);
                $allcourses = $DB->count_records('course');
                $this->value->$submode = $allcourses - $havestudentscourses;
                break;
            }

            case 'nostudentslist': {
                // Simplest way : Having no role assignments to a student role
                $sql = "
                    SELECT 
                        DISTINCT c.id
                    FROM
                        {course} c,
                        {context} ctx,
                        {role_assignments} ra,
                        {role} r
                    WHERE
                        ra.roleid = r.id AND
                        (r.archetype = 'student') AND
                        ra.contextid = ctx.id AND
                        ctx.contextlevel = ".CONTEXT_COURSE." AND
                        c.id != ".SITEID." AND
                        c.id = ctx.instanceid
                ";
                $havestudentscourses = $DB->get_records_sql($sql);
                $allcourses = $DB->get_records('course', [], 'id', 'id,id');
                $nostudentscourses = [];
                foreach (array_keys($allcourses) as $cid) {
                    if (!array_key_exists($cid, $havestudentscourses)) {
                        if ($cid != SITEID) {
                            $nostudentscourses[] = $cid;
                        }
                    }
                }
                $this->value->$submode = implode(',', $nostudentscourses);
                break;
            }

            case 'noteachers': {
                // Simplest way : Having no role assignments to a student role
                $sql = "
                    SELECT 
                        COUNT(DISTINCT c.id)
                    FROM
                        {course} c,
                        {context} ctx,
                        {role_assignments} ra,
                        {role} r
                    WHERE
                        ra.roleid = r.id AND
                        (r.archetype = 'teacher' OR r.archetype = 'editingteacher') AND
                        ra.contextid = ctx.id AND
                        ctx.contextlevel = ".CONTEXT_COURSE." AND
                        c.id != ".SITEID." AND
                        c.id = ctx.instanceid
                ";
                $havestudentscourses = $DB->count_records_sql($sql);
                $allcourses = $DB->count_records('course');
                $this->value->$submode = $allcourses - $havestudentscourses;
                break;
            }

            case 'noteacherslist': {
                // Simplest way : Having no role assignments to a student role
                $sql = "
                    SELECT 
                        DISTINCT c.id
                    FROM
                        {course} c,
                        {context} ctx,
                        {role_assignments} ra,
                        {role} r
                    WHERE
                        ra.roleid = r.id AND
                        (r.archetype = 'teacher' OR r.archetype = 'editingteacher') AND
                        ra.contextid = ctx.id AND
                        ctx.contextlevel = ".CONTEXT_COURSE." AND
                        c.id != ".SITEID." AND
                        c.id = ctx.instanceid
                ";
                $haveteacherscourses = $DB->get_records_sql($sql);
                $allcourses = $DB->get_records('course', [], 'id', 'id,id');
                $noteacherscourses = [];
                foreach (array_keys($allcourses) as $cid) {
                    if (!array_key_exists($cid, $haveteacherscourses)) {
                        if ($cid != SITEID) {
                            $noteacherscourses[] = $cid;
                        }
                    }
                }
                $this->value->$submode = implode(',', $noteacherscourses);
                break;
            }

            case 'noaccessed': {
                // Simplest way : as no time accessed by any user from one year
                $horizon = DAYSECS * 365;
                $sql = "
                    SELECT
                        COUNT(*)
                    FROM
                        ( SELECT
                            c.id,
                            MAX(timeaccess) maxtime
                          FROM
                            {course} c,
                            {user_lastaccess} ula
                          WHERE
                            ula.courseid = c.id AND
                            c.id != ".SITEID."
                          HAVING
                            maxtime < ?
                       ) as SubReq
                ";
                $this->value->$submode = 0 + $DB->count_records_sql($sql, [time() - $horizon]);
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