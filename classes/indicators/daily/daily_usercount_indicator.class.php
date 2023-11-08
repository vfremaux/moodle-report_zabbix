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

class daily_usercount_indicator extends zabbix_indicator {

    static $submodes = 'dailylogins,dailydistinctlogins,dailydistinctstudentlogins,dailydistinctteacherlogins,dailydistinctstafflogins';

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
        global $DB, $CFG;

        if(!isset($this->value)) {
            $this->value = new Stdclass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        switch ($submode) {

            case 'registered': {
                $params = ['deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id];
                $this->value->$submode = $DB->count_records('user', $params);
                break;
            }

            case 'alive': {
                $params = ['deleted' => 0, 'suspended' => 0, 'mnethostid' => $CFG->mnet_localhost_id];
                $this->value->$submode = $DB->count_records('user', $params);
                break;
            }

            case 'dailylogins': {
                $activityhorizon = time() - DAYSECS;
                // Counts number of users having lastlogin in current 24 hours.
                $this->value->$submode = $DB->count_records_select('logstore_standard_log', "timecreated > ? AND action = 'loggedin'", [$activityhorizon]);
                break;
            }

            case 'dailydistinctlogins': {
                $activityhorizon = time() - DAYSECS;
                // Counts number of users having lastlogin in current 24 hours.
                $this->value->$submode = $DB->count_records_select('user', 'lastlogin > ?', [$activityhorizon]);
                break;
            }

            case 'dailydistinctstudentlogins': {
                $activityhorizon = time() - DAYSECS;
                // Counts number of users having lastlogin in current 24 hours.
                $users = $DB->get_records_select('user', 'lastlogin > ?', [$activityhorizon], 'id,username');
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

            case 'dailydistinctteacherlogins': {
                $activityhorizon = time() - DAYSECS;
                // Counts number of users having lastlogin in current 24 hours.
                $users = $DB->get_records_select('user', 'lastlogin > ?', [$activityhorizon], 'id,username');
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

            case 'dailydistinctstafflogins': {
                $activityhorizon = time() - DAYSECS;
                // Counts number of users having lastlogin in current 24 hours.
                $count = 0;
                $users = $DB->get_records_select('user', 'lastlogin > ?', [$activityhorizon], 'id,username');
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

            default: {
                if ($CFG->debug == DEBUG_DEVELOPER) {
                    throw new coding_exception("Indicator has a submode that is not handled in aquire_submode().");
                }
            }
        }
    }
}