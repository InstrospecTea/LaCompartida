<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/classes/PaginaCobro.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/Observacion.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/Factura.php';
	require_once Conf::ServerDir().'/../app/classes/FacturaPago.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new PaginaCobro($sesion);

	$cobro = new Cobro($sesion);
	$contrato = new Contrato($sesion);
	$documento_cobro = new Documento($sesion);
	$factura = new Factura($sesion);
	$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
	
	$factura_pago = new FacturaPago($sesion);

	if( $opc == 'eliminar_pago' )
	{
		if( $eliminar_pago > 0 )
			{
				$factura_pago->Load($eliminar_pago);
				if( $factura_pago->Eliminar() )
					$pagina->addInfo(__('Pago borrado con exitó'));
			}
	}
	if($opc == "eliminar_documento")
	{
		$documento_eliminado = new Documento($sesion);
		$documento_eliminado->Load($id_documento_eliminado);

		$documento_eliminado->EliminarNeteos();
		$query_p = "DELETE from cta_corriente WHERE cta_corriente.documento_pago = '".$id_documento_eliminado."' ";
		mysql_query($query_p, $sesion->dbh) or Utiles::errorSQL($query_p,__FILE__,__LINE__,$sesion->dbh);

		if($documento_eliminado->Delete())
			$pagina->AddInfo(__('El documento ha sido eliminado satisfactoriamente'));
	}
	if(!$cobro->Load($id_cobro))
		$pagina->FatalError(__('Cobro inválido'));
		
	$moneda_total = new Moneda($sesion);
	$moneda_total->Load($cobro->fields['opc_moneda_total']);
		
	if(!$contrato->Load($cobro->fields['id_contrato']))
		$pagina->FatalError(__('Contrato inválido'));

	$idioma->Load($contrato->fields['codigo_idioma']);
	
	/*Antes de cargar el documento_cobro, es posible que se deje en 0 (anular emisión) o que se reinicie (cambio de estado desde incobrable)*/
	if($opc == 'anular_emision')
	{
		$cobro->AnularEmision($estado);	
		
		#Se ingresa la anotación en el historial
		$his = new Observacion($sesion);
		$his->Edit('fecha',date('Y-m-d H:i:s'));
		$his->Edit('comentario',"COBRO INCOBRABLE");
		$his->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
		$his->Edit('id_cobro',$cobro->fields['id_cobro']);
		if($his->Write())
			$pagina->AddInfo(__('Historial ingresado'));
	}
	//Se reinicia el documento del cobro
	if($cobro->fields['estado'] == 'INCOBRABLE' && $opc == 'guardar' && $estado != 'INCOBRABLE')
	{
				$cobro->ReiniciarDocumento();
	}

	//Ahora si se puede cargar el documento actualizado.
	if($documento_cobro->LoadByCobro($id_cobro))
	{
		$moneda_documento = new Moneda($sesion);
		$moneda_documento->Load($documento_cobro->fields['id_moneda']);
	}
	$moneda = new Moneda($sesion);

	if(!$fecha_pago && $opc != 'guardar')
	$fecha_pago=$cobro->fields['fecha_cobro'];
	/*Comprobaciones previas*/

	if($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION')
		$pagina->Redirect("cobros5.php?popup=1&id_cobro=".$id_cobro.($id_foco?'&id_foco='.$id_foco:''));

	if( !UtilesApp::GetConf($sesion,'NuevoModuloFactura') )
		{
			if($factura->LoadByCobro($id_cobro)) 
        {
                if( $opc == 'anular_factura' ) 
                {
                        #Buscar nuevo numero por la factura y ocupalo dentro de la tabla cobros 
                        $cobro->Edit('documento',''); 
                        $cobro->Edit('facturado', 0); 
                        $cobro->Write(); 
                        $factura->Edit('anulado',1); 
                        if($factura->Escribir())
                                $pagina->AddInfo(__('Factura anulado.')); 
                }
                $id_factura=$factura->fields['id_factura']; 
        }
        elseif($opc=='facturar') 
        {
                $moneda_factura = new Moneda($sesion);
                $moneda_factura->Load($documento_cobro->fields['id_moneda']);
                 
                # Se genera una factura "base", esta puede ser modificada
                $factura = new Factura($sesion);
                if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaNumeracionAutomatica') ) || ( method_exists('Conf','UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() ) ) 
                        { 
                        $numero_documento = $factura->ObtieneNumeroFactura(); 
                        $factura->Edit('numero',$numero_documento); 
                        } 
                else 
                        $factura->Edit('numero',$documento); 
                $factura->Edit('fecha',date('Y-m-d')); 
                $factura->Edit('cliente',$contrato->fields['factura_razon_social']); 
                $factura->Edit('RUT_cliente',$contrato->fields['rut']); 
                $factura->Edit('codigo_cliente',$cobro->fields['codigo_cliente']); 
                $factura->Edit('direccion_cliente',$contrato->fields['factura_direccion']); 
                if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CalculacionCyC') ) || ( method_exists('Conf','CalculacionCyC') && Conf::CalculacionCyC() ) ) 
                        { 
                                $factura->Edit('subtotal',number_format($documento_cobro->fields['subtotal_honorarios'],$moneda_factura->fields['cifras_decimales'],".","")); 
                                $factura->Edit('subtotal_gastos',number_format($documento_cobro->fields['subtotal_gastos'],$moneda_factura->fields['cifras_decimales'],".","")); 
                                $factura->Edit('descuento_honorarios',number_format($documento_cobro->fields['descuento_honorarios'],$moneda_factura->fields['cifras_decimales'],".","")); 
                                $factura->Edit('subtotal_sin_descuento',number_format($documento_cobro->fields['subtotal_sin_descuento'],$moneda_factura->fields['cifras_decimales'],".","")); 
                        } 
                else  
                        { 
                                $factura->Edit('subtotal',number_format(($documento_cobro->fields['honorarios']-$documento_cobro->fields['impuesto']),$decimales_base,".","")); 
                        } 
                $factura->Edit('iva',number_format($documento_cobro->fields['impuesto'],$decimales_base,".","")); 
                $factura->Edit('total',number_format($documento_cobro->fields['honorarios']+$documento_cobro->fields['gastos'],$decimales_base,".","")); 
 
                $cobro->Edit('facturado',1); 
                if(UtilesApp::GetConf($sesion,'NuevoModuloFactura'))
				{
					$query_lista_docLegalesActivos = "SELECT  
								group_concat(idfactura) as listaFacturas 
								,group_concat(idNotaCredito) as listaNotaCredito
								FROM (
								SELECT 
								 if(id_documento_legal = 1, if(letra is not null , letra, id_factura), '') as idfactura
								,if(id_documento_legal = 2, if(letra is not null , letra, id_factura), '') as idNotaCredito
								,id_cobro
								FROM factura
								WHERE id_cobro = '".$id_cobro."'
								AND anulado = 1
								)zz
								GROUP BY id_cobro";
					$resp_lista_docLegalesActivos = mysql_query($query_lista_docLegalesActivos,$sesion->dbh) or Utiles::errorSQL($resp_lista_docLegalesActivos,__FILE__,__LINE__,$sesion->dbh);
					list($lista_facturasActivas, $lista_NotaCreditoActivas)=mysql_fetch_array($resp_lista_docLegalesActivos);
					$cobro->Edit('documento',$lista_facturasActivas); 
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || (  method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) 
							$cobro->Edit('nota_cobro',$lista_NotaCreditoActivas);
				}
				else
				{	
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaNumeracionAutomatica') ) || ( method_exists('Conf','UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() ) ) 
							{ 
							$cobro->Edit('documento',$numero_documento); 
							} 
					else 
							$cobro->Edit('documento',$documento); 
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || (  method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) 
							$cobro->Edit('nota_cobro',$nota_cobro); 
					
				}
				$cobro->Write(); 
				
                #Fechas periodo 
                $datefrom = strtotime($cobro->fields['fecha_ini'], 0); 
                $dateto = strtotime($cobro->fields['fecha_fin'], 0); 
                $difference = $dateto - $datefrom; //Dif segundos 
                $months_difference = floor($difference / 2678400); 
                while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) 
                { 
                        $months_difference++; 
                } 
 
                $datediff = $months_difference; 

                if($cobro->fields['fecha_ini'] != '' && $cobro->fields['fecha_ini'] != '0000-00-00') 
                        $texto_fecha = __('entre los meses de').' '.Utiles::sql3fecha($cobro->fields['fecha_ini'],'%B').' '.__('y').' '.Utiles::sql3fecha($cobro->fields['fecha_fin'],'%B'); 
                else 
                        $texto_fecha = __('hasta el mes de').' '.Utiles::sql3fecha($cobro->fields['fecha_fin'],'%B'); 
 
                if( $lang == 'es' ) 
                        { 
                                $servicio_periodo = 'Honorarios por servicios profesionales prestados %fecha_ini% %fecha_fin%'; 
                                $meses_org = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');  
                                $mes_corto = array('jan','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'); 
                                 if($cobro->fields['fecha_ini'] && $cobro->fields['fecha_ini'] != '0000-00-00') 
                                        $servicio_periodo = str_replace('%fecha_ini%','desde '.str_replace($meses_org,$mes_corto,date( 'j-M-y' ,strtotime($cobro->fields['fecha_ini']))), $servicio_periodo); 
                                else 
                                        $servicio_periodo = str_replace('%fecha_ini%','', $servicio_periodo); 
                                if( $cobro->fields['fecha_fin'] && $cobro->fields['fecha_fin'] != '0000-00-00' ) 
                                        $servicio_periodo = str_replace('%fecha_fin%','hasta '.str_replace($meses_org,$mes_corto,date( 'j-M-y' ,strtotime($cobro->fields['fecha_fin']))), $servicio_periodo); 
                                else 
                                        $servicio_periodo = str_replace('%fecha_fin%','', $servicio_periodo); 
                        } 
                else 
                        { 
                                $servicio_periodo = 'For legal services rendered %fecha_ini% %fecha_fin%'; 
                                $meses_org = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');  
                                $month_short = array('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'); 
                                if($cobro->fields['fecha_ini'] && $cobro->fields['fecha_ini'] != '0000-00-00') 
                                        $servicio_periodo = str_replace('%fecha_ini%','from '.str_replace($meses_org,$month_short,date( 'M-d-y' ,strtotime($cobro->fields['fecha_ini']))), $servicio_periodo); 
                                else 
                                        $servicio_periodo = str_replace('%fecha_ini%','', $servicio_periodo); 
                                if( $cobro->fields['fecha_fin'] && $cobro->fields['fecha_fin'] != '0000-00-00') 
                                        $servicio_periodo = str_replace('%fecha_fin%','until '.str_replace($meses_org,$month_short,date( 'M-d-y' ,strtotime($cobro->fields['fecha_fin']))), $servicio_periodo); 
                                else 
                                        $servicio_periodo = str_replace('%fecha_fin%','', $servicio_periodo); 
                        } 
                         
 
                $fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha : __('durante el mes de').' '.Utiles::sql3fecha($cobro->fields['fecha_fin'],'%B'); 
                $factura->Edit('descripcion',$servicio_periodo); 
                $factura->Edit('id_cobro',$cobro->fields['id_cobro']); 
                $factura->Edit('id_moneda', $documento_cobro->fields['id_moneda']); 
                $factura->Edit('honorarios', $documento_cobro->fields['subtotal_sin_descuento']);
                $factura->Edit('gastos', $documento_cobro->fields['gastos']); 
                if( $factura->Escribir() )
                {
                	if($id_cobro)
									{
										$documento = new Documento($sesion);
										$documento->LoadByCobro($id_cobro);
										
										$valores = array( 
											$factura->fields['id_factura'],
											$id_cobro,
											$documento->fields['id_documento'],
											$factura->fields['subtotal'],
											$factura->fields['iva'],
											$documento->fields['id_moneda'],
											$documento->fieñds['id_moneda']
										);
										
										$query = "DELETE FROM factura_cobro WHERE id_factura = '".$factura->fields['id_factura']."' AND id_cobro = '".$id_cobro."' ";
										$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
										
										$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento)
										VALUES ('".implode("','",$valores)."')";
										$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
									}
                } 
                $id_factura=$factura->fields['id_factura']; 
        }
		}
	
	if($opc == 'guardar')
	{
		$cambiar_estado = false;
		if($estado != $cobro->fields['estado'])
			$cambiar_estado = true;

		if($facturado == 1 && !$cobro->fields['fecha_facturacion'])
			$cobro->Edit('fecha_facturacion',date('Y-m-d H:i:s'));

		$cobro->Edit('facturado', $facturado ? $facturado : 0);
		$cobro->Edit('fecha_emision', $fecha_emision ? Utiles::fecha2sql($fecha_emision) : '');
		$cobro->Edit('fecha_enviado_cliente', $fecha_envio ? Utiles::fecha2sql($fecha_envio) : '');
		$cobro->Edit('fecha_cobro', $fecha_pago ? Utiles::fecha2sql($fecha_pago) : '');

		$cobro->SetPagos($honorarios_pagados,$gastos_pagados);
		//Ahora hay que revisar que no se haya pasado a PAGADO, cambiando estado a PAGADO (por Historial).
		if($estado == 'PAGADO' && $cobro->fields['estado'] == 'PAGADO')
			$cambiar_estado = false;

		$cobro->Edit('forma_envio',$forma_envio);
		
		if( !( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaNumeracionAutomatica') ) && !( method_exists('Conf','UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() ) ) 
		{
			$factura->Edit('numero',$documento);
			$factura->Escribir();
			$cobro->Edit('documento',$documento);
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || (  method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) )
				$cobro->Edit('nota_cobro',$nota_cobro);
		}


		if($email_cliente != $contrato->fields['email_contacto'])
		{
			$contrato->Edit('email_contacto',$email_cliente);
			$contrato->Write();
		}

		$cobro->Edit('se_esta_cobrando',$se_esta_cobrando);

		$cobro->Write();
	}

	

	if($cambiar_estado)
	{				
			if($estado == 'EMITIDO' && !$cobro->fields['fecha_emision'])
				$cobro->Edit('fecha_emision',date('Y-m-d H:i:s'));
			if($estado == 'ENVIADO AL CLIENTE' && !$cobro->fields['fecha_enviado_cliente'])
				$cobro->Edit('fecha_enviado_cliente',date('Y-m-d H:i:s'));
			if($estado == 'PAGADO' && !$cobro->fields['fecha_cobro'])
				$cobro->Edit('fecha_cobro',date('Y-m-d H:i:s'));
			$cobro->Edit('estado',$estado);
			$cobro->Write();

			#Se ingresa la anotación en el historial
			$his = new Observacion($sesion);
			$his->Edit('fecha',date('Y-m-d H:i:s'));
			$his->Edit('comentario',"COBRO $estado");
			$his->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro',$cobro->fields['id_cobro']);
			if($his->Write())
				$pagina->AddInfo(__('Historial ingresado'));
	}


	if($opc == 'grabar_documento' || $opc == 'guardar')
	{
		$cobro->Edit("opc_ver_modalidad",$opc_ver_modalidad);
		$cobro->Edit("opc_ver_profesional",$opc_ver_profesional);
		$cobro->Edit("opc_ver_gastos",$opc_ver_gastos);
		$cobro->Edit("opc_ver_morosidad",$opc_ver_morosidad);
		$cobro->Edit("opc_ver_resumen_cobro",$opc_ver_resumen_cobro);
		$cobro->Edit("opc_ver_descuento",$opc_ver_descuento);
		$cobro->Edit("opc_ver_tipo_cambio",$opc_ver_tipo_cambio);
		$cobro->Edit("opc_ver_numpag",$opc_ver_numpag);
		$cobro->Edit("opc_papel",$opc_papel);
		$cobro->Edit("opc_ver_solicitante",$opc_ver_solicitante);
		$cobro->Edit('opc_ver_carta',$opc_ver_carta);
		$cobro->Edit("opc_ver_asuntos_separados",$opc_ver_asuntos_separados);
		$cobro->Edit("opc_ver_horas_trabajadas",$opc_ver_horas_trabajadas);
		$cobro->Edit("opc_ver_cobrable",$opc_ver_cobrable);
		// Opciones especificos para Vial Olivares
			$cobro->Edit("opc_restar_retainer",$opc_restar_retainer);
			$cobro->Edit("opc_ver_detalle_retainer",$opc_ver_detalle_retainer);
		$cobro->Edit("opc_ver_valor_hh_flat_fee",$opc_ver_valor_hh_flat_fee);
		$cobro->Edit('id_carta',$id_carta);
		$cobro->Edit('id_formato',$id_formato);
		$cobro->Edit('codigo_idioma',$lang);
		if($cobro->Write())
		{
			if($opc == 'grabar_documento')
			{
				include dirname(__FILE__).'/cobro_doc.php';
				exit;
			}
		}
	}
	elseif($opc == 'grabar_documento_factura')
	{
		include dirname(__FILE__).'/factura_doc.php';
		exit;
	}
	elseif($opc == 'descargar_excel')
	{
		$cobro->Edit("opc_ver_modalidad",$opc_ver_modalidad);
		$cobro->Edit("opc_ver_profesional",$opc_ver_profesional);
		$cobro->Edit("opc_ver_gastos",$opc_ver_gastos);
		$cobro->Edit("opc_ver_morosidad",$opc_ver_morosidad);
		$cobro->Edit("opc_ver_resumen_cobro",$opc_ver_resumen_cobro);
		$cobro->Edit("opc_ver_descuento",$opc_ver_descuento);
		$cobro->Edit("opc_ver_tipo_cambio",$opc_ver_tipo_cambio);
		$cobro->Edit("opc_ver_numpag",$opc_ver_numpag);
		$cobro->Edit("opc_papel",$opc_papel);
		$cobro->Edit("opc_ver_solicitante",$opc_ver_solicitante);
		$cobro->Edit('opc_ver_carta',$opc_ver_carta);
		$cobro->Edit("opc_ver_asuntos_separados",$opc_ver_asuntos_separados);
		$cobro->Edit("opc_ver_horas_trabajadas",$opc_ver_horas_trabajadas);
		$cobro->Edit("opc_ver_cobrable",$opc_ver_cobrable);
		// Opciones especificos para Vial Olivares
			$cobro->Edit("opc_restar_retainer",$opc_restar_retainer);
			$cobro->Edit("opc_ver_detalle_retainer",$opc_ver_detalle_retainer);
		$cobro->Edit('id_carta',$id_carta);
		$cobro->Edit('id_formato',$id_formato);
		$cobro->Edit('codigo_idioma',$lang);
		if($cobro->Write())
		{
		$desde_cobros_emitidos = true;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'XLSFormatoEspecial') ) || ( method_exists('Conf', 'XLSFormatoEspecial') && Conf::XLSFormatoEspecial() ) )
			require_once Conf::ServerDir().'/../app/interfaces/cobros_xls_formato_especial.php';
		else
			require_once Conf::ServerDir().'/../app/interfaces/cobros_xls.php';
		exit;
	}
		$desde_cobros_emitidos = true;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'XLSFormatoEspecial') ) || ( method_exists('Conf', 'XLSFormatoEspecial') && Conf::XLSFormatoEspecial() ) )
			require_once Conf::ServerDir().'/../app/interfaces/cobros_xls_formato_especial.php';
		else
			require_once Conf::ServerDir().'/../app/interfaces/cobros_xls.php';
		exit;
	}

	$cobro->Edit('etapa_cobro','5');
	$cobro->Write();
	$moneda->Load($cobro->fields['id_moneda']);

	$pagina->titulo = __('Imprimir').' '.__('Cobro').' #'.$id_cobro.' '.__('para')." ".Utiles::Glosa($sesion, $cobro->fields['codigo_cliente'],'glosa_cliente','cliente','codigo_cliente');
	if($popup)
	{
?>
		<table width="100%" border="0" cellspacing="0" cellpadding="2">
			<tr>
				<td valign="top" align="left" class="titulo" bgcolor="<?=(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColorTituloPagina'):Conf::ColorTituloPagina())?>">
					<?=__('Imprimir').' '.__('Cobro').' #'.$id_cobro.' '.__('para')." ".Utiles::Glosa($sesion, $cobro->fields['codigo_cliente'],'glosa_cliente','cliente','codigo_cliente');?>
				</td>
			</tr>
		</table>
		<br>
<?
	}
	$pagina->PrintTop($popup);
	$pagina->PrintPasos($sesion,5,'',$id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']);

	#Moneda base
	$moneda_base = new Objeto($sesion,'','','prm_moneda','id_moneda');
	$moneda_base->Load($cobro->fields['id_moneda_base']);

	/* Tipo cambio segï¿½n moneda impresion (opc_moneda) */
		$cobro_moneda = new CobroMoneda($sesion);
		$cobro_moneda->Load($cobro->fields['id_cobro']);
		$tipo_cambio_moneda_total = $cobro_moneda->GetTipoCambio($id_cobro,$cobro->fields['opc_moneda_total'] != '' ? $cobro->fields['opc_moneda_total'] : 1);

	## Contrato
	if($cobro->fields['id_contrato']!='')
	{
		$contrato = new Contrato($sesion);
		$contrato->Load($cobro->fields['id_contrato']);
	}
	
?>
<script>
document.observe('dom:loaded', function() {
	//new Tip('c_edit', 'Continuar con el cobro', {offset: {x:-2, y:5}});
});

function Refrescar()
{
	<? echo "self.location.href= 'cobros6.php?popup=1&id_cobro=".$id_cobro."';"; ?>
}

function MostrarTipoCambio()
{
	$('TipoCambioDocumento').show();
}

function CancelarDocumentoMoneda()
{
	$('TipoCambioDocumento').hide();
}

function ActualizarDocumentoMoneda()
{
	ids_monedas = $('ids_monedas_documento').value;
	arreglo_ids = ids_monedas.split(',');
	var tc = new Array();
	for(var i = 0; i< arreglo_ids.length; i++)
			tc[i] = $('documento_moneda_'+arreglo_ids[i]).value;
	$('contenedor_tipo_cambio').innerHTML = 
	"<table width=510px><tr><td align=center><br><br><img src='<?=Conf::ImgDir()?>/ajax_loader.gif'/><br><br></td></tr></table>";
	var http = getXMLHTTP();
	var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_documento_moneda&id_documento=<?=$documento_cobro->fields['id_documento']?>&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');	
	http.open('get', url);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			if(response == 'EXITO')
			{
				Refrescar();	
			}
		}
	}	
	http.send(null);
}

