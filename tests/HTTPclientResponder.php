<?php

/**
 * PHP version 5.6
 *
 * @package Logics\Tests\Foundation\HTTP
 */

namespace Logics\Tests\Foundation\HTTP;

/**
 * This is a HTTP responder for HTTPclient and HTTPclientWithCache tests. It will be contacted by tests with various parameters and will return relevant responses
 *
 * @author    Vladimir Bashkirtsev <vladimir@bashkirtsev.com>
 * @copyright 2013-2016 Vladimir Bashkirtsev
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date: 2016-08-17 14:51:40 +0000 (Wed, 17 Aug 2016) $ $Revision: 2 $
 * @link      $HeadURL: https://open.logics.net.au/foundation/HTTP/tags/0.1.4/tests/HTTPclientResponder.php $
 *
 * @donottranslate
 */

if (isset($GLOBALS["_GET"]["Cache-Control"]) === true)
    {
	header("Cache-Control: " . $GLOBALS["_GET"]["Cache-Control"]);
    }

if (isset($GLOBALS["_GET"]["Expires"]) === true)
    {
	header("Expires: " . $GLOBALS["_GET"]["Expires"]);
    }

echo "Method: " . $_SERVER["REQUEST_METHOD"] . "<br/>";
echo "User-agent: " . $_SERVER["HTTP_USER_AGENT"] . "<br/>";
echo "Time: " . date("Y-m-d H:i:s") . "<br/>";

$headers = getallheaders();
foreach ($headers as $name => $value)
    {
	echo "HEADER: " . $name . " = " . str_replace("\n", "", var_export($value, true)) . "<br/>";
    }

foreach ($GLOBALS["_GET"] as $name => $value)
    {
	echo "GET: " . $name . " = " . str_replace("\n", "", var_export($value, true)) . "<br/>";
    }

foreach ($GLOBALS["_POST"] as $name => $value)
    {
	echo "POST: " . $name . " = " . str_replace("\n", "", var_export($value, true)) . "<br/>";
    }

echo "Request body: " . file_get_contents("php://input");

?>
