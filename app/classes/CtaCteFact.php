<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Moneda.php';
require_once Conf::ServerDir().'/../app/classes/CtaCteFactMvto.php';
require_once Conf::ServerDir().'/../app/classes/Factura.php';
require_once Conf::ServerDir().'/../app/classes/CtaCteFactMoneda.php';
require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
require_once Conf::ServerDir().'/../app/classes/Documento.php';

class CtaCteFact extends Objeto
{
	function CtaCteFact($sesion = "", $fields = "", $params = "")
	{
		$this->tabla = "cta_cte_fact_mvto";
		$this->campo_id = "id_cta_cte_mvto";
		#$this->guardar_fecha = false;
		if($sesion == "")
			global $sesion;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	//Entrega el saldo de la cuenta corriente en la moneda seleccionada
    function Saldo($id_moneda = "", $cta_cte='', $fecha_desde = "", $fecha_hasta = "")
	{
		global $sesion;

		$arreglo_monedas = ArregloMonedas($sesion);

		$query = "
		SELECT sum(saldo*prm_moneda.tipo_cambio) 
		FROM `cta_cte_fact_mvto`
		JOIN prm_moneda ON (cta_cte_fact_mvto.id_moneda = prm_moneda.id_moneda)
		";

        $resp =mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__, $sesion->dbh);
		list($saldo) = mysql_fetch_array($resp); 
		//Paso el saldo a la moneda solicitada

		if($id_moneda != "")
			return $saldo / $arreglo_monedas[$id_moneda]['tipo_cambio'];
		else
			return $saldo / $arreglo_monedas['base']['tipo_cambio'];
	}

