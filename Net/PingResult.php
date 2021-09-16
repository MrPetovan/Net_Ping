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


/**
 * Container class for Net_Ping results
 *
 * @author   Jan Lehnardt <jan@php.net>
 * @version  $Revision$
 * @package  Net
 * @access   private
 */
class Net_PingResult
{
	/**
	 * ICMP sequence number and associated time in ms
	 *
	 * @var array
	 * @access private
	 */
	var $_icmp_sequence = array(); /* array($sequence_number => $time ) */

	/**
	 * The target's IP Address
	 *
	 * @var string
	 * @access private
	 */
	var $_target_ip;

	/**
	 * Number of bytes that are sent with each ICMP request
	 *
	 * @var int
	 * @access private
	 */
	var $_bytes_per_request;

	/**
	 * The total number of bytes that are sent with all ICMP requests
	 *
	 * @var int
	 * @access private
	 */
	var $_bytes_total;

	/**
	 * The ICMP request's TTL
	 *
	 * @var int
	 * @access private
	 */
	var $_ttl;

	/**
	 * The raw Net_Ping::result
	 *
	 * @var array
	 * @access private
	 */
	var $_raw_data = array();

	/**
	 * The Net_Ping::_sysname
	 *
	 * @var int
	 * @access private
	 */
	var $_sysname;

	/**
	 * Statistical information about the ping
	 *
	 * @var int
	 * @access private
	 */
	var $_round_trip = [
		'min' => null,
		'max' => null,
		'avg' => null,
		'stddev' => null
	];


	/**
	 * Constructor for the Class
	 *
	 * @access private
	 */
	function __construct($result, $sysname)
	{
		$this->_raw_data = $result;

		// The _sysname property is no longer used by Net_Ping_result.
		// The property remains for backwards compatibility so the
		// getSystemName() method continues to work.
		$this->_sysname  = $sysname;

		$this->_parseResult();
	} /* function Net_Ping_Result() */

	/**
	 * Factory for Net_Ping_Result
	 *
	 * @access public
	 * @param array $result Net_Ping result
	 * @param string $sysname OS_Guess::sysname
	 */
	static function factory($result, $sysname)
	{
		return new Net_PingResult($result, $sysname);
	}  /* function factory() */

	/**
	 * Parses the raw output from the ping utility.
	 *
	 * @access private
	 */
	function _parseResult()
	{
		// MAINTAINERS:
		//
		//   If you're in this class fixing or extending the parser
		//   please add another file in the 'tests/test_parser_data/'
		//   directory which exemplafies the problem. And of course
		//   you'll want to run the 'tests/test_parser.php' (which
		//   contains easy how-to instructions) to make sure you haven't
		//   broken any existing behaviour.

		// operate on a copy of the raw output since we're going to modify it
		$data = $this->_raw_data;

		// remove leading and trailing blank lines from output
		$this->_parseResultTrimLines($data);

		// separate the output into upper and lower portions,
		// and trim those portions
		$this->_parseResultSeparateParts($data, $upper, $lower);
		$this->_parseResultTrimLines($upper);
		$this->_parseResultTrimLines($lower);

		// extract various things from the ping output . . .

		$this->_target_ip         = $this->_parseResultDetailTargetIp($upper);
		$this->_bytes_per_request = $this->_parseResultDetailBytesPerRequest($upper);
		$this->_ttl               = $this->_parseResultDetailTtl($upper);
		$this->_icmp_sequence     = $this->_parseResultDetailIcmpSequence($upper);
		$this->_round_trip        = $this->_parseResultDetailRoundTrip($lower);

		$this->_parseResultDetailTransmitted($lower);
		$this->_parseResultDetailReceived($lower);
		$this->_parseResultDetailLoss($lower);

		if ( isset($this->_transmitted) ) {
			$this->_bytes_total = $this->_transmitted * $this->_bytes_per_request;
		}

	} /* function _parseResult() */

