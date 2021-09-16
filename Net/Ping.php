<?php
/**
 * Part of the Net_Ping package
 *
 * PHP version 4, 5 and 7
 *
 * @category Networking
 * @package  Net_Ping
 * @author   Martin Jansen <mj@php.net>
 * @author   Tomas V.V.Cox <cox@idecnet.com>
 * @author   Jan Lehnardt <jan@php.net>
 * @author   Kai Schröder <k.schroeder@php.net>
 * @author   Craig Constantine <cconstantine@php.net>
 * @license  http://www.php.net/license/3_01.txt PHP-3.01
 * @link     https://pear.php.net/package/Net_Ping
 */

define('NET_PING_FAILED_MSG',                     'execution of ping failed'        );
define('NET_PING_HOST_NOT_FOUND_MSG',             'unknown host'                    );
define('NET_PING_INVALID_ARGUMENTS_MSG',          'invalid argument array'          );
define('NET_PING_CANT_LOCATE_PING_BINARY_MSG',    'unable to locate the ping binary');
define('NET_PING_RESULT_UNSUPPORTED_BACKEND_MSG', 'Backend not Supported'           );

define('NET_PING_FAILED',                     0);
define('NET_PING_HOST_NOT_FOUND',             1);
define('NET_PING_INVALID_ARGUMENTS',          2);
define('NET_PING_CANT_LOCATE_PING_BINARY',    3);
define('NET_PING_RESULT_UNSUPPORTED_BACKEND', 4);


/**
* Wrapper class for ping calls
*
* Usage:
*
* <?php
*   require_once "Net/Ping.php";
*   $ping = Net_Ping::factory();
*   if(PEAR::isError($ping)) {
*     echo $ping->getMessage();
*   } else {
*     $ping->setArgs(array('count' => 2));
*     var_dump($ping->ping('example.com'));
*   }
* ?>
*/
class Net_Ping
{
    /**
    * Location where the ping program is stored
    *
    * @var string
    * @access private
    */
    var $_ping_path = "";

    /**
    * Array with the result from the ping execution
    *
    * @var array
    * @access private
    */
    var $_result = array();

    /**
    * OS_Guess instance
    *
    * @var object
    * @access private
    */
    var $_OS_Guess = "";

    /**
    * OS_Guess->getSysname result
    *
    * @var string
    * @access private
    */
    var $_sysname = "";

    /**
    * Ping command arguments
    *
    * @var array
    * @access private
    */
    var $_args = array();

    /**
    * Indicates if an empty array was given to setArgs
    *
    * @var boolean
    * @access private
    */
    var $_noArgs = true;

    /**
    * Contains the argument->option relation
    *
    * @var array
    * @access private
    */
    var $_argRelation = array();

    /**
    * Constructor for the Class
    *
    * @access private
    */
    function __construct($ping_path, $sysname)
    {
        $this->_ping_path = $ping_path;
        $this->_sysname   = $sysname;
        $this->_initArgRelation();
    } /* function Net_Ping() */

    /**
    * Factory for Net_Ping
    *
    * @access public
    */
    static function factory()
    {
        $ping_path = '';

        $sysname = Net_Ping::_setSystemName();

        if (($ping_path = Net_Ping::_setPingPath($sysname)) == NET_PING_CANT_LOCATE_PING_BINARY) {
            return PEAR::raiseError(NET_PING_CANT_LOCATE_PING_BINARY_MSG, NET_PING_CANT_LOCATE_PING_BINARY);
        } else {
            return new Net_Ping($ping_path, $sysname);
        }
    } /* function factory() */

    /**
     * Resolve the system name
     *
     * @access private
     */
    static function _setSystemName()
    {
        $OS_Guess  = new OS_Guess;
        $sysname   = $OS_Guess->getSysname();

        // Refine the sysname for different Linux bundles/vendors. (This
        // should go away if OS_Guess was ever extended to give vendor
        // and vendor-version guesses.)
        //
        // Bear in mind that $sysname is eventually used to craft a
        // method name to figure out which backend gets used to parse
        // the ping output. Elsewhere, we'll set $sysname back before
        // that.
        if ('linux' == $sysname) {
            if (   file_exists('/etc/lsb-release')
                && false !== ($release=@file_get_contents('/etc/lsb-release'))
                && preg_match('/gutsy/i', $release)
                ) {
                $sysname = 'linuxredhat9';
            }
            else if ( file_exists('/etc/debian_version') ) {
                $sysname = 'linuxdebian';
            }else if (file_exists('/etc/redhat-release')
                     && false !== ($release= @file_get_contents('/etc/redhat-release'))
                     )
            {
                if (preg_match('/release 8/i', $release)) {
                    $sysname = 'linuxredhat8';
                }elseif (preg_match('/release 9/i', $release)) {
                    $sysname = 'linuxredhat9';
                }
            }
        }

        return $sysname;

    } /* function _setSystemName */

