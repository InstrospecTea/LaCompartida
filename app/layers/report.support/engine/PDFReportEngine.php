<?php

require_once APP_PATH.'/dompdf/dompdf_config.inc.php';

class PDFReportEngine extends AbstractReportEngine implements IPDFReportEngine {

	var $engine;

	function __construct() {
		$this->engine = new DOMPDF();
	}

	protected function buildReport($data) {
		$this->engine->load_html($this->configuration['html']);
		$this->engine->render();
		$this->engine->stream($this->configuration['filename']);
	}

	protected function configurateReport() {
		$this->setConfiguration('html', $this->getHtmlMold());
	}

	private function getHtmlMold() {
return <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>{$this->configuration['title']}</title>
<style type="text/css">{$this->configuration['style']}</style>

</head>

<body>
<div id="header">
	{$this->configuration['header']}
</div>

<div id="footer">
	{$this->configuration['footer']}
</div>

{$this->configuration['content']}

</body></html>
HTML;
	}

}