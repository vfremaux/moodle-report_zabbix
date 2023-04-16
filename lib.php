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
 * This file contains functions used by the trainingsessions report
 *
 * @package    report_zabbix
 * @category   report
 * @copyright  2012 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

define('REPORT_ZABBIX_RATE_INSTANT', 0);
define('REPORT_ZABBIX_RATE_HOURLY', 1);
define('REPORT_ZABBIX_RATE_DAILY', 2);
define('REPORT_ZABBIX_RATE_WEEKLY', 3);
define('REPORT_ZABBIX_RATE_MONTHLY', 4);

define('CONTEXT_COHORT', 25);

/**
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 */
function report_zabbix_supports_feature($feature = null, $getsupported = false) {
    global $CFG;
    static $supports;

    if (!during_initial_install()) {
        $config = get_config('report_zabbix');
    }

    if (!isset($supports)) {
        $supports = array(
            'pro' => array(
                'extension' => array('plugins', 'directsend'),
                'discovery' => array('topcategories', 'benchtests', 'coursesofinterest', 'authmethods')
            ),
            'community' => array(
            ),
        );
    }

    if ($getsupported) {
        return $supports;
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    if (empty($feature)) {
        // Just return version.
        return $versionkey;
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    if (array_key_exists($feat, $supports['community'])) {
        if (in_array($subfeat, $supports['community'][$feat])) {
            // If community exists, default path points community code.
            if (isset($prefer[$feat][$subfeat])) {
                // Configuration tells which location to prefer if explicit.
                $versionkey = $prefer[$feat][$subfeat];
            } else {
                $versionkey = 'community';
            }
        }
    }

    return $versionkey;
}

/**
 * Seeks for any installed rated emission indicator.
 * indicators are explicitely designed indicators in its 
 * $freq indexed subdirectory. If freq is null, takes the top level indicator classes.
 * Some extra plugins may be registered for sendig also zabbix indicators. Those will
 * be registered into the mdl_report_zabbix_plugins table.
 * @param string $freq the emission frequency
 */
function report_zabbix_load_indicators($freq = null) {
    global $CFG;
    static $indicatorobjects;

    if (!empty($indicatorobjects[$freq])) {
        return $indicatorobjects[$freq];
    }

    $rootpath = '/report/zabbix';

    // Get the central indicators.
    if (is_null($freq)) {
        $indicators = glob($CFG->dirroot.$rootpath.'/classes/indicators/*');
    } else {
        $indicators = glob($CFG->dirroot.$rootpath.'/classes/indicators/'.$freq.'/*');
    }

    if (report_zabbix_supports_feature('extension/plugins')) {
        include_once($CFG->dirroot.'/report/zabbix/pro/lib.php');
        report_zabbix_load_indicator_extensions($indicators, $freq);
    }

    $objects = [];

    if (!empty($indicators)) {
        foreach ($indicators as $indicatorpath) {
            if (is_dir($indicatorpath)) {
                continue;
            }
            include_once($indicatorpath);
            $classname = '\\report_zabbix\\indicators\\'.basename($indicatorpath, '.class.php');
            $objects[] = new $classname();
        }
    }

    // Put in static cache.
    $indicatorobjects[$freq] = $objects;

    return $objects;
}

/**
 * Determines if user is of some archetype.
 * @param object $user the tested user
 * @param string $archetype the role archetype to test.
 * @return bool
 */
function report_zabbix_role_policy($userorid, $archetype) {
    global $CFG;
    static $policy;

    if (!is_object($policy)) {
        $config = get_config('report_zabbix');

        include_once($CFG->dirroot.'/report/zabbix/rolepolicies/'.$config->userrolepolicy.'_rolepolicy.class.php');
        $classname = '\\report_zabbix\\rolepolicies\\'.$config->userrolepolicy.'_rolepolicy';
        $policy = new $classname();
    }

    return $policy->match_policy($userorid, $archetype);
}

function report_zabbix_count_policy_users($archetype) {
    global $CFG;
    static $policy;

    if (!is_object($policy)) {
        $config = get_config('report_zabbix');

        include_once($CFG->dirroot.'/report/zabbix/rolepolicies/'.$config->userrolepolicy.'_rolepolicy.class.php');
        $classname = '\\report_zabbix\\rolepolicies\\'.$config->userrolepolicy.'_rolepolicy';
        $policy = new $classname();
    }

    return $policy->count_users($archetype);
}

function zabbix_trap_upload() {
    global $DB;

    $current = $DB->get_record('report_zabbix', ['name' => 'moodle.general.uploadsinprogress']);
    if (!$current) {
        $current = new StdClass;
        $current->name = 'moodle.general.uploadsinprogress';
        $current->value = 1;
        $DB->insert_record('report_zabbix', $current);
    } else {
        $current->value += 1;
        $DB->update_record('report_zabbix', $current);
    }
}

function zabbix_untrap_upload() {
    global $DB;

    $current = $DB->get_record('report_zabbix', ['name' => 'moodle.general.uploadsinprogress']);
    if ($current) {
        $current->value -= 1;
        $DB->update_record('report_zabbix', $current);
    }
}

/**
 * Converts rate code into readable value.
 * @param int $arate
 */
function report_zabbix_rate($arate, $ratestring = false) {
    switch ($arate) {
        case REPORT_ZABBIX_RATE_INSTANT: {
            if ($ratestring) {
                return 'instant';
            } else {
                return get_string('instant_task', 'report_zabbix');
            }
        }
        case REPORT_ZABBIX_RATE_HOURLY: {
            if ($ratestring) {
                return 'hourly';
            } else {
                return get_string('hourly_task', 'report_zabbix');
            }
        }
        case REPORT_ZABBIX_RATE_DAILY: {
            if ($ratestring) {
                return 'daily';
            } else {
                return get_string('daily_task', 'report_zabbix');
            }
        }
        case REPORT_ZABBIX_RATE_WEEKLY: {
            if ($ratestring) {
                return 'weekly';
            } else {
                return get_string('weekly_task', 'report_zabbix');
            }
        }
        case REPORT_ZABBIX_RATE_MONTHLY: {
            if ($ratestring) {
                return 'monthly';
            } else {
                return get_string('monthly_task', 'report_zabbix');
            }
        }
        default:
            throw new coding_exception("This is a coding error. Rate should always be in expected values");
    }
}

/**
 * all available rates.
 * @param int $arate
 */
function report_zabbix_rates() {

    $rates = [];
    $rates[REPORT_ZABBIX_RATE_INSTANT] = get_string('instant_task', 'report_zabbix');
    $rates[REPORT_ZABBIX_RATE_HOURLY] = get_string('hourly_task', 'report_zabbix');
    $rates[REPORT_ZABBIX_RATE_DAILY] = get_string('daily_task', 'report_zabbix');
    $rates[REPORT_ZABBIX_RATE_WEEKLY] = get_string('weekly_task', 'report_zabbix');
    $rates[REPORT_ZABBIX_RATE_MONTHLY] = get_string('monthly_task', 'report_zabbix');

    return $rates;
}

/**
 * Converts context code into readable value.
 * @param int $acontext
 */
function report_zabbix_context($acontext) {
    switch ($acontext) {
        case CONTEXT_SYSTEM: {
            return get_string('contextsystem', 'report_zabbix');
        }
        case CONTEXT_COURSECAT: {
            return get_string('contextcoursecategory', 'report_zabbix');
        }
        case CONTEXT_COURSE: {
            return get_string('contextcourse', 'report_zabbix');
        }
        case CONTEXT_MODULE: {
            return get_string('contextcoursemodule', 'report_zabbix');
        }
        case CONTEXT_USER: {
            return get_string('contextuser', 'report_zabbix');
        }
        case CONTEXT_COHORT: {
            return get_string('contextcohort', 'report_zabbix');
        }
        default:
            throw new coding_exception("This is a coding error. Context should always be in expected values");
    }
}

function report_zabbix_contexts() {

       $contexts = [];

        $contexts[CONTEXT_SYSTEM] = get_string('contextsystem', 'report_zabbix');
        $contexts[CONTEXT_COURSECAT] = get_string('contextcoursecat', 'report_zabbix');
        $contexts[CONTEXT_COURSE] = get_string('contextcourse', 'report_zabbix');
        $contexts[CONTEXT_MODULE] = get_string('contextmodule', 'report_zabbix');
        $contexts[CONTEXT_USER] = get_string('contextuser', 'report_zabbix');
        $contexts[CONTEXT_COHORT] = get_string('contextcohort', 'report_zabbix');

        return $contexts;
}
