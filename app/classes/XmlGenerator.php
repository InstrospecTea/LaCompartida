<?php

require_once dirname(__FILE__) . '/../conf.php';

class XmlGenerator {

	public $data;
	public $Session;
	public $xmlBody;
	public $zIndex = 0;
	private $mmX = 0;
	private $mmY = 0;
	static $mm2pt = 2.8346456692895527;
	static $mmtotwip = 56.692913;

	public function __construct($session) {
		$this->Session = $session;
	}

	public function outputXml($id_factura, $xmlTemplate, $filename) {
		$Factura = new Factura($this->Session);
		if (!$Factura->Load($id_factura)) {
			echo "<html><head><title>Error</title></head><body><p>No se encuentra la factura $id_factura.</p></body></html>";
			return;
		}

		$pdf = new FacturaPdfDatos($this->Session);
		$pdf->CargarDatos($id_factura, $Factura->fields['id_documento_legal'], true);
		$this->data = $pdf->datos;

		if (!empty($this->data['correccion_mm'])) {
			$this->mmX = $this->data['correccion_mm']['coordinateX'];
			$this->mmY = $this->data['correccion_mm']['coordinateY'];
			unset($this->data['correccion_mm']);
		}

		foreach ($this->data as $field => $shape) {
			$this->addShape($shape);
		}

		$this->xmlBody = sprintf('<w:pict>%s</w:pict>', $this->xmlBody);
		$xml = str_replace('%app_name%', APPNAME, $xmlTemplate);
		$xml = str_replace('%created%', date(DATE_ISO8601), $xml);
		$this->addPageSize($pdf->papel);
		$xml = str_replace('%xml_body%', $this->xmlBody, $xml);

		header("Content-Type: application/msword; charset=ISO-8859-1");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		echo $xml;
	}

	private function addPageSize($size) {
		$w = $this->mm2twip($size['cellW']);
		$h = $this->mm2twip($size['cellH']);
		$this->xmlBody .= sprintf('<w:sectPr><w:pgSz w:w="%d" w:h="%d" w:orient="landscape"/></w:sectPr>', $w, $h);
	}

	private function addShape($data) {
		$data['dato_letra'] = preg_replace("/<br ?\/?>\n?/", "\n", $data['dato_letra']);
		$textBoxTpl = '<v:textbox inset="0,0,0,0"><w:txbxContent>%s</w:txbxContent></v:textbox>';
		$text = $this->parseText($data);
		$textBox = sprintf($textBoxTpl, $text);

		$shapeSizePos = $this->shapeSizePosition($data);
		$shapeTpl = '<v:shape style="position:absolute;mso-position-horizontal-relative:page;mso-position-vertical-relative:page;%s">%s<w10:wrap anchorx="page" anchory="page"/></v:shape>';

		$this->xmlBody .= sprintf($shapeTpl, $shapeSizePos, $textBox);
	}

	private function textFormat($data) {
		$data['style'] = strtolower($data['style']);
		$extraStype = $data['style'] == 'u' ? ' w:val="single"' : '';
		$style = empty($data['style']) ? '' : "<w:{$data['style']}{$extraStype}/>";
		$font = vsprintf('<w:rFonts w:ascii="%s" w:h-ansi="%s" w:cs="%s"/><wx:font wx:val="%s"/>', array($data['font'], $data['font'], $data['font'], $data['font']));
		$fontSize = sprintf('<w:sz w:val="%s"/>', $data['tamano'] * 2);
		return "<w:rPr>{$font}{$fontSize}{$style}</w:rPr>";
	}

	private function ulnText($text, $uln) {
		if ($uln == 'may') {
			$text = strtoupper($text);
		} else if ($uln == 'min') {
			$text = strtolower($text);
		}
		return $text;

	}

	private function textLine($line, $format) {
		$lineBlankTpl = '<w:p><w:pPr><w:rPr>%s</w:rPr></w:pPr></w:p>';
		$lineTextTpl = '<w:p><w:r>%s<w:t>%s</w:t></w:r></w:p>';
		if (empty($line)) {
			return sprintf($lineBlankTpl, $format);
		} else {
			return sprintf($lineTextTpl, $format, $line);
		}
	}

	private function parseText($data) {
		$textFormat = $this->textFormat($data);
		$text = $this->ulnText($data['dato_letra'], $data['mayuscula']);
		$arrayText = explode("\n", $text);
		if (count($arrayText) == 1) {
			$this->textLine($text, $textFormat);
		}

		$totalArrayText = count($arrayText);
		$xmlText = '';
		for ($x = 0; $x < $totalArrayText; ++$x) {
			$line = trim($arrayText[$x]);
			$xmlText .= $this->textLine($line, $textFormat);
		}
		return $xmlText;
	}

	private function shapeSizePosition($data) {
		++$this->zIndex;
		$x = $this->mm2pt($data['coordinateX'] + $this->mmX);
		$y = $this->mm2pt($data['coordinateY'] + $this->mmY);
		$textLines = count(explode("\n", $data['dato_letra']));
		$ssp = array(
			"margin-left:{$x}pt",
			"margin-top:{$y}pt",
			'width:' . $this->autoSize($data['cellW'], 'H', strlen($data['dato_letra']), $data['tamano']),
			'height:' . $this->autoSize($data['cellH'], 'V', $textLines, $data['tamano']),
			"z-index:{$this->zIndex}"
		);
		return implode(';', $ssp);
	}

	private function autoSize($valor, $hv, $totalChar, $fontSize) {
		if (!empty($valor)) {
			$valor = $this->mm2pt($valor);
			return "{$valor}pt";
		}

		if ($hv == 'H') {
			$valor = $fontSize / 1.4 * $totalChar;
		} else if ($hv == 'V') {
			$valor = $fontSize * 1.3 * $totalChar;
		} else {
			$valor = $fontSize;
		}
		return "{$valor}pt";
	}

	private function mm2pt($v) {
		return $v * self::$mm2pt;
	}

	private function mm2twip($v) {
		return $v * self::$mmtotwip;
	}

}