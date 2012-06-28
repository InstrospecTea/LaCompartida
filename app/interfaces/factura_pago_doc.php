<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';
	require_once Conf::ServerDir().'/../app/classes/TemplateParser.php';
	require_once Conf::ServerDir().'/../app/classes/FacturaPago.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	
	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	
	$pago = new FacturaPago($sesion);

	if(isset($_GET['id_factura_pago_grabada'])) $id_factura_pago = $_GET['id_factura_pago_grabada'];
	if(isset($_GET['id_factura_pago'])) $id_factura_pago = $_GET['id_factura_pago'];
	
	if(!$pago->Load($id_factura_pago))
			$pago->FatalError('Factura Pago inválido');

	
	if( $lang == '' )
			$lang = 'es';
	require_once Conf::ServerDir()."/lang/$lang.php";
	
	$html_css = $pago->GeneraHTMLFacturaPago();
	$html = $html_css['html'];
	$cssData = $html_css['css'];

	$doc = new DocGenerator($html,$cssData);
	$valor_unico=substr(time(),-3);
	$doc->output('voucher_'.$id_factura_pago.'_'.$valor_unico.'.doc','','factura');
	exit;
	
	?>
