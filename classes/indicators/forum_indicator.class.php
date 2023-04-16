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
use StdClass;

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');

class forum_indicator extends zabbix_indicator {

    static $submodes = 'openeddiscussions,posts';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.forum';
        $this->donotsendzeros = true;
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

        if(!isset($this->value)) {
            $this->value = new Stdclass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        switch ($submode) {

            case 'openeddiscussions': {
                $sql = "
                    SELECT
                        COUNT(*)
                    FROM
                        {forum_discussions} fd,
                        {forum_posts} fp
                    WHERE
                        fd.firstpost = fp.id AND
                        fp.created > ?
                ";
                $activityhorizon = time() - MINSECS * 5;
                $this->value->$submode = $DB->count_records_sql($sql, [$activityhorizon]);
                break;
            }

            case 'posts': {
                $activityhorizon = time() - MINSECS * 5;
                $queries = $DB->count_records_select('forum_posts', "created > ?", [$activityhorizon]);
                $this->value->$submode = $queries;
                break;
            }

        }
    }
}