	//Permite Ingresar o editar un Movimiento. Este puede ser positivo o negativo. Facturas o pagos.
	function RegistrarMvto($id_moneda, $monto_neto, $monto_iva, $monto_bruto,
		$fecha_movimiento, $neteos, $id_factura=null, $id_pago=null, $tipo_mvto='F', $ids_monedas_documento='', $tipo_cambios_documento='', $anulando = false)
	{
		$mvto = new CtaCteFactMvto($this->sesion);
		$moneda = new Moneda($this->sesion);
		$moneda->Load($id_moneda);

		if($id_factura){
			$mvto->LoadByFactura($id_factura);	//Intento cargar el movimiento
			$mvto->Edit('id_factura',$id_factura);
		}
		else if($id_pago){
			$mvto->LoadByPago($id_pago);	//Intento cargar el movimiento
			$mvto->Edit('id_factura_pago',$id_pago);
		}
		
		if( $tipo_mvto == 'NC' )
			$id_factura_nc = $id_factura;

		if(!$mvto->Id()){
			$saldo = $monto_bruto;
			$estaba_anulado = false;
		}
		else{
			$saldo = $mvto->fields['saldo'] + $monto_bruto - $mvto->fields['monto_bruto'];
			$estaba_anulado = !empty($mvto->fields['anulado']);
		}
		$mvto->Edit('tipo_mvto',$tipo_mvto);
		$mvto->Edit('id_moneda',$id_moneda);
		$mvto->Edit('monto_neto',$monto_neto ? number_format($monto_neto,$moneda->fields['cifras_decimales'],'.','') : '0');
		$mvto->Edit('monto_iva',$monto_iva ? number_format($monto_iva,$moneda->fields['cifras_decimales'],'.','') : '0');
		$mvto->Edit('monto_bruto',$monto_bruto ? number_format($monto_bruto,$moneda->fields['cifras_decimales'],'.','') : '0');
		$mvto->Edit('saldo',$saldo ? number_format($saldo,$moneda->fields['cifras_decimales'],'.','') : '0');
		$mvto->Edit('anulado',$anulando ? '1' : '0');
		$mvto->Edit('fecha_movimiento',$fecha_movimiento);

		if(!$mvto->Write()) return false;

		$monedas_mvto = new CtaCteFactMoneda($this->sesion);
		$tipos_cambio = array();
		$id_cobro = null;
		if(empty($ids_monedas_documento) || empty($tipo_cambios_documento)){
			//copio del cobro
			$fact = new Factura($this->sesion);
			$id_cobro = $fact->GetNumeroCobro($id_factura);
		}
		else{
			$lista_ids_monedas = explode(',', $ids_monedas_documento);
			$lista_tipos_cambios = explode(',', $tipo_cambios_documento);
			foreach($lista_ids_monedas as $k => $mon){
				$tipos_cambio[] = array(
					'id_moneda' => $mon,
					'tipo_cambio' => $lista_tipos_cambios[$k]);
			}
		}
		$monedas_mvto->ActualizarTipoCambioMvto($mvto->Id(), $tipos_cambio, $id_cobro);

		//agarrar todos los neteos donde mvto es el ingreso
		//borrarlos de la bd, reajustando los saldos del mvto de egreso
		$saldo += $this->EliminarNeteos($mvto, true, false);

		if($anulando){ //para no distorsionar las sumas de los saldos con saldos de cosas anuladas, se setea artificialmente en 0
			$saldo = 0;
		}
		else{ //recalcular el saldo...
			$saldo = $monto_bruto;
			$lista = $mvto->GetNeteosSoyDeuda();
			for($i=0;$i<$lista->num;$i++){
				$neteo = $lista->Get($i);
				$saldo += $neteo->fields['monto'];
			}
		}

		//a todos los neteos q apunto yo ($neteos=array(array(id_fact, monto), ...)), restarles el saldo

		if(!empty($neteos) && !$anulando){
			$fact = new Factura($this->sesion);
			foreach($neteos as $detalles_neteo){
				$id_factura = $detalles_neteo[0];
				$monto_deuda = $detalles_neteo[1];
				$monto_pago = isset($detalles_neteo[2]) ? $detalles_neteo[2] : $monto_deuda;

				if(empty($monto_deuda)) continue; //salto los 0

				//crear un objeto de neteo, ajustando el saldo de su factura asociada
				$mvto_neteado = new CtaCteFactMvto($this->sesion);
				if($mvto_neteado->LoadByFactura($id_factura)){
					$moneda_neteado = new Moneda($this->sesion);
					$moneda_neteado->Load($mvto_neteado->fields['id_moneda']);

					$neteo = new CtaCteFactMvtoNeteo($this->sesion);
					$neteo->Edit('id_mvto_deuda', $mvto_neteado->Id());
					$neteo->Edit('id_mvto_pago', $mvto->Id());
					$neteo->Edit('monto', number_format($monto_deuda, $moneda_neteado->fields['cifras_decimales'],'.',''));
					$neteo->Edit('monto_pago', number_format($monto_pago, $moneda->fields['cifras_decimales'],'.',''));
					$neteo->Edit('fecha_movimiento', $fecha_movimiento);

					if($neteo->Write()){
						$era_cero = floatval($mvto_neteado->fields['saldo']) == 0;

						$mvto_neteado->Edit('saldo', number_format($mvto_neteado->fields['saldo'] + $monto_deuda, $moneda_neteado->fields['cifras_decimales'],'.',''));

						if($mvto_neteado->Write()){
							$saldo -= $monto_pago;
							//quedo en 0 -> pasar de FACTURADO a COBRADO
							//dejo de ser 0 -> pasar de COBRADO a FACTURADO
							//si estaba en otro estado, no tocar
							if( $id_factura_nc > 0 )
								$fact->CambiarEstado('C', $id_factura_nc);
							if(floatval($mvto_neteado->fields['saldo']) == 0 || $era_cero){
								$cod_estado = $fact->GetCodigoEstado($id_factura);
								if($cod_estado == 'F' && floatval($mvto_neteado->fields['saldo']) == 0){
									$fact->CambiarEstado('C', $id_factura);
								}
								else if($cod_estado == 'C' && $era_cero){
									$fact->CambiarEstado('F', $id_factura);
								}
							}
						}
					}
				}
			}
		}
		$mvto->Edit('saldo',$saldo ? number_format($saldo,$moneda->fields['cifras_decimales'],'.','') : '0');
		if(!$mvto->Write()) return false;

		return $mvto;
	}

