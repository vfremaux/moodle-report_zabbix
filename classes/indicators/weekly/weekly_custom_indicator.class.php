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

require_once($CFG->dirroot.'/report/zabbix/classes/custom_indicator.class.php');

class weekly_custom_indicator extends custom_zabbix_indicator {

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.custom';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        if (is_null($this->measurements)) {
            // Load submodes and measurement once for this rate.
            $this->measurements = $this->load_measurements(REPORT_ZABBIX_RATE_WEEKLY);
            $this->customsubmodes = $this->get_custom_submodes($this->measurements);
        }

        return array_keys($this->customsubmodes);
    }
}