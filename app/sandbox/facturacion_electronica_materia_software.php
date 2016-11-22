<?php

require_once(dirname(__FILE__) . '/../conf.php');

$Sesion = new Sesion();

$Factura = $Factura = new Factura($Sesion);
$Factura->Load($_REQUEST['id_factura']);

$Estudio = new PrmEstudio($Sesion);
$Estudio->Load($Factura->fields['id_estudio']);

$Moneda = new Moneda($Sesion);
$Moneda->Load($Factura->fields['id_moneda']);

$DocumentoLegal = new PrmDocumentoLegal($Sesion);
$DocumentoLegal->Load($Factura->fields['id_documento_legal']);

$WsFacturacionMateriaSoftware = new WsFacturacionMateriaSoftware(
	$Estudio->GetMetaData('facturacion_electronica_materia_software.Url'),
	$Estudio->GetMetaData('facturacion_electronica_materia_software.Authorization')
);

$documento = $WsFacturacionMateriaSoftware->documento(
	$Factura,
	$Moneda,
	$DocumentoLegal
);

TTB\Debug::pr(json_encode($WsFacturacionMateriaSoftware->getBodyInvoice(), JSON_PRETTY_PRINT));

// $documento = json_decode($Factura->fields['dte_url_pdf']);

$documento_anulado = $WsFacturacionMateriaSoftware->PutAnular(
	$documento->Serie,
	(int) $documento->Correlativo
);

TTB\Debug::pr(json_encode($documento_anulado, JSON_PRETTY_PRINT));

// $pdf = $WsFacturacionMateriaSoftware->GetStatus(
// 	$documento->Serie,
// 	(int) $documento->Correlativo
// );

echo "<div>Invoice: {$documento->Serie} {$documento->Correlativo}</div>";
echo '<div>Code: ', $WsFacturacionMateriaSoftware->getErrorCode(), '</div>';
echo '<div>Message: ', $WsFacturacionMateriaSoftware->getErrorMessage(), '</div>';

// header("Content-Transfer-Encoding: binary");
// header("Content-Type: application/pdf");
// header('Content-Description: File Transfer');
// header("Content-Disposition: attachment; filename={$documento->Serie}-{$documento->Correlativo}.pdf");
// echo base64_decode($pdf->PDF);
