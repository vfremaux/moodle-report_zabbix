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

use coding_exception;
use moodle_exception;

abstract class zabbix_indicator {

    static $submodes = '';

    /**
     * Zabbix key
     */
    protected $key;

    /**
     * Zabbix datatype : 'numeric' (default) or 'text'
     */
    protected $datatype;

    /**
     * Acquisition submode : some variation in the indicator range or scope.
     */
    protected $submode;

    /**
     * a local access to zabbic report plugin config.
     */
    public static $config;

    /**
     * The internal valueset.
     */
    protected $value;

    /**
     * If set to true, zero values samples will not be sent.
     * Use usually this value for instant or high density samples.
     */
    protected $donotsendzeros = false;

    /**
     * @param string $submode default submode
     */
    public function __construct() {
        global $CFG;

        if (is_null(self::$config)) {
            self::$config = get_config('report_zabbix');
        }

        if (empty(self::$config->zabbixsendercmd)) {
            set_config('zabbixsendercmd', '/usr/bin/zabbix_sender', 'report_zabbix');
        }

        if (empty(self::$config->zabbixhostname)) {
            $hostname = preg_replace('#https?://#', '', $CFG->wwroot);
            set_config('zabbixhostname', $hostname, 'report_zabbix');
        }

        $this->datatype = 'numeric';
        $this->submode = null;
    }

    /**
     * Will throw exception if submode not in accepted set.
     * @param string $submode
     */
    protected function checksubmodes($submode) {
        if (!in_array($submode, $this->get_submodes())) {
            throw new moodle_exception("Not available submode $submode in ".get_class($this));
        }
    }

    public function set_submode($submode) {
        $this->checksubmodes($submode);
        $this->submode = $submode;
    }

    public function acquire() {
        mtrace("Acquiring... ".get_class($this));
        $submodes = $this->get_submodes();

        foreach ($submodes as $submode) {
            $this->acquire_submode($submode);
        }
        mtrace("Done. ");
    }

    public function send() {
        mtrace("Sending... ");
        $submodes = $this->get_submodes();

        foreach ($submodes as $submode) {
            $this->send_submode($submode);
        }
        mtrace("Done.");
    }

    /**
     * The function that contains the logic to acquire the indicator submodes instant values.
     */
    abstract function acquire_submode($submode);

    /**
     * Send a submode indicator to zabbix.
     * @param string $submode the submode key part of the element name.
     */
    public function send_submode($submode) {
        global $CFG;

        if (empty($submode)) {
            throw new coding_exception("Submode is empty");
        }

        if (empty(self::$config->zabbixserver)) {
            // force it if we have no value.
            self::$config->zabbixserver = get_config('report_zabbix', 'zabbixserver');
        }

        $cmd = self::$config->zabbixsendercmd;
        $cmd .= ' -z '.self::$config->zabbixserver;
        if (empty(self::$config->zabbixhostname)) {
            // Strip out protocol.
            $hostname = preg_replace('#https?:\/\/#', '', $CFG->wwwroot);
            $cmd .= ' -s '.$hostname;
        } else {
            $cmd .= ' -s '.escapeshellarg(self::$config->zabbixhostname);
        }

        mtrace("Sending ".htmlentities($submode)."\n");

        if (preg_match('/<.*?>(.*)/', $submode, $matches)) {
            // This is a templated submode. value has several sub submodes.
            $submodes = $this->get_sub_submodes($matches[1]);
        } else {
            // Elsewhere it is a simple submode.
            $submodes = [$submode];
        }

        foreach ($submodes as $submode) {

            if (!empty($this->donotsendzeros) && $this->value->$submode == 0) {
                // Trap zero value samples to avoid overcrowding the database.
                if ($CFG->debug == DEBUG_DEVELOPER) {
                    mtrace("Force 0 to silence for $submode\n");
                }
                continue;
            }

            $cmd1 = $cmd . ' -k '.$this->key.'.'.$submode;
            if (!empty($this->submode)) {
                $cmd1 .= '.'.$submode;
            }
            if ($this->datatype == 'numeric' && is_numeric($this->value->$submode)) {
                $cmd1 .= ' -o '.$this->value->$submode;
            } else {
                $cmd1 .= ' -o '.escapeshellarg($this->value->$submode);
            }

            mtrace($cmd1);
            $return = exec($cmd1, $output, $resultcode);
            if (!empty($config->verbose)) {
                mtrace($ouptut);
            }
        }
    }
}