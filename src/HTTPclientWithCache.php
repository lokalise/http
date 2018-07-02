<?php

/**
 * PHP version 5.6
 *
 * @package Logics\Foundation\HTTP
 */

namespace Logics\Foundation\HTTP;

use \DateInterval;
use \DateTime;
use \Exception;
use \Logics\Foundation\SQL\SQLdatabase;

/**
 * Class for execution of HTTP POST/GET requests with local cache
 *
 * @author    Vladimir Bashkirtsev <vladimir@bashkirtsev.com>
 * @copyright 2013-2016 Vladimir Bashkirtsev
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date: 2016-08-17 14:51:40 +0000 (Wed, 17 Aug 2016) $ $Revision: 2 $
 * @link      $HeadURL: https://open.logics.net.au/foundation/HTTP/tags/0.1.4/src/HTTPclientWithCache.php $
 */

class HTTPclientWithCache extends HTTPclient
    {

	/**
	 * Database which holds HTTPcache table
	 *
	 * @var SQLdatabase
	 */
	private $_db;

	/**
	 * Construct HTTP client and set HTTP request parameters
	 *
	 * @param SQLdatabase $db      Database which holds HTTPcache table
	 * @param string      $url     URL for HTTP request
	 * @param array       $request Associative array containing NVPs to be passed with HTTP request
	 * @param array       $headers Associative array containing NVPs to be passed as HTTP headers
	 * @param array       $config  Optional cURL configuration
	 * @param int         $expiry  Amount of seconds for contents of cache to be considered current
	 *
	 * @return void
	 */

	public function __construct(SQLdatabase $db, $url, array $request = array(), array $headers = array(), array $config = array(), $expiry = 86400)
	    {
		$this->_db     = $db;
		$this->_expiry = $expiry;
		parent::__construct($url, $request, $headers, $config);

		$this->_db->execUntilSuccessful(
		    "CREATE TABLE IF NOT EXISTS `HTTPcache` (" .
		    "`id` int(11) NOT NULL AUTO_INCREMENT," .
		    "`method` ENUM('GET', 'POST'), " .
		    "`url` text NOT NULL," .
		    "`params` text NOT NULL," .
		    "`response` longblob NOT NULL," .
		    "`headers` longblob NOT NULL," .
		    "`datetime` datetime NOT NULL," .
		    "`expiry` datetime NOT NULL," .
		    "PRIMARY KEY (`id`)" .
		    ") ENGINE=InnoDB DEFAULT CHARSET=utf8;"
		);
	    } //end __construct()


	/**
	 * Execute HTTP GET request
	 *
	 * @param int $retries Number of attempts to successfully perform the request
	 *
	 * @return string
	 *
	 * @untranslatable GET
	 */

	public function get($retries = 1)
	    {
		return $this->_execute("GET", $retries);
	    } //end get()


	/**
	 * Execute HTTP POST request
	 *
	 * @param int $retries Number of attempts to successfully perform the request
	 *
	 * @return string
	 *
	 * @untranslatable POST
	 */

	public function post($retries = 1)
	    {
		return $this->_execute("POST", $retries);
	    } //end post()


	/**
	 * Execute the request
	 *
	 * @param string $method  Either GET or POST
	 * @param string $retries Number of retries to attempt
	 *
	 * @return string Response from remote web server
	 *
	 * @untranslatable GET
	 */

	private function _execute($method, $retries)
	    {
		$this->_cacheCleanup();
		$params = $this->prepareParameters();
		$params = (is_array($params) === false) ? $params : "";

		$result = $this->_db->exec(
		    "SELECT `response`, `headers` FROM `HTTPcache` " .
		    "WHERE `method` = " . $this->_db->sqlText($method) . " AND `url` = " . $this->_db->sqlText($this->url) . " AND `params` = " . $this->_db->sqlText($params)
		);
		if ($result->getNumRows() > 0)
		    {
			$row               = $result->getRow();
			$this->lastresult  = bzdecompress($row["response"]);
			$this->lastcode    = 200;
			$this->lastheaders = unserialize(bzdecompress($row["headers"]));
			return $this->lastresult;
		    }
		else
		    {
			$response = (($method === "GET") ? parent::get($retries) : parent::post($retries));
			if ($this->lastcode === 200)
			    {
				$this->_cacheStore($method, $this->url, $params, $response, $this->lastheaders);
			    }

			return $response;
		    }
	    } //end _execute()


	/**
	 * Clean up cache: remove expired data
	 *
	 * @return void
	 */

	private function _cacheCleanup()
	    {
		$this->_db->exec(
		    "DELETE FROM `HTTPcache` WHERE " .
		    "`datetime` < DATE_SUB(NOW(), INTERVAL " . $this->_expiry . " SECOND) OR `expiry` < NOW()"
		);
	    } //end _cacheCleanup()


	/**
	 * Store response in the cache
	 *
	 * @param string $method   Either GET or POST
	 * @param string $url      URL
	 * @param string $params   URL encoded parameters for the request to be cached
	 * @param string $response Remote web server response to be cached
	 * @param string $headers  Response headers to be cached
	 *
	 * @return void
	 *
	 * @untranslatable Cache-Control
	 * @untranslatable no-cache
	 * @untranslatable no-store
	 * @untranslatable Expires
	 * @untranslatable now
	 * @untranslatable P1Y
	 * @untranslatable SECOND)
	 */

	private function _cacheStore($method, $url, $params, $response, $headers)
	    {
		$cachecontrol = $this->httpheader("Cache-Control");
		if ($cachecontrol !== "no-cache" && $cachecontrol !== "no-store")
		    {
			$expires = $this->httpheader("Expires");
			if (preg_match("/max-age=(?P<maxage>\d+)/", $cachecontrol, $m) > 0)
			    {
				$ttl = $m["maxage"];
			    }
			else if ($expires !== false)
			    {
				try
				    {
					$now    = new DateTime("now");
					$expiry = new DateTime($expires);
					$ttl    = ($expiry->getTimestamp() - $now->getTimestamp());
					if ($ttl < 0)
					    {
						$ttl = 0;
					    }
				    }
				catch (Exception $e)
				    {
					$ttl = 0;
				    }
			    }
			else
			    {
				$now      = new DateTime("now");
				$expiry   = clone $now;
				$interval = new DateInterval("P1Y");
				$expiry->add($interval);
				$ttl = ($expiry->getTimestamp() - $now->getTimestamp());
			    } //end if

			if ($ttl > 0)
			    {
				$this->_db->execBinaryBlob(
				    "INSERT INTO `HTTPcache` SET " .
				    "`method` = " . $this->_db->sqlText($method) . ", " .
				    "`url` = " . $this->_db->sqlText($url) . ", " .
				    "`params` = " . $this->_db->sqlText($params) . ", " .
				    "`response` = ?, " .
				    "`headers` = ?, " .
				    "`datetime` = NOW(), " .
				    "`expiry` = DATE_ADD(NOW(), INTERVAL " . $ttl . " SECOND)",
				    array(
				     bzcompress($response),
				     bzcompress(serialize($headers)),
				    )
				);
			    }
		    } //end if
	    } //end _cacheStore()


    } //end class

?>