	function IngresarPago($pago, $neteos, $id_cobro, &$pagina, $ids_monedas_documento='', $tipo_cambios_documento=''){

		$fecha = $pago->fields['fecha'];
		$codigo_cliente = $pago->fields['codigo_cliente'];
		$monto = $pago->fields['monto'];
		$id_moneda = $pago->fields['id_moneda'];
		$monto_moneda_cobro = $pago->fields['monto_moneda_cobro'];
		$id_moneda_cobro = $pago->fields['id_moneda_cobro'];
		$tipo_doc = $pago->fields['tipo_doc'];
		$numero_doc = $pago->fields['nro_documento'];
		$numero_cheque = $pago->fields['nro_cheque'];
		$glosa_documento = $pago->fields['descripcion'];
		$id_banco = $pago->fields['id_banco'];
		$id_cuenta = $pago->fields['id_cuenta'];
		$id_pago = $pago->Id();

		$numero_operacion = ''; //?????
		
		//echo '<pre>RegistrarMvto: ';
		//print_r(array($id_moneda, $monto, '0', $monto, $fecha, $neteos, null, $id_pago, 'P'));

		//si el pago no es en la moneda del cobro, guardo tb el equivalente de cada neteo en la moneda del pago
		if($monto != $monto_moneda_cobro){
			foreach($neteos as $k => $neteo){
				$neteos[$k][2] = $neteo[1] * $monto / $monto_moneda_cobro;
			}
		}

		//ingresar un pago a los movimientos de ctacte (con sus neteos)
		$mvto = $this->RegistrarMvto($id_moneda, $monto, '0', $monto, $fecha, $neteos, null, $id_pago, 'P', $ids_monedas_documento, $tipo_cambios_documento);
		
		$arreglo_monedas = ArregloMonedas($this->sesion);
		
		$arreglo_pagos_detalle = array();
		foreach($neteos as $neteo){
			$id_fac = $neteo[0];
			$monto_neteo = $neteo[1];
			if(empty($id_fac) || empty($monto_neteo)) continue;
			
			$fac = new Factura($this->sesion);
			$fac->Load($id_fac);
			
			$fac_hon = $fac->fields['subtotal'];
			$fac_gasto_con = $fac->fields['subtotal_gastos'];
			$fac_gasto_sin = $fac->fields['subtotal_gastos_sin_impuesto'];
			$fac_iva = $fac->fields['iva'];
			$fac_total = $fac->fields['total'];
			$fac_cobro = $fac->fields['id_cobro'];
			
			if( $fac_gasto_con + $fac_hon != 0 )
				$monto_honorarios = $fac_hon + $fac_iva * $fac_hon / ($fac_gasto_con + $fac_hon);
			else
				$monto_honorarios = 0;
			$monto_gastos = $fac_total - $monto_honorarios;
			
			if(!isset($arreglo_pagos_detalle[$fac_cobro])){
				$cobro_moneda = new CobroMoneda($this->sesion);
				$cobro_moneda->Load($fac_cobro);
				
				$arreglo_pagos_detalle[$fac_cobro] = array(
					'id_cobro' => $fac_cobro,
					'monto_honorarios' => 0,
					'monto_gastos' => 0,
					'id_moneda' => $fac->fields['id_moneda']
				);
			}
			
			$monto_honorarios *= $monto_neteo / $fac_total;
			$monto_gastos *= $monto_neteo / $fac_total;
			
			$arreglo_pagos_detalle[$fac_cobro]['monto_honorarios'] += $monto_honorarios;
			$arreglo_pagos_detalle[$fac_cobro]['monto_gastos'] += $monto_gastos;
		}
		
		$documento = new Documento($this->sesion);
		$id_documento = $mvto->GetIdDocumentoLiquidacionSoyMvto();
		if( $id_documento )
			$documento->Load( $id_documento );
		$documento->IngresoDocumentoPago(&$pagina, $id_cobro, $codigo_cliente, $monto_moneda_cobro, $id_moneda_cobro, $tipo_doc, $numero_doc, $fecha, $glosa_documento, $id_banco, $id_cuenta, $numero_operacion, $numero_cheque, $ids_monedas_documento, $tipo_cambios_documento, $arreglo_pagos_detalle, $id_pago);
		
		return true;
	}
	
