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
 * @author Valery Fremaux valery@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package report_zabbix
 * @category report
 */
namespace report_zabbix\controllers;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

class measurements {

    protected $received;

    protected $data;

    public function receive($action, $data = null) {
        if (!is_null($data)) {
            $this->data = $data;
            $this->received = true;
            return;
        }

        switch($action) {
            case 'delete': {
                $this->data->deleteids = required_params_array('ids', PARAM_INT);
                $this->received = true;
            }
        }
    }

    public function process($action) {
        global $DB;

        if (!$this->received) {
            throw new coding_exception("Controller should have received data before processing");
        }

        switch ($action) {
            case 'delete': {
                $DB->delete_records_list('report_zabbix_custom', 'id', $this->data->deleteids);
                break;
            }

            case 'add': {
                $DB->insert_record('report_zabbix_custom', $this->data);
                return new moodle_url('/report/zabbix/measurements.php');
            }

            case 'update': {
                $DB->update_record('report_zabbix_custom', $this->data);
                return new moodle_url('/report/zabbix/measurements.php');
            }
        }
    }
}