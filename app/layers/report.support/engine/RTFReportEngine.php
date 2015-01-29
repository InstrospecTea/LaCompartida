<?php

class RTFReportEngine extends AbstractReportEngine implements IRTFReportEngine {



	protected function buildReport($data) {
		$html = $this->configuration['html'];
		$filename = $this->configuration['filename'];
		header('Content-type: application/msword');
		header("Content-Disposition: attachment; filename={$filename}.rtf");
		echo $html;
		die();
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