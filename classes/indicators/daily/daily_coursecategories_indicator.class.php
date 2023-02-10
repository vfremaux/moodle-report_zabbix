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

class daily_coursecategories_indicator extends zabbix_indicator {

    static $submodes = '<catid>requests,<catid>distinctusers';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.topcategory';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        global $CFG;

        if (!report_zabbix_supports_feature('discovery/topcategories')) {
            return [];
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
        list($topcategories, $categories) = $localpromanager->get_topcategories();

        if ($submodekey == 'requests') {
            // Implement a variable addition with top category submodes (Zabbix Discovery)

            // Element prototype is : moodle.topcategory.[{#CATID}.requests]
            $subsubmodes = [];
            foreach ($categories as $cat) {
                $subsubmodes[$cat->id] = '['.$cat->id.'.requests]';
            }

            return $subsubmodes;
        }

        if ($submodekey == 'distinctusers') {
            // Implement a variable addition with top category submodes (Zabbix Discovery)

            // Element prototype is : moodle.topcategory.[{#CATID}.distinctusers]
            $subsubmodes = [];
            foreach ($categories as $cat) {
                $subsubmodes[$cat->id] = '['.$cat->id.'.distinctusers]';
            }

            return $subsubmodes;
        }
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
        list($topcategories, $categories) = $localpromanager->get_topcategories();

        switch ($submode) {
            case '<catid>requests' : {

                $horizon = time() - DAYSECS;
                $catbuckets = $this->get_sub_submodes('requests');

                if (is_null($topcategories)) {
                    $sql = "
                        SELECT 
                            REGEXP_SUBSTR(cc.path, '[0-9]+') as catid,
                            COUNT(*) as rq
                        FROM
                            {logstore_standard_log} l,
                            {course} c,
                            {course_categories} cc
                        WHERE
                            l.courseid = c.id AND
                            c.category = cc.id AND
                            l.timecreated > ?
                        GROUP BY
                            REGEXP_SUBSTR(cc.path, '[0-9]+')
                    ";

                    $catrequests = $DB->get_records_sql($sql, [$horizon]);
                } else {
                    // We need scan for each root cat.
                    $catrequests = [];
                    foreach ($topcategories as $topcat) {
                        $sql = "
                            SELECT
                                REGEXP_SUBSTR(REGEXP_REPLACE(cc.path, '^{$topcat->path}/', ''), '[0-9]+') as catid,
                                COUNT(*) as rq
                            FROM
                                {logstore_standard_log} l,
                                {course} c,
                                {course_categories} cc
                            WHERE
                                l.courseid = c.id AND
                                c.category = cc.id AND
                                cc.path LIKE ? AND
                                l.timecreated > ?
                            GROUP BY
                                REGEXP_SUBSTR(REGEXP_REPLACE(cc.path, '^{$topcat->path}/', ''), '[0-9]+')
                        ";

                        $catrequests = $catrequests + $DB->get_records_sql($sql, [$topcat->path.'/%', $horizon]);
                    }
                }

                foreach ($catbuckets as $catid => $subsubmode) {
                    $this->value->$subsubmode = 0 + @$catrequests[$catid]->rq;
                }
                break;
            }

            case '<catid>distinctusers' : {

                $horizon = time() - DAYSECS;
                $catbuckets = $this->get_sub_submodes('distinctusers');

                if (is_null($topcategories)) {
                    $sql = "
                        SELECT
                            REGEXP_SUBSTR(cc.path, '[0-9]+') as catid,
                            COUNT(DISTINCT l.userid) as du
                        FROM
                            {logstore_standard_log} l,
                            {course} c,
                            {course_categories} cc
                        WHERE
                            l.courseid = c.id AND
                            c.category = cc.id AND
                            l.timecreated > ?
                        GROUP BY
                            REGEXP_SUBSTR(cc.path, '[0-9]+')
                    ";

                    $catusers = $DB->get_records_sql($sql, [$horizon]);
                } else {
                    // We need scan for each root cat.
                    $catusers = [];
                    foreach ($topcategories as $topcat) {
                        $sql = "
                            SELECT
                                REGEXP_SUBSTR(REGEXP_REPLACE(cc.path, '^{$topcat->path}/', ''), '[0-9]+') as catid,
                                COUNT(DISTINCT l.userid) as du
                            FROM
                                {logstore_standard_log} l,
                                {course} c,
                                {course_categories} cc
                            WHERE
                                l.courseid = c.id AND
                                c.category = cc.id AND
                                cc.path LIKE ? AND
                                l.timecreated > ?
                            GROUP BY
                                REGEXP_SUBSTR(REGEXP_REPLACE(cc.path, '^{$topcat->path}/', ''), '[0-9]+')
                        ";

                        $catusers = $catusers + $DB->get_records_sql($sql, [$topcat->path.'/%', $horizon]);
                    }
                }

                foreach ($catbuckets as $catid => $subsubmode) {
                    $this->value->$subsubmode = 0 + @$catusers[$catid]->du;
                }
                break;
            }
        }
    }
}