	/**
	 * determinces the number of bytes sent by ping per ICMP ECHO
	 *
	 * @access private
	 */
	function _parseResultDetailBytesPerRequest($upper)
	{
		// The ICMP ECHO REQUEST and REPLY packets should be the same
		// size. So we can also find what we want in the output for any
		// succesful ICMP reply which ping printed.
		for ( $i=1; $i<count($upper); $i++ ) {
			// anything like "64 bytes " at the front of any line in $upper??
			if ( preg_match('/^\s*(\d+)\s*bytes/i', $upper[$i], $matches) ) {
				return( (int)$matches[1] );
			}
			// anything like "bytes=64" in any line in the buffer??
			if ( preg_match('/bytes=(\d+)/i', $upper[$i], $matches) ) {
				return( (int)$matches[1] );
			}
		}

		// Some flavors of ping give two numbers, as in "n(m) bytes", on
		// the first line. We'll take the first number and add 8 for the
		// 8 bytes of header and such in an ICMP ECHO REQUEST.
		if ( preg_match('/(\d+)\(\d+\)\D+$/', $upper[0], $matches) ) {
			return( (int)(8+$matches[1]) );
		}

		// Ok we'll just take the rightmost number on the first line. It
		// could be "bytes of data" or "whole packet size". But to
		// distinguish would require language-specific patterns. Most
		// ping flavors just put the number of data (ie, payload) bytes
		// if they don't specify both numbers as n(m). So we add 8 bytes
		// for the ICMP headers.
		if ( preg_match('/(\d+)\D+$/', $upper[0], $matches) ) {
			return( (int)(8+$matches[1]) );
		}

		// then we have no idea...
		return( NULL );
	}

	/**
	 * determines the round trip time (RTT) in milliseconds for each
	 * ICMP ECHO which returned. Note that the array is keyed with the
	 * sequence number of each packet; If any packets are lost, the
	 * corresponding sequence number will not be found in the array keys.
	 *
	 * @access private
	 */
	function _parseResultDetailIcmpSequence($upper)
	{
		// There is a great deal of variation in the per-packet output
		// from various flavors of ping. There are language variations
		// (time=, rtt=, zeit=, etc), field order variations, and some
		// don't even generate sequence numbers.
		//
		// Since our goal is to build an array listing the round trip
		// times of each packet, our primary concern is to locate the
		// time. The best way seems to be to look for an equals
		// character, a number and then 'ms'. All the "time=" versions
		// of ping will match this methodology, and all the pings which
		// don't show "time=" (that I've seen examples from) also match
		// this methodolgy.

		$results = array();
		for ( $i=1; $i<count($upper); $i++ ) {
			// by our definition, it's not a success line if we can't
			// find the time
			if ( preg_match('/=\s*([\d+\.]+)\s*ms/i', $upper[$i], $matches) ) {
				// float cast deals neatly with values like "126." which
				// some pings generate
				$rtt = (float)$matches[1];
				// does the line have an obvious sequence number?
				if ( preg_match('/icmp_seq\s*=\s*([\d+]+)/i', $upper[$i], $matches) ) {
					$results[$matches[1]] = $rtt;
				}
				else {
					// we use the number of the line as the sequence number
					$results[($i-1)] = $rtt;
				}
			}
		}

		return( $results );
	}

	/**
	 * Locates the "packets lost" percentage in the ping output
	 *
	 * @access private
	 */
	function _parseResultDetailLoss($lower)
	{
		for ( $i=1; $i<count($lower); $i++ ) {
			if ( preg_match('/(\d+)%/', $lower[$i], $matches) ) {
				$this->_loss = (int)$matches[1];
				return;
			}
		}
	}

	/**
	 * Locates the "packets received" in the ping output
	 *
	 * @access private
	 */
	function _parseResultDetailReceived($lower)
	{
		for ( $i=1; $i<count($lower); $i++ ) {
			// the second number on the line
			if ( preg_match('/^\D*\d+\D+(\d+)/', $lower[$i], $matches) ) {
				$this->_received = (int)$matches[1];
				return;
			}
		}
	}

