<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/classes/PaginaCobro.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/Asunto.php';
require_once Conf::ServerDir() . '/classes/Cobro.php';
require_once Conf::ServerDir() . '/classes/CobroMoneda.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/../app/classes/Observacion.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/Factura.php';
require_once Conf::ServerDir() . '/../app/classes/FacturaPago.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Gasto.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/FacturaPdfDatos.php';

header('Content-type: text/html; charset=iso-8859-1');

$sesion = new Sesion(array('COB'));


$cobro = new Cobro($sesion);
$contrato = new Contrato($sesion);
$documento_cobro = new Documento($sesion);
$factura = new Factura($sesion);
$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$factura_pago = new FacturaPago($sesion);
if(!$id_cobro) $id_cobro=$_POST['id_cobro'];
if(!$opc) $opc=$_POST['opc'];
$confirma=$_POST['confirma'];
$opc_informar_contabilidad=$_POST['opc_ic'];

if (!$cobro->Load($id_cobro)) {
    $pagina = new PaginaCobro($sesion);
	$pagina->FatalError(__('Cobro inv�lido'));
}

if ($opc=='refrescar') {
switch($cobro->fields['estado_contabilidad']):
    case 'NO INFORMADO': 
        $estado_c = __('Sin informar');
        $titulo_c = __('El Cobro no ha sido informado.');
    break;    
    case 'PARA INFORMAR': 
        $estado_c = __('Para informar');
        $titulo_c = __('El Cobro se ha informado a Contabilidad');									
    break;
    case 'PARA INFORMAR Y FACTURAR':
        $estado_c = __('Para informar y facturar');
        $titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucci�n de facturar.');
    break;									
    case 'INFORMADO':
        $estado_c = __('Informado');
        $titulo_c = __('El Cobro ha sido requerido por Contabilidad');
    break;
    case 'INFORMADO Y FACTURADO':
        $estado_c = __('Informado para Facturar');
        $titulo_c = __('El Cobro ha sido requerido por Contabilidad, se ha indicado que debe facturarse');
    break;
    default:
        $estado_c=$cobro->fields['estado_contabilidad'];
endswitch;
echo $estado_c.'|'.$titulo_c.'|'.$cobro->fields['estado_contabilidad'].'|Estado cobro refrescado|'.$cobro->fields['nota_venta_contabilidad'];


} else if ($opc=='listapagos') {
		if ($documento_cobro->LoadByCobro($id_cobro)) {
			$moneda_documento = new Moneda($sesion);
			$moneda_documento->Load($documento_cobro->fields['id_moneda']);
		}  else {
            $moneda_documento = new Moneda($sesion);
			$moneda_documento->Load($cobro->fields['opc_moneda_total']);
        }
$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, true);

    $retorno= '<tr style="height: 26px;">
						<td colspan=3 align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
							<img src="'.Conf::ImgDir().'/coins_16.png" border="0" alt="Imprimir"/> ';
    $retorno.= __('Pago');
    $retorno.= '</td>		</tr>';

								
				if ($cobro->fields['incluye_honorarios'] == 1) {
					if ($documento_cobro->fields['honorarios_pagados'] == 'SI') {
										
						$retorno.= '<tr>	<td align=left colspan="3" >';
                                                $retorno.= __('Pago de Honorarios Completo');
                                                $retorno.= '&nbsp;&nbsp;</td>	</tr>';

                                        } else {
                                                $retorno.= '<tr><td align=left colspan="3" >';
                                                $retorno.= __('Saldo Pendiente Honorarios');
                                                $retorno.= ':&nbsp;&nbsp;</td></tr>	<tr><td align=right colspan="3" >';
                                                $retorno.= $moneda_documento->fields['simbolo'].'&nbsp;';
                                                $retorno.= ($documento_cobro->fields['saldo_honorarios'] > 0) ? $x_resultados['saldo_honorarios'][$moneda_documento->fields['id_moneda']] : '0' ;
                                                $retorno.= '</td></tr>';
                                        }
                                }
				if ($cobro->fields['incluye_gastos'] == 1) {
					if ($documento_cobro->fields['gastos_pagados'] == 'SI') {
						$retorno.= '<tr><td align=left colspan="3" >';
                                                $retorno.= __('Pago de Gastos Completo');
                                                $retorno.= '&nbsp;&nbsp;</td>	</tr>';
                                } else {
                                                $retorno.= '<tr><td align=left colspan="3" >';
                                                $retorno.= __('Saldo Pendiente Gastos');
                                                $retorno.= ':&nbsp;&nbsp; </td>	</tr>	<tr> <td align=right colspan="3" >';
                                                $retorno.= $moneda_documento->fields['simbolo'] .'&nbsp;';
                                                $retorno.= ($documento_cobro->fields['saldo_gastos'] > 0)? $x_resultados['saldo_gastos'][$moneda_documento->fields['id_moneda']] : '0';
                                                $retorno.= '	</td>	</tr>';
				}
			}
			$retorno.= '<tr><td colspan="3"><hr></td>	</tr>';
			$lista_pagos = $documento_cobro->ListaPagos();
			if ($lista_pagos) {
						
                            $retorno.= '<tr><td align=left colspan="3">';
                            $retorno.= __("Lista de Documentos de Pago");
                            $retorno.= ':	<input type="hidden" id="hay_pagos" value="si"/></td></tr>';
                            $retorno.= $lista_pagos ;
                            $retorno.= '<tr><td colspan="3"><hr></td></tr>';

                            } else { 
						$retorno.= '<input type="hidden" id="hay_pagos" value="no"/>';
			    } 
                            
 $retorno.= '<tr><td colspan="3" align=center><img src="'.Conf::ImgDir().'/money_16.gif" border=0> <a href="javascript:void(0)" onclick="MostrarTipoCambio()" title="'. __('Tipo de Cambio del Documento de Cobro al ser pagado.') .'">'. __('Actualizar Tipo de Cambio') .'</a></td>	</tr>	<tr>	<td colspan="3" align=center>	&nbsp;	</td>	</tr>';

					
					$faltan_pagos = $cobro->fields['estado'] != 'INCOBRABLE' && ( $documento_cobro->fields['honorarios_pagados'] == 'NO' || $documento_cobro->fields['gastos_pagados'] == 'NO' );
					$hay_adelantos = false;
					if($faltan_pagos && !UtilesApp::GetConf($sesion, 'NuevoModuloFactura')){
						$pago_honorarios = $documento_cobro->fields['saldo_honorarios'] != 0 ? 1 : 0;
						$pago_gastos = $documento_cobro->fields['saldo_gastos'] != 0 ? 1 : 0;
						$hay_adelantos = $documento_cobro->SaldoAdelantosDisponibles($cobro->fields['codigo_cliente'], $cobro->fields['id_contrato'], $pago_honorarios, $pago_gastos) > 0;
					}
					
					if ($faltan_pagos && !UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
					
					$retorno.= '	<tr>	<td colspan="3"><hr>	</td>		</tr><tr><td colspan="3" align=center>	';
					 if($hay_adelantos) 	$retorno.= '<img src="'. Conf::ImgDir() .'/agregar.gif" border=0 /> <a href="javascript:void(0)" onclick="UsarAdelanto('.$pago_honorarios.','.$pago_gastos.')" title="'. __('Usar Adelanto') .'">'. __('Usar Adelanto') .'</a><br/><br/>';
					$retorno.= '<img src="'. Conf::ImgDir() .'/agregar.gif" border=0 /> <a href="javascript:void(0)" onclick="AgregarPago()" title="'. __('Agregar Pago') .'">'. __('Agregar Pago') .'</a>';
								
					$retorno.= '</td></tr><tr><td colspan="3" align=center>&nbsp;	</td>	</tr>';
					 } 
                            
                            
                            
			
                     
                            echo $retorno;
    
} else if ($opc == 'guardar' && ($opc_informar_contabilidad == 'parainfo' OR $opc_informar_contabilidad == 'parainfoyfacturar')) {

	if($cobro->fields['estado_contabilidad']=='INFORMADO' && $opc_informar_contabilidad == 'parainfo' && $confirma==0) {
            $mensajeinterno='Se ignora la orden porque el webservice cambia el estado a INFORMADO';  
        } else if($cobro->fields['estado_contabilidad']=='INFORMADO Y FACTURADO' && $opc_informar_contabilidad == 'parainfoyfacturar' && $confirma==0) {
              $mensajeinterno='Se ignora la orden porque el webservice cambia el estado a INFORMADO Y FACTURADO';
        } else if ($cobro->fields['estado_contabilidad']=='PARA INFORMAR' && $opc_informar_contabilidad == 'parainfo' &&  $confirma==0) {
             $mensajeinterno='Nada cambia';
        } else if ($cobro->fields['estado_contabilidad']=='PARA INFORMAR Y FACTURAR' && $opc_informar_contabilidad == 'parainfoyfacturar' && $confirma==0) {
             $mensajeinterno='Nada cambia';
        } else {
                if($opc_informar_contabilidad == 'parainfo') {
                        $cobro->Edit('estado_contabilidad','PARA INFORMAR');
                } else if($opc_informar_contabilidad == 'parainfoyfacturar') {
			$cobro->Edit('estado_contabilidad','PARA INFORMAR Y FACTURAR');
                }
                
	$cobro->Write();
        $mensajeinterno='Cambio Realizado';
        }
   switch($cobro->fields['estado_contabilidad']):
    case 'NO INFORMADO': 
        $estado_c = __('Sin informar');
        $titulo_c = __('El Cobro no ha sido informado.');
    break;    
    case 'PARA INFORMAR': 
        $estado_c = __('Para informar');
        $titulo_c = __('El Cobro se ha informado a Contabilidad');									
    break;
    case 'PARA INFORMAR Y FACTURAR':
        $estado_c = __('Para informar y facturar');
        $titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucci�n de facturar.');
    break;									
    case 'INFORMADO':
        $estado_c = __('Informado');
        $titulo_c = __('El Cobro ha sido requerido por Contabilidad');
    break;
    case 'INFORMADO Y FACTURADO':
        $estado_c = __('Informado para Facturar');
        $titulo_c = __('El Cobro ha sido requerido por Contabilidad, se ha indicado que debe facturarse');
    break;
    default:
        $estado_c=$cobro->fields['estado_contabilidad'];
endswitch;
echo $estado_c.'|'.$titulo_c.'|'.$cobro->fields['estado_contabilidad'].'|'.$mensajeinterno.'|'.$cobro->fields['nota_venta_contabilidad'];

        
} else if($opc=='cajafacturas') {
		if ($documento_cobro->LoadByCobro($id_cobro)) {
			$moneda_documento = new Moneda($sesion);
			$moneda_documento->Load($documento_cobro->fields['id_moneda']);
		}  else {
            $moneda_documento = new Moneda($sesion);
			$moneda_documento->Load($cobro->fields['opc_moneda_total']);
        }
		$idioma->Load($contrato->fields['codigo_idioma']);
	$contrato->Load($cobro->fields['id_contrato']);
	$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, true);
		
									if ($cobro->fields['modalidad_calculo'] == 1) {
										$saldo_honorarios = $x_resultados['subtotal_honorarios'][$cobro->fields['opc_moneda_total']] - $x_resultados['descuento_honorarios'][$cobro->fields['opc_moneda_total']];
								
										$saldo_disponible_trabajos =  $saldo_trabajos =  $x_resultados['monto_trabajos'][$cobro->fields['opc_moneda_total']] - $x_resultados['descuento_honorarios'][$cobro->fields['opc_moneda_total']];
										if($saldo_disponible_trabajos<0) {
											$saldo_disponible_tramites = $saldo_tramites =  $x_resultados['monto_tramites'][$cobro->fields['opc_moneda_total']]+$saldo_disponible_trabajos;
											$saldo_disponible_trabajos = 0;
										} else {
											$saldo_disponible_tramites = $saldo_tramites =  $x_resultados['monto_tramites'][$cobro->fields['opc_moneda_total']];
										}
										
										
									 
										
										
										
										} else {
										if ($cobro->fields['porcentaje_impuesto'] > 0) {
											$honorarios_original = $cobro->fields['monto_subtotal'] - $cobro->fields['descuento'];
										} else {
											$honorarios_original = $cobro->fields['monto'];
										}
										$aproximacion_monto = number_format($honorarios_original, $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'], '.', '');
										$saldo_honorarios = $aproximacion_monto * ($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);

										//Caso retainer menor de un valor y distinta tarifa (diferencia por decimales)
										if ((($cobro->fields['total_minutos'] / 60) < $cobro->fields['retainer_horas']) && ($cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL') && $cobro->fields['id_moneda'] != $cobro->fields['id_moneda_monto']) {
											$saldo_honorarios = $honorarios_original * ($cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']);
										}
										//Caso flat fee
										if ($cobro->fields['forma_cobro'] == 'FLAT FEE' && $cobro->fields['id_moneda'] != $cobro->fields['id_moneda_monto'] && $cobro->fields['id_moneda_monto'] == $cobro->fields['opc_moneda_total'] && empty($cobro->fields['descuento']) && empty($cobro->fields['monto_tramites'])) {
											$saldo_honorarios = $cobro->fields['monto_contrato'];
										}
										$saldo_honorarios = number_format($saldo_honorarios, $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['cifras_decimales'], '.', '');
									}

									if ($saldo_honorarios < 0) {
										$saldo_honorarios = 0;
									}

									$saldo_gastos_con_impuestos = $x_resultados['subtotal_gastos'][$moneda_documento->fields['id_moneda']] - $x_resultados['subtotal_gastos_sin_impuesto'][$moneda_documento->fields['id_moneda']];
									if ($saldo_gastos_con_impuestos < 0) {
										$saldo_gastos_con_impuestos = 0;
									}

									$saldo_gastos_sin_impuestos = $x_resultados['subtotal_gastos_sin_impuesto'][$moneda_documento->fields['id_moneda']];
									if ($saldo_gastos_sin_impuestos < 0) {
										$saldo_gastos_sin_impuestos = 0;
									}
								 
	echo '<tr style="height: 26px;">
										<td colspan="12" align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;" colspan=2>
											<img src="'. Conf::ImgDir().'/imprimir_16.gif" border="0" alt="Imprimir"/> '.  __('Documentos Tributarios') ;
											  if ( UtilesApp::GetConf($sesion, 'NuevoModuloFactura') &&  $cobro->DiferenciaCobroConFactura() != '') { 
												echo '<span style="border: 1px solid #bfbfcf; color: #ffffff; background-color: #ff0000; float: right; padding: 2px">'. $cobro->DiferenciaCobroConFactura() .'</span>';
												} ?>
										</td>
									</tr>
									<tr>
									<?php echo			
												'<th>'.__('Tipo').__('Documento').'</th>
												<th>'.__('N�mero').'</th>
												<th style="white-space:nowrap; width:78px;">'.__('Fecha').'</th>';
									
									echo			'<th>'.__('Honorarios').'</th>
												<th>'. __('Gasto ').__('c/IVA') .'</th>
												<th>'.  __('Gasto ').__('s/IVA') .'</th>';
									echo			'<th>Impuesto</th>
												<th>Total</th>
												<th>Estado</th>
												
												<th>Saldo<br>por pagar</th>
												<th>'.__('Agregar Pago').'</th>
												<th>Acciones</th></tr>
											
													<tr style="background:#EFE;">
														<td>'.__('Cobro').'</td>
														
										<td>'. $cobro->fields['id_cobro'] .'</td>
														<td style="width:78px;">'. date('d-m-Y',strtotime($cobro->fields['fecha_emision'])).'</td>
														<td>';
	 echo $moneda_documento->fields['simbolo'].'&nbsp;'. number_format($saldo_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ;
	 echo '<input type="hidden" name="honorarios_total" id="honorarios_total" value="'. $saldo_honorarios .'" />
														</td>';
														
	echo '<td>'. $moneda_documento->fields['simbolo'].'&nbsp;'. number_format($saldo_gastos_con_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ;
	echo 	'<input type="hidden" name="gastos_con_iva_total" id="gastos_con_iva_total" value="'. $saldo_gastos_con_impuestos .'" /></td>';
	
	
	 echo '<td>'.$moneda_documento->fields['simbolo'] .'&nbsp;'. number_format($saldo_gastos_sin_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
	echo '	<input type="hidden" name="gastos_sin_iva_total" id="gastos_sin_iva_total" value="'. $saldo_gastos_sin_impuestos .'" /></td>';

	echo ' <td>';
	 if ($cobro->fields['porcentaje_impuesto'] > 0 || $cobro->fields['porcentaje_impuesto_gastos'] > 0) {  
		 echo $moneda_documento->fields['simbolo'] .'&nbsp;'. number_format($x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']], $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ;
	  } 
	echo '</td>';
echo '<td>	<b>'. $moneda_documento->fields['simbolo'] . '&nbsp;' . number_format($saldo_honorarios + $saldo_gastos_con_impuestos + $saldo_gastos_sin_impuestos + $x_resultados['monto_iva'][$moneda_documento->fields['id_moneda']], $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) .'</b>';
echo '		</td>					<td/>						<td/>						<td/>				<td/>											</tr>';

if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
													//documentos existentes. usar funcion magica (???)
													$query = "SELECT
                factura.id_factura,
                SUM(factura_cobro.monto_factura) as monto_factura,
                factura.serie_documento_legal,
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
													while (list( $id_factura, $monto, $serie, $numero, $tipo, $estado, $cod_estado, $subtotal_honorarios, $honorarios, $saldo, $subtotal_gastos, $subtotal_gastos_sin_impuesto, $impuesto, $cod_tipo, $id_moneda_factura, $tipo_cambio_factura, $cifras_decimales_factura ) = mysql_fetch_array($resp)) {
														//si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
														if ($cod_estado != 'A') {
															$mult = $cod_tipo == 'NC' ? 1 : -1;
															$saldo_honorarios += $subtotal_honorarios * $mult;
															$saldo_gastos_con_impuestos += $subtotal_gastos * $mult;
															$saldo_gastos_sin_impuestos += $subtotal_gastos_sin_impuesto * $mult;
														}
														$factura = new Factura($sesion);
														$factura->Load($id_factura);

														$moneda_factura = new Moneda($sesion);
														$moneda_factura->Load($id_moneda_factura);
														?>
														<tr bgcolor="<?php echo $fila++ % 2 ? '#f2f2ff' : '#ffffff' ?>">
															<td><?php echo $tipo ?></td>
															<td style="width:78px;white-space:nowrap;"><?php echo $factura->ObtenerNumero(null, null, null, true) ?></td>
															 
															<td><?php echo date('d-m-Y',strtotime($factura->fields['fecha'])); ?></td>
															<td><?php echo $moneda_factura->fields['simbolo'] . '&nbsp;' . number_format($subtotal_honorarios, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
															<td><?php echo $moneda_factura->fields['simbolo'] . '&nbsp;' . number_format($subtotal_gastos, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
															<td><?php echo $moneda_factura->fields['simbolo'] . '&nbsp;' . number_format($subtotal_gastos_sin_impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
															<td><?php echo $moneda_factura->fields['simbolo'] . '&nbsp;' . number_format($impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></td>
															<td><b><?php echo $moneda_factura->fields['simbolo'] . '&nbsp;' . number_format($subtotal_honorarios + $subtotal_gastos + $subtotal_gastos_sin_impuesto + $impuesto, $moneda_factura->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) ?></b></td>
															<td><?php echo $estado ?></td>
															
															 
															<td align="right">
		<?php echo $moneda_factura->fields['simbolo'] . '&nbsp;' . number_format(-$saldo, $moneda_factura->fields['cifras_decimales'], $idioma->fieldls['separador_decimales'], $idioma->fields['separador_miles']) ?>
		<?php $saldo_tmp = -$saldo; ?>
																<input type="hidden" name="saldo_<?php echo $id_factura ?>" id="saldo_<?php echo $id_factura ?>" value="<?php echo str_replace(',', '.', $saldo_tmp); ?>" />
																<input type="hidden" name="id_moneda_factura_<?php echo $id_factura ?>" id="id_moneda_factura_<?php echo $id_factura ?>" value="<?php echo $id_moneda_factura ?>" />
																<input type="hidden" name="tipo_cambio_factura_<?php echo $id_factura ?>" id="tipo_cambio_factura_<?php echo $id_factura ?>" value="<?php echo $tipo_cambio_factura ?>" />
																<input type="hidden" name="cifras_decimales_factura_<?php echo $id_factura ?>" id="cifras_decimales_factura_<?php echo $id_factura ?>" value="<?php echo $cifras_decimales_factura ?>" />
															</td>
															<td align="center">
																<input type="checkbox" name="pagar_factura_<?php echo $id_factura ?>" id="pagar_factura_<?php echo $id_factura ?>" value="<?php echo $saldo ?>"   class="tooltip" alt="Active esta casilla y luego pinche en 'Pagar' para a�adir pagos" />
															</td>
															<td style="white-space:nowrap;cursor:pointer;">
																<a href='javascript:void(0)' onclick="nuovaFinestra('Editar_Factura', 800, 600, 'agregar_factura.php?id_factura=<?php echo $id_factura ?>&popup=1&id_cobro=<?php echo $id_cobro ?>', 'top=100, left=155');" ><img src='<?php echo Conf::ImgDir() ?>/editar_on.gif' border="0" title="Editar"/></a>
																<?php if (UtilesApp::GetConf($sesion, 'ImprimirFacturaDoc')) { ?>
																	<a href='javascript:void(0)' onclick="ValidarFactura('', <?php echo $id_factura ?>, 'imprimir');" ><img src='<?php echo Conf::ImgDir() ?>/doc.gif' border="0" title="Descargar Word"/></a>
																<?php } ?>
																<?php if (UtilesApp::GetConf($sesion, 'ImprimirFacturaPdf')) { ?>
																	<a href='javascript:void(0)' onclick="ValidarFactura('', <?php echo $id_factura ?>, 'imprimir_pdf');" ><img src='<?php echo Conf::ImgDir() ?>/pdf.gif' border="0" title="Descargar Pdf"/></a>
																<?php } ?>
																<img title="Ver pagos para este documento" src="<?php echo Conf::ImgDir() ?>/ver_persona_nuevo.gif" onclick="MostrarVerDocumentosPagos(<?php echo $id_factura ?>);" border="0" alt="Examinar" />
															</td>
														</tr>
														<tr>
															<td align=right colspan="12">
																<div id="VerDocumentosPagos_<?php echo $id_factura ?>" style="display:none; left: 100px; top: 250px; background-color: white; position:absolute; z-index: 4;">
																	<fieldset style="background-color:white;">
																		<legend><?php echo __('Lista de pagos asociados a documento #') . $numero ?></legend>
																		<div id="contenedor_tipo_load">&nbsp;</div>
																		<div id="contenedor_tipo_cambio">
		<?php echo FacturaPago::HtmlListaPagos($sesion, $factura, $documento_cobro->fields['id_documento']); ?>
																			<table style='border-collapse:collapse;' cellpadding='3'>
																				<tr>
																					<td colspan=<?php echo $num_monedas ?> align=center>
																						<input type="button" onclick="CancelarVerDocumentosPagos(<?php echo $id_factura ?>)" value="<?php echo __('Cancelar') ?>" />
																					</td>
																				</tr>
																			</table>
																		</div>
																	</fieldset>
																</div>
															</td>
														</tr>
														<?php
													} // end while
													//agregar docs, defaulteando segun conf

													$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
                                    FROM contrato_documento_legal
                                    WHERE id_contrato = " . $contrato->fields['id_contrato'];

													$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales, __FILE__, __LINE__, $sesion->dbh);
													$nro_docs_legales = mysql_num_rows($contrato_docs_legales);

													if (!$nro_docs_legales) {
														$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
                                        FROM contrato_documento_legal
                                        WHERE id_contrato IS NULL";

														$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales, __FILE__, __LINE__, $sesion->dbh);
													}

													if (($saldo_honorarios) < 0.0001) {
														$saldo_honorarios = 0;
													}
													if (($saldo_gastos_con_impuestos) < 0.0001) {
														$saldo_gastos_con_impuestos = 0;
													}
													if (($saldo_gastos_sin_impuestos) < 0.0001) {
														$saldo_gastos_sin_impuestos = 0;
													}

													$saldo_disponible_honorarios = $saldo_honorarios;
													$saldo_disponible_gastos_con_impuestos = $saldo_gastos_con_impuestos;
													$saldo_disponible_gastos_sin_impuestos = $saldo_gastos_sin_impuestos;

													$boton_pagar = '<button type="button" onclick="AgregarPagoFactura()" >' . __('Pagar') . '</button>';

													$idx = 0;
													while (list($agregar_tipo, $agregar_honorarios, $agregar_gastos_con_impuestos, $agregar_gastos_sin_impuestos) = mysql_fetch_array($contrato_docs_legales)) {
														if ($agregar_honorarios && $saldo_honorarios ||
																$agregar_gastos_con_impuestos && $saldo_gastos_con_impuestos ||
																$agregar_gastos_sin_impuestos && $saldo_gastos_sin_impuestos) {

															$idx++;
															$honorarios_doc = 0;

															if ($agregar_honorarios) {
																$honorarios_doc = number_format($saldo_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
																$saldo_honorarios = 0;
															}

															$gastos_con_impuestos_doc = 0;
															if ($agregar_gastos_con_impuestos) {
																$gastos_con_impuestos_doc = number_format($saldo_gastos_con_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
																$saldo_gastos_con_impuestos = 0;
															}
															$gastos_sin_impuestos_doc = 0;
															if ($agregar_gastos_sin_impuestos) {
																$gastos_sin_impuestos_doc = number_format($saldo_gastos_sin_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
																$saldo_gastos_sin_impuestos = 0;
															}
															?>
															<tr>
																<td colspan="3">
																	<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_' . $idx, $agregar_tipo, '', '', 100); ?>
																</td>
																<td nowrap>
																	<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input  class="mini_input"  type="text" id="honorarios_<?php echo $idx ?>" value="<?php echo $honorarios_doc ?>" size="8" onkeydown="MontoValido( this.id );"/>
																</td>
																<td nowrap>
																	<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input  class="mini_input"  type="text" id="gastos_con_impuestos_<?php echo $idx ?>" value="<?php echo $gastos_con_impuestos_doc ?>" size="8" onkeydown="MontoValido( this.id );"/>
																</td>
																<td nowrap>
																<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" class="mini_input" id="gastos_sin_impuestos_<?php echo $idx ?>" value="<?php echo $gastos_sin_impuestos_doc ?>" size="8" onkeydown="MontoValido( this.id );"/>
																</td>
																<td align="center">
																	<button type="button" onclick="AgregarFactura(<?php echo $idx ?>)" ><?php echo __('Emitir'); ?></button>
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

															<?php
														}
													}

													//si quedaron saldos o no se genero nada default, tiro uno mas
													if (!$idx || $saldo_honorarios || $saldo_gastos_con_impuestos || $saldo_gastos_sin_impuestos) {
														?>
														<tr>
															<td colspan="3">
																<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_0', '', '', '', 100); ?>
															</td>
															<td nowrap>
																<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" class="mini_input" id="honorarios_0" value="<?php echo number_format($saldo_honorarios, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], '') ?>" size="8" onkeydown="MontoValido( this.id );"/>
															</td>
															<td nowrap>
																<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" class="mini_input"  id="gastos_con_impuestos_0" value="<?php echo number_format($saldo_gastos_con_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], '') ?>" size="8" onkeydown="MontoValido( this.id );"/>
															</td>
															<td nowrap>
		<?php echo $moneda_documento->fields['simbolo'] ?>&nbsp;<input type="text" id="gastos_sin_impuestos_0" class="mini_input"  value="<?php echo number_format($saldo_gastos_sin_impuestos, $moneda_documento->fields['cifras_decimales'], $idioma->fields['separador_decimales'], '') ?>" size="8" onkeydown="MontoValido( this.id );"/>
															</td>
															<td align="center">
																<button type="button"  onclick="AgregarFactura(0)" ><?php echo __('Emitir') ?></button>
 															<input type="hidden" id="honorarios_disponibles" value="<?php echo floatval($saldo_disponible_honorarios) ?>"/>
																<input type="hidden" id="trabajos_disponibles" value="<?php echo floatval($saldo_disponible_trabajos) ?>"/>
																	<input type="hidden" id="tramites_disponibles" value="<?php echo floatval($saldo_disponible_tramites) ?>"/>
								<input type="hidden" id="gastos_con_iva_disponibles" value="<?php echo $saldo_disponible_gastos_con_impuestos ?>"/>
								<input type="hidden" id="gastos_sin_iva_disponibles" value="<?php echo $saldo_disponible_gastos_sin_impuestos ?>"/>
															</td>
															<td/>
															<td/>
															<td/>
															 
															<td align="center">
																<?php
																echo $boton_pagar;
																$boton_pagar = '';
																?>
															</td><td/>
														</tr>
	<?php } 
			
	
}

}
die();

 