    /**
    * Set the arguments array
    *
    * @param array $args Hash with options
    * @return mixed true or PEAR_error
    * @access public
    */
    function setArgs($args)
    {
        if (!is_array($args)) {
            return PEAR::raiseError(NET_PING_INVALID_ARGUMENTS_MSG, NET_PING_INVALID_ARGUMENTS);
        }

        $this->_setNoArgs($args);

        $this->_args = $args;

        return true;
    } /* function setArgs() */

    /**
    * Set the noArgs flag
    *
    * @param array $args Hash with options
    * @return void
    * @access private
    */
    function _setNoArgs($args)
    {
        if (0 == count($args)) {
            $this->_noArgs = true;
        } else {
            $this->_noArgs = false;
        }
    } /* function _setNoArgs() */

    /**
    * Sets the system's path to the ping binary
    *
    * @access private
    */
    static function _setPingPath($sysname)
    {
        $status    = '';
        $output    = array();
        $ping_path = '';

        if ("windows" == $sysname) {
            return "ping";
        } else {
            $ping_path = exec("which ping", $output, $status);
            if (0 != $status) {
                return NET_PING_CANT_LOCATE_PING_BINARY;
            } else {
                // be certain "which" did what we expect. (ref bug #12791)
                if ( is_executable($ping_path) ) {
                    return $ping_path;
                }
                else {
                    return NET_PING_CANT_LOCATE_PING_BINARY;
                }
            }
        }
    } /* function _setPingPath() */

    /**
    * Creates the argument list according to platform differences
    *
    * @return string Argument line
    * @access private
    */
    function _createArgList()
    {
        $retval     = array();

        $timeout    = "";
        $iface      = "";
        $ttl        = "";
        $count      = "";
        $quiet      = "";
        $size       = "";
        $seq        = "";
        $deadline   = "";

        foreach($this->_args AS $option => $value) {
            if(!empty($option) && isset($this->_argRelation[$this->_sysname][$option]) && NULL != $this->_argRelation[$this->_sysname][$option]) {
                ${$option} = $this->_argRelation[$this->_sysname][$option]." ".$value." ";
             }
        }

        switch($this->_sysname) {

        case "sunos":
             if ($size || $count || $iface) {
                 /* $size and $count must be _both_ defined */
                 $seq = " -s ";
                 if ($size == "") {
                     $size = " 56 ";
                 }
                 if ($count == "") {
                     $count = " 5 ";
                 }
             }
             $retval['pre'] = $iface.$seq.$ttl;
             $retval['post'] = $size.$count;
             break;

        case "freebsd":
             $retval['pre'] = $quiet.$count.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "darwin":
             $retval['pre'] = $count.$timeout.$size;
             $retval['post'] = "";
             break;

        case "netbsd":
             $retval['pre'] = $quiet.$count.$iface.$size.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "openbsd":
             $retval['pre'] = $quiet.$count.$iface.$size.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "linux":
             $retval['pre'] = $quiet.$deadline.$count.$ttl.$size.$timeout;
             $retval['post'] = "";
             break;

        case "linuxdebian":
             $retval['pre'] = $quiet.$count.$ttl.$size.$timeout;
             $retval['post'] = "";
             $this->_sysname = 'linux'; // undo linux vendor refinement hack
             break;

        case "linuxredhat8":
             $retval['pre'] = $iface.$ttl.$count.$quiet.$size.$deadline;
             $retval['post'] = "";
             $this->_sysname = 'linux'; // undo linux vendor refinement hack
             break;

        case "linuxredhat9":
             $retval['pre'] = $timeout.$iface.$ttl.$count.$quiet.$size.$deadline;
             $retval['post'] = "";
             $this->_sysname = 'linux'; // undo linux vendor refinement hack
             break;

        case "windows":
             $retval['pre'] = $count.$ttl.$timeout;
             $retval['post'] = "";
             break;

        case "hpux":
             $retval['pre'] = $ttl;
             $retval['post'] = $size.$count;
             break;

        case "aix":
            $retval['pre'] = $count.$timeout.$ttl.$size;
            $retval['post'] = "";
            break;

        default:
             $retval['pre'] = "";
             $retval['post'] = "";
             break;
        }
        return($retval);
    }  /* function _createArgList() */

    /**
    * Execute ping
    *
    * @param  string    $host   hostname
    * @return mixed  String on error or array with the result
    * @access public
    */
    function ping($host)
    {

        if ($this->_noArgs) {
            $this->setArgs(array('count' => 3));
        }

        $argList = $this->_createArgList();
		$cmd = $this->_ping_path." ".$argList['pre']." ".escapeshellarg($host)." ".$argList['post'];

        // since we return a new instance of Net_Ping_Result (on
        // success), users may call the ping() method repeatedly to
        // perform unrelated ping tests Make sure we don't have raw data
        // from a previous call laying in the _result array.
        $this->_result = array();

        exec($cmd, $this->_result);

        if (!is_array($this->_result)) {
            return PEAR::raiseError(NET_PING_FAILED_MSG, NET_PING_FAILED);
        }

        if (count($this->_result) == 0) {
            return PEAR::raiseError(NET_PING_HOST_NOT_FOUND_MSG, NET_PING_HOST_NOT_FOUND);
        }

        // Here we pass $this->_sysname to the factory(), but it is
        // not actually used by the class. It's only maintained in
        // the Net_Ping_Result class because the
        // Net_Ping_Result::getSysName() method needs to be retained
        // for backwards compatibility.
        return Net_PingResult::factory($this->_result, $this->_sysname);
    } /* function ping() */