	/**
	 * determines the mininum, maximum, average and standard deviation
	 * of the round trip times.
	 *
	 * @access private
	 */
	function _parseResultDetailRoundTrip($lower)
	{
		// The first pattern will match a sequence of 3 or 4
		// alaphabet-char strings separated with slashes without
		// presuming the order. eg, "min/max/avg" and
		// "min/max/avg/mdev". Some ping flavors don't have the standard
		// deviation value, and some have different names for it when
		// present.
		$p1 = '[a-z]+/[a-z]+/[a-z]+/?[a-z]*';

		// And the pattern for 3 or 4 numbers (decimal values permitted)
		// separated by slashes.
		$p2 = '[0-9\.]+/[0-9\.]+/[0-9\.]+/?[0-9\.]*';

		$results = [
			'min' => null,
			'max' => null,
			'avg' => null,
			'stddev' => null
		];
		$matches = array();
		for ( $i=(count($lower)-1); $i>=0; $i-- ) {
			if ( preg_match('|('.$p1.')[^0-9]+('.$p2.')|i', $lower[$i], $matches) ) {
				break;
			}
		}

		// matches?
		if ( count($matches) > 0 ) {
			// we want standardized keys in the array we return. Here we
			// look for the values (min, max, etc) and setup the return
			// hash
			$fields = explode('/', $matches[1]);
			$values = explode('/', $matches[2]);
			for ( $i=0; $i<count($fields); $i++ ) {
				if ( preg_match('/min/i', $fields[$i]) ) {
					$results['min'] = (float)$values[$i];
				}
				else if ( preg_match('/max/i', $fields[$i]) ) {
					$results['max'] = (float)$values[$i];
				}
				else if ( preg_match('/avg/i', $fields[$i]) ) {
					$results['avg'] = (float)$values[$i];
				}
				else if ( preg_match('/dev/i', $fields[$i]) ) { # stddev or mdev
					$results['stddev'] = (float)$values[$i];
				}
			}
			return( $results );
		}

		// So we had no luck finding RTT info in a/b/c layout. Some ping
		// flavors give the RTT information in an "a=1 b=2 c=3" sort of
		// layout.
		$p3 = '[a-z]+\s*=\s*([0-9\.]+).*';
		for ( $i=(count($lower)-1); $i>=0; $i-- ) {
			if ( preg_match('/min.*max/i', $lower[$i]) ) {
				if ( preg_match('/'.$p3.$p3.$p3.'/i', $lower[$i], $matches) ) {
					$results['min'] = $matches[1];
					$results['max'] = $matches[2];
					$results['avg'] = $matches[3];
				}
				break;
			}
		}

		// either an array of min, max and avg from just above, or still
		// the empty array from initialization way above
		return( $results );
	}

	/**
	 * determinces the target IP address actually used by ping
	 *
	 * @access private
	 */
	function _parseResultDetailTargetIp($upper)
	{
		// Grab the first IP addr we can find. Most ping flavors
		// put the target IP on the first line, but some only list it
		// in successful ping packet lines.
		for ( $i=0; $i<count($upper); $i++ ) {
			if ( preg_match('/(\d+\.\d+\.\d+\.\d+)/', $upper[$i], $matches) ) {
				return( $matches[0] );
			}
		}

		// no idea...
		return( NULL );
	}

	/**
	 * Locates the "packets received" in the ping output
	 *
	 * @access private
	 */
	function _parseResultDetailTransmitted($lower)
	{
		for ( $i=1; $i<count($lower); $i++ ) {
			// the first number on the line
			if ( preg_match('/^\D*(\d+)/', $lower[$i], $matches) ) {
				$this->_transmitted = (int)$matches[1];
				return;
			}
		}
	}

	/**
	 * determinces the time to live (TTL) actually used by ping
	 *
	 * @access private
	 */
	function _parseResultDetailTtl($upper)
	{
		//extract TTL from first icmp echo line
		for ( $i=1; $i<count($upper); $i++ ) {
			if (   preg_match('/ttl=(\d+)/i', $upper[$i], $matches)
				&& (int)$matches[1] > 0
			) {
				return( (int)$matches[1] );
			}
		}

		// No idea what ttl was used. Probably because no packets
		// received in reply.
		return( NULL );
	}

	/**
	 * Modifies the array to temoves leading and trailing blank lines
	 *
	 * @access private
	 */
	function _parseResultTrimLines(&$data)
	{
		if ( !is_array($data) ) {
			print_r($this);
			exit;
		}
		// Trim empty elements from the front
		while ( preg_match('/^\s*$/', $data[0]) ) {
			array_splice($data, 0, 1);
		}
		// Trim empty elements from the back
		while ( preg_match('/^\s*$/', $data[(count($data)-1)]) ) {
			array_splice($data, -1, 1);
		}
	}

	/**
	 * Separates the upper portion (data about individual ICMP ECHO
	 * packets) and the lower portion (statistics about the ping
	 * execution as a whole.)
	 *
	 * @access private
	 */
	function _parseResultSeparateParts($data, &$upper, &$lower)
	{
		$upper = array();
		$lower = array();

		// find the blank line closest to the end
		$dividerIndex = count($data) - 1;
		while ( !preg_match('/^\s*$/', $data[$dividerIndex]) ) {
			$dividerIndex--;
			if ( $dividerIndex < 0 ) {
				break;
			}
		}

		// This is horrible; All the other methods assume we're able to
		// separate the upper (preamble and per-packet output) and lower
		// (statistics and summary output) sections.
		if ( $dividerIndex < 0 ) {
			$upper = $data;
			$lower = $data;
			return;
		}

		for ( $i=0; $i<$dividerIndex; $i++ ) {
			$upper[] = $data[$i];
		}
		for ( $i=(1+$dividerIndex); $i<count($data); $i++ ) {
			$lower[] = $data[$i];
		}
	}