function AgregarPagoFactura()
{
	var monto = 0;
	var lista_facturas = "";
	$$('[id^="saldo_"]').each(function(elem){ 
		ids=elem.id.split('_');
		if($('pagar_factura_'+ids[1]).checked) {
			lista_facturas += ","+ids[1];
			monto += (MontoFacturaMoneda(ids[1])-0);
		}
	});
	lista_facturas = lista_facturas.substr(1);
	var codigo_cliente = '<?=$cobro->fields['codigo_cliente']?>';
	nuevaVentana('Agregar_Pago', 630, 520, 'agregar_pago_factura.php?lista_facturas='+lista_facturas+'&id_moneda='+$('opc_moneda_total').value+'&monto_pago='+monto+'&codigo_cliente='+codigo_cliente+'&id_cobro=<?=$cobro->fields['id_cobro']?>&popup=1', 'top=100, left=155, scrollbars=yes');
}

function EliminarPago( id_pago )
{
	$('opc').value = 'eliminar_pago';
	if( $('eliminar_pago').value = id_pago )	
		$('todo_cobro').submit();
}

function MontoFacturaMoneda( id )
{
	var monto = $('saldo_'+id).value;
	var tc1 = $('tipo_cambio_factura_'+id).value;
	var cd1 = $('cifras_decimales_factura_'+id).value;
	var tc2 = $('tipo_cambio_moneda_total').value;
	var cd2 = $('cifras_decimales_moneda_total').value;
	
	var resultado = ( Math.pow(10,cd2) * ( monto * tc1 / tc2 ) ).round() / Math.pow(10,cd2);
	return resultado;
}
	
