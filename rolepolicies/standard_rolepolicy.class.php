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

use context_system;

require_once($CFG->dirroot.'/report/zabbix/rolepolicies/rolepolicy_base.class.php');

defined('MOODLE_INTERNAL') || die();

class standard_rolepolicy extends rolepolicy_base {

    public function match_policy($userorid, $archetype) {
        global $DB;

        if (is_object($userorid)) {
            $userid = $userorid->id;
            $user = $userorid;
        } else {
            $userid = $userorid;
            $user = $DB->get_record('user', ['id' => $userorid]);
        }

        // Quick resolution for site admins.
        $systemcontext = context_system::instance();
        if (has_capability('moodle/site:config', $systemcontext, $user)) {
            if ($archetype == 'staff') {
               return true;
            } else {
               return false;
            }
        }

        // Search and count role assigments by archetype.
        // This may be a costfull request and other more optimized policies might
        // load the whole system a bit less.
        $sql = "
            SELECT
                r.archetype,
                COUNT(ra.id) as ras,
                ra.userid
            FROM
                {role} r
            LEFT JOIN
                {role_assignments} ra
            ON
                ra.roleid = r.id
            WHERE
                ra.userid = ?
            GROUP BY
                r.archetype
        ";
        $ras = $DB->get_records_sql($sql, [$userid]);

        switch ($archetype) {
            case 'staff': {
                if (array_key_exists('manager', $ras) && $ras['manager']->ras > 0) {
                    return true;
                }
                break;
            }

            case 'teacher': {
                if (array_key_exists('manager', $ras) && $ras['manager']->ras > 0) {
                    return false;
                }
                if ((array_key_exists('teacher', $ras) && $ras['teacher']->ras > 0) || (array_key_exists('editingteacher', $ras) && $ras['editingteacher']->ras > 0)) {
                    return true;
                }
                break;
            }

            case 'student': {
                if (array_key_exists('manager', $ras) && $ras['manager']->ras > 0) {
                    return false;
                }
                if ((array_key_exists('teacher', $ras) && $ras['teacher']->ras > 0) || (array_key_exists('editingteacher', $ras) && $ras['editingteacher']->ras > 0)) {
                    return false;
                }
                if (array_key_exists('student', $ras) && $ras['student']->ras > 0) {
                    return true;
                }
                break;
            }
        }

        return false;
    }

    /**
     * Counts users against the standard main role policy.
     */
    public function count_users($archetype) {
        global $DB, $CFG;

        switch($archetype) {
            case 'students': {
                list($insql, $inparams) = $DB->get_in_or_equal(['student']);
                break;
            }

            case 'teachers': {
                list($insql, $inparams) = $DB->get_in_or_equal(['teacher']);
                break;
            }

            case 'staff': {
                list($insql, $inparams) = $DB->get_in_or_equal(['manager']);
                break;
            }
        }

        $inparams[] = $CFG->mnet_localhost_id;

        $sql = "
            SELECT
                COUNT(DISTINCT ra.userid)
            FROM
                {role} r
            LEFT JOIN
                {role_assignments} ra
            ON
                ra.roleid = r.id
            LEFT JOIN
                {user} u
            ON
                ra.userid = u.id
            WHERE
                u.deleted = 0 AND
                u.suspended = 0 AND
                r.archetype $insql AND
                u.mnethostid = ?
        ";
        $counter = $DB->count_records_sql($sql, $inparams);
        return 0 + $counter;
    }

}
