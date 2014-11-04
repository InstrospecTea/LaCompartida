<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new Pagina($sesion);
$factura = new Factura($sesion);

if (!$factura->Load($id_factura_grabada)) {
	$pagina->FatalError('Factura inválido');
}

if ($lang == '') {
	$lang = 'es';
}
if (file_exists(Conf::ServerDir() . "/lang/{$lang}_" . Conf::dbUser() . ".php")) {
	$lang_archivo = $lang . '_' . Conf::dbUser() . '.php';
} else {
	$lang_archivo = $lang . '.php';
}

require_once Conf::ServerDir() . "/lang/$lang_archivo";

$desactivar_clave_rtf = Conf::GetConf($sesion, 'DesactivarClaveRTF');
$configuracion = array();
$configuracion['desactivar_clave_rtf'] = $desactivar_clave_rtf;

$html_css = $factura->GeneraHTMLFactura();
$html = $html_css['html'];
$cssData = $html_css['css'];
$xml = $html_css['xml'];
$xmlbit = $html_css['xmlbit'];

$nombre_documento = "doc_tributario_{$id_factura_grabada}_{$valor_unico}";

/*
 * Si el xml tiene el anchor %xml_body% se asume que es un template para XmlGenerator
 */
if ($xmlbit == 1 && strpos($xml, '%xml_body%') !== false) {
	$xml = new XmlGenerator($sesion);
	$filename = "$nombre_documento.xml";
	$xml->outputXml($id_factura_grabada, $html_css['xml'], $filename);
	exit;
}

list($docm_top, $docm_right, $docm_bottom, $docm_left, $docm_header, $docm_footer, $papel) = UtilesApp::ObtenerMargenesFactura($sesion, $factura->fields['id_documento_legal']);

if (empty($papel)) {
	$papel = 'LETTER';
}

$doc = new DocGenerator($html, $cssData, $papel, false, 'PORTRAIT', $docm_top, $docm_right, $docm_bottom, $docm_left, 'EMITIDO', '', $configuracion);
$valor_unico = substr(time(), -3);

if ($xmlbit == 1) {
	$doc->outputxml($xml, "$nombre_documento.xml");
} else {
	$doc->output("$nombre_documento.doc", '', 'factura');
}

exit;