<?php

use mikehaertl\wkhtmlto\Pdf;

class WKPDFReportEngine extends AbstractReportEngine implements IWKPDFReportEngine {

  var $engine;

  function __construct() {
    $this->engine = new Pdf;
  }

  protected function buildReport($data) {
    $options = array(
      'encoding' => 'ISO-8859-1',
      'binary' => '/usr/local/bin/wkhtmltopdf'
    );
    $this->engine->setOptions($options);
    $this->engine->addPage($this->configuration['html']);
    $this->engine->send($this->configuration['filename'] . '.pdf');
    exit();
  }

  protected function configurateReport() {
    $this->setConfiguration('html', $this->getHtmlMold());
    $this->setConfiguration('htmlFooter', $this->getHtmlFooter());
  }

  private function getHtmlFooter() {
    return <<<HTML
<div id="footer">
  {$this->configuration['footer']}
</div>
HTML;
  }

  private function getHtmlMold() {
    return <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
<title>{$this->configuration['title']}</title>
<style type="text/css">{$this->configuration['style']}</style>
</head>
<body>
  <div id="header">
  {$this->configuration['header']}
  </div>
  {$this->configuration['content']}
</body></html>
HTML;
  }

}
