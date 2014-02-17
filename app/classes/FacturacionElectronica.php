<?php

Class FacturacionElectronica {

	public static function BotonDescargarHTML($id_factura) {
		$img_dir = Conf::ImgDir();
		$content = "<a class = 'factura-documento' data-factura = '$id_factura' href = '#'> <img src='$img_dir/pdf.gif' border='0' /></a>";
		$content .= "<a class = 'factura-documento' data-format = 'xml' data-factura = '$id_factura' href = '#' > <img src='$img_dir/xml.gif' border='0' /></a>";
		return $content;
	}

	public static function BotonGenerarHTML($id_factura) {
		$img_dir = Conf::ImgDir();
		$content = "<a style = 'margin-left: 8px;margin-right: 8px;text-decoration: none;' title = 'Generar Factura Electrónica' class = 'factura-electronica' data-factura = '$id_factura' href = '#' >
			<img src = '$img_dir/invoice.png' border='0' />
		</a>";
		return $content;
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