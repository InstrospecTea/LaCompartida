<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once 'Cobro.php';
require_once 'Cliente.php';
require_once 'Asunto.php';
require_once 'CobroMoneda.php';
require_once 'MontoEnPalabra.php';
require_once 'UtilesApp.php';

class Factura extends Objeto
{
	function Factura($sesion, $fields = "", $params = "")
	{
		$this->tabla = "factura";
		$this->campo_id = "id_factura";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	
	function Id($id=null){
		if($id) $this->fields[$this->campo_id] = $id;
		if(empty($this->fields[$this->campo_id])) return false;
		return $this->fields[$this->campo_id];
	}

	function LoadByCobro($id_cobro)
	{
		$query = "SELECT id_factura FROM factura WHERE anulado = 0 AND id_cobro = '$id_cobro';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if($id)
			return $this->Load($id);
		return false;
	}
	
	function LoadByNumero($numero)
	{
		$query = "SELECT id_factura FROM factura WHERE numero = '$numero';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if($id)
			return $this->Load($id);
		return false;
	}

	function Escribir()
	{
		$cobro = new Cobro($this->sesion);
		if($cobro->Load($this->fields['id_cobro']))
		{
			$this->Edit('id_contrato',$cobro->fields['id_contrato']);
		}
		if($this->Write())
		{
			if($cobro->Load($this->fields['id_cobro']))
			{
				if( UtilesApp::GetConf($this->sesion,'NuevoModuloFactura'))
				{
					$query = "SELECT
						group_concat(idDocLegal) as listaDocLegal
						FROM (
						SELECT
						 CONCAT(if(f.id_documento_legal != 0, if(f.letra is not null, if(f.letra != '',concat('LETRA ',f.letra), CONCAT(p.codigo,' ',f.numero)), CONCAT(p.codigo,' ',f.numero)), ''),IF(f.anulado=1,' (ANULADO)',''),' ') as idDocLegal
						,f.id_cobro
						FROM factura f, prm_documento_legal p
						WHERE f.id_documento_legal = p.id_documento_legal
						AND id_cobro = '".$this->fields['id_cobro']."'
						)zz
						GROUP BY id_cobro";
					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list($lista) = mysql_fetch_array($resp);
					$cobro->Edit('documento',$lista);
				}
				else
				{
					$cobro->Edit('documento',$this->fields['numero']);
				}
				
				$cobro->Write();
				
				/*if( ( $this->fields['subtotal_gastos'] > 0 || $this->fields['subtotal_gastos_sin_impuesto'] > 0 ) && $this->ComparaGastos() )
				{
					$this->GastosAsociaCobro();
				}*/
			}			
			return true;
		}
		else
		{
			return false;
		}
	}

	/*function ComparaGastos()
	{
		$factura_subtotal_gastos = 0; 		$factura_subtotal_gastos_sin_impuesto = 0;		
		$documento_subtotal_gastos = 0;		$documento_subtotal_gastos_sin_impuesto = 0;
		
		$query = "SELECT subtotal_gastos, subtotal_gastos_sin_impuesto FROM factura 
					WHERE id_cobro = " . $this->fields['id_cobro'] . " 
					AND ( subtotal_gastos > 0 OR subtotal_gastos_sin_impuesto > 0 ) ";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list( $factura_subtotal_gastos, $factura_subtotal_gastos_sin_impuesto ) = mysql_fetch_array($resp);
		
		$query = "SELECT subtotal_gastos, subtotal_gastos_sin_impuesto FROM documento 
					WHERE id_cobro = " . $this->fields['id_cobro'] . " 
					AND ( subtotal_gastos > 0 OR subtotal_gastos_sin_impuesto > 0 ) ";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list( $documento_subtotal_gastos, $documento_subtotal_gastos_sin_impuesto ) = mysql_fetch_array($resp);
		
		if( $factura_subtotal_gastos != $documento_subtotal_gastos || $factura_subtotal_gastos_sin_impuesto != $documento_subtotal_gastos_sin_impuesto )
		{
			return false;
		}
		return true;		
	}
	
	function GastosAsociaCobro()
	{
		$query = "SELECT id_movimiento FROM cta_corriente WHERE id_cobro = " . $this->fields['id_cobro'] . "";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		$gasto = new Gasto( $this->sesion );
		while( list($id_movimiento) = mysql_fetch_array($resp) )
		{
			$gasto->Load($id_movimiento);
			
			if( $this->fields['id_estado'] == 5 )
			{
				$gasto->Edit('id_factura', "NULL" );
				$gasto->Edit('fecha_factura', "NULL");
			}
			else
			{
				$gasto->Edit('id_factura', $this->fields['id_factura']);
				$gasto->Edit('fecha_factura', $this->fields['fecha']);
			}
			if( !$gasto->Write())
			{
				//return false;
			}
		}
		
		return true;
		
	}*/
	function PrimerTipoDocumentoLegal()
	{
		$query = "SELECT id_documento_legal FROM prm_documento_legal ORDER BY id_documento_legal ASC LIMIT 1";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_documento_legal) = mysql_fetch_array($resp);
		return $id_documento_legal;
	}
	
