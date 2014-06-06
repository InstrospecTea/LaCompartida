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
		$img = $Html->img("{$img_dir}/invoice.png", array('border' => '0'));
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
		$BotonDescargarHTML = self::BotonDescargarHTML('0');
		echo <<<EOF
			jQuery(document).on("click", ".factura-electronica", function() {
				if (!confirm("¿Confirma la generación de Factura electrónica?")) {
					return;
				}
				var self = jQuery(this);
				var id_factura = self.data("factura");
				var loading = jQuery("<span/>", {class: "loadingbar", style: "float:left;position:absolute;width:95px;height:20px;margin-left:-90px;"});
				self.parent().append(loading);
				jQuery.ajax({url: root_dir + "/api/index.php/invoices/" + id_factura +  "/build",
					type: "POST"
				}).success(function(data) {
					loading.remove();
					buttons = jQuery('{$BotonDescargarHTML}');
					buttons.each(function(i, e) { jQuery(e).attr("data-factura", id_factura)});
					self.replaceWith(buttons);
					window.location = root_dir + "/api/index.php/invoices/" + id_factura +  "/document?format=pdf"
				}).error(function(error_data){
					loading.remove();
					response = JSON.parse(error_data.responseText);
					if (response.errors) {
						error_message = response.errors[0].message;
						alert(error_message);
					}
				});
			});

			jQuery(document).on("click", ".factura-documento", function() {
				var self = jQuery(this);
				var id_factura = self.data("factura");
				var format = self.data("format") || "pdf";
				window.location = root_dir + "/api/index.php/invoices/" + id_factura +  "/document?format=" + format
			});

			jQuery(document).on("change", "#dte_metodo_pago",  function() {
				var metodo_pago = jQuery("#dte_metodo_pago option:selected").text();
				if (metodo_pago != "No Identificado") {
					jQuery("#dte_metodo_pago_cta").show();
				} else {
					jQuery("#dte_metodo_pago_cta").hide();
				}
			});

			jQuery("#dte_metodo_pago").trigger("change");
EOF;
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