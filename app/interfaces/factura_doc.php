<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/DocGenerator.php';
require_once Conf::ServerDir() . '/../app/classes/TemplateParser.php';
require_once Conf::ServerDir() . '/../app/classes/Factura.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

$sesion = new Sesion(array('COB'));
$pagina = new Pagina($sesion);
$factura = new Factura($sesion);

if ( !$factura->Load($id_factura_grabada) ) {
	$pagina->FatalError('Factura inválido');
}

if ( $lang == '' ) {
	$lang = 'es';
}
	
if ( file_exists(Conf::ServerDir() . "/lang/{$lang}_" . Conf::dbUser() . ".php")) {
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

list($docm_top, $docm_right, $docm_bottom, $docm_left, $docm_header, $docm_footer,$papel) = UtilesApp::ObtenerMargenesFactura($sesion, $factura->fields['id_documento_legal']);

if ( !$papel || $papel=='' ) {
	$papel='LETTER';
}

$doc = new DocGenerator($html, $cssData, $papel, false, 'PORTRAIT', $docm_top, $docm_right, $docm_bottom, $docm_left, 'EMITIDO', '', $configuracion);

//echo '<style>'.$cssData.'</style>'.$html; exit;

$valor_unico = substr(time(), -3);

if ( $xmlbit == 1) {
	$doc->outputxml($xml, 'doc_tributario_' . $id_factura_grabada . '_' . $valor_unico . '.xml');
} else {
	$doc->output('doc_tributario_' . $id_factura_grabada . '_' . $valor_unico . '.doc', '', 'factura');
}

exit;
?>