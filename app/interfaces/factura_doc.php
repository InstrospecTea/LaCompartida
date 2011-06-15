<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';
	require_once Conf::ServerDir().'/../app/classes/TemplateParser.php';
	require_once Conf::ServerDir().'/../app/classes/Factura.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	
	$factura = new Factura($sesion);
	
	if(!$factura->Load($id_factura_grabada))
			$pagina->FatalError('Factura inválido');
			
	if( $lang == '' )
			$lang = 'es';
	require_once Conf::ServerDir()."/lang/$lang.php";

	//$cssData = UtilesApp::TemplateCartaCSS($sesion,$cobro->fields['id_carta']);
	//$cssData .= UtilesApp::CSSCobro($sesion);
	//$html = $factura->GeneraHTMLFactura();
	//$cssData = UtilesApp::TemplateFacturaCSS($sesion);
	
	$html_css = $factura->GeneraHTMLFactura();
	$html = $html_css['html'];
	$cssData = $html_css['css'];
	
	$doc = new DocGenerator($html,$cssData);
	$valor_unico=substr(time(),-3);
	$doc->output('doc_tributario_'.$id_factura_grabada.'_'.$valor_unico.'.doc', '', 'factura');
	exit;
	
	?>
