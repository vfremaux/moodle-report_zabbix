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
namespace report_zabbix;

defined('MOODLE_INTERNAL') || die;

define('DNS', 0);
define('PUBLICIP', 1);
define('INTERNALIP', 2);

use Exception;
use StdClass;

// A wrapper class to Zabbix API. Shall be used to push in the current host definition and
// Attaches the host to the MOODLE Zabbix model.
class api {

    protected $serverversion;

    protected $token;

    protected $jsonendpoint;

    protected $querytrace;

    protected $apiix; // will count queries

    /**
     * My host record in zabbix.
     */
     protected $me;

    /**
     * Implemented templates. This template list is discovered in the zabbix server.
     * This assumes that the MOODLE* models are already installed.
     */
    protected $templates;

    /**
     * Groups to bind to.
     */
    protected $groups;

    /**
     * Some runtime options.
     */
    protected $options;

    // A singleton instanciator.
    public static function instance($options) {
        static $api;

        if (is_null($api)) {
            $api = new api($options);
        }

        return $api;
    }

    /**
     * Builds the API class representative and connects to the zabbix server.
     */
    protected function __construct($options) {
        global $CFG;

        // Make an authentication to zabbix.

        $this->options = $options;

        $config = get_config('report_zabbix');
        $this->serverversion = $config->zabbixversion;
        $this->apiix = 0; // Initiate query sequence to 0.

        if (empty($config->zabbixserver)) {
            if (!empty($this->options['debugging'])) {
                print_object($config);
            }
            throw new call_exception("Zabbix server is not defined");
        }

        if (empty($config->zabbixadminusername)) {
            if (!empty($this->options['debugging'])) {
                print_object($config);
            }
            throw new call_exception("Zabbix server admin username is not set");
        }

        if (empty($CFG->zabbixusetesttarget)) {
            $this->jsonendpoint = $config->zabbixprotocol.'://'.$config->zabbixserver.'/api_jsonrpc.php';
            if (!empty($config->zabbixapipath)) {
                $this->jsonendpoint .= $config->zabbixapipath;
            }
        } else {
            mtrace("Using zabbix test target\n");
            $this->jsonendpoint = $config->zabbixprotocol.'://'.$config->zabbixserver.'/test_post.php';
            if (!empty($config->zabbixapipath)) {
                $this->jsonendpoint .= $config->zabbixapipath;
            }
        }

        if ($CFG->debug == DEBUG_DEVELOPER) {
            $this->check_api_version();
        }

        $params = new StdClass;
        if ($config->zabbixversion >= 6.2) {
            $params->username = $config->zabbixadminusername;
        } else {
            $params->user = $config->zabbixadminusername;
        }
        $params->password = $config->zabbixadminpassword;

        $json = $this->make_call("user.login", $params, null);

        try {
            // Stores query in query trace for reference in case of error.
            $return = $this->curl_send($json);
            $this->token = $return->result;
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }

        $this->init_templates();
        if (!empty($config->zabbixgroups)) {
            $this->init_groups($config->zabbixgroups);
        }
    }

    public function logout() {
        $json = $this->make_call("user.logout", [], null);
        return $json;
    }

    public function is_logged_in() {
        return !empty($this->token);
    }

    /**
     * Makes a call object to zabbix json rpc api and formats it.
     * @param string $method method name.
     * @param object $params Params to mass to method.
     * @return a json encoded call.
     */
    protected function make_call($method, $params) {

        if (!in_array($method, ['user.login', 'apiinfo.version']) && is_null($this->token)) {
            throw new call_exception("user.login has never been called yet. Auth is not available.\n");
        }

        $call = new StdClass;
        $call->jsonrpc = "2.0";
        $call->method = $method;
        $call->params = $params;
        $call->id = $this->apiix;
        if ($method != 'apiinfo.version') {
            $call->auth = $this->token; // null at first user login.
        }

        $json = json_encode($call);

        $this->querytrace[$this->apiix] = $json;

        $this->apiix++;

        return $json;
    }

    /**
     * Sends a jsonrpc call, validates it and delivers result.
     * @throws query_exception if bad return code, or server could not be joined.
     * @throws api_exception if server returns understandable error.
     * @throws json_exception returned data cannot be json decoded
     */
    protected function curl_send($json) {
        global $CFG;

        if (!empty($this->options['debugging'])) {
            mtrace("Shooting to ".$this->jsonendpoint."\nBody: ".$json);
        }

        $ch = curl_init($this->jsonendpoint);

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json-rpc charset=UTF-8"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        // check for proxy
        $usingproxy = 0;
        if (!empty($CFG->proxyhost) and !is_proxybypass($this->jsonendpoint)) {
            $usingproxy = 1;
            // SOCKS supported in PHP5 only
            if (!empty($CFG->proxytype) and ($CFG->proxytype == 'SOCKS5')) {
                if (defined('CURLPROXY_SOCKS5')) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                } else {
                    curl_close($ch);
                    print_error( 'socksnotsupported', 'mnet');
                }
            }

            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);

