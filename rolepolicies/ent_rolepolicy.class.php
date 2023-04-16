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
 * This file contains a role policy method for zabbix indicators.
 *
 * @package     report_zabbix
 * @category    report
 * @author      Valery Fremaux <valery.fremaux@gmail.com
 * @copyright   (C) 2022  Valery Fremaux (http://www.activeprolearn.com)
 * @licence     http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 */
namespace report_zabbix\rolepolicies;

require_once($CFG->dirroot.'/report/zabbix/rolepolicies/rolepolicy_base.class.php');

defined('MOODLE_INTERNAL') || die();

class ent_rolepolicy extends rolepolicy_base {

    static $policyfields;

    public function __construct() {
        global $DB;

        if (!isset(self::$policyfields)) {
            self::$policyfields['student'] = $DB->get_record('user_info_field', ['shortname' => 'eleve']);
            self::$policyfields['teacher'] = $DB->get_record('user_info_field', ['shortname' => 'enseignant']);
            self::$policyfields['staff'] = $DB->get_record('user_info_field', ['shortname' => 'administration']);

            // Special implementation
            self::$policyfields['cdt'] = $DB->get_record('user_info_field', ['shortname' => 'cdt']);
        }
    }

    /**
     * Matches a single user against a role policy.
     * @param object or id $userorid
     * @param string the role policy archetype
     * @return bool
     */
    public function match_policy($userorid, $archetype) {
        global $DB;

        if (is_object($userorid)) {
            $userid = $userorid->id;
        } else {
            $userid = $userorid;
        }

        switch ($archetype) {
            case 'student': {
                if ($DB->record_exists('user_info_data', ['userid' => $userid, 'fieldid' => self::$policyfields['student']->id])) {
                    return true;
                }
                break;
            }

            case 'teacher': {
                if ($DB->record_exists('user_info_data', ['userid' => $userid, 'fieldid' => self::$policyfields['teacher']->id])
                        || $DB->record_exists('user_info_data', ['userid' => $userid, 'fieldid' => self::$policyfields['cdt']->id])) {
                    return true;
                }
                break;
            }

            case 'administration': {
                if ($DB->record_exists('user_info_data', ['userid' => $userid, 'fieldid' => self::$policyfields['staff']->id])) {
                    return true;
                }
                break;
            }
        }

        return false;
    }

    /**
     * Counts users against the ent policy, using user status profile
     * fields.
     * @param string $archetype the main role archetype.
     * @return int
     */
    public function count_users($archetype) {
        global $DB, $CFG;

        switch ($archetype) {
            case 'students': {
                $fieldids = self::$policyfields['student']->id;
                break;
            }

            case 'teachers': {
                $fieldids = [self::$policyfields['teacher']->id, self::$policyfields['cdt']->id];
                break;
            }

            case 'staff': {
                $fieldids = [self::$policyfields['staff']->id];
                break;
            }
        }

        list($insql, $inparams) = $DB->get_in_or_equal($fieldids);

        $inparams[] = $CFG->mnet_localhost_id;

        $sql = "
            SELECT
                COUNT(DISTINCT uid.userid)
            FROM
                {user_info_data} uid,
                {user} u
            WHERE
                uid.userid = u.id AND
                u.deleted = 0 AND
                u.suspended = 0 AND
                uid.fieldid $insql AND
                u.mnethostid = ?
        ";
        $counter = $DB->count_records_sql($sql, $inparams);
        return 0 + $counter;
    }

}
