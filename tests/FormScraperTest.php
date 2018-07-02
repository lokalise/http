<?php

/**
 * PHP version 5.6
 *
 * @package Logics\Tests\Foundation\HTTP
 */

namespace Logics\Tests\Foundation\HTTP;

use \Exception;
use \Logics\Foundation\HTTP\FormScraper;
use \PHPUnit_Framework_TestCase;

/**
 * Test for FormScraper class
 *
 * @author    Vladimir Bashkirtsev <vladimir@bashkirtsev.com>
 * @copyright 2013-2016 Vladimir Bashkirtsev
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date: 2017-01-05 15:16:14 +0000 (Thu, 05 Jan 2017) $ $Revision: 16 $
 * @link      $HeadURL: https://open.logics.net.au/foundation/HTTP/tags/0.1.4/tests/FormScraperTest.php $
 *
 * @runTestsInSeparateProcesses
 *
 * @donottranslate
 */

class FormScraperTest extends PHPUnit_Framework_TestCase
    {

	/**
	 * Testing object
	 *
	 * @var FormScraper
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
		$html  = "<html>";
		$html .= "<body>";
		$html .= "<form name=\"testform\" action=\"/post.php\">";
		$html .= "<input type=\"text\" name=\"field\" value=\"value\" id=\"fieldid\" />";
		$html .= "<input type=\"text\" name=\"anotherfield\" value=\"anothervalue\" readonly/>";
		$html .= "<input type=\"text\" name=\"extrafield\" value=\"extravalue\" disabled/>";
		$html .= "<input type=\"radio\" name=\"sex\" value=\"male\">Male";
		$html .= "<input type=\"radio\" name=\"sex\" value=\"female\">Female";
		$html .= "<select name=\"make\"><option value=\"volvo\" selected>Volvo</option><option value=\"saab\">Saab</option></select>";
		$html .= "<input type=\"hidden\" name=\"hiddenfield\" value=\"hiddenvalue\"/>";
		$html .= "<input type=\"submit\" value=\"submit\"/>";
		$html .= "<textarea name=\"testtext\">some text</textarea>";
		$html .= "</form>";
		$html .= "<form name=\"anotherform\"></form>";
		$html .= "</body>";
		$html .= "</html>";

		$this->object = new FormScraper($html);
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
	 * Test formExists()
	 *
	 * @return void
	 */

	public function testFormExists()
	    {
		$this->assertEquals(true, $this->object->formExists("testform"));
		$this->assertEquals(true, $this->object->formExists("anotherform"));
		$this->assertEquals(false, $this->object->formExists("nonexistentform"));
	    } //end testFormExists()


	/**
	 * Test validate() with non existent schema
	 *
	 * @return void
	 *
	 * @exceptioncode EXCEPTION_NO_SUCH_SCHEMA
	 */

	public function testValidateNoSchema()
	    {
		defined("EXCEPTION_NO_SUCH_SCHEMA") || define("EXCEPTION_NO_SUCH_SCHEMA", 1);

		$this->expectException(Exception::CLASS);
		$this->expectExceptionCode(EXCEPTION_NO_SUCH_SCHEMA);
		$this->assertEquals(true, $this->object->validate("testform"));
	    } //end testValidateNoSchema()


	/**
	 * Test validate()
	 *
	 * @return void
	 */

	public function testValidate()
	    {
		$this->assertEquals(true, $this->object->validate("testform", __DIR__ . "/testform.xsd"));
	    } //end testValidate()


	/**
	 * Test validate() non existent form
	 *
	 * @return void
	 *
	 * @exceptioncode EXCEPTION_NO_SUCH_FORM
	 */

	public function testValidateNonExistentForm()
	    {
		defined("EXCEPTION_NO_SUCH_FORM") || define("EXCEPTION_NO_SUCH_FORM", 1);

		$this->expectException(Exception::CLASS);
		$this->expectExceptionCode(EXCEPTION_NO_SUCH_FORM);
		$this->assertEquals(true, $this->object->validate("nonexistentform", __DIR__ . "/testform.xsd"));
	    } //end testValidateNonExistentForm()


	/**
	 * Test validate() wrong form
	 *
	 * @return void
	 *
	 * @exceptioncode EXCEPTION_CANNOT_VALIDATE
	 */

	public function testValidateWrongForm()
	    {
		defined("EXCEPTION_CANNOT_VALIDATE") || define("EXCEPTION_CANNOT_VALIDATE", 1);

		$this->expectException(Exception::CLASS);
		$this->expectExceptionCode(EXCEPTION_CANNOT_VALIDATE);
		$this->assertEquals(true, $this->object->validate("anotherform", __DIR__ . "/testform.xsd"));
	    } //end testValidateWrongForm()


	/**
	 * Test __get() and __isset() magic methods
	 *
	 * @return void
	 */

	public function testMagicMethods()
	    {
		$this->assertEquals(true, $this->object->validate("testform", __DIR__ . "/testform.xsd"));
		$this->assertEquals(true, isset($this->object->hiddenfield));
		$this->assertEquals(false, isset($this->object->nonexistentfield));
		$this->assertEquals("hiddenvalue", $this->object->hiddenfield);
		$this->assertNull($this->object->nonexistentfield);
	    } //end testMagicMethods()


	/**
	 * Test getActionURI()
	 *
	 * @return void
	 */

	public function testGetActionURI()
	    {
		$this->assertEquals(true, $this->object->validate("testform", __DIR__ . "/testform.xsd"));
		$this->assertEquals("/post.php", $this->object->getActionURI());
		$this->assertEquals(true, $this->object->validate("anotherform", __DIR__ . "/anotherform.xsd"));
		$this->assertEquals("", $this->object->getActionURI());
	    } //end testGetActionURI()


	/**
	 * Test getFields()
	 *
	 * @return void
	 */

	public function testGetFields()
	    {
		$this->assertEquals(true, $this->object->validate("testform", __DIR__ . "/testform.xsd"));
		$this->assertEquals(array(
				     "hiddenfield"  => "hiddenvalue",
				     "sex"          => "",
				     "make"         => "volvo",
				     "anotherfield" => "anothervalue",
				     "extrafield"   => "extravalue",
				     "field"        => "value",
				     "testtext"     => "some text",
				    ), $this->object->getFields());
		$this->assertEquals(true, $this->object->validate("anotherform", __DIR__ . "/anotherform.xsd"));
		$this->assertEquals(array(), $this->object->getFields());
	    } //end testGetFields()


	/**
	 * Test getOptions()
	 *
	 * @return void
	 */

	public function testGetOptions()
	    {
		$this->assertEquals(array(
				     array(
				      "value"    => "volvo",
				      "text"     => "Volvo",
				      "selected" => true,
				     ),
				     array(
				      "value"    => "saab",
				      "text"     => "Saab",
				      "selected" => false,
				     ),
				    ), $this->object->getOptions("testform", "make"));
		$this->assertEquals(array(), $this->object->getOptions("anotherform", "make"));
	    } //end testGetOptions()


	/**
	 * Test getInputValueById()
	 *
	 * @return void
	 */

	public function testGetInputValueById()
	    {
		$this->assertEquals("value", $this->object->getInputValueById("testform", "fieldid"));
		$this->assertEquals("", $this->object->getInputValueById("anotherform", "fieldid"));
		$this->assertInternalType("string", $this->object->getInputValueById("anotherform", "fieldid"));
	    } //end testGetInputValueById()


	/**
	 * Test isReadonly()
	 *
	 * @return void
	 */

	public function testIsReadonly()
	    {
		$this->assertEquals(false, $this->object->isReadonly("testform", "field"));
		$this->assertEquals(true, $this->object->isReadonly("testform", "anotherfield"));
		$this->assertEquals(false, $this->object->isReadonly("anotherform", "anotherfield"));
	    } //end testIsReadonly()


	/**
	 * Test isDisabled()
	 *
	 * @return void
	 */

	public function testIsDisabled()
	    {
		$this->assertEquals(false, $this->object->isDisabled("testform", "field"));
		$this->assertEquals(true, $this->object->isDisabled("testform", "extrafield"));
		$this->assertEquals(false, $this->object->isDisabled("anotherform", "extrafield"));
	    } //end testIsDisabled()


    } //end class

?>
