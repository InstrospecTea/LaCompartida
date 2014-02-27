<?php

require_once Conf::ServerDir() . '/classes/Html.php';

Class FacturacionElectronica {

	private static $Html;

	public static function getHtml() {
		if (empty(self::$Html)) {
			self::$Html = new \TTB\Html;
		}
		return self::$Html;
	}

	public static function BotonDescargarHTML($id_factura) {
		$img_dir = Conf::ImgDir();
		$Html = self::getHtml();
		$img_pdf = $Html->img("{$img_dir}/pdf.gif", array('border' => 0));
		$a1 = $Html->tag('a', $img_pdf, array('class' => 'factura-documento', 'data-factura' => $id_factura, 'href' => '#'));
		$img_xml = $Html->img("{$img_dir}/xml.gif", array('border' => 0));
		$a2 = $Html->tag('a', $img_xml, array('class' => 'factura-documento', 'data-factura' => $id_factura, 'data-format' => 'xml', 'href' => '#'));
		return $a1 . $a2;
	}

	public static function BotonGenerarHTML($id_factura) {
		$Html = self::getHtml();
		$img_dir = Conf::ImgDir();
		$img = $Html->img("$img_dir/invoice.png", array('border' => '0'));
		$attr_a = array(
			'style' => 'margin-left: 8px;margin-right: 8px;',
			'title' => 'Generar Factura Electrónica',
			'class' => 'factura-electronica',
			'data-factura' => $id_factura,
			'href' => '#'
		);
		return  $Html->tag('a', $img, $attr_a);
	}

	public static function AgregarBotonFacturaElectronica($hookArg) {
		$Factura = $hookArg['Factura'];
		if ($Factura->FacturaElectronicaCreada()) {
			$hookArg['content'] = self::BotonDescargarHTML($Factura->fields['id_factura']);
		} elseif (!$Factura->Anulada()) {
			$hookArg['content'] = self::BotonGenerarHTML($Factura->fields['id_factura']);
		}
		return $hookArg;
	}

	public static function InsertaJSFacturaElectronica() {

	}

	public static function InsertaMetodoPago() {

	}

	public static function ValidarFactura() {

	}

	public static function GeneraFacturaElectronica() {

	}

	public static function AnulaFacturaElectronica() {

	}

}