<?php

/**
 * @link https://github.com/ptrofimov/beanstalk_console
 * @link http://kr.github.com/beanstalkd/
 * @author Petr Trofimov, Sergey Lysenko
 */
function autoload_class($class) {
    require_once str_replace('_', '/', $class) . '.php';
}

spl_autoload_register('autoload_class');

session_start();
require_once 'Pheanstalk/ClassLoader.php';
Pheanstalk_ClassLoader::register(dirname(__FILE__));

require_once 'BeanstalkInterface.class.php';
require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) . '/../src/Storage.php';

$GLOBALS['server'] = !empty($_GET['server']) ? filter_input(INPUT_GET, 'server', FILTER_SANITIZE_SPECIAL_CHARS) : '';
$GLOBALS['action'] = !empty($_GET['action']) ? filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) : '';
$GLOBALS['state'] = !empty($_GET['state']) ? filter_input(INPUT_GET, 'state', FILTER_SANITIZE_SPECIAL_CHARS) : '';
$GLOBALS['count'] = !empty($_GET['count']) ? filter_input(INPUT_GET, 'count', FILTER_SANITIZE_SPECIAL_CHARS) : '';
$GLOBALS['tube'] = !empty($_GET['tube']) ? filter_input(INPUT_GET, 'tube', FILTER_SANITIZE_SPECIAL_CHARS) : '';
$GLOBALS['tplMain'] = !empty($_GET['tplMain']) ? filter_input(INPUT_GET, 'tplMain', FILTER_SANITIZE_SPECIAL_CHARS) : '';
$GLOBALS['tplBlock'] = !empty($_GET['tplBlock']) ? filter_input(INPUT_GET, 'tplBlock', FILTER_SANITIZE_SPECIAL_CHARS) : '';

class Console {

    /**
     * @var BeanstalkInterface
     */
    public $interface;
    protected $_tplVars = array();
    protected $_globalVar = array();
    protected $_errors = array();
    private $serversConfig = array();
    private $serversEnv = array();
    private $serversCookie = array();
    private $searchResults = array();
    private $actionTimeStart = 0;

    public function __construct() {
        $this->__init();
        $this->_main();
    }

    /** @return array */
    public function getServers() {
        return array_merge($this->serversConfig, $this->serversEnv, $this->serversCookie);
    }

    /** @return array */
    public function getServersConfig() {
        return $this->serversConfig;
    }

    /** @return array */
    public function getServersEnv() {
        return $this->serversEnv;
    }

    /** @return array */
    public function getServersCookie() {
        return $this->serversCookie;
    }

    public function getServerStats($server) {
        try {
            $interface = new BeanstalkInterface($server);
            $stats = $interface->getServerStats();
        } catch (Pheanstalk_Exception_ConnectionException $e) {
            $stats = array();
        }

        return $stats;
    }