	/**
	 * Returns a Ping_Result property
	 *
	 * @param string $name property name
	 * @return mixed property value
	 * @access public
	 */
	function getValue($name)
	{
		return isset($this->$name)?$this->$name:'';
	} /* function getValue() */

	/**
	 * Accessor for $this->_target_ip;
	 *
	 * @return string IP address
	 * @access public
	 * @see Ping_Result::_target_ip
	 */
	function getTargetIp()
	{
		return $this->_target_ip;
	} /* function getTargetIp() */

	/**
	 * Accessor for $this->_icmp_sequence;
	 *
	 * @return array ICMP sequence
	 * @access private
	 * @see Ping_Result::_icmp_sequence
	 */
	function getICMPSequence()
	{
		return $this->_icmp_sequence;
	} /* function getICMPSequencs() */

	/**
	 * Accessor for $this->_bytes_per_request;
	 *
	 * @return int bytes per request
	 * @access private
	 * @see Ping_Result::_bytes_per_request
	 */
	function getBytesPerRequest()
	{
		return $this->_bytes_per_request;
	} /* function getBytesPerRequest() */

	/**
	 * Accessor for $this->_bytes_total;
	 *
	 * @return int total bytes
	 * @access private
	 * @see Ping_Result::_bytes_total
	 */
	function getBytesTotal()
	{
		return $this->_bytes_total;
	} /* function getBytesTotal() */

	/**
	 * Accessor for $this->_ttl;
	 *
	 * @return int TTL
	 * @access private
	 * @see Ping_Result::_ttl
	 */
	function getTTL()
	{
		return $this->_ttl;
	} /* function getTTL() */

	/**
	 * Accessor for $this->_raw_data;
	 *
	 * @return array raw data
	 * @access private
	 * @see Ping_Result::_raw_data
	 */
	function getRawData()
	{
		return $this->_raw_data;
	} /* function getRawData() */

	/**
	 * Accessor for $this->_sysname;
	 *
	 * @return string OS_Guess::sysname
	 * @access private
	 * @see Ping_Result::_sysname
	 */
	function getSystemName()
	{
		return $this->_sysname;
	} /* function getSystemName() */

	/**
	 * Accessor for $this->_round_trip;
	 *
	 * @return array statistical information
	 * @access private
	 * @see Ping_Result::_round_trip
	 */
	function getRoundTrip()
	{
		return $this->_round_trip;
	} /* function getRoundTrip() */

	/**
	 * Accessor for $this->_round_trip['min'];
	 *
	 * @return array statistical information
	 * @access private
	 * @see Ping_Result::_round_trip
	 */
	function getMin()
	{
		return $this->_round_trip['min'];
	} /* function getMin() */

	/**
	 * Accessor for $this->_round_trip['max'];
	 *
	 * @return array statistical information
	 * @access private
	 * @see Ping_Result::_round_trip
	 */
	function getMax()
	{
		return $this->_round_trip['max'];
	} /* function getMax() */

	/**
	 * Accessor for $this->_round_trip['stddev'];
	 *
	 * @return array statistical information
	 * @access private
	 * @see Ping_Result::_round_trip
	 */
	function getStddev()
	{
		return $this->_round_trip['stddev'];
	} /* function getStddev() */

	/**
	 * Accessor for $this->_round_tripp['avg'];
	 *
	 * @return array statistical information
	 * @access private
	 * @see Ping_Result::_round_trip
	 */
	function getAvg()
	{
		return $this->_round_trip['avg'];
	} /* function getAvg() */

	/**
	 * Accessor for $this->_transmitted;
	 *
	 * @return array statistical information
	 * @access private
	 */
	function getTransmitted()
	{
		return $this->_transmitted;
	} /* function getTransmitted() */

	/**
	 * Accessor for $this->_received;
	 *
	 * @return array statistical information
	 * @access private
	 */
	function getReceived()
	{
		return $this->_received;
	} /* function getReceived() */

	/**
	 * Accessor for $this->_loss;
	 *
	 * @return array statistical information
	 * @access private
	 */
	function getLoss()
	{
		return $this->_loss;
	} /* function getLoss() */

} /* class Net_Ping_Result */
