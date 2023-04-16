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

/**
 * Class notes : 
 * the first release of this class is not optimized. 
 * the furthuer work would try to get all measurements in much fewer requests, to address
 * highly loaded instances.
 */

class hourly_storage_indicator extends zabbix_indicator {

    /**
     * Indicator submodes. Note that we introduce here for the first time a "patterned" submode. Patterned submodes
     * can federate some instructions for aquiring multiple data in the same query, then distribute them efficienty
     * across outgoing indicators.
     */
    static $submodes = '<areatype>areassize,storeddocumentssize,storedvideosize,storedbackupsize,storeduserbackupsize,storedactivitybackupsize,storedcoursebackupsize,storedautomatedbackupsize,dbsize,logsize,oldestlog';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.storage';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        return explode(',', self::$submodes);
    }

    /**
     * Checks for existance of subsubmodes.
     * @param string $submodekey the submode key radical, without any variable subpattern (<>)
     * @return an array of subsumodes for this submodekey
     */
    protected function get_sub_submodes($submodekey) {
        if ($submodekey == 'areassize') {
            return ['draftareassize', 'storedareassize'];
        }

        throw new \coding_exception("Unsupported submodekey $submodekey when getting submodes");
    }

    /**
     * the function that contains the logic to acquire the indicator instant value.
     * @param string $submode to target an aquisition to an explicit submode, elsewhere
     * @return the effective submode, that might be an array in case of templated submode.
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
            case '<areatype>areassize': {
                /*
                 * This is a way to raise extraction performance, when 
                 * several submodes might be extracted from a single query.
                 */ 
                $areatypes = $this->get_sub_submodes('areassize');
                $sql = "
                    SELECT
                        SUM(CASE WHEN filearea = ? THEN filesize ELSE 0 END) as draftareassize,
                        SUM(CASE WHEN filearea != ? THEN filesize ELSE 0 END) as storedareassize
                    FROM
                        {files}
                ";
                $areasizes = $DB->get_record_sql($sql, ['draft', 'draft']);
                foreach ($areatypes as $atype) {
                    $this->value->$atype = $areasizes->$atype;
                }
                break;
            }

            case 'storeddocumentssize': {
                $sqllike = $DB->sql_like('mimetype', ':mime');
                $params = ['filearea' => 'draft', 'mime' => 'application%'];
                $size = $DB->get_field_select('files', 'SUM(filesize)', 'filearea != :filearea AND filesize != 0 AND '.$sqllike, $params);
                $this->value->$submode = $size;
                break;
            }

            case 'storedvideosize': {
                $sqllike = $DB->sql_like('mimetype', ':mime');
                $params = ['filearea' => 'draft', 'mime' => 'video%'];
                $size = $DB->get_field_select('files', 'SUM(filesize)', 'filearea != :filearea AND filesize != 0 AND '.$sqllike, $params);
                $this->value->$submode = $size;
                break;
            }

            case 'storedbackupsize': {
                $params = ['component' => 'backup'];
                $size = $DB->get_field_select('files', 'SUM(filesize)', 'component = :component AND filesize != 0 ', $params);
                $params = ['component' => 'user', 'filearea' => 'backup'];
                $usersize = $DB->get_field_select('files', 'SUM(filesize)', 'component = :component AND filearea = :filearea AND filesize != 0 ', $params);
                $this->value->$submode = $size + $usersize;
                break;
            }

            case 'storeduserbackupsize': {
                $sqllike = $DB->sql_like('mimetype', ':mime');
                $params = ['component' => 'user', 'filearea' => 'backup'];
                $usersize = $DB->get_field_select('files', 'SUM(filesize)', 'component = :component AND filearea = :filearea AND filesize != 0 ', $params);
                $this->value->$submode = $usersize;
                break;
            }

            case 'storedactivitybackupsize': {
                $sqllike = $DB->sql_like('mimetype', ':mime');
                $params = ['component' => 'backup', 'filearea' => 'activity'];
                $size = $DB->get_field_select('files', 'SUM(filesize)', 'component = :component AND filearea = :filearea AND filesize != 0 ', $params);
                $this->value->$submode = $size;
                break;
            }

            case 'storedcoursebackupsize': {
                $sqllike = $DB->sql_like('mimetype', ':mime');
                $params = ['component' => 'backup', 'filearea' => 'course'];
                $size = $DB->get_field_select('files', 'SUM(filesize)', 'component = :component AND filearea = :filearea AND filesize != 0 ', $params);
                $this->value->$submode = $size;
                break;
            }

            case 'storedautomatedbackupsize': {
                $sqllike = $DB->sql_like('mimetype', ':mime');
                $params = ['component' => 'backup', 'filearea' => 'automated'];
                $size = $DB->get_field_select('files', 'SUM(filesize)', 'component = :component AND filearea = :filearea AND filesize != 0 ', $params);
                $this->value->$submode = $size;
                break;
            }

            case 'dbsize': {
                $sql = "
                    SELECT
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as dbsize
                    FROM
                        information_schema.tables
                    WHERE
                        table_schema = ?
               ";

                $db = $DB->get_record_sql($sql, [$CFG->dbname]);
                $this->value->$submode = 0 + $db->dbsize;
                break;
            }

            case 'logsize': {
                $sql = "
                    SELECT
                        COUNT(*) as logsize
                    FROM
                        {logstore_standard_log}
                ";
                $db = $DB->count_records_sql($sql, []);
                $this->value->$submode = 0 + $db->logsize;
                break;
            }

            case 'oldestlog': {
                $sql = "
                    SELECT
                        MIN(timecreated) as oldestlog
                    FROM
                        {logstore_standard_log}
                ";
                $db = $DB->get_record_sql($sql, []);
                $this->value->$submode = date('r', $db->oldestlog);
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