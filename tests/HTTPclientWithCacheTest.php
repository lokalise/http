<?php

/**
 * PHP version 5.6
 *
 * @package Logics\Tests\Foundation\HTTP
 */

namespace Logics\Tests\Foundation\HTTP;

use \DateInterval;
use \DateTime;
use \Logics\Foundation\HTTP\HTTPclientWithCache;
use \Logics\Foundation\SQL\MySQLdatabase;
use \Logics\Tests\GetConnectionMySQL;
use \Logics\Tests\InternalWebServer;
use \Logics\Tests\PHPUnit_Extensions_Database_SQL_TestCase;

/**
 * Test for HTTPclientWithCache class
 *
 * @author    Vladimir Bashkirtsev <vladimir@bashkirtsev.com>
 * @copyright 2013-2016 Vladimir Bashkirtsev
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date: 2016-08-17 14:51:40 +0000 (Wed, 17 Aug 2016) $ $Revision: 2 $
 * @link      $HeadURL: https://open.logics.net.au/foundation/HTTP/tags/0.1.4/tests/HTTPclientWithCacheTest.php $
 *
 * @donottranslate
 */

class HTTPclientWithCacheTest extends PHPUnit_Extensions_Database_SQL_TestCase
    {

	use GetConnectionMySQL;

	use InternalWebServer;

	/**
	 * Database to use
	 *
	 * @var SQLdatabase
	 */
	private $_db;

	/**
	 * Name folder which should be removed after tests
	 *
	 * @var string
	 */
	protected $remotepath;

	/**
	 * Testing object
	 *
	 * @var HTTPclientWithCache
	 */
	protected $object;

	/**
	 * Get test data set
	 *
	 * @return dataset
	 */

	public function getDataSet()
	    {
		return $this->createFlatXmlDataSet(__DIR__ . "/testHTTPclientWithCacheTable.xml");
	    } //end getDataSet()


	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */

	protected function setUp()
	    {
		$this->_db = new MySQLdatabase($GLOBALS["DB_HOST"], $GLOBALS["DB_DBNAME"], $GLOBALS["DB_USER"], $GLOBALS["DB_PASSWD"]);
		$this->_db->execUntilSuccessful("DROP TABLE IF EXISTS `HTTPcache`");

		$this->remotepath = $this->webserverURL();
		$this->object     = new HTTPclientWithCache($this->_db, $this->remotepath . "/HTTPclientResponder.php");

		parent::setUp();
	    } //end setUp()


	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */

	protected function tearDown()
	    {
		unset($this->object);
		$this->_db->execUntilSuccessful("DROP TABLE IF EXISTS `FailedDocuments`");
		$this->_db->execUntilSuccessful("DROP TABLE IF EXISTS `HTTPcache`");
	    } //end tearDown()


	/**
	 * Testing HTTP GET
	 *
	 * @return void
	 */

	public function testGet()
	    {
		$page = $this->object->get();
		$this->assertInternalType("string", $page);

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("param" => "value"),
		    array("HTTPclientWithCacheTest-headers" => "value"),
		    array("useragent" => "HTTPclientWithCacheTest"),
		    10
		);
		$page         = $this->object->get();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("User-agent: HTTPclientWithCacheTest", $page);
		$this->assertContains("HTTPclientWithCacheTest-headers = 'value'", $page);
		$this->assertContains("GET: param = 'value'", $page);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "one"))
		);
		$page = $this->object->get();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("User-agent: HTTPclientWithCacheTest", $page);
		$this->assertContains("HTTPclientWithCacheTest-headers = 'one'", $page);
		$this->assertRegExp("/GET: param =.*('one'|'two')/", $page);

		sleep(5);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "two"))
		);
		$cached = $this->object->get();
		$this->assertInternalType("string", $cached);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertEquals($page, $cached);

		sleep(10);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "two"))
		);
		$cached = $this->object->get();
		$this->assertInternalType("string", $cached);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertNotEquals($page, $cached);

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/nonexistentResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "two")),
		    array("useragent" => "HTTPclientWithCacheTest")
		);
		$page         = $this->object->get();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(404, $lastcode);
	    } //end testGet()


	/**
	 * Testing HTTP POST
	 *
	 * @return void
	 */

	public function testPost()
	    {
		$page = $this->object->post();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("param" => "value"),
		    array("HTTPclientWithCacheTest-headers" => "value"),
		    array("useragent" => "HTTPclientWithCacheTest"),
		    10
		);
		$page         = $this->object->post();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("User-agent: HTTPclientWithCacheTest", $page);
		$this->assertContains("HTTPclientWithCacheTest-headers = 'value'", $page);
		$this->assertContains("POST: param = 'value'", $page);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "one"))
		);
		$page = $this->object->post();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("User-agent: HTTPclientWithCacheTest", $page);
		$this->assertContains("HTTPclientWithCacheTest-headers = 'one'", $page);
		$this->assertRegExp("/POST: param =.*('one'|'two')/", $page);

		sleep(5);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "two"))
		);
		$cached = $this->object->post();
		$this->assertInternalType("string", $cached);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertEquals($page, $cached);

		sleep(10);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "two"))
		);
		$cached = $this->object->post();
		$this->assertInternalType("string", $cached);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertNotEquals($page, $cached);

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/nonexistentResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientWithCacheTest-headers" => array("one", "two")),
		    array("useragent" => "HTTPclientWithCacheTest")
		);
		$page         = $this->object->post();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(404, $lastcode);
	    } //end testPost()


	/**
	 * Test Expires header with bad date/time. Should expire responses immediately
	 *
	 * @return void
	 */

	public function testBadExpires()
	    {
		$page = $this->_callResponder("sometime");

		sleep(5);

		$cached = $this->_callResponder("sometime");
		$this->assertNotEquals($page, $cached);
	    } //end testBadExpires()


	/**
	 * Test Expires header with date/time in the past. Should expire responses immediately
	 *
	 * @return void
	 */

	public function testExpiresInThePast()
	    {
		$expires  = new DateTime("now");
		$interval = new DateInterval("PT10S");
		$expires->sub($interval);

		$page = $this->_callResponder($expires->format(DateTime::RFC1123));

		sleep(5);

		$cached = $this->_callResponder($expires->format(DateTime::RFC1123));
		$this->assertNotEquals($page, $cached);
	    } //end testExpiresInThePast()


	/**
	 * Test Expires header with date/time in the future
	 *
	 * @return void
	 */

	public function testExpiresInTheFuture()
	    {
		$expires  = new DateTime("now");
		$interval = new DateInterval("PT20S");
		$expires->add($interval);

		$page = $this->_callResponder($expires->format(DateTime::RFC1123));

		sleep(5);

		$cached = $this->_callResponder($expires->format(DateTime::RFC1123));
		$this->assertEquals($page, $cached);

		sleep(20);

		$cached = $this->_callResponder($expires->format(DateTime::RFC1123));
		$this->assertNotEquals($page, $cached);
	    } //end testExpiresInTheFuture()


	/**
	 * Call HTTPclient responder with Expires parameter and check successful response
	 *
	 * @param string $expires Content of Expires parameter
	 *
	 * @return string HTTP response from responder
	 */

	private function _callResponder($expires)
	    {
		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("Expires" => $expires)
		);
		$page         = $this->object->get();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("GET: Expires = '" . $expires . "'", $page);
		return $page;
	    } //end _callResponder()


	/**
	 * Test response expiration with Cache-Control: max-age= . Should disregard Expires header
	 *
	 * @return void
	 */

	public function testCacheControlMaxAge()
	    {
		$expires = new DateTime("now");

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/HTTPclientResponder.php",
		    array(
		     "Expires"       => $expires->format(DateTime::RFC1123),
		     "Cache-Control" => "max-age=10",
		    )
		);
		$page         = $this->object->get();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("GET: Expires = '" . $expires->format(DateTime::RFC1123) . "'", $page);

		sleep(5);

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/HTTPclientResponder.php",
		    array(
		     "Expires"       => $expires->format(DateTime::RFC1123),
		     "Cache-Control" => "max-age=10",
		    )
		);
		$cached       = $this->object->get();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("GET: Expires = '" . $expires->format(DateTime::RFC1123) . "'", $page);
		$this->assertEquals($page, $cached);

		sleep(10);

		$this->object = new HTTPclientWithCache(
		    $this->_db,
		    $this->remotepath . "/HTTPclientResponder.php",
		    array(
		     "Expires"       => $expires->format(DateTime::RFC1123),
		     "Cache-Control" => "max-age=10",
		    )
		);
		$cached       = $this->object->get();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("GET: Expires = '" . $expires->format(DateTime::RFC1123) . "'", $page);
		$this->assertNotEquals($page, $cached);
	    } //end testCacheControlMaxAge()


    } //end class

?>