function ValidarTodo(form)
{
	if(form.estado.value == 'CREADO') //Significa que estoy anulando la emisión
	{
		if(!confirm('<?=__("¿Está seguro que requiere anular la emisión de este cobro?")?>'))
			return false;
		else
		{
			if( form.existe_factura.value == 1 )
			{
				alert( 'No se puede anular un cobro que tiene facturas asociados.' );
				form.estado.value = form.estado_original.value;
				return false;
			}
			else
			{
				form.action = 'cobros5.php?popup=1';
				form.opc.value = 'anular_emision';
				form.submit();
				return false;
			}
		}
	}
	
	if( form.estado.value == 'EN REVISION' ) //Significa que estoy anulando la emisión
	{
		if(!confirm('<?=__("¿Está seguro que requiere anular la emisión de este cobro?")?>'))
			return false;
		else
		{
				form.action = 'cobros5.php?popup=1';
				form.opc.value = 'anular_emision';
				form.submit();
				return false;
		}
	}
	
	if(form.estado.value == 'INCOBRABLE') //Significa que estoy dando como INCOBRABLE el cobro
	{
		if(!confirm('<?=__("¿Está seguro que desea definir este cobro como \"Incobrable\"?")?>'))
			return false;
		else
		{
			form.opc.value = 'anular_emision';
			form.submit();
			return false;
		}
	}
	
	if(form.estado.value == 'PAGADO' && form.estado_original.value != 'PAGADO' && !form.existe_pago.value) //No se puede avanzar a PAGADO por aqui. Debe ser mediante Agregar Pago.
	{
		alert('<?=__("No puede definir el Cobro como \"PAGADO\". Debe ingresar un documento de pago completo por el saldo pendiente.")?>');
		return false;
	}
	
	if(form.estado.value != form.estado_original.value)
	{
			if(!confirm('<?=__("¿Está seguro de que desea modificar el estado del cobro?")?>'))
				return false;
	}

	form.opc.value = 'guardar';
	form.submit();
	return true;
}

function AnularFactura(form,opcion)
{
	if(!form)
		var form = $('todo_cobro');
	$('facturado').checked = false;
	form.opc.value = 'anular_factura';
	form.submit();
	return true;
}

	function MostrarVerDocumentosPagos(id_factura)
	{
		$('VerDocumentosPagos_'+id_factura).show();
	}
	
	function CancelarVerDocumentosPagos(id_factura)
	{
		$('VerDocumentosPagos_'+id_factura).hide();
	}
	
function ValidarFactura(form,id_factura,opcion)
{
<?
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) )
	{
?>
		if(!form)
			var form = $('todo_cobro');
		if(opcion=='imprimir')
		{
			<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ImprimirFacturaPdf') ) || ( method_exists('Conf','ImprimirFacturaPdf') && Conf::ImprimirFacturaPdf() ) )
		 			{ ?>
					nuevaVentana('Imprimir_Factura',730,580,'agregar_factura.php?opc=generar_factura&id_cobro=<?=$id_cobro?>&id_factura='+id_factura, 'top=500, left=500');
					//ValidarTodo(form);
			<?	}
				else
					{	?>
					form.opc.value='grabar_documento_factura';
					form.id_factura_grabada.value = id_factura;
			<?	}	?>
		}
		else
		{
			$('facturado').checked = true;
			form.opc.value = 'facturar';
		}
		form.submit();
		return true;
<?
	}
	else
	{
?>
		alert('Funcionalidad en desarrollo.');
		return false;
<?
	}
?>
}

function AlertarPago(checked)
{
	if(checked)
		alert("Usted ha marcado un Pago sin especificar documento.\nSe recomienda utilizar la opción de 'Agregar Pago' para cancelar el cobro.");
}

function ToggleDiv( divId )
{
	var divObj = document.getElementById( divId );

	if( divObj )
	{
		if( divObj.style.display == 'none' )
			divObj.style.display = 'inline';
		else
			divObj.style.display = 'none';
	}
}

/*Desacativa/activa carta selector*/
function ActivaCarta(check)
{
	var form = $('todo_cobro');
	if(check)
		form.id_carta.disabled = false;
	else
		form.id_carta.disabled = true;
}

function VerDetalles( form )
{
	form.opc.value = 'grabar_documento';
	form.submit();
	return true;
}

function DescargarExcel(form)
{
	form.opc.value = 'descargar_excel';
	form.submit();
	return true;
}

function UpdateCobro(valor, accion)
{
	var form = $('cambio_estado');
	var id = form.id_cobro.value;

	var http = getXMLHTTP();
	http.open('get', 'ajax.php?accion='+accion+'&id_cobro='+id+'&valor='+valor, false);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
		}
	};
	http.send(null);
}

function ShowGastos(show)
{
	var tr_gastos = $('tr_gastos');
	var btnEstado = $('btnEstado');

	if(show)
	{
		tr_gastos.style.display = 'inline';
		btnEstado.style.display = 'none';
	}
	else
	{
		tr_gastos.style.display = 'none';
		btnEstado.style.display = 'inline';
	}
}

function RevisarPagado(estado)
{
	if(estado == 'PAGADO')
	{
		if($("estado_original").value=='INCOBRABLE')
		{
				alert("<?=__('No puede pasar a estado \'Pagado\'. Debe pasar por estado \'Emitido\' para recuperar los montos, luego agregar el pago.')?>");
				$("estado").selectedIndex = 5;
				return false;
		}
		
		if( $('existe_pago').value == 1 )
			return true;
		
		if($("estado_original").value=='EMITIDO' || $("estado_original").value=='ENVIADO AL CLIENTE' )
		{
				AgregarPago();
		}
	}
	if( estado != 'PAGADO' && $('estado_original').value == 'PAGADO' && $('hay_pagos').value == 'si')
	{
		$("estado").selectedIndex = 4;
		alert('<?=__("No se puede salir de estado PAGADO. Debe eliminar los Documentos de Pago del listado.")?>');
	}
}

function AgregarPago()
{
		<?="var urlo = \"ingresar_documento_pago.php?popup=1&pago=true&id_cobro=".$cobro->fields['id_cobro']."&codigo_cliente=".$cobro->fields['codigo_cliente']."\";"?>
		nuevaVentana('Ingreso',730,470,urlo,'top=100, left=125, scrollbars=yes');
}

function EditarPago(id)
{
		<?="var urlo = \"ingresar_documento_pago.php?popup=1&pago=true&id_cobro=".$cobro->fields['id_cobro']."&id_documento=\"+id+\"&codigo_cliente=".$cobro->fields['codigo_cliente']."\";"?>
		nuevaVentana('Ingreso',730,470,urlo,'top=100, left=125');
}

function EliminaDocumento(id_documento)
{
	var form = $('todo_cobro');
	if(parseInt(id_documento) > 0 && confirm('¿Desea eliminar el documento #'+id_documento+'?') == true)
		self.location.href = 'cobros6.php?popup=1&id_cobro='+<?=$id_cobro?>+'&id_documento_eliminado='+id_documento+'&opc=eliminar_documento';
}

