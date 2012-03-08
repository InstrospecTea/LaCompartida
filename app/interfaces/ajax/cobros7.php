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
	$pagina->FatalError(__('Cobro inválido'));
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
        $titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucción de facturar.');
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
}
        else {
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
        $titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucción de facturar.');
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

        
}


die();


?>