            if (empty($CFG->proxyport)) {
                curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost);
            } else {
                curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
            }

            if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypassword);
                if (defined('CURLOPT_PROXYAUTH')) {
                    // any proxy authentication if PHP 5.1
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
                }
            }
        }

        $res = curl_exec($ch);

        // Check for curl errors.
        $info =  curl_getinfo($ch);
        $curlerrno = curl_errno($ch);
        if ($curlerrno != 0) {
            throw new query_exception("Request for {$this->jsonendpoint} failed with curl error $curlerrno (using proxy: $usingproxy)\n".print_r($info, true));
        }

        // check HTTP error code
        if (array_key_exists('http_code', $info) and ($info['http_code'] != 200)) {
            $ex = "Request for {$this->jsonendpoint} failed with HTTP code ".$info['http_code']." (using proxy: $usingproxy)\n";
            $ex .= "Query: ".$this->querytrace[$this->apiix - 1]."\n"; // last call.
            throw new query_exception($ex);
        }

        curl_close($ch);

        $ret = json_decode($res);
        if (empty($ret)) {
            throw new json_exception("Json was not readable in response : $res\n");
        }

        // Add detection of API error message.
        if (!empty($ret->error)) {
            throw new api_exception("API Error status : {$ret->error->message}\n{$ret->error->data}\nOriginal query: ".$this->querytrace[$this->apiix -1]."\n");
        }

        if (!empty($this->options['debugging'])) {
            print_object($ret);
        }

        return $ret;
    }

    /**
     * checks for MOODLE related templates.
     * @returns false or the hostid.
     */
    public function init_templates() {
        global $CFG;

        $params = new StdClass;
        if ($this->serverversion >= 6.2) {
            $params->search = new StdClass;
            $params->host = 'MOODLE%';
        } else {
            $params->search = ['host', 'MOODLE'];
        }
        $params->searchWildcardsEnabled = true;
        $params->output = ['templateid', 'host', 'name'];

        try {
            $json = $this->make_call('template.get', $params);
            $ret = $this->curl_send($json);

            if (count($ret->result) == 0) {
                throw new call_exception("Init templates : MOODLE* Models seems NOT be installed in this zabbix\n");
            }

            // there is a strange behaviour of the search response. Securize templates by over filtering them.
            foreach ($ret->result as $template) {
                if (!preg_match('/^MOODLE/', $template->name)) {
                    continue;
                }

                if (preg_match('/^MOODLE GROUP/', $template->name)) {
                    // MOODLE GROUPs are special model for "set of moodle instances".
                    continue;
                }

                if ($template->name == 'MOODLE SHOP') {
                    // Do NOT install moodle shop template if moodle shop plugin
                    // not installed.
                    if (!is_dir($CFG->dirroot.'/local/shop')) {
                        continue;
                    }
                }
                if ($template->name == 'MOODLE LTC') {
                    // Do NOT install moodle shop template if moodle shop plugin
                    // not installed.
                    if (!is_dir($CFG->dirroot.'/mod/learningtimecheck')) {
                        continue;
                    }
                }
                if ($template->name == 'MOODLE ENT INSTALLER') {
                    // Do NOT install moodle shop template if moodle shop plugin
                    // not installed.
                    if (!is_dir($CFG->dirroot.'/local/ent_installer')) {
                        continue;
                    }
                }

                $this->templates[$template->templateid] = $template;
            }

        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

    }

    /**
     * checks the required groups.
     * @params string $grouplist the comma separated list of groups.
     * @returns void.
     */
    public function init_groups($grouplist) {
        global $CFG;

        $groupnames = explode(',', $grouplist);
        foreach ($groupnames as &$g) {
            $g = trim($g);
        }

        if (!empty($groupnames)) {

            $params = new StdClass;
            if ($this->serverversion >= 6.2) {
                $params->output = 'extend';
                $params->filter = new StdClass;
                $params->filter->name = $groupnames;
            } else {
                $params->filter = ['name', $groupnames];
            }
//            $params->startSearch = true;
            $params->searchByAny = true;
            $params->output = ['groupid', 'name'];

            try {
                $json = $this->make_call('hostgroup.get', $params);
                $ret = $this->curl_send($json);

                if (empty($ret->result)) {
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        throw new call_exception("Init groups : No groups found\n");
                    }
                }

                foreach ($ret->result as $group) {
                    // Search NOT working ! filter by name.
                    if (in_array($group->name, $groupnames)) {
                        $this->groups[$group->groupid] = $group;
                    }
                }
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
        }
    }

    /**
     * get and reports the API version.
     */
    public function check_api_version() {
        $params = [];

        $json = $this->make_call('apiinfo.version', $params);
        $ret = $this->curl_send($json);
        mtrace("Zabbix version: ".$ret->result."\n");
    }

    /**
     * checks if an host exists that has our WWWROOT name as host.
     * @returns false or the hostid.
     */
    public function check_host_exists() {
        global $CFG;

        $hostname = $CFG->wwwroot;
        $hostroot = preg_replace('#https?://#', '', $CFG->wwwroot);
        $dnsname = preg_replace('#/.*#', '', $hostroot);

        $params = new StdClass;
        // $params->search = ['host', [$dnsname]];
        $params->output = ['hostid', 'host'];

        $json = $this->make_call('host.get', $params);
        $ret = $this->curl_send($json);
        if (count($ret->result) > 0) {
            // There is an odd beahaviour of host.get with filter or search that doesn't work as expected.
            // So scan all the result.
            $found = 0;
            foreach ($ret->result as $host) {
                if ($host->host == $dnsname) {
                    $found = 1;
                    break;
                }
            }

            if ($found) {
                $this->me = $host;
                return true;
            }
        }
        return false;
    }

    /**
     * Asks zabbix to create an host with our WWWROOT as host.
     * @returns false or the hostid.
     */
    public function create_me() {
        global $SITE, $CFG;

        $hostname = $CFG->wwwroot;
        $hostroot = preg_replace('#https?://#', '', $CFG->wwwroot);
        $dnsname = preg_replace('#/.*#', '', $hostroot);

        if (strlen($hostroot) > 64) {
            throw new call_exception("Create me : hostid cannot exceed 64 chars in zabbix");
        }

        $params = new StdClass;
        $params->host = $hostroot;
        $params->name = $hostroot; // could be shortname, but more significant technically and univoque.
        $params->description = $SITE->fullname;
        $params->interfaces = $this->interfaces($dnsname);

        if (!empty($this->groups)) {
            // List groups by groupid.
            foreach (array_keys($this->groups) as $groupid) {
                $group = new Stdclass;
                $group->groupid = $groupid;
                $params->groups[] = $group;
            }
        } else {
            throw new call_exception("Create me : cannot create with no groups");
        }

        if (!empty($this->templates)) {
            // List groups by groupid.
            foreach (array_keys($this->templates) as $templateid) {
                $template = new Stdclass;
                $template->templateid = $templateid;
                $params->templates[] = $template;
            }
        } else {
            throw new call_exception("Create me : cannot create with no templates applied");
        }

        $json = $this->make_call('host.create', $params);
        $ret = $this->curl_send($json);
        $this->logout();
    }

    /**
     * Updates my definition if some params have changed (or the report_zabbix install algorithm)
     */
     public function update_me() {
        global $SITE, $CFG;

        $hostname = $CFG->wwwroot;
        $hostroot = preg_replace('#https?://#', '', $CFG->wwwroot);
        $dnsname = preg_replace('#/.*#', '', $hostroot);

        $params = new StdClass;
        $myhost = new StdClass;
        $params->hostid = $this->me->hostid;

        $params->description = $SITE->fullname;
        // Do not try to delete-add interfaces.
        // $params->interfaces = $this->interfaces($dnsname);

        if (!empty($this->groups)) {
            // List groups by groupid.
            foreach (array_keys($this->groups) as $groupid) {
                $group = new Stdclass;
                $group->groupid = $groupid;
                $params->groups[] = $group;
            }
        } else {
            throw new call_exception("Update me : cannot update with no groups");
        }
        if (!empty($this->templates)) {
            // List groups by groupid.
            foreach (array_keys($this->templates) as $templateid) {
                $template = new Stdclass;
                $template->templateid = $templateid;
                $params->templates[] = $template;
            }
        } else {
            throw new call_exception("Update me : cannot update with no templates applied");
        }

        $json = $this->make_call('host.update', $params);
        $ret = $this->curl_send($json);

        // Update interface
        // Get my known interface
        $params = new StdClass;
        $params->output = 'interfaceid';
        $params->hostids = $this->me->hostid;
        $json = $this->make_call('hostinterface.get', $params);
        $ret = $this->curl_send($json);
        $interface = $ret->result[0];

        $params = $this->interfaces($dnsname);
        $params->interfaceid = $interface->interfaceid;
        $json = $this->make_call('hostinterface.update', $params);
        $ret = $this->curl_send($json);

        // Finally update web scenario variables to adjust the host.
        $this->update_web_scenario($this->me->hostid);
        $this->logout();
     }

    /**
     * For a moodle host representation, web scenario variables
     * should be updated after deployment.
     * Note : there is one web scenario per moodle host that tests
     * the frontend access to moodle.
     * Relies on a manual account creation zabbix uses to fire login. (to be fixed)
     * If the zabbix admin user does not exist, will create one.
     * @param string $hostid the zabbix hostid.
     * @return void
     */
    public function update_web_scenario($hostid) {
        global $DB, $CFG;

        $params = new StdClass;
        $params->hostids = $hostid;
        $json = $this->make_call('httptest.get', $params);
        $ret = $this->curl_send($json);

        if (empty($ret->result)) {
            throw new query_exception("Missing web scenario for hostid $hostid");
        }

        $zabbixaccount = $DB->get_record('user', ['username' => 'zabbixadmin']);

        // In both case we need to renew password as we cannot get it from existing account
        // (unless storing it clear somewhere).
        if (empty($zabbixaccount)) {
            $password = generate_password();
            $user = new StdClass();
            $user->username = 'zabbixadmin';
            $user->firstname = 'Zabbix';
            $user->lastname = 'Administrator';
            $user->password = $password;
            $user->email = 'zabbix@foomail.invalid';
            $user->auth = 'manual';
            $user->suspended = 0;
            $user->deleted = 0;
            $user->confirmed = 1;
            $user->policyagreed = 1;

            user_create_user($user);
        } else {
            $password = generate_password();
            $zabbixaccount->password = $password;
            $zabbixaccount->department = '';
            user_update_user($zabbixaccount);
        }

        $webscenario = $ret->result[0];
        $webscenario->variables = [];
        $var = new StdClass;
        $var->name = '{wwwroot}';
        $var->value = $CFG->wwwroot;
        $webscenario->variables[] = $var;

        $var = new StdClass;
        $var->name = '{username}';
        $var->value = 'zabbixadmin';
        $webscenario->variables[] = $var;

        $var = new Stdclass;
        $var->name = '{password}';
        $var->value = $password;
        $webscenario->variables[] = $var;

        unset($webscenario->nextcheck); // This is a readonly data.
        unset($webscenario->hostid); // Not expected return.
        unset($webscenario->templateid); // Not expected return.
        unset($webscenario->name); // Cannot update as locked by a template define.

        $json = $this->make_call('httptest.update', $webscenario);
        $ret = $this->curl_send($json);

        // Should return the webscenario id.
    }

    /**
     * Trys to find internal SourceIP from zabbix local config file.
     */
    protected function find_source_ip() {
        $usuallocation = '/etc/zabbix/zabbix_agentd.conf';

        if (!file_exists($usuallocation)) {
            return false;
        }

        if (!is_readable($usuallocation)) {
            return false;
        }

        $conf = implode("\n", file($usuallocation));
        if (preg_match_all('/^SourceIP=(.*?)$/m', $conf, $matches)) {
            return $matches[1][0];
        }
        return false;
    }

    /**
     * Get the current known interface associated with his moodle.
     */
     protected function interfaces($dnsname) {

        $config = get_config('report_zabbix');

        $interfaces = new StdClass;
        $interfaces->type = 1;
        $interfaces->main = 1;

        switch($config->interfacedef) {
            case DNS: {
                $interfaces->useip = 0;
                // Try to get public visible address
                $dnsresolution = file_get_contents('https://ipv4.icanhazip.com/');
                if ($dnsresolution === false) {
                    throw new env_exception("Environment : icanhazip is not reachable from here, due to environment limitations. Choose internal IP resolution method.");
                }
                $interfaces->ip = trim($dnsresolution);
                $interfaces->dns = $dnsname;
                break;
            }
            case INTERNALIP: {
                // This will search in the system for the "sourceIP configuration of the zabbix agent";
                $interfaces->useip = 1;
                $interfaces->ip = $this->find_source_ip();
                if (empty($interfaces->ip)) {
                    $interfaces->ip = getHostByName(getHostName());
                }
                $interfaces->dns = '';
                break;
            }
            case PUBLICIP: {
                $interfaces->useip = 1;
                // Try to get public visible address
                $dnsresolution = file_get_contents('https://ipv4.icanhazip.com/');
                if ($dnsresolution === false) {
                    throw new env_exception("Environment : icanhazip is not reachable from here, due to environment limitations. Choose internal IP resolution method.");
                }
                $interfaces->ip = trim($dnsresolution);
                $interfaces->dns = '';
                break;
            }
        }

        $interfaces->port = "10050";
        return $interfaces;
     }
}

/**
 * Exceptions related to a limitation of the working surrounding environment.
 */
class env_exception extends Exception {
}

/**
 * Exceptions related to an API availability error. Zabbix cannnot
 * even be joined.
 */
class call_exception extends Exception {
}

/**
 * Exceptions related to a query exception. That is, error conditions
 * detected moodle side but on correct answers of the zabbix endpoint.
 */
class query_exception extends Exception {
}

/**
 * Exceptions reported as errors by remote API.
 */
class api_exception extends Exception {
}

/**
 * Exceptions related to JSON decoding.
 */
class json_exception extends Exception {
}