function AgregarFactura(idx){
	<?php
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			$cliente_factura = new Cliente($sesion);
			$codigo_cliente_secundario_factura = $cliente_factura->CodigoACodigoSecundario($cobro->fields['codigo_cliente']);
			$url_agregar_factura = "agregar_factura.php?popup=1&id_cobro=".$id_cobro."&codigo_cliente_secundario=".$codigo_cliente_secundario_factura;
		}
		else
		{
			$url_agregar_factura = "agregar_factura.php?popup=1&id_cobro=".$id_cobro."&codigo_cliente=".$cobro->fields['codigo_cliente'];
		}

		$query = "SELECT id_documento_legal, codigo FROM prm_documento_legal";
		$resp =mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
		$id_tipo_documento = array();
		while(list($id, $codigo) = mysql_fetch_array($resp)) $id_tipo_documento[$codigo] = $id;
	?>

	var honorarios = Number($F('honorarios_'+idx).replace(',', '.'));
	var gastos_con_impuestos = Number($F('gastos_con_impuestos_'+idx).replace(',', '.'));
	var gastos_sin_impuestos = Number($F('gastos_sin_impuestos_'+idx).replace(',', '.'));

	var honorarios_disp = Number($F('honorarios_disponibles').replace(',', '.'));
	var gastos_con_impuestos_disp = Number($F('gastos_con_iva_disponibles').replace(',', '.'));
	var gastos_sin_impuestos_disp = Number($F('gastos_sin_iva_disponibles').replace(',', '.'));

	var esCredito = $F('tipo_documento_legal_'+idx) == <?=$id_tipo_documento['NC']?>;
	var esFactura = $F('tipo_documento_legal_'+idx) == <?=$id_tipo_documento['FA']?>;
	var esDebito = $F('tipo_documento_legal_'+idx) == <?=$id_tipo_documento['ND']?>;

	if(!honorarios && !gastos_con_impuestos && !gastos_sin_impuestos ||
		honorarios<0 || gastos_con_impuestos<0 || gastos_sin_impuestos<0){
		alert('Ingrese montos válidos para el documento legal');
		return false;
	}
	/* revisar validacion segun tipo_doc: credito siempre funca, debito tb?, factura necesita saldo, boleta...? y los otros? (en rebaza hay varios mas)*/
	if(!esCredito && (
		honorarios > honorarios_disp ||
		gastos_con_impuestos > gastos_con_impuestos_disp ||
		gastos_sin_impuestos > gastos_sin_impuestos_disp)){
		if(!confirm('<?=__("Los montos ingresados superan el saldo a facturar")?>')){
			if(honorarios > honorarios_disp) {
				$('honorarios_'+idx).focus();
			}
			else if(gastos_con_impuestos > gastos_con_impuestos_disp) {
				$('gastos_con_impuestos_'+idx).focus();
			}
			else if(gastos_sin_impuestos > gastos_sin_impuestos_disp) {
				$('gastos_sin_impuestos_'+idx).focus();
			}
			return false;
		}
	}
	
	nuevaVentana('Agregar_Factura', 730, 580, '<?php echo $url_agregar_factura;?>' +
		'&honorario=' + honorarios +
		'&gastos_con_iva=' + gastos_con_impuestos +
		'&gastos_sin_iva=' + gastos_sin_impuestos +
		'&honorario_disp=' + honorarios_disp +
		'&gastos_con_impuestos_disp=' + gastos_con_impuestos_disp +
		'&gastos_sin_impuestos_disp=' + gastos_sin_impuestos_disp +
		'&id_documento_legal='+$F('tipo_documento_legal_'+idx), 'top=100, left=155');

}
</script>
<br>

<? 
	$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'],array(),0,true);
	$query = "SELECT count(*) FROM factura_cobro WHERE id_cobro = '".$cobro->fields['id_cobro']."'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($numero_facturas_asociados) = mysql_fetch_array($resp);
	if( $numero_facturas_asociados > 0 ) 
		$existe_factura = 1;
	else
		$existe_factura = 0;
		
		
	$query = "SELECT count(*) FROM documento WHERE id_cobro = '".$cobro->fields['id_cobro']."' AND tipo_doc != 'N'";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($numero_documentos_pagos_asociados) = mysql_fetch_array($resp);
	
	if( $numero_documentos_pagos_asociados > 0 )
		$existe_pago = 1;
	else
		$existe_pago = 0;
?>

<!-- Estado del Cobro -->
<form name="todo_cobro" id='todo_cobro' method="post" action="">
<input type="hidden" name="existe_factura" id="existe_factura" value="<?=$existe_factura?>" />
<input type="hidden" name="existe_pago" id="existe_pago" value="<?=$existe_pago?>" />
<input type="hidden" name="id_cobro" value="<?=$cobro->fields['id_cobro']?>" />
<input type="hidden" name="id_factura_grabada" value = 0 />
<input type="hidden" name="estado_original" id="estado_original" value="<?=$cobro->fields['estado']?>" />
<input type="hidden" name="honorarios_pagados_original" value="<?=$cobro->fields['honorarios_pagados']?>" />
<input type="hidden" name="gastos_pagados_original" value="<?=$cobro->fields['gastos_pagados']?>" />
<input type="hidden" name="eliminar_pago" id="eliminar_pago" value="" />
<input type=hidden name=opc id=opc>

