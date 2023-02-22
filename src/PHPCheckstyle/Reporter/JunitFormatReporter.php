<?php
namespace PHPCheckstyle\Reporter;
use DOMDocument;
use DOMElement;
use DOMException;

/**
 * Writes the errors into an xml file.
 *
 * Format:
 * ================================
 * <checkstyle>
 * <file name="file1">
 * <error line="M" column="1" severity="error" message="error message"/>
 * </file>
 * <file name="file2">
 * <error line="X" message="error message"/>
 * <error line="Y" message="error message"/>
 * </file>
 * <file name="file3"/>
 * </checkstyle>
 * ================================
 *
 * @author Hari Kodungallur <hkodungallur@spikesource.com>
 */
class JunitFormatReporter extends Reporter {

    /**
     * @var DOMDocument
    */
	private $document = false;

    /**
     * @var DOMElement
     */
	private $root = false;

    /**
     * @var DOMElement
    */
	private $currentElement = false;

	private $ofile = "/style-junit.xml"; // The output file name

    private $time = 0;

	/**
	 * Constructor; calls parent's constructor
	 *
	 * @param bool $ofolder the
	 *        	folder name
	 */
	public function __construct($ofolder = false) {
		parent::__construct($ofolder, $this->ofile);
	}

	/**
	 *
	 * @see Reporter::start create the document root (<phpcheckstyle>)
	 *
	 */
	public function start() {
		$this->initXml();
	}

	/**
	 *
	 * @see Reporter::start add the last element to the tree and save the DOM tree to the
	 *      xml file
	 *
	 */
	public function stop() {
		$this->endCurrentElement();
		$this->document->save($this->outputFile);
	}

    /**
     *
     * @param string $phpFile the
     *            file currently processed
     * @throws DOMException
     * @see Reporter::currentlyProcessing add the previous element to the tree and start a new element
     *      for the new file
     *
     */
	public function currentlyProcessing($phpFile) {
		parent::currentlyProcessing($phpFile);
		$this->endCurrentElement();
		$this->startNewElement($phpFile);
	}

    /**
     * {@inheritdoc}
     *
     * @param Integer $line
     *            the line number
     * @param String $check
     *            the name of the check
     * @param String $message
     *            error message
     * @param String $level
     *            the severity level
     * @throws DOMException
     */
	public function writeError($line, $check, $message, $level = WARNING) {
        $level = strtoupper($level);
        $testcase = $this->document->createElement('testcase');
        $testcase->setAttribute('id',$check);
        $testcase->setAttribute('name',$check);
        $errEl = $this->document->createElement("failure");
		$errEl->setAttribute("type", $level);
		$errEl->setAttribute("message", $message);
        $errEl->textContent = "$level: $message
Category: $check
File: {$this->currentElement->getAttribute('name')}
Line: $line";
		if (empty($this->currentElement)) {
			$this->startNewElement("");
		}
        $testcase->appendChild($errEl);
		$this->currentElement->appendChild($testcase);
        $this->currentElement->setAttribute('tests', (int)$this->currentElement->getAttribute('tests') + 1);
        $this->currentElement->setAttribute('failures', (int)$this->currentElement->getAttribute('failures') + 1);
	}

    /**
     * XML header.
     * @throws DOMException
     */
	protected function initXml() {
		$this->document = new DOMDocument("1.0");
		$this->root = $this->document->createElement('testsuites');
        $this->root->setAttribute('id', md5(microtime(true)));
        $this->root->setAttribute('name', 'PHPCheckstyle Results (' . date('Y-m-d H:i:s') . ')');
        $this->root->setAttribute('tests', 0);
        $this->root->setAttribute('failures', 0);
        $this->root->setAttribute('time', 0);
        $this->document->appendChild($this->root);
	}

    /**
     * Creates a new file element.
     *
     * @param string $fileEl file
     * @throws DOMException
     */
	protected function startNewElement($fileEl) {
		$this->currentElement = $this->document->createElement("testsuite");
        $this->time = microtime(true);
		// remove the "./" at the beginning ot the path in case of relative path
		if (substr($fileEl, 0, 2) === './') {
			$fileEl = substr($fileEl, 2);
		}
		$this->currentElement->setAttribute("name", $fileEl);
		$this->currentElement->setAttribute("tests", 0);
		$this->currentElement->setAttribute("failures", 0);
	}

	/**
	 * Returns the document.
	 *
	 * @return DOMDocument object
	 */
	protected function getDocument() {
		return $this->document;
	}

	/**
	 * Close the current element.
	 */
	protected function endCurrentElement() {
        if ($this->currentElement) {
            $this->currentElement->setAttribute("time", microtime(true) - $this->time);
            $this->root->appendChild($this->currentElement);
            $this->root->setAttribute('tests', (int)$this->root->getAttribute('tests') + (int)$this->currentElement->getAttribute('tests'));
            $this->root->setAttribute('failures', (int)$this->root->getAttribute('failures') + (int)$this->currentElement->getAttribute('failures'));
            $this->root->setAttribute('time', (double)$this->root->getAttribute('time') + (double)$this->currentElement->getAttribute('time'));
        }
	}
}

