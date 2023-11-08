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

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');

use StdClass;

abstract class custom_zabbix_indicator extends zabbix_indicator {

    /**
     * An array of submodes got from custom measurement definitions.
     * Maps submode key to measurement id.
     */
    protected $customsubmodes = [];

    /**
     * An array of measurement definitions.
     */
    protected $measurements;

    public function load_measurements($ratecode) {
        global $DB;

        return $DB->get_records('report_zabbix_custom', ['rate' => $ratecode, 'active' => 1]);
    }

    public function get_custom_submodes(& $measurements, $returnnames = false) {
        global $DB;

        // A key/value pair association where key is "submode" and value is measurement index where to find sqlstatement.
        $submodes = [];

        foreach ($measurements as $meas) {

            $select = $this->compute_select($meas);

            if ($meas->context == CONTEXT_SYSTEM) {
                if ($returnnames) {
                    $submodes['['.$meas->shortname.'.'.$meas->units.']'] = $meas->name;
                } else {
                    $submodes['['.$meas->shortname.'.'.$meas->units.']'] = $meas->id;
                }
                continue;
            } else if ($meas->context == CONTEXT_COURSECAT) {
                $allcontextinstances = $DB->get_records_select('course_categories', $select, [], 'name', 'id,name');
                $submodekey = 'coursecat';
            } else if ($meas->context == CONTEXT_COURSE) {
                $allcontextinstances = $DB->get_records_select('course', $select, [], 'shortname', 'id, shortname as name');
                $submodekey = 'course';
            } else if ($meas->context == CONTEXT_MODULE) {
                $allcontextinstances = $DB->get_records_select('course_modules', $select, [], 'name', 'id, name');
                $submodekey = 'module';
            } else if ($meas->context == CONTEXT_USER) {
                $allcontextinstances = $DB->get_records_select('user', $select, [], 'name', 'id, username as name ');
                $submodekey = 'user';
            } else if ($meas->context == CONTEXT_COHORT) {
                $allcontextinstances = $DB->get_records_select('cohort', $select, [], 'name', 'id, name');
                $submodekey = 'cohort';
            }

            if (!empty($allcontextinstances)) {
                if ($returnnames) {
                    foreach ($allcontextinstances as $instance) {
                        $submodes['['.$submodekey.'.'.$instance->id.'.'.$meas->shortname.'.'.$meas->units.']'] = $meas->name.' in "'.$instance->name.'"';
                    }
                } else {
                    foreach (array_keys($allcontextinstances) as $instanceid) {
                        $submodes['['.$submodekey.'.'.$instanceid.'.'.$meas->shortname.'.'.$meas->units.']'] = $meas->id;
                    }
                }
            }
        }

        return $submodes;
    }

    /**
     * Compute filtering select clause from measurement settings.
     * @param object $meas the measurement
     * @return string a select statement.
     */
    public function compute_select($meas) {

        $ids = [];

        $allids = false;
        if ($meas->allow == '*') {
            $allids = true;
        } else if (!empty($meas->allow)) {
            $ids = preg_split('/[,\s]+/', $meas->allow);
        } else {
            // Empty start array.
            $ids = [];
        }

        if (!empty($meas->deny)) {
            $notids = preg_split('/[,\s]+/', $meas->deny);
        }

        if ($allids == false) {
            $outids = $ids;
            if (!empty($notids)) {
                $outids = array_diff($ids, $notids);
            }
            $select = " id IN (".implode(',', $outids).') ';
        } else if (!empty($notids)) {
            // Just negative in with not ids.
            $select = " id NOT IN (".implode(',', $notids).') ';
        } else {
            // Catch them all. Can cause performance issues...
            return ' 1 = 1 ';
        }

        return $select;
    }

    /**
     * the function that contains the logic to acquire the indicator instant value.
     * 2 cases here as custom measurements : 
     * - case 1 : System context measurement. SQL is run as is.
     * - case 2 : Other context measurement, SQL is run with contect instance id replacement in :instanceid
     * This is not much efficiant as one query per instance needs to be run. We will optimise next with groupby
     * capability.
     * @param string $submode to target an aquisition to an explicit submode, elsewhere 
     */
    public function acquire_submode($submode) {
        global $DB;

        if(!isset($this->value)) {
            $this->value = new Stdclass;
        }

        $associatedmeas = $this->measurements[$this->customsubmodes[$submode]];

        if ($associatedmeas->context == CONTEXT_SYSTEM) {
            $result = $DB->get_record_sql($associatedmeas->sqlstatement);
        } else {
            if (preg_match('/^course|coursecat|module|user|cohort\\.\\[(\\d+)\\./', $submode, $matched)) {
                $result = $DB->get_record_sql($associatedmeas->sqlstatement, ['instanceid' => $matched[1]]);
            }
        }

        if (isset($result->meas)) {
            $this->value->$submode = $result->meas;
        } else {
            $this->value->$submode = '';
        }
    }

}