<table width="100%">
	<tr>
		<!-- Cabecera -->
		<td colspan="3" align="center">
			<!-- Calendario DIV -->
			<div id="calendar-container" style="width:221px; position:absolute; display:none;">
			<div class="floating" id="calendar"></div>
			</div>
			<!-- Fin calendario DIV -->

			<?function TArriba($celda,$estado)
			{
				if( ( $celda == 'BORRADOR' && ( $estado == 'CREADO' || $estado == 'EN REVISION' ) ) || $celda == $estado )
					return "<td rowspan = 3 style=\"text-align:center; vertical-align: middle; border: 1px solid #5bde5b; background: #fefeaa ; font-size: 12px;\"> ".$estado." </td>";
				else
					return "<td> </td>";
			}
			 function TMedio($celda,$estado)
			{
				if( ( $celda == 'BORRADOR' && ( $estado == 'CREADO' || $estado == 'EN REVISION' ) ) || $celda == $estado )
					return " ";
				else
					return "<td  style=\"text-align:center; border: 1px solid #9f9f9f; background: #efefef; font-size: 9px;\"> ".$celda."</td>";
			}
			function TAbajo($celda,$estado)
			{
				if( ( $celda == 'BORRADOR' && ( $estado == 'CREADO' || $estado == 'EN REVISION' ) ) || $celda == $estado )
					return " ";
				else
					return "<td> </td>";
			}
			?>

			<table border="0" cellspacing="0" cellpadding="1" bgcolor="#dfdfdf" style="border: 1px solid #bfbfcf;" width="100%" height="30">
				<tr style="height: 26px;  vertical-align: middle;" >
					<td style="height: 26px;  vertical-align: top;" align=right nowrap>
						<br>
						<?=__('Avance del Cobro')?>:&nbsp;&nbsp;
						<!-- <br/><br/><?=__('Forma de cobro')?>: <?= $cobro->fields['forma_cobro'] ?> -->
					</td>
					<td align="left" style="font-size: 11px; font-weight: bold;">
						<table cellpadding="3">
							<tr height=3>
								<?=TArriba("BORRADOR",$cobro->fields['estado'])?>
								<?=TArriba("EMITIDO",$cobro->fields['estado'])?>
								<?=TArriba("ENVIADO AL CLIENTE",$cobro->fields['estado'])?>
								<?=TArriba("PAGADO",$cobro->fields['estado'])?>
							</tr>
							<tr height=13>
								<?=TMedio("BORRADOR",$cobro->fields['estado'])?>
								<?=TMedio("EMITIDO",$cobro->fields['estado'])?>
								<?=TMedio("ENVIADO AL CLIENTE",$cobro->fields['estado'])?>
								<?=TMedio("PAGADO",$cobro->fields['estado'])?>
							</tr>
							<tr height=3>
								<?=TAbajo("BORRADOR",$cobro->fields['estado'])?>
								<?=TAbajo("EMITIDO",$cobro->fields['estado'])?>
								<?=TAbajo("ENVIADO AL CLIENTE",$cobro->fields['estado'])?>
								<?=TAbajo("PAGADO",$cobro->fields['estado'])?>
							<tr>
							<tr>
								<td>
									<input type="text" value="<?= Utiles::sql2date($cobro->fields['fecha_creacion']) ?>" size="11" disabled />
								</td>
								<td nowrap>
									<input type="text" value="<?= Utiles::sql2date($cobro->fields['fecha_emision']) ?>" name="fecha_emision" id="fecha_emision" size="11" maxlength="10" />
									<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_emision" style="cursor:pointer" />
								</td>
								<td nowrap>
									<input type="text" name="fecha_envio" value="<?= Utiles::sql2date($cobro->fields['fecha_enviado_cliente']) ?>" id="fecha_envio" size="11" maxlength="10" />
									<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_envio" style="cursor:pointer" />
								</td>
								<td nowrap>
									<?
										if(Utiles::sql2date($cobro->fields['fecha_cobro']))
											$fecha_pago = Utiles::sql2date($cobro->fields['fecha_cobro']);
										else if($cobro->fields['estado']=='PAGADO')
											$fecha_pago = Utiles::sql2date($documento_cobro->FechaPagos());
									?>
									<input type="text" name="fecha_pago" value="<?=$fecha_pago?>" id="fecha_pago" size="11" maxlength="10" />
									<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_pago" style="cursor:pointer" />
								</td>
							</tr>
						</table>
					</td>
					<td rowspan="2" align="right">
							<!-- Imprimir -->
							<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;" width="220" height=100>
								<tr>
									<td align="left" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
										<input type="hidden" name="guardar_opciones" value="1" />
										<img src="<?=Conf::ImgDir()?>/imprimir_16.gif" border="0" alt="Imprimir"/> <?=__('Imprimir')?>
									</td>
									<td align="right"  style="vertical-align: middle;">
										<a href="javascript:void(0);" style="color: #990000; font-size: 9px; font-weight: normal;" onclick="ToggleDiv('doc_opciones');"><?=__('opciones')?></a>
									</td>
								</tr>
								<tr>
									<td align="center" colspan="2">
											<div id="doc_opciones" style="display: none; position: relative;">
											<table border="0" cellspacing="0" cellpadding="2" style="font-size: 10px;">
												<tr>
													<td colspan="2">&nbsp;</td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_asuntos_separados" id="opc_ver_asuntos_separados" value="1" <?=$cobro->fields['opc_ver_asuntos_separados']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_asuntos_separados"><?=__('Ver asuntos por separado')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_resumen_cobro" id="opc_ver_resumen_cobro" value="1" <?=$cobro->fields['opc_ver_resumen_cobro']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_resumen_cobro"><?=__('Mostrar resumen del cobro')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_modalidad" id="opc_ver_modalidad" value="1" <?=$cobro->fields['opc_ver_modalidad']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_modalidad"><?=__('Mostrar modalidad del cobro')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_profesional" id="opc_ver_profesional" value="1" <?=$cobro->fields['opc_ver_profesional']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_profesional"><?=__('Mostrar detalle por profesional')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_gastos" id="opc_ver_gastos" value="1" <?=$cobro->fields['opc_ver_gastos']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_gastos"><?=__('Mostrar gastos del cobro')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_morosidad" id="opc_ver_morosidad" value="1" <?=$cobro->fields['opc_ver_morosidad']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_morosidad"><?=__('Mostrar saldo adeudado')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_tipo_cambio" id="opc_ver_tipo_cambio" value="1" <?=$cobro->fields['opc_ver_tipo_cambio']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_tipo_cambio"><?=__('Mostrar tipos de cambio')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_descuento" id="opc_ver_descuento" value="1" <?=$cobro->fields['opc_ver_descuento']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_descuento"><?=_('Mostrar el descuento del cobro')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_numpag" id="opc_ver_numpag" value="1" <?=$cobro->fields['opc_ver_numpag']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_numpag"><?=__('Mostrar números de página')?></label></td>
												</tr>
										<?
											if(method_exists('Conf','GetConf'))
												$solicitante = Conf::GetConf($sesion, 'OrdenadoPor');
											elseif(method_exists('Conf','Ordenado_por'))
												$solicitante = Conf::Ordenado_por();
											else
												$solicitante = 2;

											if($solicitante == 0)		// no mostrar
											{
										?>
												<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />
										<?
											}
											elseif($solicitante == 1)	// obligatorio
											{
										?>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?=$cobro->fields['opc_ver_solicitante']=='1'?'checked="checked"':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_solicitante"><?=__('Mostrar solicitante')?></label></td>
												</tr>
										<?
											}
											elseif ($solicitante == 2)	// opcional
											{
										?>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?=$cobro->fields['opc_ver_solicitante']=='1'?'checked="checked"':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_solicitante"><?=__('Mostrar solicitante')?></label></td>
												</tr>
										<?
											}
										?>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_horas_trabajadas" id="opc_ver_horas_trabajadas" value="1" <?=$cobro->fields['opc_ver_horas_trabajadas']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_horas_trabajadas"><?=__('Mostrar horas trabajadas')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_cobrable" id="opc_ver_cobrable" value="1" <?=$cobro->fields['opc_ver_cobrable']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_cobrable"><?=__('Mostrar trabajos no visibles')?></label></td>
												</tr>
										<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ResumenProfesionalVial') ) || ( method_exists('Conf','ResumenProfesionalVial') && Conf::ResumenProfesionalVial() ) )
												{  ?>
												<tr>
													<td align="right"><input type="checkbox" name="opc_restar_retainer" id="opc_restar_retainer" value="1"  <?=$cobro->fields['opc_restar_retainer']=='1'?'checked="checked"':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_restar_retainer"><?=__('Restar valor retainer')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_detalle_retainer" id="opc_ver_detalle_retainer" value="1"  <?=$cobro->fields['opc_ver_detalle_retainer']=='1'?'checked="checked"':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_detalle_retainer"><?=__('Mostrar detalle retainer')?></label></td>
												</tr>
										<?    }  ?>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_valor_hh_flat_fee" id="opc_ver_valor_hh_flat_fee" value="1"  <?=$cobro->fields['opc_ver_valor_hh_flat_fee']=='1'?'checked="checked"':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_valor_hh_flat_fee"><?=__('Mostrar valor HH en caso de flat fee')?></label></td>
												</tr>
												<tr>
													<td align="right"><input type="checkbox" name="opc_ver_carta" id="opc_ver_carta" value="1" onclick="ActivaCarta(this.checked)" <?=$cobro->fields['opc_ver_carta']=='1'?'checked':''?>></td>
													<td align="left" style="font-size: 10px;"><label for="opc_ver_carta"><?=__('Mostrar Carta')?></label></td>
												</tr>
											<tr>
												<td align="right">&nbsp;</td>
												<td align="left" style="font-size: 10px;">
													<?=__('Formato de Carta Cobro')?>:
													<?= Html::SelectQuery($sesion, "SELECT carta.id_carta, carta.descripcion
																							FROM carta ORDER BY id_carta","id_carta",
																			$cobro->fields['id_carta'] ? $cobro->fields['id_carta'] : $contrato->fields['id_carta'], $cobro->fields['opc_ver_carta']=='1'?'':'disabled'); ?>
												</td>
											</tr> 
											<tr>
												<td align="right">&nbsp;</td>
												<td align="left" style="font-size: 10px;">
													<?=__('Formato Detalle Carta Cobro')?>:

													<?= Html::SelectQuery($sesion, "SELECT cobro_rtf.id_formato, cobro_rtf.descripcion
																							FROM cobro_rtf ORDER BY cobro_rtf.id_formato","id_formato",
																			$cobro->fields['id_formato'] ? $cobro->fields['id_formato'] : $contrato->fields['id_formato'], '','Seleccione'); ?>

												</td>
											</tr>
												<tr>
													<td align="right">&nbsp;</td>
													<td align="left" style="font-size: 10px;">
														<?=__('Tamaño del papel')?>:
														<select name="opc_papel">
															<option value="LETTER" <?=$cobro->fields['opc_papel']=='LETTER'?'selected':''?>><?=__('Carta')?></option>
															<option value="OFFICE" <?=$cobro->fields['opc_papel']=='OFFICE'?'selected':''?>><?=__('Oficio')?></option>
															<option value="A4" <?=$cobro->fields['opc_papel']=='A4'?'selected':''?>><?=__('A4')?></option>
															<option value="A5" <?=$cobro->fields['opc_papel']=='A5'?'selected':''?>><?=__('A5')?></option>
														</select>
													</td>
												</tr>
												<tr>
														<td align="right">&nbsp;</td>
														<td align="left" style="font-size: 10px;">
															<?=__('Mostrar total en')?>:
															<?=Html::SelectQuery( $sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_total_view',$cobro->fields['opc_moneda_total'] != '' ? $cobro->fields['opc_moneda_total'] : '1','disabled','','60')?>
															<input type="hidden" name="opc_moneda_total" id="opc_moneda_total" value="<?=$cobro->fields['opc_moneda_total'] ? $cobro->fields['opc_moneda_total'] : '1'?>">
															<input type="hidden" name="tipo_cambio_moneda_total" id="tipo_cambio_moneda_total" value="<?=$moneda_total->fields['tipo_cambio']?>">
															<input type="hidden" name="cifras_decimales_moneda_total" id="cifras_decimales_moneda_total" value="<?=$moneda_total->fields['cifras_decimales']?>">
														</td>
													</tr>
													<tr>
														<td align="right">&nbsp;</td>
														<td align="left" style="font-size: 10px;">
															<?=__('Tipo de cambio')?>: <input type="text" name="opc_moneda_total_tipo_cambio_view" disabled value="<?=$tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_base->fields['tipo_cambio']?>" size="8">
															</td>
													</tr>
												<tr>
													<td colspan="2">&nbsp;</td>
												</tr>
											</table>
											</div>
									</td>
								</tr>
								<tr>
									<td align="center" colspan="2">
										<?=__('Idioma')?>: <?= Html::SelectQuery($sesion,"SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma","lang",$cobro->fields['codigo_idioma'] != '' ? $cobro->fields['codigo_idioma'] : $contrato->fields['codigo_idioma'],'','',80);?>
										<br>
										<input type="submit" class="btn" value="<?=__('Descargar Archivo')?>" onclick="return VerDetalles(this.form);" \>
										<br>
										<input type="submit" class="btn" value="<?=__('Descargar Excel')?>" onclick="return DescargarExcel(this.form);" \>
									</td>
								</tr>
							</table>
							<!-- fin Imprimir -->
					</td>
				</tr>
				<tr>
					<td align=right style="vertical-align: top;" nowrap>
						<?=__('Cambiar a')?>:&nbsp;&nbsp;
					</td>
					<td align=left style="vertical-align: top;">
						<?= Html::SelectQuery($sesion, "SELECT codigo_estado_cobro FROM prm_estado_cobro ORDER BY orden",'estado',$cobro->fields['estado'],'onchange="RevisarPagado(this.value);"','',150); ?>
					</td>
				</tr>
			</table>
			<!-- Fin del Estado del cobro -->
		</td>
		<!-- Fin Cabecera -->
	</tr>
	<tr>
		<!-- Facturas -->
		<td colspan="2">
				<?
				if(UtilesApp::GetConf($sesion,'NuevoModuloFactura')) {
					?>
					<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;">
					<?
					if( $cobro->fields['modalidad_calculo'] == 1 ){
						$saldo_honorarios = $x_resultados['subtotal_honorarios'][$cobro->fields['opc_moneda_total']] - $x_resultados['descuento_honorarios'][$cobro->fields['opc_moneda_total']];
					}
					else
					{
						if( $cobro->fields['porcentaje_impuesto'] > 0 )
							$honorarios_original = $cobro->fields['monto_subtotal']-$cobro->fields['descuento'];
						else
							$honorarios_original = $cobro->fields['monto'];
						$aproximacion_monto = number_format($honorarios_original,$cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'],'.','');
						$saldo_honorarios = $aproximacion_monto * ($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);
						//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
						if((($cobro->fields['total_minutos']/60)<$cobro->fields['retainer_horas'])&&($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL')&&$cobro->fields['id_moneda']!=$cobro->fields['id_moneda_monto'])
						{
							$saldo_honorarios = $honorarios_original*($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);
						}
						//Caso flat fee
						if($cobro->fields['forma_cobro']=='FLAT FEE'&&$cobro->fields['id_moneda']!=$cobro->fields['id_moneda_monto']&&$cobro->fields['id_moneda_monto']==$cobro->fields['opc_moneda_total']&&empty($cobro->fields['descuento'])&&empty($cobro->fields['monto_tramites']))
						{
							$saldo_honorarios = $cobro->fields['monto_contrato'];
						}
						$saldo_honorarios = number_format($saldo_honorarios, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales'],'.','');
					}
					if($saldo_honorarios < 0) $saldo_honorarios = 0;

					$saldo_gastos_con_impuestos = $x_resultados['subtotal_gastos'][$moneda_documento->fields['id_moneda']]-$x_resultados['subtotal_gastos_sin_impuesto'][$moneda_documento->fields['id_moneda']];
					if($saldo_gastos_con_impuestos < 0) $saldo_gastos_con_impuestos = 0;

					$saldo_gastos_sin_impuestos = $x_resultados['subtotal_gastos_sin_impuesto'][$moneda_documento->fields['id_moneda']];
					if($saldo_gastos_sin_impuestos < 0) $saldo_gastos_sin_impuestos = 0;
					?>
					<tr style="height: 26px;">
						<td align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;" colspan=3>
							<img src="<?=Conf::ImgDir()?>/imprimir_16.gif" border="0" alt="Imprimir"/> <?=__('Documentos Tributarios')?>
						</td>
					</tr>
					<tr>
						<td>
							<table style="border: 1px solid #bfbfcf;">
								<thead bgcolor="#dfdfdf">
									<td>Tipo Documento</td>
									<td>Número</td>
									<td>Honorarios</td>
									<td>Gastos c/iva</td>
									<td>Gastos s/iva</td>
									<td>Impuesto</td>
									<td>Total</td>
									<td>Estado</td>
									<td>Acciones</td>
									<td>Saldo por pagar</td>
									<td>Agregar Pago</td>
									<td>Ver Pagos</td>
								</thead>
								<tbody>
									<tr bgcolor="#aaffaa">
										<td>Liquidación</td>
										<td><?php echo $cobro->fields['id_cobro'] ?></td>
										<td>
											<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<?php echo number_format($saldo_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?>
										</td>
										<td>
											<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<?php echo number_format($saldo_gastos_con_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?>
										</td>
										<td>
											<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<?php echo number_format($saldo_gastos_sin_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?>
										</td>
										<td>
										<?php if( $cobro->fields['porcentaje_impuesto'] > 0 || $cobro->fields['porcentaje_impuesto_gastos'] > 0 ): ?>
											<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<?php echo number_format($x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']], $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?>
										<?php endif; ?>
										</td>
										<td>
											<b><?= $moneda_documento->fields['simbolo'].'&nbsp;'.number_format($saldo_honorarios + $saldo_gastos_con_impuestos + $saldo_gastos_sin_impuestos + $x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']], $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></b>
										</td>
										<td/>
										<td/>
										<td/>
										<td/>
										<td/>
									</tr>
									<?php //documentos existentes. usar funcion magica (???)
									$query = "SELECT
											factura.id_factura,
											SUM(factura_cobro.monto_factura) as monto_factura,
											factura.numero,
											prm_documento_legal.glosa as tipo,
											prm_estado_factura.glosa,
											prm_estado_factura.codigo,
											factura.subtotal_sin_descuento,
											honorarios,
											ccfm.saldo as saldo,
											subtotal_gastos,
											subtotal_gastos_sin_impuesto,
											iva,
											prm_documento_legal.codigo as cod_tipo,
											factura.id_moneda,
											pm.tipo_cambio,
											pm.cifras_decimales
										FROM factura
										JOIN prm_moneda AS pm ON factura.id_moneda = pm.id_moneda
										LEFT JOIN cta_cte_fact_mvto AS ccfm ON factura.id_factura = ccfm.id_factura
										JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
										JOIN prm_estado_factura ON factura.id_estado = prm_estado_factura.id_estado
										LEFT JOIN factura_cobro ON factura_cobro.id_factura = factura.id_factura
										WHERE factura.id_cobro = '$id_cobro'
										GROUP BY factura.id_factura";

									$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
									$fila = 0;
									while( list( $id_factura, $monto, $numero, $tipo, $estado, $cod_estado, $subtotal_honorarios, $honorarios, $saldo, $subtotal_gastos, $subtotal_gastos_sin_impuesto, $impuesto, $cod_tipo, $id_moneda_factura, $tipo_cambio_factura, $cifras_decimales_factura ) = mysql_fetch_array($resp) ) {
										//si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
										if($cod_estado != 'A'){
											$mult = $cod_tipo == 'NC' ? 1 : -1;
											$saldo_honorarios += $subtotal_honorarios*$mult;
											$saldo_gastos_con_impuestos += $subtotal_gastos*$mult;
											$saldo_gastos_sin_impuestos += $subtotal_gastos_sin_impuesto*$mult;
										}
										$factura = new Factura($sesion);
										$factura->Load($id_factura);
										?>
									<tr bgcolor="<?=$fila++%2 ? '#f2f2ff' : '#ffffff'?>">
										<td><?php echo $tipo ?></td>
										<td><?php echo $numero ?></td>
										<td><?php echo $moneda_documento->fields['simbolo'].'&nbsp;'.number_format($subtotal_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
										<td><?php echo $moneda_documento->fields['simbolo'].'&nbsp;'.number_format($subtotal_gastos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
										<td><?php echo $moneda_documento->fields['simbolo'].'&nbsp;'.number_format($subtotal_gastos_sin_impuesto, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
										<td><?php echo $moneda_documento->fields['simbolo'].'&nbsp;'.number_format($impuesto, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
										<td>
											<b><?= $moneda_documento->fields['simbolo'].'&nbsp;'.number_format($subtotal_honorarios + $subtotal_gastos + $subtotal_gastos_sin_impuesto + $impuesto, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></b>
										</td>
										<td><?php echo $estado ?></td>
										<td>
											<a href='javascript:void(0)' onclick="nuevaVentana('Editar_Factura', 730, 580, 'agregar_factura.php?id_factura=<?=$id_factura ?>&popup=1&id_cobro=<?=$id_cobro?>', 'top=100, left=155');" ><img src='<?=Conf::ImgDir()?>/editar_on.gif' border="0" title="Editar"/></a>
											<a href='javascript:void(0)' onclick="ValidarFactura('', <?=$id_factura?>, 'imprimir');" ><img src='<?=Conf::ImgDir()?>/doc.gif' border="0" title="Descargar"/></a>
										</td>
										<td align="right">
											<?php echo $moneda_documento->fields['simbolo'].'&nbsp;'.number_format(-$saldo, $moneda_documento->fields['cifras_decimales'], $idioma->fieldls['separador_decimales'], $idioma->fields['separador_miles']) ?>
											<?php $saldo_tmp = -$saldo;?>
											<input type="hidden" name="saldo_<?=$id_factura?>" id="saldo_<?=$id_factura?>" value="<?= str_replace(',','.',$saldo_tmp); ?>" />
											<input type="hidden" name="id_moneda_factura_<?=$id_factura?>" id="id_moneda_factura_<?=$id_factura?>" value="<?=$id_moneda_factura ?>" />
											<input type="hidden" name="tipo_cambio_factura_<?=$id_factura?>" id="tipo_cambio_factura_<?=$id_factura?>" value="<?=$tipo_cambio_factura ?>" />
											<input type="hidden" name="cifras_decimales_factura_<?=$id_factura?>" id="cifras_decimales_factura_<?=$id_factura?>" value="<?=$cifras_decimales_factura ?>" />
										</td>
										<td align="center">
											<input type="checkbox" name="pagar_factura_<?=$id_factura?>" id="pagar_factura_<?=$id_factura?>" value="<?=$saldo?>" />
										</td>
										<td align="center"><img src="<?=Conf::ImgDir()?>/ver_persona_nuevo.gif" onclick="MostrarVerDocumentosPagos(<?=$id_factura?>);" border="0" alt="Examinar" /></td>
									</tr>
												<tr>
													<td align=right colspan="12">
														<div id="VerDocumentosPagos_<?=$id_factura?>" style="display:none; left: 100px; top: 250px; background-color: white; position:absolute; z-index: 4;">
															<fieldset style="background-color:white;">
															<legend><?=__('Lista de pagos asociados a documento #').$numero ?></legend>
															<div id="contenedor_tipo_load">&nbsp;</div>
															<div id="contenedor_tipo_cambio">
															<?=FacturaPago::HtmlListaPagos($sesion,$factura)?>
															<table style='border-collapse:collapse;' cellpadding='3'>
																<tr>
																	<td colspan=<?=$num_monedas?> align=center>
																		<input type=button onclick="CancelarVerDocumentosPagos(<?=$id_factura?>)" value="<?=__('Cancelar')?>" />
																	</td>
																</tr>
														</table>
														</div>
														</fieldset>
														
														</div>
													</td>
												</tr>
									<?php }
									//agregar docs, defaulteando segun conf
									
									$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
										FROM contrato_documento_legal 
										WHERE id_contrato = " . $contrato->fields['id_contrato'];

									$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales,__FILE__,__LINE__,$sesion->dbh);
									$nro_docs_legales = mysql_num_rows($contrato_docs_legales);

									if(!$nro_docs_legales){
										$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
											FROM contrato_documento_legal 
											WHERE id_contrato IS NULL";

										$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales,__FILE__,__LINE__,$sesion->dbh);
									}

									if(($saldo_honorarios) < 0.0001) $saldo_honorarios = 0;
									if(($saldo_gastos_con_impuestos) < 0.0001) $saldo_gastos_con_impuestos = 0;
									if(($saldo_gastos_sin_impuestos) < 0.0001) $saldo_gastos_sin_impuestos = 0;

									$saldo_disponible_honorarios = $saldo_honorarios;
									$saldo_disponible_gastos_con_impuestos = $saldo_gastos_con_impuestos;
									$saldo_disponible_gastos_sin_impuestos = $saldo_gastos_sin_impuestos;

									$boton_pagar = '<button type="button" onclick="AgregarPagoFactura()" >'.__('Pagar').'</button>';

									$idx = 0;
									while (list($agregar_tipo, $agregar_honorarios, $agregar_gastos_con_impuestos, $agregar_gastos_sin_impuestos) = mysql_fetch_array($contrato_docs_legales)) {
										if ($agregar_honorarios && $saldo_honorarios ||
											$agregar_gastos_con_impuestos && $saldo_gastos_con_impuestos ||
											$agregar_gastos_sin_impuestos && $saldo_gastos_sin_impuestos){
											$idx++;
											$honorarios_doc = 0;
											if($agregar_honorarios){
												$honorarios_doc = $saldo_honorarios;
												$saldo_honorarios = 0;
											}
											$gastos_con_impuestos_doc = 0;
											if($agregar_gastos_con_impuestos){
												$gastos_con_impuestos_doc = $saldo_gastos_con_impuestos;
												$saldo_gastos_con_impuestos = 0;
											}
											$gastos_sin_impuestos_doc = 0;
											if($agregar_gastos_sin_impuestos){
												$gastos_sin_impuestos_doc = $saldo_gastos_sin_impuestos;
												$saldo_gastos_sin_impuestos = 0;
											}
											?>

										<tr>
											<td colspan="2">
												<?= Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal",'tipo_documento_legal_'.$idx,$agregar_tipo,'','',100); ?>
											</td>
											<td nowrap>
												<?=$moneda_documento->fields['simbolo']?>&nbsp;<input type="text" id="honorarios_<?=$idx?>" value="<?=$honorarios_doc?>" size="8"/>
											</td>
											<td nowrap>
												<?=$moneda_documento->fields['simbolo']?>&nbsp;<input type="text" id="gastos_con_impuestos_<?=$idx?>" value="<?=$gastos_con_impuestos_doc?>" size="8"/>
											</td>
											<td nowrap>
												<?=$moneda_documento->fields['simbolo']?>&nbsp;<input type="text" id="gastos_sin_impuestos_<?=$idx?>" value="<?=$gastos_sin_impuestos_doc?>" size="8"/>
											</td>
											<td align="center">
												<button type="button" onclick="AgregarFactura(<?=$idx?>)" >
													<?=__('Emitir')?>
												</button>
											</td>
											<td/>
											<td/>
											<td/>
											<td/>
											<td align="center">
												<?php
													echo $boton_pagar;
													$boton_pagar = '';
												?>
											</td>
										</tr>

										<?php } 
									}

									//si quedaron saldos o no se genero nada default, tiro uno mas
									if(!$idx || $saldo_honorarios || $saldo_gastos_con_impuestos || $saldo_gastos_sin_impuestos){?>
										<tr>
											<td colspan="2">
												<?= Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal",'tipo_documento_legal_0','','','',100); ?>
											</td>
											<td nowrap>
												<?=$moneda_documento->fields['simbolo']?>&nbsp;<input type="text" id="honorarios_0" value="<?=$saldo_honorarios?>" size="8"/>
											</td>
											<td nowrap>
												<?=$moneda_documento->fields['simbolo']?>&nbsp;<input type="text" id="gastos_con_impuestos_0" value="<?=$saldo_gastos_con_impuestos?>" size="8"/>
											</td>
											<td nowrap>
												<?=$moneda_documento->fields['simbolo']?>&nbsp;<input type="text" id="gastos_sin_impuestos_0" value="<?=$saldo_gastos_sin_impuestos?>" size="8"/>
											</td>
											<td align="center">
												<button type="button" onclick="AgregarFactura(0)" >
													<?=__('Emitir')?>
												</button>
											</td>
											<td/>
											<td/>
											<td/>
											<td/>
											<td align="center">
												<?php
													echo $boton_pagar;
													$boton_pagar = '';
												?>
											</td>
										</tr>
									<?php }

									?>
								</tbody>
							</table>
							<input type="hidden" id="honorarios_disponibles" value="<?=$saldo_disponible_honorarios?>"/>
							<input type="hidden" id="gastos_con_iva_disponibles" value="<?=$saldo_disponible_gastos_con_impuestos?>"/>
							<input type="hidden" id="gastos_sin_iva_disponibles" value="<?=$saldo_disponible_gastos_sin_impuestos?>"/>
							</td>
						</tr>
					</table>
					<?
				}
				else{ ?>
				<table width="100%" border="0" cellspacing="0" cellpadding="2" style="border: 0px solid #bfbfcf;">
				<tr>
					<td width="33%">
					<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;" width="220" height=100>
						<tr style="height: 26px; ">
					    <td colspan=2 align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
					      <img src="<?=Conf::ImgDir()?>/cobro.png" border="0" alt="Imprimir"/> <?=__('Emisión')?>
					    </td>
					  </tr>
					<?
						if( $cobro->fields['solo_gastos'] == 0 )
						{
							?>
						<tr>
							<td colspan=2 align=left >
						    	<?=__('Monto Honorarios')?>:
							</td>
					  </tr>
					  <tr>
							<td align=right colspan="2" >
					<?
						if( $cobro->fields['modalidad_calculo'] == 1 )
							$honorarios_corregidos = $x_resultados['subtotal_honorarios'][$cobro->fields['opc_moneda_total']] - $x_resultados['descuento_honorarios'][$cobro->fields['opc_moneda_total']];
						else
							{
								if( $cobro->fields['porcentaje_impuesto'] > 0 )
									$honorarios_original = $cobro->fields['monto_subtotal']-$cobro->fields['descuento'];
								else
									$honorarios_original = $cobro->fields['monto'];
								$aproximacion_monto = number_format($honorarios_original,$cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'],'.','');
								$honorarios_corregidos = $aproximacion_monto * ($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);
								//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
								if((($cobro->fields['total_minutos']/60)<$cobro->fields['retainer_horas'])&&($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL')&&$cobro->fields['id_moneda']!=$cobro->fields['id_moneda_monto'])
								{
									$honorarios_corregidos = $honorarios_original*($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);
								}
								//Caso flat fee
								if($cobro->fields['forma_cobro']=='FLAT FEE'&&$cobro->fields['id_moneda']!=$cobro->fields['id_moneda_monto']&&$cobro->fields['id_moneda_monto']==$cobro->fields['opc_moneda_total']&&empty($cobro->fields['descuento'])&&empty($cobro->fields['monto_tramites']))
								{
									$honorarios_corregidos = $cobro->fields['monto_contrato'];
								}
							}
					?>
								<?= $moneda_documento->fields['simbolo']?>&nbsp;<?=$honorarios_corregidos>0 ? number_format($honorarios_corregidos, $moneda_documento->fields['cifras_decimales'],'.','') : '0'?>
							</td>
						</tr>
					<?
					}
					?>
						<tr>
							<td colspan=2 align=left>
								<?=__('Monto Gastos')?>:
							</td>
						</tr>
						<tr>
							<td align=right colspan="2" >
								<? 
								if( $cobro->fields['modalidad_calculo'] == 1 )
									echo $moneda_documento->fields['simbolo'].' '.( $x_resultados['subtotal_gastos'][$moneda_documento->fields['id_moneda']]>0 ? $x_resultados['subtotal_gastos'][$moneda_documento->fields['id_moneda']] : "0" );
								else
									echo $moneda_documento->fields['simbolo'].' '.( $documento_cobro->fields['subtotal_gastos'] + $documento_cobro->fields['subtotal_gastos_sin_impuesto'] > 0 ? $documento_cobro->fields['subtotal_gastos'] + $documento_cobro->fields['subtotal_gastos_sin_impuesto'] : "0" );
								?>
							</td>
						</tr>
	
					<?
					if( $cobro->fields['porcentaje_impuesto'] > 0 || $cobro->fields['porcentaje_impuesto_gastos'] > 0 )
						{
						?>
							<tr>
								<td colspan=2 align=left >
									<?=__('Impuesto')?>:
								</td>
							</tr>
							<tr>
								<td align=right colspan="2" >
									<?
									if( $cobro->fields['modalidad_calculo'] == 1 )
										echo $moneda_documento->fields['simbolo'].' '.( $x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']] > 0 ? $x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']] : "0");
									else
										echo $moneda_documento->fields['simbolo'].' '.( $documento_cobro->fields['impuesto'] > 0 ? $documento_cobro->fields['impuesto'] : "0" );
									?>
								</td>
							</tr>
						<?
						}
				?>
					<tr>
						<td colspan=2 align=left>
							<?=__('Forma de cobro')?>:
						</td>
					</tr>
					<tr>
						<td colspan=2 align=right>
							<?= $cobro->fields['forma_cobro'] ?>
						</td>
					</tr>
				</table>
				<br>
		</td>
		<td align="center" width="33%">
			<? 
				if( !( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') ) )
				{ ?>
					<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;" width="220" height=100>
							<tr style="height: 26px;">
								<td align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;" colspan=3>
									<img src="<?=Conf::ImgDir()?>/imprimir_16.gif" border="0" alt="Imprimir"/> <?=__('Ver Factura')?>
								</td>
							</tr>
							<tr>
								<td align="left" colspan=3>
									<?=__('Facturado')?>: <input type="checkbox" name=facturado id=facturado value=1 <?=$cobro->fields['facturado'] ? 'checked' : '' ?> >
								</td>
							</tr>
							<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || (  method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) { ?>
								<tr>
									<td align="left" colspan=3> 
										<?=__('Nota Cobro')?>: <input name='nota_cobro' size='5' value='<?=$cobro->fields['nota_cobro']?>'> 
									</td>
								</tr>
							<?	} ?>
							<tr>
								<td align="left" colspan=3>
									<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaNumeracionAutomatica') ) || ( method_exists('Conf','UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() ) ) 
									{ ?> 
										<?=__('Factura N°')?>: <input name='documento' size='5' value='<?=$cobro->fields['documento']?>'> 
									<?	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) ) && !empty($id_factura) ) { ?>
												<a href='javascript:void(0)' onclick="nuevaVentana('Editar_Factura',730,580,'agregar_factura.php?id_factura=<?=$id_factura ?>&popup=1', 'top=100, left=155');" ><img src='<?=Conf::ImgDir()?>/editar_on.gif' border=0 title=Editar></a> 
									<?	} ?>
								 <?	}
									else { ?>
										<?=__('Factura N°')?>: <input name='documento' size='5' value='<?=$cobro->fields['documento']?>'> 
										<?	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) ) && !empty($id_factura) ) { ?>
													<a href='javascript:void(0)' onclick="nuevaVentana('Editar_Factura',730,580,'agregar_factura.php?id_factura=<?=$id_factura ?>&popup=1', 'top=100, left=155');" ><img src='<?=Conf::ImgDir()?>/editar_on.gif' border=0 title=Editar></a> 
										<?	} ?>
									<?}	?>
								</td>
							</tr>
							<?	if( ( empty($id_factura) || $factura->fields['anulado'] == 1 ) && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) ) ) { ?>
								<tr>
									<td align="center" colspan=3>
										<a href='#' onclick="ValidarFactura(this.form,'','generar');" >Generar Factura</a>
									</td>
								</tr>
							<?	}
							else if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) ) ) { ?>
								<tr>
									<td align="center" colspan=3>
										<input type=button class=btn value="<?=__('Descargar')?>" onclick="ValidarFactura(this.form,<?=$id_factura?>,'imprimir');">
										<?  if( $factura->fields['anulado'] == 0 ) { ?>
												<input type=button class=btn value="<?=__('Anular Factura')?>" onclick="AnularFactura(this.form,'anular');">
										<?	} ?>
									</td>
								</tr>
							<?	}
						} ?>
					</table>
		<? } ?>
		</td>
		<!-- Fin Factura -->
		<td width="33%" align="right" rowspan="2">
				<!-- Envío del Cobro -->
						<table width="220" height="100" border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;">
							<tr style="height: 26px;">
								<td align="left" colspan=2 bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
									<img src="<?=Conf::ImgDir()?>/cobro.png" border="0" alt="Imprimir"/> <?=__('Envío al Cliente')?>
								</td>
							</tr>
							<tr>
								<td align=left>
									<?=__('Forma Envío')?>:
								</td>
								<td align=left>
									<select name="forma_envio" id="forma_envio" style='width: 80px;' />
									<option value='CARTA' <?=$cobro->fields['forma_envio']=='CARTA'? 'selected':'' ?> > Carta </option>
									<option value='E-MAIL' <?=$cobro->fields['forma_envio']=='E-MAIL'? 'selected':'' ?> > E-mail </option>
									<option value='OTRA' <?=$cobro->fields['forma_envio']=='OTRA'? 'selected':'' ?> > Otra </option>
								</td>
							</tr>
							<tr>
								<td colspan=2 align=left>
									<?=__('Email Cliente')?>:
								</td>
							</tr>
							<tr>
								<td colspan=2 align=right>
									<input type="text" name="email_cliente" id="email_cliente" size="35" maxlength="50" value = <?=$contrato->fields['email_contacto']?> />
								</td>
							</tr>
						</table>
						<br>
						<!-- fin Envío del Cobro -->
						<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;margin-top:10px;" width="220" >
							<tr style="height: 26px;">
								<td colspan=3 align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
									<img src="<?=Conf::ImgDir()?>/coins_16.png" border="0" alt="Imprimir"/> <?=__('Pago')?>
								</td>
							</tr>

							<?	if($cobro->fields['incluye_honorarios'] == 1)
								{
									if($documento_cobro->fields['honorarios_pagados'] == 'SI')
									{
							?>
							<tr>
								<td align=left colspan="3" >
									<?=__('Pago de Honorarios Completo')?>.&nbsp;&nbsp;
								</td>
							</tr>
							<?
									}
									else
									{
							?>
							<tr>
								<td align=left colspan="3" >
									<?=__('Saldo Pendiente Honorarios')?>:&nbsp;&nbsp;
								</td>
							</tr>
							<tr>
								<td align=right colspan="3" >
									<?= $moneda_documento->fields['simbolo']?>&nbsp;<?=$documento_cobro->fields['saldo_honorarios']>0 ? $x_resultados['saldo_honorarios'][$moneda_documento->fields['id_moneda']] : '0'?>
								</td>
							</tr>
							<?
									}
								}
							?>
							<?
								if($cobro->fields['incluye_gastos'] == 1)
								{
									if($documento_cobro->fields['gastos_pagados'] == 'SI')
									{
							?>
							<tr>
								<td align=left colspan="3" >
									<?=__('Pago de Gastos Completo')?>.&nbsp;&nbsp;
								</td>
							</tr>
							<?
									}
									else
									{
							?>
							<tr>
								<td align=left colspan="3" >
									<?=__('Saldo Pendiente Gastos')?>:&nbsp;&nbsp;
								</td>
							</tr>
							<tr>
								<td align=right colspan="3" >
									<?= $moneda_documento->fields['simbolo']?>&nbsp;<?=$documento_cobro->fields['saldo_gastos']>0 ? $x_resultados['saldo_gastos'][$moneda_documento->fields['id_moneda']] : '0'?>
								</td>
							</tr>
							<?
									}
								}
							?>
							<tr>
								<td colspan="3">
									<hr>
								</td>
							</tr>
							<?
								$lista_pagos = $documento_cobro->ListaPagos();
								if($lista_pagos)
							{?>
							<tr>
								<td align=left colspan="3">
									<?=__("Lista de Documentos de Pago")?>:
									<input type=hidden id=hay_pagos value='si'/>
								</td>
							</tr>
							<?=$lista_pagos?>
							<tr>
								<td colspan="3">
									<hr>
								</td>
							</tr>
							
							<?}?>
							
							<tr>
								<td colspan="3" align=center>
									<img src="<?=Conf::ImgDir()?>/money_16.gif" border=0> <a href='javascript:void(0)' onclick="MostrarTipoCambio()" title="<?=__('Tipo de Cambio del Documento de Cobro al ser pagado.')?>"><?=__('Actualizar Tipo de Cambio')?></a>
								</td>
							</tr>
							<tr>
								<td colspan="3" align=center>
									&nbsp;
								</td>
							</tr>
							
							<? if($cobro->fields['estado']!='INCOBRABLE' && ( $documento_cobro->fields['honorarios_pagados'] == 'NO' || $documento_cobro->fields['gastos_pagados'] == 'NO' ) 
										&& !UtilesApp::GetConf($sesion,'NuevoModuloFactura') )
							{?>
							<tr>
								<td colspan="3">
									<hr>
								</td>
							</tr>
							<tr>
								<td colspan="3" align=center>
									<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0><a href='javascript:void(0)' onclick="AgregarPago()" title="Agregar Pago"><?=__('Agregar Pago')?></a>
								</td>
							</tr>
							<tr>
								<td colspan="3" align=center>
									&nbsp;
								</td>
							</tr>
							<?} else {?>
								<input type=hidden id=hay_pagos value='no'/>
							<?}?>
						</table>
					<!-- fin Pago -->
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table cellspacing="0" cellpadding="3" width="220px" style='border:1px dotted #bfbfcf'>
				<tr>
					<td bgcolor="#dfdfdf">
						<span style="font-weight: bold; font-size: 11px;"><?=__('Se esta cobrando:')?></span>
					</td>
				</tr>
				<tr>
					<td>
						<?php
						$se_esta_cobrando = __('Periodo');
						$se_esta_cobrando .=': ';
						if($cobro->fields['fecha_ini'] != '0000-00-00')
						{
								$se_esta_cobrando_fecha_ini = Utiles::sql2date($cobro->fields['fecha_ini']);
								$se_esta_cobrando .=__('Desde').': '.$se_esta_cobrando_fecha_ini.' ';
						}
						if($cobro->fields['fecha_fin'] != '0000-00-00')
						{
								$se_esta_cobrando_fecha_fin = Utiles::sql2date($cobro->fields['fecha_fin']);
								$se_esta_cobrando .=__('Hasta').': '.$se_esta_cobrando_fecha_fin;
						}

						if($cobro->fields['se_esta_cobrando'])
							$se_esta_cobrando = $cobro->fields['se_esta_cobrando'];
						?>
						<textarea name="se_esta_cobrando" id="se_esta_cobrando"><?php echo $se_esta_cobrando;?></textarea>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<!-- Submit -->
		<td colspan="3" align=center style="vertical-align: bottom; height:50px;">
			<input type=button id="enviar" class=btn value="<?=__('Guardar Cambios')?>" onclick="ValidarTodo(this.form); ">
		</td>
	</tr>
	<tr>
		<td colspan="3"><hr></td>
	</tr>
</table>
<!-- Fin FORM unica -->
</form>

<div id="TipoCambioDocumento" style="display:none; left: 100px; top: 300px; background-color: white; position:absolute; z-index: 4;">
	<fieldset style="background-color:white;">
		<legend><?=__('Tipo de Cambio')?></legend>
		<div id="contenedor_tipo_cambio">
			<div style="padding-top:5px; padding-bottom:5px;">&nbsp;<img src="<?=Conf::ImgDir()?>/alerta_16.gif" title="Alerta" />&nbsp;&nbsp;<?=__('Este tipo de cambio sólo afecta al Documento de Cobro en los Reportes. No modifica la Carta de Cobro.')?></div>
			<table style='border-collapse:collapse;'  cellpadding='3'>
				<?
				$query = 
				"SELECT id_documento, prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio 
				FROM documento_moneda 
				JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
				WHERE id_documento = '".$documento_cobro->fields['id_documento']."'";
				$resp =mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
				$num_monedas=0; $ids_monedas = array();
				while(list($id_documento,$id_moneda,$glosa_moneda,$tipo_cambio) = mysql_fetch_array($resp))
				{
				?>
					<td>
							<span><b><?=$glosa_moneda?></b></span><br>
							<input type='text' size=9 id='documento_moneda_<?=$id_moneda?>' name='documento_moneda_<?=$id_moneda?>' value='<?=$tipo_cambio?>' />
					</td>
				<?
					$num_monedas++;
					$ids_monedas[] = $id_moneda;
				}
				?>
				<tr>
					<td colspan=<?=$num_monedas?> align=center>
						<input type=button onclick="ActualizarDocumentoMoneda($('todo_cobro'))" value="<?=__('Guardar')?>" />
						<input type=button onclick="CancelarDocumentoMoneda()" value="<?=__('Cancelar')?>" />
						<input type=hidden id="ids_monedas_documento" value="<?=implode(',',$ids_monedas)?>"/>
					</td>
				</tr>
			</table>
		</div>
	</fieldset>
</div>
<!-- Fin Tipo Cambio -->
<div width="100%" align="center">
	<iframe src="historial_cobro.php?id_cobro=<?=$id_cobro?>" width=600px height=450px style="border: none;" frameborder=0></iframe>
</div>
<script type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha_pago",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_pago"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_emision",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_emision"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_envio",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_envio"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_pago_gastos",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "imgfecha_fecha_pago_gastos"		// ID of the button
	}
);
</script>
<?
	$pagina->PrintBottom($popup);
?>
