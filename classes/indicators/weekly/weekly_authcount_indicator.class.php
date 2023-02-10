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

class weekly_authcount_indicator extends zabbix_indicator {

    static $submodes = '';

    public function __construct() {
        global $CFG;

        parent::__construct();
        $this->key = 'moodle.auth';

        if (empty(self::$submodes)) {
            $auths = ['manual','ldap','cas'];
            $activeauths = explode(',', $CFG->auth);
            foreach ($activeauths as $aauth) {
                // Add non standard or additional auth methods.
                if (!in_array($aauth, $auths)) {
                    if (strpos($aauth, 'cas') === 0) {
                        $aauth = 'cas'; // federates any cas alternative method to CAS.
                    }
                    $auths[] = $aauth;
                }
            }

            $submodes = [];
            foreach ($auths as $auth) {
                $submodes[] = 'weekly'.$auth.'users';
            }
            self::$submodes = implode(',', $submodes);
        }
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

        $auth = str_replace('weekly', '', str_replace('users', '', $submode));

        // Simplest way : TODO : add category visibility information.
        $authusers = $DB->count_records('user', ['auth' => $auth, 'deleted' => 0, 'suspended' => 0]);
        $this->value->$submode = $authusers;
    }
}