	//elimina los neteos de las cosas q el mvto esta pagando, y retorna el saldo liberado
	function EliminarNeteos($mvto, $eliminar_soypago=true, $eliminar_soyfactura=true, $actualizar_saldo=false){
		$fact = new Factura($this->sesion);
		$monto = 0;
		
		if($eliminar_soypago){
			$lista = $mvto->GetNeteosSoyPago();
			for($i=0;$i<$lista->num;$i++){
				$neteo = $lista->Get($i);
				$mvto_neteado = new CtaCteFactMvto($this->sesion);

				if($mvto_neteado->Load($neteo->fields['id_mvto_deuda'])){	//Intento cargar el movimiento
					$era_cero = floatval($mvto_neteado->fields['saldo']) == 0;

					$moneda = new Moneda($this->sesion);
					$moneda->Load($mvto_neteado->fields['id_moneda']);
					$mvto_neteado->Edit('saldo', number_format($mvto_neteado->fields['saldo'] - $neteo->fields['monto'],$moneda->fields['cifras_decimales'],'.',''));

					if($mvto_neteado->Write()){
						//quedo en 0 -> pasar de FACTURADO a COBRADO
						//dejo de ser 0 -> pasar de COBRADO a FACTURADO
						//si estaba en otro estado, no tocar
						if(floatval($mvto_neteado->fields['saldo']) == 0 || $era_cero){
							$cod_estado = $fact->GetCodigoEstado($mvto_neteado->fields['id_factura']);
							if($cod_estado == 'F' && floatval($mvto_neteado->fields['saldo']) == 0){
								$fact->CambiarEstado('C', $mvto_neteado->fields['id_factura']);
							}
							else if($cod_estado == 'C' && $era_cero){
								$fact->CambiarEstado('F', $mvto_neteado->fields['id_factura']);
							}
						}

						$monto += $neteo->fields['monto_pago'];
						$neteo->Delete();
					}
				}
			}
		}
		
		if($eliminar_soyfactura){
			$lista = $mvto->GetNeteosSoyDeuda();
			for($i=0;$i<$lista->num;$i++){
				$neteo = $lista->Get($i);
				$mvto_neteado = new CtaCteFactMvto($this->sesion);
				if($mvto_neteado->Load($neteo->fields['id_mvto_pago'])){	//Intento cargar el movimiento
					$mvto_neteado->Edit('saldo', $mvto_neteado->fields['saldo'] + $neteo->fields['monto_pago']);
					if($mvto_neteado->Write()){
						$monto -= $neteo->fields['monto'];
						$neteo->Delete();
					}
				}
			}
		}
		
		if($actualizar_saldo){
			$saldo = $mvto->fields['saldo'] + $monto;
			$mvto->Edit('saldo', $saldo ? $saldo : '0');
			$mvto->Write();
		}
		return $monto;
	}
	
	function EliminarMvto($id_mvto){
		$mvto = new CtaCteFactMvto($this->sesion);
		$mvto->Load($id_mvto);
		$this->EliminarNeteos($mvto);
		return $mvto->Delete();
	}
	
	function EliminarMvtoPago($id_pago){
		$documento = new Documento($this->sesion);
		$documento->EliminarDesdeFacturaPago($id_pago);

		$mvto = new CtaCteFactMvto($this->sesion);
		$mvto->LoadByPago($id_pago);
		$this->EliminarNeteos($mvto);
		return $mvto->Delete();
	}
	
	function EliminarMvtoFactura($id_factura){
		$mvto = new CtaCteFactMvto($this->sesion);
		$mvto->LoadByFactura($id_factura);
		$this->EliminarNeteos($mvto);
		return $mvto->Delete();
	}

	function Egreso()
	{
	}

	function AnularMvto()
	{
	}
}
