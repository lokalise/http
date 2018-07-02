<?php

/**
 * PHP version 5.6
 *
 * @package Logics\Tests\Foundation\HTTP
 */

namespace Logics\Tests\Foundation\HTTP;

use \Logics\Foundation\HTTP\HTTPclient;
use \Logics\Tests\InternalWebServer;
use \PHPUnit_Framework_TestCase;

/**
 * Test for HTTPclient class
 *
 * @author    Vladimir Bashkirtsev <vladimir@bashkirtsev.com>
 * @copyright 2013-2016 Vladimir Bashkirtsev
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date: 2016-08-17 14:51:40 +0000 (Wed, 17 Aug 2016) $ $Revision: 2 $
 * @link      $HeadURL: https://open.logics.net.au/foundation/HTTP/tags/0.1.4/tests/HTTPclientTest.php $
 *
 * @donottranslate
 */

class HTTPclientTest extends PHPUnit_Framework_TestCase
    {

	use InternalWebServer;

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
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */

	protected function setUp()
	    {
		$this->remotepath = $this->webserverURL();
		$this->object     = new HTTPclient($this->remotepath . "/HTTPclientResponder.php");
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
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);

		$this->object = new HTTPclient(
		    $this->remotepath . "/HTTPclientResponder.php?inline=value",
		    array("param" => "value"),
		    array("HTTPclientTest-headers" => "value"),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->get();
		$this->assertContains("Method: GET", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'value'", $page);
		$this->assertContains("GET: inline = 'value'", $page);
		$this->assertContains("GET: param = 'value'", $page);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "one"))
		);
		$page = $this->object->get();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: GET", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'one'", $page);
		$this->assertRegExp("/GET: param =.*('one'|'two')/", $page);

		$this->object = new HTTPclient(
		    $this->remotepath . "/nonexistentResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "two")),
		    array("useragent" => "HTTPclientTest")
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

		$this->object = new HTTPclient(
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("param" => "value"),
		    array("HTTPclientTest-headers" => "value"),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->post();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: POST", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'value'", $page);
		$this->assertContains("POST: param = 'value'", $page);

		$this->object = new HTTPclient(
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("" => "<xml>"),
		    array("HTTPclientTest-headers" => "value"),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->post();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: POST", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'value'", $page);
		$this->assertContains("Request body: <xml>", $page);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "one"))
		);
		$page = $this->object->post();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: POST", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'one'", $page);
		$this->assertRegExp("/POST: param =.*('one'|'two')/", $page);

		$this->object = new HTTPclient(
		    $this->remotepath . "/nonexistentResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "two")),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->post();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(404, $lastcode);
	    } //end testPost()


	/**
	 * Testing HTTP PUT
	 *
	 * @return void
	 */

	public function testPut()
	    {
		$page = $this->object->put();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);

		$this->object = new HTTPclient(
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("param" => "value"),
		    array("HTTPclientTest-headers" => "value"),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->put();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: PUT", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'value'", $page);
		$this->assertContains("POST: param = 'value'", $page);

		$this->object = new HTTPclient(
		    $this->remotepath . "/HTTPclientResponder.php",
		    array("" => "<xml>"),
		    array("HTTPclientTest-headers" => "value"),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->put();
		$lastcode     = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: PUT", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'value'", $page);
		$this->assertContains("Request body: <xml>", $page);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "one"))
		);
		$page = $this->object->put();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: PUT", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'one'", $page);
		$this->assertRegExp("/POST: param =.*('one'|'two')/", $page);

		$this->object = new HTTPclient(
		    $this->remotepath . "/nonexistentResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "two")),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->put();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(404, $lastcode);
	    } //end testPut()


	/**
	 * Testing HTTP DELETE
	 *
	 * @return void
	 */

	public function testDelete()
	    {
		$page = $this->object->delete();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);

		$this->object = new HTTPclient(
		    $this->remotepath . "/HTTPclientResponder.php?inline=value",
		    array("param" => "value"),
		    array("HTTPclientTest-headers" => "value"),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->delete();
		$this->assertContains("Method: DELETE", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'value'", $page);
		$this->assertContains("GET: inline = 'value'", $page);
		$this->assertContains("GET: param = 'value'", $page);

		$this->object->setRequest(
		    "HTTPclientResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "one"))
		);
		$page = $this->object->delete();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(200, $lastcode);
		$this->assertContains("Method: DELETE", $page);
		$this->assertContains("User-agent: HTTPclientTest", $page);
		$this->assertContains("HTTPclientTest-headers = 'one'", $page);
		$this->assertRegExp("/GET: param =.*('one'|'two')/", $page);

		$this->object = new HTTPclient(
		    $this->remotepath . "/nonexistentResponder.php",
		    array("param" => array("one", "two")),
		    array("HTTPclientTest-headers" => array("one", "two")),
		    array("useragent" => "HTTPclientTest")
		);
		$page         = $this->object->delete();
		$this->assertInternalType("string", $page);
		$lastcode = $this->object->lastcode();
		$this->assertInternalType("int", $lastcode);
		$this->assertEquals(404, $lastcode);
	    } //end testDelete()


    } //end class

?>
