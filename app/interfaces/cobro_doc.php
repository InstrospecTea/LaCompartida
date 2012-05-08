<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Asunto.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';
	require_once Conf::ServerDir().'/../app/classes/TemplateParser.php';



	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);

	// Carga de datos del cobro
	$cobro = new Cobro($sesion);
	//$cobro->Load(this->fields['id_cobro'];

	if(!$cobro->Load($id_cobro))
		$pagina->FatalError('Cobro inválido');

	$cobro->LoadAsuntos();
	$comma_separated = implode("','", $cobro->asuntos);

	if( $lang == '' )
		$lang = 'es';
	if( file_exists(Conf::ServerDir()."/lang/{$lang}_".Conf::dbUser().".php") ) {
		$lang_archivo = $lang.'_'.Conf::dbUser().'.php';
	} else {
		$lang_archivo = $lang.'.php';
	}
	
	require_once Conf::ServerDir()."/lang/$lang_archivo";

	//Usa el segundo formato de nota de cobro
	//solo si lo tiene definido en el conf y solo tiene gastos
	$css_cobro=1;
	$solo_gastos=true;
	for($k=0;$k<count($cobro->asuntos);$k++)
	{
	
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($cobro->asuntos[$k]);
		$query = "SELECT SUM(TIME_TO_SEC(duracion))
							FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE t2.cobrable = 1
							AND t2.codigo_asunto='".$asunto->fields['codigo_asunto']."'
							AND cobro.id_cobro='".$cobro->fields['id_cobro']."'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($total_monto_trabajado) = mysql_fetch_array($resp);
		if( $asunto->fields['trabajos_total_duracion'] > 0 )
		{
			$solo_gastos=false;
		}
	}
	if( method_exists('Conf','GetConf') )
	{
		if($solo_gastos && Conf::GetConf($sesion,'CSSSoloGastos'))
			$css_cobro=2;
	}
	else if (method_exists('Conf','CSSSoloGastos'))
	{
		if($solo_gastos && Conf::CSSSoloGastos())
			$css_cobro=2;
	}
	
	if( empty( $cobro->fields['id_formato'] ) ) {
		$id_formato = $css_cobro;
	} else {
		$id_formato = $cobro->fields['id_formato'];
	}

	#$cobro->GuardarCobro();

	$html .= $cobro->GeneraHTMLCobro(false,$id_formato);
	//echo $html; exit;
	$cssData = UtilesApp::TemplateCartaCSS($sesion,$cobro->fields['id_carta']);
	$cssData .= UtilesApp::CSSCobro($sesion,$id_formato);
	list($docm_top, $docm_right, $docm_bottom, $docm_left, $docm_header, $docm_footer) = UtilesApp::ObtenerMargenesCarta( $sesion, $cobro->fields['id_carta']);
	
	// margenes 1.5, 2.0, 2.0, 2.0
	$doc = new DocGenerator( $html, $cssData, $cobro->fields['opc_papel'], $cobro->fields['opc_ver_numpag'] ,'PORTRAIT',$docm_top,$docm_right,$docm_bottom,$docm_left,$cobro->fields['estado'], $id_formato, '',$docm_header, $docm_footer, $lang);
	$valor_unico=substr(time(),-3);

        
	//echo '<style>'.$cssData.'</style>'.$html;
	//exit;


	if( $enpdf )
	{
		require_once '../dompdf/dompdf_config.inc.php';
		$cambios = array("TR" => "tr", "TD" => "td", "TABLE" =>  "table", "TH"=>"th", "BR" => "br" , "HR" => "hr", "SPAN" => "span");
		$cssData = strtr($cssData, $cambios);
		$dompdf = new DOMPDF();
		if( $cobro->fields['id_formato'] )
		{
			$query = "SELECT pdf_encabezado_imagen, pdf_encabezado_texto FROM cobro_rtf WHERE id_formato=". $cobro->fields['id_formato']; 
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh); 
			list($encabezado_imagen, $encabezado_texto) = mysql_fetch_array($resp); 
			
			$img_dir = "../templates/".Conf::Templates()."/img/";
			list($img_archivo, $img_formato, $img_ancho, $img_alto, $img_x, $img_y) = explode( "::", $encabezado_imagen);
			list($texto_texto, $texto_tipografia, $texto_estilo,$texto_size, $texto_color, $texto_x, $texto_y) = explode("::", $encabezado_texto);
		}
		
		ob_start();
		if( isset($pdf))
		{
			$anchodoc = $pdf->get_width();
		}
		$margin_body = ceil(($img_alto + $img_y )/2) +10;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<style type="text/css">			
			<?php echo $cssData; ?>
			hr{
				border: 0px;
				border-top: 1px solid #999;
				border-left: 1px solid #999;
				height: 1px;
			}
			
			table.tabla_normal tr.tr_total3 td {
				border: 0px;
				border-top: 1pt solid #999;
			}
		</style>
	</head>
	<body style="margin-top: <?php echo $margin_body; ?>pt;">
		<script type="text/php">
		   if ( isset($pdf) )
		   {
			$anchodoc = $pdf->get_width();
			$header = $pdf->open_object(); 
			if( '<?php echo $img_archivo; ?>' != '&nbsp;' )
			{
				if( '<?php echo $img_x; ?>' != '-1' )
				{
					$pdf->image("<?php echo $img_dir . $img_archivo; ?>", "<?php echo $img_formato; ?>", <?php echo $img_x; ?>, <?php echo $img_y; ?>, <?php echo $img_ancho; ?>, <?php echo $img_alto; ?>);
				}
				else
				{
					$pdf->image("<?php echo $img_dir . $img_archivo; ?>", "<?php echo $img_formato; ?>", ( $anchodoc -  <?php echo ($img_ancho ); ?> ) / 2, <?php echo $img_y; ?>, <?php echo $img_ancho; ?>, <?php echo $img_alto; ?>);
				}
			} 
			
			if( '<?php echo $texto_texto; ?>' != '&nbsp;')
			{
				$font = Font_Metrics::get_font("<?php echo $texto_tipografia; ?>", "<?php echo $texto_estilo; ?>");
				$pdf->page_text( <?php echo $texto_x; ?>, <?php echo $texto_y; ?>, '<?php echo $texto_texto; ?>', $font, <?php echo $texto_size; ?>, <?php echo $texto_color; ?>);
			}
			$pdf->close_object(); 
			if( strlen( $header ) > 0 )
			{
				$pdf->add_object($header, 'all'); 
			}
		   }
		   </script>
		   <?php     echo $html; ?>
	</body>
</html>
<?php
		$cambio_html = array("<br size=\"1\" class=\"separador_vacio_salto\">" => '<div style="page-break-after: always;"></div>');
		$html = ob_get_clean(); 
		$html = strtr($html, $cambio_html);
		$dompdf->load_html($html);
		$dompdf->set_paper(strtolower($cobro->fields['opc_papel']), 'portrait'); //letter, landscape
		$dompdf->render();
		$dompdf->stream('cobro_'.$id_cobro.'_'.$valor_unico.'.pdf');
		#echo $html;
	}
	else
	{
		$doc->output('cobro_'.$id_cobro.'_'.$valor_unico.'.doc');
	}
	exit;






?>