    /**
    * Check if a host is up by pinging it
    *
    * @param string $host   The host to test
    * @param bool $severely If some of the packages did reach the host
    *                       and severely is false the function will return true
    * @return bool True on success or false otherwise
    *
    */
    function checkHost($host, $severely = true)
    {
    	$matches = array();

        $this->setArgs(array("count" => 10,
                             "size"  => 32,
                             "quiet" => null,
                             "deadline" => 10
                             )
                       );
        $res = $this->ping($host);
        if (PEAR::isError($res)) {
            return false;
        }
        if ($res->_received == 0) {
            return false;
        }
        if ($res->_received != $res->_transmitted && $severely) {
            return false;
        }
        return true;
    } /* function checkHost() */

    /**
    * Output errors with PHP trigger_error(). You can silence the errors
    * with prefixing a "@" sign to the function call: @Net_Ping::ping(..);
    *
    * @param mixed $error a PEAR error or a string with the error message
    * @return bool false
    * @access private
    * @author Kai Schrder <k.schroeder@php.net>
    */
    function _raiseError($error)
    {
        if (PEAR::isError($error)) {
            $error = $error->getMessage();
        }
        trigger_error($error, E_USER_WARNING);
        return false;
    }  /* function _raiseError() */

    /**
    * Creates the argument list according to platform differences
    *
    * @return string Argument line
    * @access private
    */
    function _initArgRelation()
    {
        $this->_argRelation["sunos"] = array(
                                             "timeout"   => NULL,
                                             "ttl"       => "-t",
                                             "count"     => " ",
                                             "quiet"     => "-q",
                                             "size"      => " ",
                                             "iface"     => "-i"
                                             );

        $this->_argRelation["freebsd"] = array (
                                                "timeout"   => "-t",
                                                "ttl"       => "-m",
                                                "count"     => "-c",
                                                "quiet"     => "-q",
                                                "size"      => NULL,
                                                "iface"     => NULL
                                                );

        $this->_argRelation["netbsd"] = array (
                                               "timeout"   => "-w",
                                               "iface"     => "-I",
                                               "ttl"       => "-T",
                                               "count"     => "-c",
                                               "quiet"     => "-q",
                                               "size"      => "-s"
                                               );

        $this->_argRelation["openbsd"] = array (
                                                "timeout"   => "-w",
                                                "iface"     => "-I",
                                                "ttl"       => "-t",
                                                "count"     => "-c",
                                                "quiet"     => "-q",
                                                "size"      => "-s"
                                                );

        $this->_argRelation["darwin"] = array (
                                               "timeout"   => "-t",
                                               "iface"     => NULL,
                                               "ttl"       => NULL,
                                               "count"     => "-c",
                                               "quiet"     => "-q",
                                               "size"      => NULL
                                               );

        $this->_argRelation["linux"] = array (
                                              "timeout"   => "-W",
                                              "iface"     => NULL,
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w"
                                              );

        $this->_argRelation["linuxdebian"] = array (
                                              "timeout"   => "-W",
                                              "iface"     => NULL,
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w",
                                              );

        $this->_argRelation["linuxredhat8"] = array (
                                              "timeout"   => NULL,
                                              "iface"     => "-I",
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w"
                                              );

        $this->_argRelation["linuxredhat9"] = array (
                                              "timeout"   => "-W",
                                              "iface"     => "-I",
                                              "ttl"       => "-t",
                                              "count"     => "-c",
                                              "quiet"     => "-q",
                                              "size"      => "-s",
                                              "deadline"  => "-w"
                                              );

        $this->_argRelation["windows"] = array (
                                                "timeout"   => "-w",
                                                "iface"     => NULL,
                                                "ttl"       => "-i",
                                                "count"     => "-n",
                                                "quiet"     => NULL,
                                                "size"      => "-l"
                                                 );

        $this->_argRelation["hpux"] = array (
                                             "timeout"   => NULL,
                                             "iface"     => NULL,
                                             "ttl"       => "-t",
                                             "count"     => "-n",
                                             "quiet"     => NULL,
                                             "size"      => " "
                                             );

        $this->_argRelation["aix"] = array (
                                            "timeout"   => "-i",
                                            "iface"     => NULL,
                                            "ttl"       => "-T",
                                            "count"     => "-c",
                                            "quiet"     => NULL,
                                            "size"      => "-s"
                                            );
    }  /* function _initArgRelation() */
} /* class Net_Ping */
