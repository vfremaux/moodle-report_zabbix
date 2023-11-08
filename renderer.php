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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/tablelib.php');

class report_zabbix_renderer extends plugin_renderer_base {

    public function measurements($measurements) {

        $table = new html_table();
        $namestr = get_string('name', 'report_zabbix');
        $shortnamestr = get_string('shortname', 'report_zabbix');
        $ratestr = get_string('rate', 'report_zabbix');
        $contextstr = get_string('context', 'report_zabbix');
        $activestr = get_string('customactive', 'report_zabbix');
        $table->head = [$namestr, $shortnamestr, $ratestr, $contextstr, $activestr, ''];
        $table->width = '95%';
        $table->size = ['35%', '15%', '10%', '10%', '10%', '20%'];
        $table->align = ['left', 'left', 'left', 'left', 'center', 'right'];

        foreach ($measurements as $meas) {
            $row = [];
            $row[] = format_string($meas->name);
            $row[] = $meas->shortname;
            $row[] = report_zabbix_rate($meas->rate);
            $row[] = report_zabbix_context($meas->context);
            $row[] = ($meas->active) ? $this->output->pix_icon('t/ok', 'yes') : $this->output->pix_icon('t/nook', 'no');

            $cmd = '';
            $deleteurl = new moodle_url('/report/zabbix/measurements.php', ['ids[]' => $meas->id, 'sesskey' => sesskey()]);
            $cmd .= '<a href="'.$deleteurl.'">'.$this->output->pix_icon('t/delete', get_string('delete', 'core'), 'core').'</a>';
            $editurl = new moodle_url('/report/zabbix/edit_measurement.php', ['id' => $meas->id]);
            $cmd .= '&nbsp;<a href="'.$editurl.'">'.$this->output->pix_icon('t/edit', get_string('update', 'core'), 'core').'</a>';

            $row[] = $cmd;

            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

}