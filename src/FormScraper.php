<?php

/**
 * PHP version 5.6
 *
 * @package Logics\Foundation\HTTP
 */

namespace Logics\Foundation\HTTP;

use \DOMDocument;
use \DOMElement;
use \DOMXPath;
use \Exception;
use \Logics\Foundation\BaseLib\ReadOnlyProperties;
use \Logics\Foundation\XML\XMLerrors;

/**
 * Class for web scraping and validation of forms
 *
 * @author    Vladimir Bashkirtsev <vladimir@bashkirtsev.com>
 * @copyright 2013-2016 Vladimir Bashkirtsev
 * @license   https://opensource.org/licenses/MIT MIT License
 * @version   SVN: $Date: 2016-08-17 14:51:40 +0000 (Wed, 17 Aug 2016) $ $Revision: 2 $
 * @link      $HeadURL: https://open.logics.net.au/foundation/HTTP/tags/0.1.4/src/FormScraper.php $
 */

class FormScraper
    {

	use XMLerrors;

	use ReadOnlyProperties;

	/**
	 * DOMDocument containing original HTML page to process
	 *
	 * @var DOMDocument
	 */
	private $_dom;

	/**
	 * Form action URI
	 *
	 * @var string
	 */
	private $_actionURI;

	/**
	 * Web scraper constructor
	 *
	 * @param string $page Original HTML page to process
	 *
	 * @return void
	 */

	public function __construct($page)
	    {
		$this->prepareForXMLerrors();

		$this->_dom = new DOMDocument();
		$this->_dom->loadHTML($page);

		$this->clearXMLerrors();

		$this->_actionURI = "";
	    } //end __construct()


	/**
	 * Check if form exists in original page
	 *
	 * @param string $form Name of the form to look for
	 *
	 * @return boolean True if form exists in the page
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 */

	public function formExists($form)
	    {
		$xpath = new DOMXPath($this->_dom);
		$list  = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']"));
		return ($list->length > 0);
	    } //end formExists()


	/**
	 * Validate form against schema
	 *
	 * @param string $form   Name of the form to process
	 * @param string $schema Schema name, if not given then form name is used
	 *
	 * @return boolean True if validation is successful
	 *
	 * @throws Exception No schema is found
	 *
	 * @exceptioncode EXCEPTION_NO_SUCH_SCHEMA
	 * @exceptioncode EXCEPTION_CANNOT_VALIDATE
	 *
	 * @untranslatable forms/
	 * @untranslatable , DOM:
	 */

	public function validate($form, $schema = "")
	    {
		if ($schema === "")
		    {
			$schema = "forms/" . $form . ".xsd";
		    }

		if (file_exists($schema) === false)
		    {
			throw new Exception(_("No schema named") . " " . $schema . " " . _("exists"), EXCEPTION_NO_SUCH_SCHEMA);
		    }

		$dom = $this->_buildDOM($form);

		$this->prepareForXMLerrors();

		if ($dom->schemaValidate($schema) === false)
		    {
			$s = $this->getXMLerrors();

			$this->clearXMLerrors();

			throw new Exception(_("Cannot validate against") . " " . $schema . ": " . $s . ", DOM: " . $dom->saveXML(), EXCEPTION_CANNOT_VALIDATE);
		    }
		else
		    {
			$this->clearXMLerrors();
			return true;
		    }
	    } //end validate()


	/**
	 * Build DOM to validate from form on original page
	 *
	 * @param string $form Name of the form to process
	 *
	 * @return DOMDocument DOM containing form parameters
	 *
	 * @throws Exception No form is found in original page
	 *
	 * @exceptioncode EXCEPTION_NO_SUCH_FORM
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable action
	 * @untranslatable NONAME
	 */

	private function _buildDOM($form)
	    {
		if ($this->formExists($form) === false)
		    {
			throw new Exception(_("No form named") . " " . $form . " " . _("exists"), EXCEPTION_NO_SUCH_FORM);
		    }

		$dom  = new DOMDocument();
		$root = $dom->createElement("form");
		$dom->appendChild($root);

		$xpath = new DOMXPath($this->_dom);

		$list             = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']"));
		$this->_actionURI = $list->item(0)->getAttribute("action");

		$inputs = $this->_getInputsAndButtons($form, $xpath);
		$inputs = array_merge($inputs, $this->_getTextareas($form, $xpath));
		$inputs = array_merge($inputs, $this->_getSelects($form, $xpath));

		ksort($inputs);
		$this->readonlyproperties = array();
		foreach ($inputs as $type => $input)
		    {
			$container = $dom->createElement($type);
			$root->appendChild($container);
			ksort($input);
			foreach ($input as $name => $value)
			    {
				$name = (($name === "") ? "NONAME" : $name);
				$this->_processNVpair($dom, $container, $name, $value);
				unset($this->readonlyproperties["NONAME"]);
			    } //end foreach
		    } //end foreach

		return $dom;
	    } //end _buildDOM()


	/**
	 * Process name/value pair from the form. Append it to container for further validation and to $this->readonlyproperties for later use
	 *
	 * @param DOMDocument $dom       DOMDocument holding containers
	 * @param DOMElement  $container XML container for parsed form elements
	 * @param string      $name      Form element name
	 * @param mixed       $value     String or array of strings containing elements values
	 *
	 * @return void
	 *
	 * @untranslatable selected
	 * @untranslatable true
	 */

	private function _processNVpair(DOMDocument $dom, DOMElement $container, $name, $value)
	    {
		if (is_array($value) === true)
		    {
			asort($value);
			$selected = ((isset($value["selected"]) === true) ? $value["selected"] : false);
			if ($selected === false)
			    {
				$this->readonlyproperties[$name] = "";
			    }

			unset($value["selected"]);
			foreach ($value as $option)
			    {
				$element = $dom->createElement(preg_replace("/[^\w\d]/", "", $name), $option);
				if ($selected === $option)
				    {
					$element->setAttribute("selected", "true");
					$this->readonlyproperties[$name] = $option;
				    }

				$container->appendChild($element);
			    }
		    }
		else
		    {
			$container->appendChild($dom->createElement(preg_replace("/[^\w\d]/", "", $name), $value));
			$this->readonlyproperties[$name] = $value;
		    } //end if
	    } //end _processNVpair()


	/**
	 * Get input and button fields as array
	 *
	 * @param string   $form  Name of the form to process
	 * @param DOMXPath $xpath XPath representing original HTML document
	 *
	 * @return array containing input and button fields
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //input|
	 * @untranslatable //button
	 * @untranslatable type
	 * @untranslatable name
	 * @untranslatable value
	 */

	private function _getInputsAndButtons($form, DOMXPath $xpath)
	    {
		$inputs = array();

		$list = $xpath->query(
		    "//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//input|" .
		    "//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//button"
		);
		foreach ($list as $input)
		    {
			$type  = $input->getAttribute("type");
			$name  = $input->getAttribute("name");
			$value = $input->getAttribute("value");
			if (isset($inputs[$type][$name]) === true)
			    {
				if (is_array($inputs[$type][$name]) === false)
				    {
					$inputs[$type][$name] = array($inputs[$type][$name]);
				    }

				$inputs[$type][$name][] = $value;
			    }
			else
			    {
				$inputs[$type][$name] = $value;
			    }
		    }

		return $inputs;
	    } //end _getInputsAndButtons()


	/**
	 * Get textarea fields as array
	 *
	 * @param string   $form  Name of the form to process
	 * @param DOMXPath $xpath XPath representing original HTML document
	 *
	 * @return array containing textarea fields
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //textarea
	 * @untranslatable name
	 */

	private function _getTextareas($form, DOMXPath $xpath)
	    {
		$inputs = array();

		$list = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//textarea");
		foreach ($list as $input)
		    {
			$inputs["textarea"][$input->getAttribute("name")] = $input->nodeValue;
		    }

		return $inputs;
	    } //end _getTextareas()


	/**
	 * Get select fields as array
	 *
	 * @param string   $form  Name of the form to process
	 * @param DOMXPath $xpath XPath representing original HTML document
	 *
	 * @return array containing select fields
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //select
	 * @untranslatable option
	 * @untranslatable selected
	 * @untranslatable name
	 * @untranslatable value
	 */

	private function _getSelects($form, DOMXPath $xpath)
	    {
		$inputs = array();

		$list = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//select");
		foreach ($list as $input)
		    {
			foreach ($input->getElementsByTagName("option") as $option)
			    {
				if ($option->hasAttribute("selected") === true)
				    {
					$inputs["select"][$input->getAttribute("name")]["selected"] = $option->getAttribute("value");
				    }

				$inputs["select"][$input->getAttribute("name")][] = $option->getAttribute("value");
			    }
		    }

		return $inputs;
	    } //end _getSelects()


	/**
	 * Get action URI from last validated form
	 *
	 * @return string containing action URI
	 */

	public function getActionURI()
	    {
		return $this->_actionURI;
	    } //end getActionURI()


	/**
	 * Get current fields from last validated form
	 *
	 * @return array containing field values
	 */

	public function getFields()
	    {
		return $this->readonlyproperties;
	    } //end getFields()


	/**
	 * Get list of options for a particular select
	 *
	 * @param string $form   Name of the form to look for
	 * @param string $select Name of select to get options for
	 *
	 * @return array containing list of option values, texts and selected flags
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //select[@name='
	 * @untranslatable option
	 * @untranslatable value
	 * @untranslatable selected
	 */

	public function getOptions($form, $select)
	    {
		$xpath  = new DOMXPath($this->_dom);
		$list   = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//select[@name='" . $select . "']");
		$result = array();
		foreach ($list as $input)
		    {
			foreach ($input->getElementsByTagName("option") as $option)
			    {
				$line["value"]    = $option->getAttribute("value");
				$line["text"]     = trim($option->nodeValue);
				$line["selected"] = ($option->hasAttribute("selected") === true);

				$result[] = $line;
			    }
		    }

		return $result;
	    } //end getOptions()


	/**
	 * Get input value by ID
	 *
	 * @param string $form Name of the form to look for
	 * @param string $id   ID of input field
	 *
	 * @return string Value of input field
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //input[@id='
	 * @untranslatable value
	 */

	public function getInputValueById($form, $id)
	    {
		$xpath = new DOMXPath($this->_dom);
		$list  = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//input[@id='" . $id . "']");
		if ($list->length > 0)
		    {
			return $list->item(0)->getAttribute("value");
		    }
		else
		    {
			return "";
		    }
	    } //end getInputValueById()


	/**
	 * Check if form element is read only
	 *
	 * @param string $form    Name of the form to look for
	 * @param string $element Name of element to check
	 *
	 * @return boolean True if element is read only
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //*[@name='
	 * @untranslatable ' and @readonly]
	 */

	public function isReadOnly($form, $element)
	    {
		$xpath = new DOMXPath($this->_dom);
		$list  = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//*[@name='" . $element . "' and @readonly]");
		return ($list->length > 0);
	    } //end isReadOnly()


	/**
	 * Check if form element is disabled
	 *
	 * @param string $form    Name of the form to look for
	 * @param string $element Name of element to check
	 *
	 * @return boolean True if element is disabled
	 *
	 * @untranslatable //form
	 * @untranslatable [@name='
	 * @untranslatable //*[@name='
	 * @untranslatable ' and @disabled]
	 */

	public function isDisabled($form, $element)
	    {
		$xpath = new DOMXPath($this->_dom);
		$list  = $xpath->query("//form" . (($form === "") ? "" : "[@name='" . $form . "']") . "//*[@name='" . $element . "' and @disabled]");
		return ($list->length > 0);
	    } //end isDisabled()


    } //end class

?>