	function GeneraHTMLFactura($id_formato_factura = null)
	{
		if( $this->fields['id_moneda'] != 2 &&  ( ( method_exists('Conf','InfoBancariaCYC') && Conf::InfoBancariaCYC() ) || ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'InfoBancariaCYC') ) ) )
		{
			$templateData = UtilesApp::TemplateFactura($this->sesion,2);
			$cssData = UtilesApp::TemplateFacturaCSS($this->sesion,2);
		}
		else
		{ 
			if($id_formato_factura != null)
			{
				$templateData = UtilesApp::TemplateFactura($this->sesion,$id_formato_factura);
				$cssData = UtilesApp::TemplateFacturaCSS($this->sesion,$id_formato_factura);
			}
			else{ 
				// verificar el tipo de documento legal, y mostrar ese formato, sino mostrar por defecto
				$query=""; 
				if($this->fields['id_documento_legal'] > 0)
				{
					$query = "SELECT id_factura_formato FROM factura_rtf WHERE id_tipo='".$this->fields['id_documento_legal']."' order by id_factura_formato asc limit 0,1";
				}
				else
				{
					$query = "SELECT id_factura_formato FROM factura_rtf ORDER BY id_factura_formato ASC LIMIT 1";
				}
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($id_formato_factura) = mysql_fetch_array($resp);
				if($id_formato_factura>0)
				{
					$templateData = UtilesApp::TemplateFactura($this->sesion,$id_formato_factura);
					$cssData = UtilesApp::TemplateFacturaCSS($this->sesion,$id_formato_factura);				
				}
				else
				{				
					$templateData = UtilesApp::TemplateFactura($this->sesion);
					$cssData = UtilesApp::TemplateFacturaCSS($this->sesion);
				}
			}
		}
		$templateData = $this->ReemplazarMargenes($templateData);
		$parser = new TemplateParser($templateData);
		
		$query = "SELECT cobro.codigo_idioma
							FROM factura
							LEFT JOIN cobro ON factura.id_cobro=cobro.id_cobro
							WHERE factura.id_factura=".$this->fields['id_factura'];
		$resp = mysql_query( $query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($lang) = mysql_fetch_array($resp);
		
		if(!$lang)
		{
			$query = "SELECT contrato.codigo_idioma
							FROM factura
							LEFT JOIN cliente ON factura.codigo_cliente=cliente.codigo_cliente
							LEFT JOIN contrato ON contrato.id_contrato = cliente.id_contrato
							WHERE factura.id_factura=".$this->fields['id_factura'];
			$resp = mysql_query( $query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($lang) = mysql_fetch_array($resp);
		}
		
		$html = $this->GenerarDocumento( $parser, 'CARTA_FACTURA', $lang );
		
		$html_css=array();
		$html_css['html'] = $html;
		$html_css['css'] = $cssData;
		
		return $html_css;
	}
	
	function ReemplazarMargenes($html)
	{
		$espacios_monto_palabra = "";
		for($i=0; $i < UtilesApp::GetConf($this->sesion,'EspacioMontoPalabra');$i++)
			$espacios_monto_palabra .= "&nbsp;";
		$html = str_replace('%espacio_encabezado%', UtilesApp::GetConf($this->sesion,'EspacioEncabezado'), $html);
		$html = str_replace('%margen_izquierda_rsocial%', UtilesApp::GetConf($this->sesion,'MargenIzquierdaRsocial'), $html);
		$html = str_replace('%espacio_cuerpo%', UtilesApp::GetConf($this->sesion,'EspacioCuerpo'), $html);
		$html = str_replace('%espacios_monto_palabra%', $espacios_monto_palabra, $html);
		$html = str_replace('%margen_derecha_cuerpo%', UtilesApp::GetConf($this->sesion,'MargenDerechaCuerpo'), $html);
		$html = str_replace('%ancho_columna_dia%', UtilesApp::GetConf($this->sesion,'AnchoColumnaDia'), $html);
		$html = str_replace('%ancho_columna_mes%', UtilesApp::GetConf($this->sesion,'AnchoColumnaMes'), $html);
		$html = str_replace('%ancho_columna_anyo%', UtilesApp::GetConf($this->sesion,'AnchoColumnaAnyo'), $html);
		$html = str_replace('%ancho_columna_base_encabezado%', UtilesApp::GetConf($this->sesion,'AnchoColumnaBaseEncabezado'), $html);
		$html = str_replace('%ancho_columna_base_cuerpo%', UtilesApp::GetConf($this->sesion,'AnchoColumnaBaseCuerpo'), $html);
		$html = str_replace('%margen_izquierda_cuerpo%', UtilesApp::GetConf($this->sesion,'MargenIzquierdaCuerpo'), $html);
		
		return $html;
	}
	
	function GenerarDocumento( $parser_factura, $theTag='', $lang='es' )
	{
		if( !isset($parser_factura->tags[$theTag]) )
			return;

		$idioma = new Objeto($this->sesion,'','','prm_idioma','codigo_idioma');
		$idioma->Load($lang);

		global $cobro_moneda;
		$cobro_moneda = new CobroMoneda( $this->sesion );
		$cobro_moneda->Load( $this->fields['id_cobro'] );

		$cobro = new Cobro( $this->sesion );
		$cobro->Load( $this->fields['id_cobro'] );
		$cobro->LoadAsuntos();

		$tipo_cambio_moneda_total = $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];

		$html2 = $parser_factura->tags[$theTag];

		switch( $theTag )
		{
			case 'CARTA_FACTURA':
					$html2 = str_replace('%ENCABEZADO%', $this->GenerarDocumento( $parser_factura, 'ENCABEZADO', $lang ) , $html2);
					$html2 = str_replace('%DATOS_FACTURA%', $this->GenerarDocumento( $parser_factura, 'DATOS_FACTURA', $lang), $html2);
					$html2 = str_replace('%BOTTOM%', $this->GenerarDocumento( $parser_factura, 'BOTTOM' ,$lang), $html2);
					$html2 = str_replace('%BOTTOM_COPIA%', $this->GenerarDocumento( $parser_factura, 'BOTTOM_COPIA', $lang), $html2);
					$html2 = str_replace('%CLIENTE%', 				$cobro->GenerarDocumento($parser_factura,'CLIENTE',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html3, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%DETALLE_COBRO%', 	$cobro->GenerarDocumento($parser_factura,'DETALLE_COBRO',$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html3, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%SALTO_PAGINA%', 		$cobro->GenerarDocumento($parser_factura,'SALTO_PAGINA',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html3, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%ASUNTOS%', 				$cobro->GenerarDocumento($parser_factura,'ASUNTOS',			$parser_carta,$moneda_cliente_cambio, $moneda_cli, $lang, $html3, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html2);
					$html2 = str_replace('%GASTOS%', 					$cobro->GenerarDocumento($parser_factura,'GASTOS',  $parser_carta,$moneda_Cliente_cambio, $moneda_cli, $lang, $html3, & $idioma, $cliente, $moneda, $moneda_base, $trabajo, & $profesionales, $gasto, & $totales, $tipo_cambio_moneda_total, $asunto), $html2);
			break;

			case 'ENCABEZADO':
					if( method_exists('Conf','GetConf') )
							{
							$PdfLinea1 = Conf::GetConf( $this->sesion, 'PdfLinea1' );
							$PdfLinea2 = Conf::GetConf( $this->sesion, 'PdfLinea2' );
					 		$PdfLinea3 = Conf::GetConf( $this->sesion, 'PdfLinea3' );
					 		$CiudadSignatura = Conf::GetConf( $this->sesion, 'CiudadSignatura' );
					 		$logo_doc = Conf::GetConf( $this->sesion, 'LogoDoc' );
				 			}
					else
							{
								if( method_exists('Conf','PdfLinea1') )
							$PdfLinea1 = Conf::PdfLinea1();
								if( method_exists('Conf','PdfLinea2') )
							$PdfLinea2 = Conf::PdfLinea2();
								if( method_exists('Conf','PdfLinea3') )
							$PdfLinea3 = Conf::PdfLinea3();
							if( method_exists('Conf','FicheroLogoDoc') )
							$logo_doc = Conf::FicheroLogoDoc();
							}
					$html2 = str_replace('%linea1%', $PdfLinea1, $html2);
					$html2 = str_replace('%linea2%', $PdfLinea2, $html2);
					$html2 = str_replace('%linea3%', $PdfLinea3, $html2);
					$html2 = str_replace('%ciudad%', $CiudadSignatura, $html2);
					$html2 = str_replace('%LogoDoc%', $logo_doc, $html2);
					$query = "SELECT titulo_contacto, contacto, apellido_contacto, cobro.id_cobro, factura.numero,
													 CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre, factura.fecha as fecha,
													prm_documento_legal.glosa
											FROM contrato
											LEFT JOIN cobro ON contrato.id_contrato=cobro.id_contrato
											LEFT JOIN factura ON cobro.id_cobro=factura.id_cobro
											LEFT JOIN prm_documento_legal ON prm_documento_legal.id_documento_legal = factura.id_documento_legal
											LEFT JOIN usuario ON contrato.id_usuario_responsable=usuario.id_usuario
											WHERE id_factura=".$this->fields['id_factura'];
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list( $titulo_contacto, $contacto, $apellido_contacto, $id_cobro, $numero_factura, $encargado_comercial, $fecha_factura, $glosa_tipo_doc) = mysql_fetch_array( $resp );
					$glosa_tipo_doc_mayus=str_replace('é','É',strtoupper($glosa_tipo_doc));
					if( $lang == 'es' )
						{
						$html2 = str_replace('%numero_factura%', $glosa_tipo_doc.' No. '.$numero_factura , $html2);
						$html2 = str_replace('%Senores%', 'SEÑORES', $html2);
						$html2 = str_replace('%tipo_doc_legal%', $glosa_tipo_doc_mayus , $html2);
						$html2 = str_replace('%numero_doc_legal%', $numero_factura , $html2);
						}
					else
						{
						$titulos_es = array('Sr.','Sra.','Srta.');
 						$titulos_en = array('Mr.','Mrs.','Ms.');
 						$titulo_contacto = str_replace($titulos_es,$titulos_en,$titulo_contacto);
 						$html2 = str_replace('%numero_factura%', $glosa_tipo_doc.' No. '.$numero_factura , $html2);
						$html2 = str_replace('%Senores%', 'Messrs', $html2);
						}
					$html2 = str_replace('%subtitulo%', '', $html2);
					if( method_exists('Conf','Server') && method_exists('Conf','ImgDir') )
					$html2 = str_replace('%logo_cobro%', Conf::Server().'/'.Conf::ImgDir(), $html2);

					$fecha_factura = $this->fields['fecha'];

					$html2 = str_replace('%nombre_cliente%', $this->fields['cliente'], $html2);
					$html2 = str_replace('%glosa_cliente%', $this->fields['cliente'], $html2);
					$html2 = str_replace('%glosa_cliente_mayuscula%', strtoupper($this->fields['cliente']), $html2);
					$html2 = str_replace('%encargado_comercial%', $encargado_comercial, $html2);
					$html2 = str_replace('%rut_cliente%', $this->fields['RUT_cliente'], $html2);
					$meses_org = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
					$month_short = array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
					$mes_corto = array('JAN','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC');
					$mes_largo_es = array('ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICICIEMBRE');
					$html2 = str_replace('%nombre_encargado%', strtoupper($titulo_contacto.' '.$contacto.' '.$apellido_contacto), $html2);
					$html2 = str_replace('%direccion_cliente%', $this->fields['direccion_cliente'], $html2);
					$html2 = str_replace('%direccion_cliente_mayuscula%', strtoupper($this->fields['direccion_cliente']), $html2);
					$html2 = str_replace('%num_dia%', date( 'd' ,strtotime($fecha_factura)), $html2);
					$html2 = str_replace('%glosa_mes%', str_replace($meses_org,$mes_largo_es,date( 'M' ,strtotime($fecha_factura))), $html2);
					$html2 = str_replace('%num_anio%', date( 'Y' ,strtotime($fecha_factura)), $html2);
					$html2 = str_replace('%num_mes%', date( 'm' ,strtotime($fecha_factura)), $html2);
					$html2 = str_replace('%num_anio_2cifras%', date( 'y' ,strtotime($fecha_factura)), $html2);
					$anio_yyyy = date( 'Y' ,strtotime($fecha_factura));
					$html2 = str_replace('%num_anio_ultimacifra%', $anio_yyyy[3], $html2);
					if( $lang == 'es' )
						{
						$html2 = str_replace('%fecha_actual%', str_replace($meses_org,$mes_corto,date( 'j-M-y' ,strtotime($fecha_factura))), $html2);
						$html2 = str_replace('%glosa_fecha%','FECHA',$html2);
 						$html2 = str_replace('%ATN%','ATENCION',$html2);
 						$html2 = str_replace('%id_cobro%', '  '.$id_cobro, $html2);
 						}
 					elseif( $lang == 'en' )
 						{
 						$html2 = str_replace('%fecha_actual%', str_replace($meses_org,$month_short,date( 'M-d-y' ,strtotime($fecha_factura))), $html2);
						$html2 = str_replace('%glosa_fecha%','DATE',$html2);
						$html2 = str_replace('%ATN%','Attention',$html2);
 						$html2 = str_replace('%id_cobro%', '   <br> INVOICE No.   '.$id_cobro, $html2);
 						}
			break;

			case 'DATOS_FACTURA':

					$select_col = "";
					if(( method_exists('Conf','GetConf') && (Conf::GetConf($this->sesion,'DesgloseFactura')=='con_desglose')))
					{
						$select_col = ",
									factura.descripcion_subtotal_gastos,
									factura.descripcion_subtotal_gastos_sin_impuesto,
									factura.subtotal_gastos,
									factura.subtotal_gastos_sin_impuesto";
					}

					$query = "SELECT
									factura.id_moneda,
									factura.descripcion,
									cobro.id_cobro,
									cobro.id_moneda,
									cobro.fecha_ini,
									cobro.fecha_fin,
									cobro.porcentaje_impuesto,
									prm_moneda.glosa_moneda,
									prm_moneda.glosa_moneda_plural,
									prm_moneda.simbolo,
									prm_moneda.cifras_decimales,
									factura.subtotal,
									factura.subtotal_sin_descuento,
									factura.descuento_honorarios,
									factura.honorarios,
									factura.subtotal_gastos,
									factura.gastos,
									factura.iva,
									factura.total
									$select_col
									FROM factura
									LEFT JOIN cobro ON factura.id_cobro=cobro.id_cobro
									LEFT JOIN prm_moneda ON cobro.id_moneda=prm_moneda.id_moneda
									WHERE id_factura=".$this->fields['id_factura'];

					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

					if(( method_exists('Conf','GetConf') && (Conf::GetConf($this->sesion,'DesgloseFactura')=='con_desglose')))
					{
						list($factura_id_moneda, $factura_descripcion, $id_cobro, $cobro_id_moneda, $fecha_ini, $fecha_fin, $porcentaje_impuesto, $glosa_moneda, $glosa_moneda_plural, $simbolo, $cifras_decimales, $monto_subtotal, $monto_subtotal_sin_descuento, $descuento_honorarios, $honorarios, $subtotal_gastos, $monto_gastos, $impuesto, $total ,$descripcion_subtotal_gastos,$descripcion_subtotal_gastos_sin_impuesto,$subtotal_gastos_con_impuesto,$subtotal_gastos_sin_impuesto)=mysql_fetch_array($resp);
					}
					else
					{
						list($factura_id_moneda, $factura_descripcion, $id_cobro, $cobro_id_moneda, $fecha_ini, $fecha_fin, $porcentaje_impuesto, $glosa_moneda, $glosa_moneda_plural, $simbolo, $cifras_decimales, $monto_subtotal, $monto_subtotal_sin_descuento, $descuento_honorarios, $honorarios, $subtotal_gastos, $monto_gastos, $impuesto, $total)=mysql_fetch_array($resp);
					}
					$moneda_factura = new Moneda($this->sesion);
					$moneda_factura->Load($factura_id_moneda);
					$query = "SELECT glosa_asunto , codigo_asunto
											FROM cobro
											JOIN contrato ON cobro.id_contrato=contrato.id_contrato
											JOIN asunto ON contrato.id_contrato=asunto.id_contrato
											WHERE cobro.id_cobro='".$id_cobro."'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $this->sesion->dbh);
					$i=1;
					while( list($glosa_asunto, $codigo_asunto) = mysql_fetch_array($resp) )
					{
						if($i==1) {
							$asuntos = $glosa_asunto;
							$cod_asuntos = $codigo_asunto;
						}
						else {
							$asuntos .= ', '.$glosa_asunto;
							$cod_asuntos .= ', '.$codigo_asunto;
						}
						$i++;
					}
					if( $lang == 'es' )
						{
						$html2 = str_replace('%<br><br>%', '<br><br>', $html2);
						$html2 = str_replace('%servicios_periodo%', strtoupper($factura_descripcion), $html2);
						$html2 = str_replace('%servicios_periodo%', strtoupper('Honorarios por servicios profesionales prestados %fecha_ini% %fecha_fin%'), $html2);
						$meses_org = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
						$mes_corto = array('jan','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic');
						if($fecha_ini && $fecha_ini != '0000-00-00')
							{
								$html2 = str_replace('%fecha_ini%','desde '.str_replace($meses_org,$mes_corto,date( 'j-M-y' ,strtotime($fecha_ini))), $html2);
							}
						else
							$html2 = str_replace('%fecha_ini%','', $html2);
						if( $fecha_fin && $fecha_fin != '0000-00-00' )
							{
								$html2 = str_replace('%fecha_fin%','hasta '.str_replace($meses_org,$mes_corto,date( 'j-M-y' ,strtotime($fecha_fin))), $html2);
							}
						else
							$html2 = str_replace('%fecha_fin%','', $html2);
						$html2 = str_replace('%texto_honorarios%', 'HONORARIOS', $html2);
						$html2 = str_replace('%texto_gastos%', 'GASTOS', $html2);
						$html2 = str_replace('%texto_descripcion_gastos%', 'Gastos incurridos en su caso, según relación adjunta.', $html2);
						$html2 = str_replace('%total_honorarios_y_gastos%', 'Total servicios profesionales y gastos incurridos', $html2);
						$html2 = str_replace('%pje_impuesto%', $porcentaje_impuesto.'%', $html2);
						if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) )
							{
							if( method_exists( 'Conf', 'GetConf' ) )
								$html2 = str_replace('%texto_impuesto%', __('IVA') . ' ('.Conf::GetConf($this->sesion,'ValorImpuesto').'%)',$html2);
							else if( method_exists('Conf','ValorImpuesto') )
								$html2 = str_replace('%texto_impuesto%', __('IVA') . ' ('.$porcentaje_impuesto.'%)', $html2);
							}
						else
							$html2 = str_replace('%texto_impuesto%', '', $html2);
						$html2 = str_replace('%descripcion%', '', $html2);
						$html2 = str_replace('%texto_total%', 'Total ', $html2);
						$html2 = str_replace('%firma%', 'Firma', $html2);
						}
					else if( $lang == 'en' )
						{
							$html2 = str_replace('%<br><br>%', '', $html2);
							$html2 = str_replace('%servicios_periodo%', $factura_descripcion, $html2);
							$html2 = str_replace('%servicios_periodo%', 'For legal services rendered %fecha_ini% %fecha_fin%', $html2);
							$meses_org = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
							$month_short = array('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
							if($fecha_ini && $fecha_ini != '0000-00-00')
								$html2 = str_replace('%fecha_ini%','from '.str_replace($meses_org,$month_short,date( 'M-d-y' ,strtotime($fecha_ini))), $html2);
							else
								$html2 = str_replace('%fecha_ini%','', $html2);
							if( $fecha_fin && $fecha_fin != '0000-00-00')
								$html2 = str_replace('%fecha_fin%','until '.str_replace($meses_org,$month_short,date( 'M-d-y' ,strtotime($fecha_fin))), $html2);
							else
								$html2 = str_replace('%fecha_fin%','', $html2);
							$html2 = str_replace('%texto_honorarios%', 'LEGAL SERVICES', $html2);
							$html2 = str_replace('%texto_gastos%', 'EXPENSES', $html2);
							$html2 = str_replace('%texto_descripcion_gastos%', 'Expenses incurred in this case.', $html2);
							$html2 = str_replace('%total_honorarios_y_gastos%', 'Total legal services and expenses', $html2);
							if( ( (  method_exists('Conf','GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) )
								{
								if( method_exists( 'Conf', 'GetConf' ) )
									$html2 = str_replace('%texto_impuesto%', __('IVA') . ' ('.Conf::GetConf($this->sesion,'ValorImpuesto').'%)',$html2);
								else if( method_exists('Conf','ValorImpuesto') )
									$html2 = str_replace('%texto_impuesto%', __('IVA') . ' ('.Conf::ValorImpuesto().'%)', $html2);
								}
							else
								$html2 = str_replace('%texto_impuesto%', '', $html2);
								$html2 = str_replace('%descripcion%', '', $html2);
							if($lang=='en')
								{
									$meses = array('enero','febrero','marzo','abril','mayo','junio','agosto','septiembre','octubre','noviembre','diciembre');
									$months = array('january','february','march','april','may','june','july','august','september','october','november','december');
									$html2 = str_replace('Honorarios por asesorías prestadas','Legal services rendered',$html2);
									$html2 = str_replace('durante el mes de','during the month of',$html2);
									$html2 = str_replace($meses,$months,$html2);
								}
							$html2 = str_replace('%texto_total%', 'Total ', $html2);
							$html2 = str_replace('%firma%', 'Signature', $html2);
						}
					$html2 = str_replace('%asuntos%', $asuntos, $html2);
					$html2 = str_replace('%cod_asuntos%', $cod_asuntos, $html2);
					if( method_exists('Conf','Server') && method_exists('Conf','ImgDir') )
					$html2 = str_replace('%logo_cobro%', Conf::Server().'/'.Conf::ImgDir(), $html2);
					$html2 = str_replace('%glosa_moneda_factura%', '%'.$cobro_moneda->moneda[$factura_id_moneda]['glosa_moneda'].'%', $html2);
					$html2 = str_replace('%Peso%', 'PESOS', $html2);
					if( $lang == 'es' )
						$html2 = str_replace('%Dólar%', 'DÓLARES', $html2);
					else
						$html2 = str_replace('%Dólar%', 'DOLLARS', $html2);
					$html2 = str_replace('%Euro%', 'EUROS', $html2);
					if( method_exists( 'Conf', 'GetConf' ) )
						$html2 = str_replace('%porcentaje%', Conf::GetConf($this->sesion,'ValorImpuesto').'%',$html2);
					else if( method_exists('Conf','ValorImpuesto') )
						$html2 = str_replace('%porcentaje%', Conf::ValorImpuesto().'%', $html2);
					$monto_gastos_sin_impuesto = $monto_gastos / ( 1 + ( $porcentaje_impuesto / 100 ) ) ;
					$impuesto_gastos = $monto_gastos - $monto_gastos_sin_impuesto;
					
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CalculacionCYC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) )
						{
							$html2 = str_replace('%monto_honorarios%', number_format($honorarios, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							$html2 = str_replace('%monto_gastos%', number_format($subtotal_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							$total_factura_netto = round($monto_subtotal_sin_descuento + $subtotal_gastos,$moneda_factura->fields['cifras_decimales']);
							$html2 = str_replace('%monto_total%', number_format($total_factura_netto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							$impuesto_factura = round(round($monto_subtotal_sin_descuento+$subtotal_gastos,$moneda_factura->fields['cifras_decimales'])*($porcentaje_impuesto/100),$moneda_factura->fields['cifras_decimales']);
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) )
								$html2 = str_replace('%monto_impuestos%', number_format($impuesto_factura, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							else
								$html2 = str_replace('%monto_impuestos%', '', $html2);
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) )
								$html2 = str_replace('%monto_total_brutto%', number_format($total_factura_netto + $impuesto_factura, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							else
								$html2 = str_replace('%monto_total_brutto%', '', $html2);
						}
					else
						{
							$html2 = str_replace('%monto_honorarios%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							$html2 = str_replace('%monto_gastos%', number_format($monto_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							$html2 = str_replace('%monto_total%', number_format($monto_subtotal + $monto_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) )
								$html2 = str_replace('%monto_impuestos%', number_format($impuesto + $impuesto_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							else
								$html2 = str_replace('%monto_impuestos%', '', $html2);
							if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) ) )
								$html2 = str_replace('%monto_total_brutto%', number_format($total + $monto_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
							else
								$html2 = str_replace('%monto_total_brutto%', '', $html2);
						}


						/*
						Montos Rebaza-alcazar
						*/
						
						if(( method_exists('Conf','GetConf') && (Conf::GetConf($this->sesion,'DesgloseFactura')=='con_desglose')))
						{
							$html2 = str_replace('%honorarios%', number_format($honorarios, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

							$html2 = str_replace('%simbolo_subtotal_gastos_con_impuesto%', $simbolo, $html2);
							$html2 = str_replace('%descripcion_subtotal_gastos_con_impuesto%', strtoupper($descripcion_subtotal_gastos), $html2);
							$html2 = str_replace('%subtotal_gastos_con_impuesto%', number_format($subtotal_gastos_con_impuesto, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

							if(( method_exists('Conf','GetConf') && (Conf::GetConf($this->sesion,'UsarGastosConSinImpuesto')=='1')))
							{
								$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', $simbolo, $html2);
								$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', strtoupper($descripcion_subtotal_gastos_sin_impuesto), $html2);
								$html2 = str_replace('%subtotal_gastos_sin_impuesto%', number_format($subtotal_gastos_sin_impuesto, 0, $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
								}
							else
							{
								$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', '', $html2);
								$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', '', $html2);
								$html2 = str_replace('%subtotal_gastos_sin_impuesto%', '', $html2);
							}

							$suma_monto_sin_iva = number_format($honorarios, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
							$suma_monto_con_iva = number_format($total, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
							$impuesto = number_format($impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

							$html2 = str_replace('%suma_montos_sin_iva%', $suma_monto_sin_iva, $html2);
							$html2 = str_replace('%suma_montos_solo_iva%', $impuesto, $html2);
							$html2 = str_replace('%suma_monto_con_iva%', $suma_monto_con_iva, $html2);

							$monto_subtotal_honorario_y_gastos = $monto_subtotal + $subtotal_gastos + $subtotal_gastos_sin_impuesto;
							$html2 = str_replace('%monto_subtotal_honorario_y_gastos%', number_format($monto_subtotal_honorario_y_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);


						}
						else
						{
							$html2 = str_replace('%honorarios%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);

							$html2 = str_replace('%simbolo_subtotal_gastos_con_impuesto%', '', $html2);
							$html2 = str_replace('%descripcion_subtotal_gastos_con_impuesto%', '', $html2);
							$html2 = str_replace('%subtotal_gastos_con_impuesto%', '', $html2);

							$html2 = str_replace('%simbolo_subtotal_gastos_sin_impuesto%', '', $html2);
							$html2 = str_replace('%descripcion_subtotal_gastos_sin_impuesto%', '', $html2);
							$html2 = str_replace('%subtotal_gastos_sin_impuesto%', '', $html2);

							$html2 = str_replace('%suma_montos_sin_iva%', '', $html2);
							$html2 = str_replace('%suma_montos_solo_iva%', '', $html2);
							$html2 = str_replace('%suma_monto_con_iva%', '', $html2);
							$html2 = str_replace('%monto_subtotal_honorario_y_gastos%', '', $html2);
						}

						$monto_palabra=new MontoEnPalabra($this->sesion);

						$glosa_moneda_lang = __($glosa_moneda);
						$glosa_moneda_plural_lang = __($glosa_moneda_plural);

						$monto_total_palabra = strtoupper($monto_palabra->ValorEnLetras($total,$cobro_id_moneda,$glosa_moneda_lang,$glosa_moneda_plural_lang));
						$html2 = str_replace('%simbolo%', $simbolo , $html2);

						$html2 = str_replace('%subtotal%', number_format($monto_subtotal, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_impuesto_sin_gastos%', number_format($impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_total_bruto_sin_gastos%', number_format($total, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']), $html2);
						$html2 = str_replace('%monto_total_palabra%', $monto_total_palabra , $html2);






			break;

			case ( $theTag == 'BOTTOM' || $theTag == 'BOTTOM_COPIA' ):
					if( method_exists( 'Conf','GetConf' ) )
							{
					 		$PdfLinea1 = Conf::GetConf( $this->sesion, 'PdfLinea1' );
					 		$numeracion = Conf::GetConf( $this->sesion, 'Numeracion' );
					 		$numeracion_fecha = Conf::GetConf( $this->sesion, 'NumeracionFecha');
					 		$numeracion_desde = Conf::GetConf( $this->sesion, 'NumeracionDesde');
					 		$numeracion_hasta = Conf::GetConf( $this->sesion, 'NumeracionHasta');
					 		$titulo_empresa = Conf::GetConf( $this->sesion, 'NombreEmpresa');
					 		$subtitulo_empresa = Conf::GetConf( $this->sesion, 'SubtituloEmpresa');
					 		}
					else
							{
							if( method_exists('Conf','PdfLinea1') )
								$PdfLinea1 = Conf::PdfLinea1();
							$numeracion = '';
							$numeracion_fecha = '';
							$numeracion_desde = '';
							$numeracion_hasta = '';
							$titulo_empresa = '';
							$subtitulo_empresa = '';
							}

					$html2 = str_replace('%linea1%', $PdfLinea1, $html2);
					$html2 = str_replace('%numeracion%', $numeracion, $html2);
					$meses_org = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
					$meses_largo = array('ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE');
					$html2 = str_replace('%fecha_numeracion%', str_replace($meses_org,$meses_largo,date('M j Y',strtotime($numeracion_fecha))), $html2);
					$html2 = str_replace('%numeracion_desde%', $numeracion_desde, $html2);
					$html2 = str_replace('%numeracion_hasta%', $numeracion_hasta, $html2);
					$html2 = str_replace('%titulo%', $titulo_empresa, $html2);
					$html2 = str_replace('%subtitulo%', $subtitulo_empresa, $html2);
			break;
		}

		return $html2;
	}
	
	function ObtieneNumeroFactura()
	{
		if( method_exists('Conf','GetConf') )
			{
				$min_numero_factura = Conf::GetConf($this->sesion,'NumeracionDesde');
				$max_numero_factura = Conf::GetConf($this->sesion,'NumeracionHasta');
			}
		else if( method_exists('Conf','NumeracionDesde') ) 
			{
				$min_numero_factura = Conf::NumeracionDesde();
				$max_numero_factura = Conf::NumeracionHasta();
			}
			
			$where_max = " 1 ";
			if( $max_numero_factura > 0 ) 
				$where_max .= " AND numero <= ".$max_numero_factura;
			
		$query = "SELECT MAX(numero) FROM factura WHERE $where_max";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($max_numero_documento) = mysql_fetch_array($resp);
			
		if( $max_numero_documento < $min_numero_factura )
			return $min_numero_factura;
		else
			return $max_numero_documento + 1;
	}
	
	function ObtieneNumeroDocumentoLegal($tipo_documento_legal)
	{
		return $this->ObtenerNumeroDocLegal($tipo_documento_legal);

		if( method_exists('Conf','GetConf') )
		{
			$min_numero_factura = Conf::GetConf($this->sesion,'NumeracionDesde');
			$max_numero_factura = Conf::GetConf($this->sesion,'NumeracionHasta');
		}
		else if( method_exists('Conf','NumeracionDesde') ) 
		{
			$min_numero_factura = Conf::NumeracionDesde();
			$max_numero_factura = Conf::NumeracionHasta();
		}
			$where_max = " 1 ";
			if( $max_numero_factura > 0 ) 
				$where_max .= " AND numero <= ".$max_numero_factura;
			if( $tipo_documento_legal > 0 ) 
				$where_max .= " AND f.id_documento_legal = ".$tipo_documento_legal;	
			
		$query = "SELECT IF(MAX(f.numero)>pdl.numero_inicial,MAX(f.numero),pdl.numero_inicial) as numero_actual 
					FROM factura f 
					LEFT JOIN prm_documento_legal pdl on pdl.id_documento_legal = f.id_documento_legal
					WHERE $where_max";

		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($max_numero_documento) = mysql_fetch_array($resp);

		if( $max_numero_documento < $min_numero_factura )
			return $min_numero_factura;
		else
			return $max_numero_documento + 1;
	}
	
	function ObtenerNumeroDocLegal($tipo_documento_legal) {
		if (empty($tipo_documento_legal)) {
			return false;
		}
		$query = "SELECT numero_inicial FROM prm_documento_legal WHERE id_documento_legal = " . $tipo_documento_legal;
		$numero_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__ , __LINE__, $this->sesion->dbh);
		list($numero) = mysql_fetch_array($numero_resp);
		return $numero;
	}
	
	function ExisteNumeroDocLegal($tipo_documento_legal, $numero) {
		if (empty($tipo_documento_legal) or empty($numero)) {
			echo "$tipo_documento_legal, $numero";
			return false;
		}
		
		$query = "SELECT COUNT(*) FROM factura WHERE numero = ".$numero." AND id_documento_legal = '".$tipo_documento_legal."' AND serie_documento_legal = '".Conf::GetConf($this->sesion,'SerieDocumentosLegales')."'";
		$cantidad_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__ , __LINE__, $this->sesion->dbh);
		list($cantidad) = mysql_fetch_array($cantidad_resp);
		if ($cantidad > 0)
			return false;
		return true;
	}
	
	function ValidarDocLegal() {
		if (empty($this->fields['id_factura'])) {
			if (!$this->ExisteNumeroDocLegal($this->fields['id_documento_legal'], $this->fields['numero'])) {
				return false;
			}
		}

		return true;
	}
	

	function GetUltimoPagoSoyFactura($id=null) {
		if(!$id) { 
			$id = $this->Id();
			$where = " WHERE ccfm2.id_factura =  '".$id."'";
		}
		else {
			$where = " WHERE ccfm2.id_factura IN (".$id.") ";
		}

		$query = "SELECT fp.id_factura_pago
								FROM factura_pago AS fp
								JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
								JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
								LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
								".$where."
								ORDER BY fp.fecha,fp.id_factura_pago DESC	";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__ , __LINE__, $this->sesion->dbh);
		list($ultimo_id_factura_pago) = mysql_fetch_array($resp);
		return $ultimo_id_factura_pago;
	}

	function GetPagosSoyFactura($id=null) {
		if(!$id) { $id = $this->Id(); }
		$query = "SELECT fp.*,
									ccfmn.monto as monto_aporte
								FROM factura_pago AS fp
								JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
								JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
								LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
								WHERE ccfm2.id_factura =  '".$id."'";
		return new ListaFacturaPago($this->sesion, null, $query);
	}
	
	function MaxNumeroDocLegal($tipo_documento_legal) {
		if (empty($tipo_documento_legal)) {
			return false;
		}
		$query = "SELECT MAX(numero) as numero_actual FROM factura WHERE id_documento_legal = '".$tipo_documento_legal."' AND serie_documento_legal = '".Conf::GetConf($this->sesion,'SerieDocumentosLegales')."'";
		$numero_resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__ , __LINE__, $this->sesion->dbh);
		list($numero_max) = mysql_fetch_array($numero_resp);
		return $numero_max;
	}
	
	function GuardarNumeroDocLegal($tipo_documento_legal, $numero) {
		if (empty($tipo_documento_legal) or empty($numero)) {
			return false;
		}
		$numero += 1;
		if (!$this->ExisteNumeroDocLegal($tipo_documento_legal, $numero)) {
			$numero = $this->MaxNumeroDocLegal($tipo_documento_legal) + 1;
		}
		//
		$query = "UPDATE prm_documento_legal SET numero_inicial = $numero WHERE id_documento_legal = '".$tipo_documento_legal."'";
		$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}
	
	function CambiarEstado($codigo_estado, $id_factura=null){
		if(!$id_factura) $id_factura = $this->fields[$this->campo_id];

		$query = "UPDATE factura SET id_estado = (SELECT id_estado FROM prm_estado_factura WHERE codigo = '$codigo_estado') WHERE id_factura = '$id_factura'";
		$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function GetCodigoEstado($id_factura=null){
		if(!$id_factura) $id_factura = $this->fields[$this->campo_id];

		$query = "SELECT e.codigo FROM prm_estado_factura e JOIN factura f ON e.id_estado = f.id_estado WHERE f.id_factura = '$id_factura'";
		$respuesta = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo) = mysql_fetch_array($respuesta);
		return $codigo;
	}

	function GetNumeroCobro($id_factura){
		$query = "SELECT id_cobro FROM factura WHERE id_factura = '$id_factura';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}

	function GetlistaCobroSoyDatoFactura($id = null,$tipo = null,$numero = null){
		$lista_cobros = '';
		$where = " WHERE 1";
		if($id)
			$where .= " AND f.id_factura = '".$id."'";
		if($tipo)
			$where .= " AND f.id_documento_legal = '".$tipo."'";
		if($numero)
			$where .= " AND f.numero = '".$numero."'";
		$query = "SELECT GROUP_CONCAT(id_cobro) , '1' as grupo FROM factura f " . $where . " GROUP BY grupo";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($lista_cobros,$grupo) = mysql_fetch_array($resp);
		return $lista_cobros;
	}
} #end Class

class ListaFacturas extends Lista
{
    function ListaFacturas($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Factura', $params, $query);
    }
}