    public function getServerStatsGroups() {
        return array(
            'binlog' => array(
                'binlog-current-index' => 'the index of the current binlog file being written to. If binlog is not active this value will be 0',
                'binlog-max-size' => 'the maximum size in bytes a binlog file is allowed to get before a new binlog file is opened',
                'binlog-oldest-index' => 'the index of the oldest binlog file needed to store the current jobs',
                'binlog-records-migrated' => 'the cumulative number of records written as part of compaction',
                'binlog-records-written' => 'the cumulative number of records written to the binlog',
            ),
            'cmd' => array(
                'cmd-bury' => 'the cumulative number of bury commands',
                'cmd-delete' => 'the cumulative number of delete commands',
                'cmd-ignore' => 'the cumulative number of ignore commands',
                'cmd-kick' => 'the cumulative number of kick commands',
                'cmd-list-tube-used' => 'the cumulative number of list-tube-used commands',
                'cmd-list-tubes' => 'the cumulative number of list-tubes commands',
                'cmd-list-tubes-watched' => 'the cumulative number of list-tubes-watched commands',
                'cmd-pause-tube' => 'the cumulative number of pause-tube commands',
                'cmd-peek' => 'the cumulative number of peek commands',
                'cmd-peek-buried' => 'the cumulative number of peek-buried commands',
                'cmd-peek-delayed' => 'the cumulative number of peek-delayed commands',
                'cmd-peek-ready' => 'the cumulative number of peek-ready commands',
                'cmd-put' => 'the cumulative number of put commands',
                'cmd-release' => 'the cumulative number of release commands',
                'cmd-reserve' => 'the cumulative number of reserve commands',
                'cmd-stats' => 'the cumulative number of stats commands',
                'cmd-stats-job' => 'the cumulative number of stats-job commands',
                'cmd-stats-tube' => 'the cumulative number of stats-tube commands',
                'cmd-use' => 'the cumulative number of use commands',
                'cmd-watch' => 'the cumulative number of watch commands',
            ),
            'current' => array(
                'current-connections' => 'the number of currently open connections',
                'current-jobs-buried' => 'the number of buried jobs',
                'current-jobs-delayed' => 'the number of delayed jobs',
                'current-jobs-ready' => 'the number of jobs in the ready queue',
                'current-jobs-reserved' => 'the number of jobs reserved by all clients',
                'current-jobs-urgent' => 'the number of ready jobs with priority < 1024',
                'current-producers' => 'the number of open connections that have each issued at least one put command',
                'current-tubes' => 'the number of currently-existing tubes',
                'current-waiting' => 'the number of open connections that have issued a reserve command but not yet received a response',
                'current-workers' => 'the number of open connections that have each issued at least one reserve command',
            ),
            'other' => array(
                'hostname' => 'the hostname of the machine as determined by uname',
                'id' => 'a random id string for this server process, generated when each beanstalkd process starts',
                'job-timeouts' => 'the cumulative count of times a job has timed out',
                'max-job-size' => 'the maximum number of bytes in a job',
                'pid' => 'the process id of the server',
                'rusage-stime' => 'the cumulative system CPU time of this process in seconds and microseconds',
                'rusage-utime' => 'the cumulative user CPU time of this process in seconds and microseconds',
                'total-connections' => 'the cumulative count of connections',
                'total-jobs' => 'the cumulative count of jobs created',
                'uptime' => 'the number of seconds since this server process started running',
                'version' => 'the version string of the server',
            ),
        );
    }

    public function getTubeStatFields() {
        return array(
            'current-jobs-urgent' => 'number of ready jobs with priority < 1024 in this tube',
            'current-jobs-ready' => 'number of jobs in the ready queue in this tube',
            'current-jobs-reserved' => 'number of jobs reserved by all clients in this tube',
            'current-jobs-delayed' => 'number of delayed jobs in this tube',
            'current-jobs-buried' => 'number of buried jobs in this tube',
            'total-jobs' => 'cumulative count of jobs created in this tube in the current beanstalkd process',
            'current-using' => 'number of open connections that are currently using this tube',
            'current-waiting' => 'number of open connections that have issued a reserve command while watching this tube but not yet received a response',
            'current-watching' => 'number of open connections that are currently watching this tube',
            'pause' => 'number of seconds the tube has been paused for',
            'cmd-delete' => 'cumulative number of delete commands for this tube',
            'cmd-pause-tube' => 'cumulative number of pause-tube commands for this tube',
            'pause-time-left' => 'number of seconds until the tube is un-paused',
        );
    }

    public function getTubeStatGroups() {
        return array(
            'current' => array(
                'current-jobs-buried',
                'current-jobs-delayed',
                'current-jobs-ready',
                'current-jobs-reserved',
                'current-jobs-urgent',
                'current-using',
                'current-waiting',
                'current-watching',
            ),
            'other' => array(
                'cmd-delete',
                'cmd-pause-tube',
                'pause',
                'pause-time-left',
                'total-jobs',
            ),
        );
    }

    public function getTubeStatVisible() {
        if (!empty($_COOKIE['tubefilter'])) {
            return explode(',', $_COOKIE['tubefilter']);
        } else {
            return array(
                'current-jobs-buried',
                'current-jobs-delayed',
                'current-jobs-ready',
                'current-jobs-reserved',
                'current-jobs-urgent',
                'total-jobs',
            );
        }
    }

    public function getTubeStatValues($tube) {
        // make sure, that rapid tube disappearance (eg: anonymous tubes, don't kill the interface, as they might be missing)
        try {
            return $this->interface->_client->statsTube($tube);
        } catch (Pheanstalk_Exception_ServerException $ex) {
            if (strpos($ex->getMessage(), Pheanstalk_Response::RESPONSE_NOT_FOUND) !== false) {
                return array();
            } else {
                throw $ex;
            }
        }
    }

