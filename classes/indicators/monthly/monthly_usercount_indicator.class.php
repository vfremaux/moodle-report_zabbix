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
use StdClass;

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');

class monthly_usercount_indicator extends zabbix_indicator {

    static $submodes = 'monthlyalive,monthlylogins,monthlydistinctlogins,monthlydistinctstudentlogins,monthlydistinctteacherlogins,monthlydistinctstafflogins';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.users';
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

            case 'monthlyalive': {
                $this->value->$submode = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
                break;
            }

            case 'monthlylogins': {
                // Counts number of users having lastlogin in current 24 hours.
                $select = "timecreated >= ? AND timecreated < ? AND action = 'loggedin'";
                $this->value->$submode = $DB->count_records_select('logstore_standard_log', $select, [$monthstart, $monthend]);
                break;
            }

            case 'monthlydistinctlogins': {
                // Counts number of users having lastlogin in current 24 hours.
                $select = 'lastlogin >= ? AND lastlogin < ?';
                $this->value->$submode = $DB->count_records_select('user', $select, [$monthstart, $monthend]);
                break;
            }

            case 'monthlydistinctstudentlogins': {
                // Counts number of users having lastlogin in current 24 hours.
                $select = 'lastlogin >= ? AND lastlogin < ?';
                $users = $DB->get_records_select('user', $select, [$monthstart, $monthend], 'id,username');
                $count = 0;
                foreach ($users as $user) {
                    // Ask for student againts the local role policy.
                    // Policy can be different depending on the implementation or environment.
                    if (report_zabbix_role_policy($user, 'student')) {
                        $count++;
                    }
                }
                $this->value->$submode = $count;
                break;
            }

            case 'monthlydistinctteacherlogins': {
                // Counts number of users having lastlogin in current 24 hours.
                $select = 'lastlogin >= ? AND lastlogin < ?';
                $users = $DB->get_records_select('user', $select, [$monthstart, $monthend], 'id,username');
                $count = 0;
                foreach ($users as $user) {
                    // Ask for teacher status againts the local role policy.
                    // Policy can be different depending on the implementation or environment.
                    if (report_zabbix_role_policy($user, 'teacher')) {
                        $count++;
                    }
                }
                $this->value->$submode = $count;
                break;
            }

            case 'monthlydistinctstafflogins': {
                // Counts number of users having lastlogin in current 24 hours.
                $count = 0;
                $select = 'lastlogin >= ? AND lastlogin < ?';
                $users = $DB->get_records_select('user', $select, [$monthstart, $monthend], 'id,username');
                foreach ($users as $user) {
                    // Ask for teacher status againts the local role policy.
                    // Policy can be different depending on the implementation or environment.
                    if (report_zabbix_role_policy($user, 'staff')) {
                        $count++;
                    }
                }
                $this->value->$submode = $count;
                break;
            }
        }
    }
}