    public function getSearchResult() {
        return $this->searchResults;
    }

    protected function __init() {
        $this->_globalVar = array(
            'server' => $GLOBALS['server'],
            'action' => $GLOBALS['action'],
            'state' => $GLOBALS['state'],
            'count' => $GLOBALS['count'],
            'tube' => $GLOBALS['tube'],
            '_tplMain' => $GLOBALS['tplMain'],
            '_tplBlock' => $GLOBALS['tplBlock'],
            'config' => $GLOBALS['config']);
        $this->_tplVars = $this->_globalVar;
        if (!in_array($this->_tplVars['_tplBlock'], array('allTubes', 'serversList'))) {
            unset($this->_tplVars['_tplBlock']);
        }
        if (!in_array($this->_tplVars['_tplMain'], array('main'))) {
            unset($this->_tplVars['_tplMain']);
        }
        if (empty($this->_tplVars['_tplMain'])) {
            $this->_tplVars['_tplMain'] = 'main';
        }

        foreach ($GLOBALS['config']['servers'] as $key => $server) {
            $this->serversConfig[$key] = $server;
        }
        if (false !== getenv('BEANSTALK_SERVERS')) {
            foreach (explode(',', getenv('BEANSTALK_SERVERS')) as $key => $server) {
                $this->serversEnv[$key] = $server;
            }
        }
        if (isset($_COOKIE['beansServers'])) {
            foreach (explode(';', $_COOKIE['beansServers']) as $key => $server) {
                $this->serversCookie[$key] = $server;
            }
        }
        try {
            $storage = new Storage($GLOBALS['config']['storage']);
        } catch (Exception $ex) {
            $this->_errors[] = $ex->getMessage();
        }
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getTplVars($var = null) {
        if (!empty($var)) {
            $result = !empty($this->_tplVars[$var]) ? $this->_tplVars[$var] : null;
        } else {
            $result = $this->_tplVars;
        }

        return $result;
    }

    protected function _main() {


        if (!isset($_GET['server'])) {
            // execute methods without a server
            if (isset($_GET['action']) && in_array($_GET['action'], array('serversRemove', 'manageSamples', 'deleteSample', 'editSample', 'newSample'))) {
                $funcName = "_action" . ucfirst($this->_globalVar['action']);
                if (method_exists($this, $funcName)) {
                    $this->$funcName();
                }
                return;
            }
            return;
        }

        try {
            $this->interface = new BeanstalkInterface($this->_globalVar['server']);

            $this->_tplVars['tubes'] = $this->interface->getTubes();

            $stats = $this->interface->getTubesStats();

            $this->_tplVars['tubesStats'] = $stats;
            $this->_tplVars['peek'] = $this->interface->peekAll($this->_globalVar['tube']);
            $this->_tplVars['contentType'] = $this->interface->getContentType();
            if (!empty($_GET['action'])) {
                $funcName = "_action" . ucfirst($this->_globalVar['action']);
                if (method_exists($this, $funcName)) {
                    $this->$funcName();
                }
                return;
            }
        } catch (Pheanstalk_Exception_ConnectionException $e) {
            $this->_errors[] = 'The server is unavailable';
        } catch (Pheanstalk_Exception_ServerException $e) {
            // if we get response not found, we just skip it (as the peekAll reached a tube which no longer existed)
            if (strpos($e->getMessage(), Pheanstalk_Response::RESPONSE_NOT_FOUND) === false) {
                $this->_errors[] = $e->getMessage();
            }
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
        }
    }

    protected function _actionKick() {
    }

    protected function _actionKickJob() {
    }

    protected function _actionDelete() {
    }

    protected function _actionDeleteJob() {
    }

    protected function _postDelete() {
    }

    protected function _actionDeleteAll($tube = null) {
    }

    protected function _actionServersRemove() {
    }

    protected function _actionAddjob() {
    }

    protected function _actionClearTubes() {
    }

    protected function _actionPause() {
    }

    protected function _actionAddSample() {
    }

    protected function _actionLoadSample() {
    }

    protected function _actionManageSamples() {
    }

    protected function _actionEditSample() {
    }

    protected function _actionNewSample() {
    }

    protected function _actionDeleteSample() {
    }

    protected function _actionMoveJobsTo() {
    }

    protected function _actionSearch() {
    }
}
