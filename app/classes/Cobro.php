<?php

require_once dirname(__FILE__) . '/../conf.php';


if(!class_exists('Cobro')) {
class Cobro extends Objeto {

	var $asuntos = array();
	var $ArrayFacturasDelContrato =array();
	var $ArrayTotalesDelContrato =array();
	var $ArrayStringFacturasDelContrato =array();

	function __construct($sesion, $fields = "", $params = "") {
		$this->tabla = "cobro";
		$this->campo_id = "id_cobro";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;
		$this->guardar_fecha = true;


	}

	function Write() {
		$ingreso_historial = false;

		if ($this->fields['estado'] != $this->valor_antiguo['estado'] &&
			!empty($this->fields['estado']) && !empty($this->valor_antiguo['estado'])) {
			$ingreso_historial = true;
		}
		if (parent::Write()) {
			if ($ingreso_historial) {
				// Esa linea es necesaria para que el estado no se guardará dos veces
				$this->valor_antiguo['estado'] = $this->fields['estado'];

				$his = new Observacion($this->sesion);

				if ($ultimaobservacion = $his->UltimaObservacion($this->fields['id_cobro'])) {
					if ($ultimaobservacion['comentario'] != __("COBRO {$this->fields['estado']}")) {
						$his->Edit('fecha', date('Y-m-d H:i:s'));
						$his->Edit('comentario', __("COBRO {$this->fields['estado']}"));
						$his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
						$his->Edit('id_cobro', $this->fields['id_cobro']);
						$his->Write();
					}
				}
			}
			return true;
		}
	}

	function BotoneraCobro() {
		echo "<br /><br />    <a class=\"btn botonizame\" icon=\"ui-icon-doc\" setwidth=\"185\" onclick=\"return VerDetalles(jQuery('#todo_cobro').get(0));\" >" . __('Descargar Archivo') . " Word</a>";

		if (UtilesApp::GetConf($this->sesion, 'MostrarBotonCobroPDF')) {
			echo "<br class=\"clearfix vpx\" /><a class=\"btn botonizame\"  icon=\"ui-icon-pdf\"  setwidth=\"185\" onclick=\"return VerDetallesPDF(jQuery('#todo_cobro').get(0));\">" . __('Descargar Archivo') . " PDF</a>";
		}
		if (!UtilesApp::GetConf($this->sesion, 'EsconderExcelCobroModificable')) {
			echo "<br class=\"clearfix vpx\"/><a class=\"btn botonizame\" icon=\"xls\" setwidth=\"185\" onclick=\"return DescargarExcel(jQuery('#todo_cobro').get(0)); \">" . __('descargar_excel_modificable') . " </a>";
		}
		if (UtilesApp::GetConf($this->sesion, 'ExcelRentabilidadFlatFee')) {
			echo "	<br class=\"clearfix vpx\" /><a class=\"btn botonizame\" icon=\"xls\" setwidth=\"185\" onclick=\"return DescargarExcel(jQuery('#todo_cobro').get(0), 'rentabilidad'); \">" . __('Excel rentabilidad') . "  </a>	";
		}
		if (UtilesApp::GetConf($this->sesion, 'XLSFormatoEspecial') != '' && UtilesApp::GetConf($this->sesion, 'XLSFormatoEspecial') != 'cobros_xls.php') {
			echo "  <br class=\"clearfix vpx\" /><a class=\"btn botonizame\" icon=\"xls\" setwidth=\"185\" onclick=\"return DescargarExcel(jQuery('#todo_cobro').get(0), 'especial');\">" . __('Descargar Excel Cobro') . " </a>";
		}
	}

	//Guarda los pagos que pudo haber hecho un documento
	function SetPagos($pago_honorarios, $pago_gastos, $id_documento = null) {
		$nuevo_pago = false;
		$pagado = false;
		if ($pago_honorarios) {
			if ($this->fields['honorarios_pagados'] == 'NO') {
				if ($id_documento) {
					$this->Edit('id_doc_pago_honorarios', $id_documento);
				}
				$this->Edit('honorarios_pagados', 'SI');
				$nuevo_pago = true;
			}
		} else {
			$this->Edit('id_doc_pago_honorarios', 'NULL');
			$this->Edit('honorarios_pagados', 'NO');
		}
		if ($pago_gastos) {
			if ($this->fields['gastos_pagados'] == 'NO') {
				if ($id_documento) {
					$this->Edit('id_doc_pago_gastos', $id_documento);
				}
				$this->Edit('gastos_pagados', 'SI');

				$descripcion = __("Pago de Gasto de Cobro #") . $this->fields['id_cobro'];
				if ($id_documento) {
					$descripcion .= __(" por Documento #") . $id_documento;
				}

				// Deprecated. El ingreso de este movimiento ficticio ya se maneja en la clase NeteoDocumento.
				/*if ($this->fields['monto_gastos'] > 0) {
					$provision = new Gasto($this->sesion);
					$provision->Edit('id_moneda', $this->fields['opc_moneda_total']);
					$provision->Edit('ingreso', $this->fields['monto_gastos']);
					$provision->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
					$provision->Edit('id_usuario_orden', $this->sesion->usuario->fields['id_usuario']);
					$provision->Edit('id_cobro', $this->fields['id_cobro']);
					$provision->Edit('codigo_cliente', $this->fields['codigo_cliente']);
					$provision->Edit('codigo_asunto', 'NULL');
					$provision->Edit('descripcion', $descripcion);
					$provision->Edit('documento_pago', $id_documento);
					$provision->Edit('incluir_en_cobro', 'NO');
					$provision->Edit('fecha', date('Y-m-d H:i:s'));
					$provision->Write();
				}*/
				$nuevo_pago = true;
			}
		} else {
			$this->Edit('id_doc_pago_gastos', 'NULL');
			$this->Edit('gastos_pagados', 'NO');
		}

		if ($this->fields['honorarios_pagados'] == 'SI' && $this->fields['gastos_pagados'] == 'SI' && ($this->fields['estado'] != 'PAGADO' || $nuevo_pago)) {
			if (!$this->fields['fecha_cobro']) {
				$this->Edit('fecha_cobro', date('Y-m-d H:i:s'));
			}

			$estado_anterior = $this->fields['estado'];
			$this->Edit('estado', 'PAGADO');

			#Se ingresa la anotación en el historial
			if ($estado_anterior != 'PAGADO') {
				/* $his = new Observacion($this->sesion);
				  $his->Edit('fecha', date('Y-m-d H:i:s'));
				  $his->Edit('comentario', __('COBRO PAGADO'));
				  $his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
				  $his->Edit('id_cobro', $this->fields['id_cobro']);
				  if ($his->Write())
				  $pagado = true; */
				$pagado = true;
			}
		}
		$this->Write();
		return $pagado;
	}

	function TieneFacturaActivoAsociado() {
		$query = "SELECT count(*) FROM factura WHERE id_cobro = '" . $this->fields['id_cobro'] . "' AND anulado = 0";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cont) = mysql_fetch_array($resp);
		if ($cont > 0) {
			return true;
		} else {
			return false;
		}
	}

	#retorna el listado de asuntos asociados a un cobro

	function LoadAsuntos() {
		$query = "SELECT codigo_asunto FROM cobro_asunto WHERE id_cobro='" . $this->fields['id_cobro'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$this->asuntos = array();
		while (list($codigo) = mysql_fetch_array($resp)) {
			array_push($this->asuntos, $codigo);
		}
		return true;
	}

	function LoadGlosaAsuntos() {
		$query = "SELECT glosa_asunto FROM cobro_asunto JOIN asunto USING( codigo_asunto ) WHERE id_cobro='" . $this->fields['id_cobro'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$this->glosa_asuntos = array();
		while (list($glosa) = mysql_fetch_array($resp)) {
			array_push($this->glosa_asuntos, $glosa);
		}
		return true;
	}

	function AsuntosNombreCodigo($id_cobro) {
		$query = "SELECT asunto.codigo_asunto, asunto.glosa_asunto
		FROM cobro_asunto
		LEFT JOIN asunto ON cobro_asunto.codigo_asunto = asunto.codigo_asunto
		WHERE cobro_asunto.id_cobro = '" . $id_cobro . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$asuntos = array();
		while (list($codigo, $glosa) = mysql_fetch_array($resp)) {
			$asuntos[] = $codigo . " " . $glosa;
		}
		return $asuntos;
	}

	#revisa si tiene pagos asociados

	function TienePago() {
		$query = "SELECT * FROM documento WHERE tipo_doc != 'N' AND id_cobro='" . $this->fields['id_cobro'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$numrows = mysql_num_rows($resp);
		if ($numrows > 0) {
			return true;
		}
		return false;
	}

	function CalcularEstadoAnterior() {
		/*
		 * este estado anterior es sólo cuando no tiene pagos.
		 * cuando si tiene pagos lo calcula en la funcion CambiarEstadoSegunFactura() esta misma clase
		 */
		/* $query = "SELECT * FROM prm_estado_cobro WHERE ( codigo_estado_cobro != 'CREADO' AND codigo_estado_cobro != 'EN REVISION' ) ORDER BY orden ASC";
		  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		  $estado_anterior_temp = "";
		  while( list( $codigo_estado_cobro, $orden ) = mysql_fetch_array($resp) )
		  {
		  if( $codigo_estado_cobro == 'EMITIDO' || $codigo_estado_cobro == 'FACTURADO' || $codigo_estado_cobro == 'ENVIADO AL CLIENTE' )
		  {
		  $estado_anterior_temp = ( $this->TieneFacturasSinAnular() ? "ENVIADO AL CLIENTE" : "EMITIDO" );
		  }


		  } */
		$estado_anterior_temp = 'EMITIDO'; /* estado más atrás que puede llegar al borrar pago */
		$codigo_estado_cobro = $this->fields['estado'];
		if ($this->TienePago()) {
			$estado_anterior_temp = 'PAGO PARCIAL';
		} else {
			if ($codigo_estado_cobro == 'PAGADO' || $codigo_estado_cobro == 'PAGO PARCIAL') {
				if (!empty($this->fields['fecha_enviado_cliente']) && $this->fields['fecha_enviado_cliente'] != '0000-00-00 00:00:00') {
					$estado_anterior_temp == 'ENVIADO AL CLIENTE';
				} else {
					if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
						if ($this->TieneFacturasSinAnular()) {
							$estado_anterior_temp = 'FACTURADO';
						}
					}
				}
			} else if ($codigo_estado_cobro == 'ENVIADO AL CLIENTE') {
				if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
					if ($this->TieneFacturasSinAnular()) {
						$estado_anterior_temp = 'FACTURADO';
					}
				}
			} else if ($codigo_estado_cobro == 'FACTURADO') {
				if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
					if ($this->TieneFacturasSinAnular()) {
						$estado_anterior_temp = 'FACTURADO';
					}
				}
			}
		}

		return $estado_anterior_temp;
	}

	function CambiarEstadoAnterior() {
		if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$this->CambiarEstadoSegunFacturas();
		} else {
			$nuevo_estado = $this->CalcularEstadoAnterior();

			if (!$this->TienePago()) {
				$query_update_cobro = "UPDATE cobro SET estado='$nuevo_estado', fecha_pago_parcial=NULL, fecha_cobro=NULL WHERE id_cobro='" . $this->fields['id_cobro'] . "'";
				mysql_query($query_update_cobro, $this->sesion->dbh) or Utiles::errorSQL($query_update_cobro, __FILE__, __LINE__, $this->sesion->dbh);
			} else {
				$otros = "";
				if ($nuevo_estado != 'PAGADO') {
					$otros = ", fecha_cobro=NULL";
					if ($nuevo_estado != 'PAGO PARCIAL') {
						$otros .= ", fecha_pago_parcial = NULL";
					}
				}
				$query_update_cobro = "UPDATE cobro SET estado='$nuevo_estado' $otros WHERE id_cobro='" . $this->fields['id_cobro'] . "'";
				mysql_query($query_update_cobro, $this->sesion->dbh) or Utiles::errorSQL($query_update_cobro, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
	}

	function CantidadFacturasSinAnular() {
		$query = "SELECT COUNT(*)
					FROM factura f
						JOIN prm_documento_legal pdl ON ( f.id_documento_legal = pdl.id_documento_legal )
					WHERE id_cobro = '" . $this->fields['id_cobro'] . "'
						AND f.id_estado != 5
						AND f.estado != 'ANULADA'
						AND f.anulado = 0
						AND pdl.codigo != 'NC'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cantidad_facturas) = mysql_fetch_array($resp);
		return $cantidad_facturas;
	}

	/*
	 * @param $sesion objeto sesion
	 * @param $nuevomodulofactura bool dice si está activo
	 * @param $id_cobro int si se especifica un cobro, solo trae facturas y documentos de ese cobro
	 * @param $tipo string sirve para filtrar la query: 'G' trae solo cobros de gastos, 'H' solo cobro de honorarios, 'M' sólo cobros mixtos.
	 */
		function FacturasDelContrato($sesion=null,$nuevomodulofactura=false,$id_cobro=null,$tipo='') {
		if($sesion==null) $sesion=$this->sesion;
		$this->ArrayFacturasDelContrato=array();
			if ($nuevomodulofactura) {
					$query = "SELECT
				concat(prm_documento_legal.glosa,' N° ',  lpad(factura.serie_documento_legal,'3','0'),'-',lpad(factura.numero,'7','0')) as facturanumero ,
				cobro.id_cobro,

				  cobro.fecha_enviado_cliente,cobro.fecha_emision,
								prm_moneda.simbolo, moneda_total.glosa_moneda, moneda_total.simbolo as simbolo_moneda_total,
								factura.subtotal as subtotal_honorarios,
								cobro_moneda.tipo_cambio,
								cobro.tipo_cambio_moneda,
								prm_moneda.cifras_decimales,


								 cast(factura.total-factura.iva as decimal(10,4)) as total_sin_impuesto ,
								factura.iva,
								factura.total ,
								date_format(factura.fecha,'%Y-%m') as periodo,
								factura.subtotal as subtotal_honorarios,
									factura.subtotal_gastos,
									factura.subtotal_gastos_sin_impuesto,
									ccfm.saldo,
									ccfm.id_moneda,
									cobro.incluye_honorarios,
									cobro.incluye_gastos,
								(if(ccfm.id_moneda=cobro_moneda.id_moneda, 1,(cobro.tipo_cambio_moneda/cobro_moneda.tipo_cambio)  )) as tasa_cambio
								, if(cobro.incluye_honorarios=1 and cobro.incluye_gastos=0 , 'H',
										if(cobro.incluye_honorarios=0 and cobro.incluye_gastos=1 , 'G','M')
									) as tipo_cobro
								FROM cobro
								LEFT JOIN factura using (id_cobro)
								LEFT JOIN cta_cte_fact_mvto ccfm using (id_factura)
								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cobro.id_moneda
								LEFT JOIN prm_moneda as moneda_total ON moneda_total.id_moneda = cobro.opc_moneda_total
								LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=cobro.opc_moneda_total
								JOIN prm_documento_legal ON (prm_documento_legal.id_documento_legal = factura.id_documento_legal)
                              	WHERE   cobro.estado!='CREADO' AND cobro.estado!='EN REVISION' AND cobro.estado!='INCOBRABLE'
								 ";//and ccfm.saldo<0  ";
					//editado: AND cobro.estado!='PAGADO'
				} else {
					$query = "SELECT cobro.documento as facturanumero,
								cobro.id_cobro,
								cobro.fecha_enviado_cliente,cobro.fecha_emision,
								prm_moneda.simbolo, moneda_total.glosa_moneda, moneda_total.simbolo as simbolo_moneda_total, cobro.monto,
								cobro_moneda.tipo_cambio,cobro.tipo_cambio_moneda,prm_moneda.cifras_decimales,
								 cast(documento.monto-documento.impuesto as decimal(10,4)) as total_sin_impuesto ,
								documento.impuesto as  iva,
								documento.monto as total ,
								date_format(documento.fecha,'%Y-%m') as periodo,
								documento.subtotal_honorarios,
								documento.subtotal_gastos,
								documento.subtotal_gastos_sin_impuesto,
								documento.saldo_honorarios,
								documento.saldo_gastos,
								documento.id_moneda,
									cobro.incluye_honorarios,
									cobro.incluye_gastos,
								if(documento.id_moneda= cobro.id_moneda,1,cm1.tipo_cambio / cm2.tipo_cambio) as tasa_cambio,
								if(cobro.incluye_honorarios=1 and cobro.incluye_gastos=0 , 'H',
										if(cobro.incluye_honorarios=0 and cobro.incluye_gastos=1 , 'G','M')
									) as tipo_cobro
 								FROM cobro
								LEFT join documento on cobro.id_cobro=documento.id_cobro and documento.tipo_doc='N'

				LEFT JOIN cobro_moneda as cm1 ON cm1.id_cobro = documento.id_cobro AND cm1.id_moneda = documento.id_moneda
				LEFT JOIN cobro_moneda as cm2 ON cm2.id_cobro =cobro.id_cobro AND cm2.id_moneda =cobro.opc_moneda_total

								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cobro.id_moneda
								LEFT JOIN prm_moneda as moneda_total ON moneda_total.id_moneda = cobro.opc_moneda_total
								LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=cobro.opc_moneda_total
								WHERE   cobro.estado!='CREADO' AND cobro.estado!='EN REVISION' AND cobro.estado!='INCOBRABLE'";
				}
//AND cobro.estado!='PAGADO'

				$query .= " AND cobro.id_contrato=" . $this->fields['id_contrato'];
				if($id_cobro!=null) $query .= " AND cobro.id_cobro=" . $id_cobro;

				if($tipo!='') $query .= " AND if(cobro.incluye_honorarios=1 and cobro.incluye_gastos=0 , 'H',
										if(cobro.incluye_honorarios=0 and cobro.incluye_gastos=1 , 'G','M')
									)='" . $tipo."'";
		// echo '<br><br>'.$query.'<hr><br>';

				$facturasST=$sesion->pdodbh->query($query) ;
				$this->ArrayFacturasDelContrato=$facturasST->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);
				return $this->ArrayFacturasDelContrato;


	}
function TotalesDelContrato($facturas,$nuevomodulofactura=false,$id_cobro=null) {
	$totales=array();

	if (count($facturas)>0) {
					foreach ($facturas as $facturaarray) {

						$factura=$facturaarray[0];
						$monto_honorarios = number_format($factura['subtotal_honorarios'], $factura['cifras_decimales'], '.', '');
						$monto_gastos_c_iva = number_format($factura['subtotal_gastos'], $factura['cifras_decimales'], '.', '');
						$monto_gastos_s_iva = number_format($factura['subtotal_gastos_sin_impuesto'], $factura['cifras_decimales'], '.', '');
						$monto_gastos=$monto_gastos_c_iva+$monto_gastos_s_iva;

						$monto_honorarios_moneda = $monto_honorarios*$factura['tasa_cambio'];
						$monto_gastos_c_iva_moneda = $monto_gastos_c_iva*$factura['tasa_cambio'];
						$monto_gastos_s_iva_moneda = $monto_gastos_s_iva*$factura['tasa_cambio'];
						$monto_gastos_moneda=$monto_gastos*$factura['tasa_cambio'];


						$total_en_moneda = $monto_honorarios_moneda= $total_honorarios * ($factura['tipo_cambio_moneda'] / $factura['tipo_cambio']);
							if ($nuevomodulofactura) {
							if ($factura['incluye_honorarios'] == 1) {
								$saldo_honorarios = -1 * $factura['saldo'];
								$saldo_gastos = 0;
							} else {
								$saldo_honorarios = 0;
								$saldo_gastos = -1 * $factura['saldo'];
							}
						} else {
							$saldo_honorarios = $factura['saldo_honorarios'];
							$saldo_gastos = $factura['saldo_gastos'];
						}

						$totales['contrato']['adeudado']+=$total_en_moneda;
						$totales['contrato']['moneda_adeudado'] = $factura['glosa_moneda'];
						$totales['contrato']['monto_gastos_c_iva']+=$monto_gastos_c_iva;
						$totales['contrato']['monto_gastos_c_iva_moneda']+=$monto_gastos_c_iva_moneda;
						$totales['contrato']['monto_gastos_s_iva']+=$monto_gastos_s_iva;
						$totales['contrato']['monto_gastos_s_iva_moneda']+=$monto_gastos_s_iva_moneda;
						$totales['contrato']['monto_gastos']+=$monto_gastos;
						$totales['contrato']['monto_gastos_moneda']+=$monto_gastos_moneda;
						$totales['contrato']['monto_honorarios']+=$monto_honorarios;
						$totales['contrato']['monto_honorarios_moneda']+=$monto_honorarios_moneda;
						$totales['contrato']['monto_honorarios+gastos']+=$monto_gastos+$monto_honorarios;
						$totales['contrato']['monto_honorarios+gastos_moneda']+=$monto_gastos_moneda+$monto_honorarios_moneda;
						$totales['contrato']['saldo_honorarios']+=$saldo_honorarios;
						$totales['contrato']['saldo_gastos']+=$saldo_gastos;

						$totales['contrato']['simbolo_moneda_total'] = $factura['simbolo_moneda_total'];

						if($id_cobro!=null && $factura['id_cobro']==$id_cobro) {
							$totales[$id_cobro]['adeudado']+=$total_en_moneda;
							$totales[$id_cobro]['moneda_adeudado'] = $factura['glosa_moneda'];
							$totales[$id_cobro]['monto_gastos_c_iva']+=$monto_gastos_c_iva;
							$totales[$id_cobro]['monto_gastos_c_iva_moneda']+=$monto_gastos_c_iva_moneda;
							$totales[$id_cobro]['monto_gastos_s_iva']+=$monto_gastos_s_iva;
							$totales[$id_cobro]['monto_gastos_s_iva_moneda']+=$monto_gastos_s_iva_moneda;
							$totales[$id_cobro]['monto_gastos']+=$monto_gastos;
							$totales[$id_cobro]['monto_gastos_moneda']+=$monto_gastos_moneda;
							$totales[$id_cobro]['monto_honorarios']+=$monto_honorarios;
							$totales[$id_cobro]['monto_honorarios_moneda']+=$monto_honorarios_moneda;
							$totales[$id_cobro]['monto_honorarios+gastos']+=$monto_gastos+$monto_honorarios;
							$totales[$id_cobro]['monto_honorarios+gastos_moneda']+=$monto_gastos_moneda+$monto_honorarios_moneda;
							$totales[$id_cobro]['saldo_honorarios']+=$saldo_honorarios;
							$totales[$id_cobro]['saldo_gastos']+=$saldo_gastos;
							$totales[$id_cobro]['simbolo_moneda_total'] = $factura['simbolo_moneda_total'];

						}

					}

	}
	$this->ArrayTotalesDelContrato=$totales;
	return $this->ArrayTotalesDelContrato;
}
	function TieneFacturasSinAnular() {
		$cantidad_facturas = $this->CantidadFacturasSinAnular();
		if ($cantidad_facturas > 0) {
			return true;
		}
		return false;
	}

	function CambiarEstadoSegunFacturas() {
		/**
		 * INCOBRABLE			si estaba en incobrable no se toca
		 * ENVIADO AL CLIENTE	si no hay facturas y pasó por el estado enviado al cliente, dejarlo asi
		 * 						(salvo que tenga el config EnviarAlClienteAntesDeFacturar)
		 * EMITIDO				sin facturas (no-anuladas) y sin fecha de enviado al cliente
		 * FACTURADO			con facturas && monto pagado == 0
		 * PAGADO				con facturas && monto_pagado == monto_total
		 * PAGO PARCIAL			con facturas && 0 < monto_pagado < monto_total
		 */
		$actual = $this->fields['estado'];
		if ($actual == 'INCOBRABLE') {
			return;
		}
		$estado = $actual;

		if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$num_facturas = $this->CantidadFacturasSinAnular();
		} else {
			$num_facturas = $this->fields['facturado'];
		}

		if ($num_facturas == 0 && UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
			if ($actual != 'ENVIADO AL CLIENTE') {
				$estado = 'EMITIDO';
			}
			$this->Edit('fecha_facturacion', '0000-00-00 00:00:00');
		} else {
			$query = "SELECT f.fecha FROM factura f
							JOIN prm_documento_legal pdl ON f.id_documento_legal = pdl.id_documento_legal
						WHERE f.id_cobro = '{$this->fields['id_cobro']}'
							AND ( f.id_estado != 5 AND f.estado != 'ANULADA' AND f.anulado = 0 )
							AND pdl.codigo != 'NC'
						ORDER BY f.fecha ASC LIMIT 1";

			if ($num_facturas == 1 && ( empty($this->fields['fecha_facturacion']) || $this->fields['fecha_facturacion'] == '0000-00-00 00:00:00' )) {
				// Dejar la fecha de facturacion como la fecha de la primera factura
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$fecha_facturacion = mysql_result($resp, 0, 0);

				if (!empty($fecha_facturacion) && $fecha_facturacion != 'NULL') {
					$this->Edit('fecha_facturacion', $fecha_facturacion);
				} else {
					$this->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
				}
			} else if ($num_facturas > 1) {

				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$fecha_facturacion = mysql_result($resp, 0, 0);

				if (!empty($fecha_facturacion) && $fecha_facturacion != 'NULL') {
					$this->Edit('fecha_facturacion', $fecha_facturacion);
				}
			}

			// Tomar suma de todos los pagos aplicados a facturas
			if (UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
				$query = "SELECT ROUND(SUM(ccfmn.monto*mf.tipo_cambio/mc.tipo_cambio),m_cobro.cifras_decimales), SUM(IF(ccfmp.id_factura IS NULL,ROUND(ccfmn.monto*mf.tipo_cambio/mc.tipo_cambio,m_cobro.cifras_decimales),0))
							FROM cta_cte_fact_mvto_neteo ccfmn
								JOIN cta_cte_fact_mvto ccfm ON ccfmn.id_mvto_deuda = ccfm.id_cta_cte_mvto
								JOIN cta_cte_fact_mvto ccfmp ON ( ccfmn.id_mvto_pago = ccfmp.id_cta_cte_mvto )
								JOIN factura f ON f.id_factura = ccfm.id_factura
								JOIN cobro c ON c.id_cobro = f.id_cobro
								JOIN prm_moneda as m_cobro ON m_cobro.id_moneda = c.opc_moneda_total
								JOIN cobro_moneda as mf ON mf.id_cobro = f.id_cobro AND mf.id_moneda = f.id_moneda
								JOIN cobro_moneda as mc ON mc.id_cobro = c.id_cobro AND mc.id_moneda = c.opc_moneda_total
							WHERE f.id_cobro = '" . $this->fields['id_cobro'] . "'";
			} else {
				$query = "SELECT ROUND( SUM(-1*docpago.monto), mon.cifras_decimales ), ROUND( SUM(-1*docpago.monto), mon.cifras_decimales )
							FROM documento doccobro
								join neteo_documento nd on doccobro.id_documento=nd.id_documento_cobro
								join documento docpago on docpago.id_documento=nd.id_documento_pago
								JOIN prm_moneda as mon ON mon.id_moneda ='" . $this->fields['opc_moneda_total'] . "'

							WHERE doccobro.id_cobro = '" . $this->fields['id_cobro'] . "' and doccobro.tipo_doc='N' AND docpago.tipo_doc != 'N' ";
			}
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($monto_pagado, $monto_pago_menos_ncs) = mysql_fetch_array($resp);

			//echo 'PASE POR ACS:'.$query.' , monto pagado:'.$monto_pagado.' monto pago menos ncs: '.$monto_pago_menos_ncs;

			if (empty($monto_pago_menos_ncs) || $monto_pago_menos_ncs == 'NULL') {
				if ($num_facturas > 0) {
					if (!empty($this->fields['fecha_enviado_cliente']) &&
						$this->fields['fecha_enviado_cliente'] != '0000-00-00 00:00:00' &&
						!UtilesApp::GetConf($this->sesion, 'EnviarAlClienteAntesDeFacturar')) {
						$estado = 'ENVIADO AL CLIENTE';
					} else {
						$estado = 'FACTURADO';
					}
				} else {
					if (!empty($this->fields['fecha_enviado_cliente']) && $this->fields['fecha_enviado_cliente'] != '0000-00-00 00:00:00' && !UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura')) {
						$estado = 'ENVIADO AL CLIENTE';
					} else {
						$estado = 'EMITIDO';
					}
					$this->Edit('fecha_facturacion', '0000-00-00 00:00:00');
				}
			} else if (!empty($monto_pago_menos_ncs) && $monto_pago_menos_ncs > 0) {
				// Ver si los pagos son por el monto total del cobro
				$query = "SELECT monto
							FROM documento
							WHERE tipo_doc = 'N' AND id_cobro = '" . $this->fields['id_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$monto_total = mysql_result($resp, 0, 0);
				//  echo 'monto_pagado: '.$monto_pagado.' monto total: '.$monto_total.'<br>'; exit;
				$estado = (round($monto_pagado, 2) < round($monto_total, 2)) ? 'PAGO PARCIAL' : 'PAGADO';
				if ($estado == 'PAGO PARCIAL' && ( empty($this->fields['fecha_pago_parcial']) || $this->fields['fecha_pago_parcial'] == '0000-00-00 00:00:00' )) {
					$fecha_primer_pago = $this->FechaPrimerPago();
					$this->Edit('fecha_pago_parcial', $fecha_primer_pago);
				}

				if ($estado == 'PAGADO' && ( empty($this->fields['fecha_cobro']) || $this->fields['fecha_cobro'] == '0000-00-00 00:00:00' )) {
					$fecha_ultimo_pago = $this->FechaUltimoPago();
					$this->Edit('fecha_cobro', $fecha_ultimo_pago);
				}
			}
		}
		if ($estado != 'PAGADO') {
			// Esta fecha debiese llenarse cuando tiene pago completo
			$this->Edit('fecha_cobro', '0000-00-00 00:00:00');
			if ($estado != 'PAGO PARCIAL') {
				// Esta fecha debiese existir solo con pagos
				$this->Edit('fecha_pago_parcial', '0000-00-00 00:00:00');
			}
		}
		$estado_anterior = $this->fields['estado'];
		$this->Edit('estado', $estado);
		if ($this->Write() && $estado_anterior != $estado) {
			/* $his = new Observacion($this->sesion);
			  $his->Edit('fecha', date('Y-m-d H:i:s'));
			  $his->Edit('comentario', __('COBRO ' . $estado));
			  $his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
			  $his->Edit('id_cobro', $this->fields['id_cobro']);
			  $his->Write(); */
			return $estado;
		}
		return null;
	}

	function FechaPrimerPago() {
		$query = "SELECT MIN(fp.fecha)
					FROM cta_cte_fact_mvto_neteo ccfmn
						JOIN cta_cte_fact_mvto ccfm ON ccfmn.id_mvto_deuda = ccfm.id_cta_cte_mvto
						JOIN cta_cte_fact_mvto ccfmp ON ( ccfmn.id_mvto_pago = ccfmp.id_cta_cte_mvto )
						JOIN factura_pago fp ON fp.id_factura_pago = ccfmp.id_factura_pago
						JOIN factura f ON f.id_factura = ccfm.id_factura
					WHERE f.id_cobro = '" . $this->fields['id_cobro'] . "' ";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha_ini) = mysql_fetch_array($resp);

		return $fecha_ini;
	}

	function FechaUltimoPago() {
		$query = "SELECT MAX(fp.fecha)
					FROM cta_cte_fact_mvto_neteo ccfmn
						JOIN cta_cte_fact_mvto ccfm ON ccfmn.id_mvto_deuda = ccfm.id_cta_cte_mvto
						JOIN cta_cte_fact_mvto ccfmp ON ( ccfmn.id_mvto_pago = ccfmp.id_cta_cte_mvto )
						JOIN factura_pago fp ON fp.id_factura_pago = ccfmp.id_factura_pago
						JOIN factura f ON f.id_factura = ccfm.id_factura
					WHERE f.id_cobro = '" . $this->fields['id_cobro'] . "' ";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha_ini) = mysql_fetch_array($resp);

		return $fecha_ini;
	}

	function FechaPrimerTrabajo() {
		$query = "SELECT MIN( fecha ) FROM trabajo WHERE id_cobro = '" . $this->fields['id_cobro'] . "' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha_ini) = mysql_fetch_array($resp);

		return $fecha_ini;
	}

	function Eliminar() {
		$id_cobro = $this->fields[id_cobro];
		if ($id_cobro) {
			//Elimina el gasto generado y la provision generada, SOLO si la provision no ha sido incluida en otro cobro:
			if ($this->fields['id_provision_generada']) {
				$provision_generada = new Gasto($this->sesion);
				$gasto_generado = new Gasto($this->sesion);
				$provision_generada->Load($this->fields['id_provision_generada']);

				if ($provision_generada->Loaded()) {
					if (!$provision_generada->fields['id_cobro']) {
						$provision_generada->Eliminar();
						$gasto_generado->Load($this->fields['id_gasto_generado']);
						if ($gasto_generado->Loaded()) {
							$gasto_generado->Eliminar();
						}
					}
				}
			}

			$this->AnularDocumento();

			$query = "UPDATE trabajo SET id_cobro = NULL, fecha_cobro= 'NULL', monto_cobrado='NULL' WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE tramite SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			$query = "UPDATE cobro_pendiente SET id_cobro = NULL WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			#Se ingresa la anotación en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha', @date('Y-m-d H:i:s'));
			$his->Edit('comentario', __('COBRO ELIMINADO'));
			$his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro', $id_cobro);
			$his->Write();

			$query = "DELETE FROM cobro WHERE id_cobro = $id_cobro";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			return true;
		} else {
			return false;
		}
	}

	function AnularEmision($estado = 'CREADO') {
		$id_cobro = $this->fields['id_cobro'];

		$query = "SELECT count(*) FROM documento WHERE id_cobro = '$id_cobro' AND tipo_doc != 'N' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cantidad_pagos) = mysql_fetch_array($resp);

		#No se puede anular si tiene facturas.
		if ($estado == 'CREADO') {
			$query = "SELECT id_factura FROM factura_cobro WHERE id_cobro ='" . $id_cobro . "'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			if (list($id) = mysql_fetch_array($resp)) {
				return false;
			}

			if ($cantidad_pagos > 0) {
				return false;
			}
		}

		$query = "UPDATE trabajo SET fecha_cobro= 'NULL', monto_cobrado='NULL' WHERE id_cobro = $id_cobro";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if (array_key_exists('estado_anterior', $this->fields)) {
			$this->Edit('estado_anterior', $this->fields['estado']);
		}
		$this->Edit('estado', $estado);
		$this->Edit('id_doc_pago_honorarios', 'NULL');
		$this->Edit('id_doc_pago_gastos', 'NULL');
		$this->Write();
		$this->AnularDocumento($estado, $cantidad_pagos > 0 ? true : false);
	}

	function AnularDocumento($estado = 'CREADO', $hay_pagos = false) {
		$documento = new Documento($this->sesion);
		$documento->LoadByCobro($this->fields['id_cobro']);

		if ($estado == 'INCOBRABLE') {
			$documento->EliminarNeteos();
			$documento->AnularMontos();
		} else if (!$hay_pagos) {
			$documento->EliminarNeteos();
			$query_factura = "UPDATE factura_cobro SET id_documento = NULL WHERE id_documento = '" . $documento->fields['id_documento'] . "'";
			mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura, __FILE__, __LINE__, $this->sesion->dbh);
			//if( $sesion->usuario->TienePermiso('SADM')) print_r($documento);
			$documento->Delete();
		}
	}

	function IdMoneda($id_cobro = '') {
		if (!empty($id_cobro)) {
			$query = "SELECT id_moneda FROM cobro WHERE id_cobro = '$id_cobro' ";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_moneda) = mysql_fetch_array($resp);
		} else {
			$id_moneda = $this->fields['id_moneda'];
		}

		return $id_moneda;
	}

	function FechaUltimoCobro($codigo_cliente) {
		$query = "SELECT IF( (fecha_fin > '0000-00-00' AND fecha_fin IS NOT NULL ), fecha_fin, NULL)
							FROM cobro WHERE codigo_cliente = '$codigo_cliente' AND estado <> 'CREADO' ORDER BY fecha_cobro DESC LIMIT 0,1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha_ultimo_cobro) = mysql_fetch_array($resp);
		#echo $fecha_ultimo_cobro;
		return $fecha_ultimo_cobro;
	}

	function CalculaMontoGastos($id_cobro) {
		$query = "SELECT egreso, ( monto_cobrable * cobro_moneda.tipo_cambio ) as monto, ingreso
								FROM cta_corriente
								LEFT JOIN cobro_moneda ON (cta_corriente.id_cobro=cobro_moneda.id_cobro AND cta_corriente.id_moneda=cobro_moneda.id_moneda)
								WHERE cta_corriente.id_cobro='" . $id_cobro . "'
								AND (egreso > 0 OR ingreso > 0)
								AND cta_corriente.incluir_en_cobro = 'SI'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$monto_total = 0;
		while (list($egreso, $monto, $ingreso) = mysql_fetch_array($resp)) {
			if ($egreso > 0) {
				$monto_total += $monto;
			} else if ($ingreso > 0) {
				$monto_total -= $monto;
			}
		}
		return $monto_total;
	}

	function CalculaMontoTramites($cobro) {
		$query = "SELECT SUM(tarifa_tramite)
								FROM tramite
								WHERE tramite.id_cobro='" . $cobro->fields['id_cobro'] . "' AND tramite.cobrable=1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_monto_tramites) = mysql_fetch_array($resp);

		return $total_monto_tramites;
	}

	// Cargar información de escalonadas a un objeto
	function CargarEscalonadas() {
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		$this->escalonadas = array();
		$this->escalonadas['num'] = 0;
		$this->escalonadas['monto_fijo'] = 0;

		$tiempo_inicial = 0;
		for ($i = 1; $i < 4; $i++) {
			if (empty($this->fields['esc' . $i . '_tiempo'])) {
				break;
			}

			$this->escalonadas['num']++;
			$this->escalonadas[$i] = array();

			$this->escalonadas[$i]['tiempo_inicial'] = $tiempo_inicial;
			$this->escalonadas[$i]['tiempo_final'] = $this->fields['esc' . $i . '_tiempo'] + $tiempo_inicial;
			$this->escalonadas[$i]['id_tarifa'] = $this->fields['esc' . $i . '_id_tarifa'];
			$this->escalonadas[$i]['id_moneda'] = $this->fields['esc' . $i . '_id_moneda'];
			$this->escalonadas[$i]['monto'] = UtilesApp::CambiarMoneda(
					$this->fields['esc' . $i . '_monto'], $cobro_moneda->moneda[$this->escalonadas[$i]['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->escalonadas[$i]['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']
				) * ( 1 - $this->fields['esc' . $i . '_descuento'] / 100 );

			$this->escalonadas[$i]['descuento'] = $this->fields['esc' . $i . '_descuento'];

			if (!empty($this->escalonadas[$i]['monto'])) {
				$this->escalonadas[$i]['escalonada_tarificada'] = 0;
				$this->escalonadas['monto_fijo'] += $this->escalonadas[$i]['monto'];
			} else {
				$this->escalonadas[$i]['escalonada_tarificada'] = 1;
			}

			$tiempo_inicial += $this->fields['esc' . $i . '_tiempo'];
		}

		$this->escalonadas['num']++;
		$i = 4;  //ultimo campo (por la genialidad de agregar como mil campos en una tabla)
		$i2 = $this->escalonadas['num']; //proximo "slot" en el array de escalonadas

		$this->escalonadas[$i2] = array();

		$this->escalonadas[$i2]['tiempo_inicial'] = $tiempo_inicial;
		$this->escalonadas[$i2]['tiempo_final'] = '';
		$this->escalonadas[$i2]['id_tarifa'] = $this->fields['esc' . $i . '_id_tarifa'];
		$this->escalonadas[$i2]['id_moneda'] = $this->fields['esc' . $i . '_id_moneda'];
		$this->escalonadas[$i2]['monto'] = UtilesApp::CambiarMoneda(
				$this->fields['esc' . $i . '_monto'], $cobro_moneda->moneda[$this->escalonadas[$i2]['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->escalonadas[$i2]['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']
			) * ( 1 - $this->fields['esc' . $i . '_descuento'] / 100 );
		$this->escalonadas[$i2]['descuento'] = $this->fields['esc' . $i . '_descuento'];

		if (!empty($this->escalonadas[$i2]['monto'])) {
			$this->escalonadas[$i2]['escalonada_tarificada'] = 0;
			$this->escalonadas['monto_fijo'] += $this->escalonadas[$i2]['monto'];
		} else {
			$this->escalonadas[$i2]['escalonada_tarificada'] = 1;
		}
	}

	// Para calcular monto honorarios en caso de forma de cobro ESCALONADA
	function MontoHonorariosEscalonados($lista_trabajos) {
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		// Cargar escalonadas
		$this->CargarEscalonadas();

		// Contador escalonadas
		$x_escalonada = 1;
		$detalle_trabajos = array();

		$detalle_trabajos['trabajos'] = array();

		// Crear arreglos para traer datos detallados ...
		$detalle_trabajos['detalle_escalonadas'] = array();

		$detalle_trabajos['detalle_escalonadas'][1] = array();
		$detalle_trabajos['detalle_escalonadas'][1]['usuarios'] = array();
		$detalle_trabajos['detalle_escalonadas'][1]['trabajos'] = array();

		$detalle_trabajos['detalle_escalonadas'][1]['totales'] = array();
		$detalle_trabajos['detalle_escalonadas'][1]['totales']['duracion'] = 0;
		$detalle_trabajos['detalle_escalonadas'][1]['totales']['valor'] = 0;
		// Variable para sumar monto total
		$cobro_total_honorario_cobrable = $this->escalonadas['monto_fijo'];

		// Contador de duracion
		$cobro_total_duracion = 0;

		$duracion_hora_restante = 0;

		for ($z = 0; $z < $lista_trabajos->num; $z++) {
			$trabajo = $lista_trabajos->Get($z);
			$valor_trabajo = 0;
			$valor_trabajo_hh = 0;
			$valor_trabajo_estandar = 0;
			$duracion_retainer_trabajo = 0;
			$aporte_monto_trabajo = 0;

			$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['trabajos'][$trabajo->fields['id_trabajo']] = array();

			if ($trabajo->fields['cobrable']) {
				// Revisa duración de la hora y suma duracion que sobro del trabajo anterior, si es que se cambió de escalonada
				list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) : '0');
				$duracion_trabajo = $duracion;

				// Mantengase en el mismo trabajo hasta que no se require un cambio de escalonada...
				while (true) {

					// Calcula tiempo del trabajo actual que corresponde a esa escalonada y tiempo que corresponde a la proxima.
					if (!empty($this->escalonadas[$x_escalonada]['tiempo_final'])) {
						$duracion_escalonada_actual = min($duracion, $this->escalonadas[$x_escalonada]['tiempo_final'] - $cobro_total_duracion);
						$duracion_hora_restante = $duracion - $duracion_escalonada_actual;
					} else {
						$duracion_escalonada_actual = $duracion;
						$duracion_hora_restante = 0;
					}

					$cobro_total_duracion += $duracion_escalonada_actual;

					// Busca la tarifa según abogado y definición de la escalonada
					$tarifa_estandar = UtilesApp::CambiarMoneda(
							Funciones::TarifaDefecto($this->sesion, $trabajo->fields['id_usuario'], $this->escalonadas[$x_escalonada]['id_moneda']), $cobro_moneda->moneda[$this->escalonadas[$x_escalonada]['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->escalonadas[$x_escalonada]['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']
					);
					if (!empty($this->escalonadas[$x_escalonada]['id_tarifa']) && $this->escalonadas[$x_escalonada]['id_tarifa'] != "NULL") {
						$tarifa = UtilesApp::CambiarMoneda(
								Funciones::Tarifa($this->sesion, $trabajo->fields['id_usuario'], $this->escalonadas[$x_escalonada]['id_moneda'], '', $this->escalonadas[$x_escalonada]['id_tarifa']), $cobro_moneda->moneda[$this->escalonadas[$x_escalonada]['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->escalonadas[$x_escalonada]['id_moneda']]['cifras_decimales'], $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales']
						);

						$valor_escalonada_actual = ( 1 - $this->escalonadas[$x_escalonada]['descuento'] / 100 ) * $duracion_escalonada_actual * $tarifa;
						$valor_trabajo += $valor_escalonada_actual;
						$valor_trabajo_hh += $valor_escalonada_actual;
						$valor_trabajo_estandar += ( 1 - $this->escalonadas[$x_escalonada]['descuento'] / 100 ) * $duracion_escalonada_actual * $tarifa_estandar;
					} else {
						$duracion_retainer_trabajo += $duracion_escalonada_actual;
						$valor_escalonada_actual = 0;
						$valor_trabajo += 0;
						$valor_trabajo_hh += $duracion_escalonada_actual * $tarifa_estandar;
						$valor_trabajo_estandar += $duracion_escalonada_actual * $tarifa_estandar;

						// Esa variable hay que sumar al valor cobrado de ese trabajo ...
						$deltatiempo = $this->escalonadas[$x_escalonada]['tiempo_final'] - $this->escalonadas[$x_escalonada]['tiempo_inicial'];
						if ($deltatiempo > 0) {
							$aporte_monto_trabajo += $duracion_escalonada_actual * ( $this->escalonadas[$x_escalonada]['monto'] / ( $deltatiempo ) );
						} else {
							$aporte_monto_trabajo += $this->escalonadas[$x_escalonada]['monto'];
						}
					}

					$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['trabajos'][$trabajo->fields['id_trabajo']]['duracion'] = $duracion_escalonada_actual;
					$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['trabajos'][$trabajo->fields['id_trabajo']]['valor'] = $valor_escalonada_actual;

					$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['totales']['duracion'] += $duracion_escalonada_actual;
					$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['totales']['valor'] += $valor_escalonada_actual;

					$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['trabajos'][$trabajo->fields['id_trabajo']]['usuario'] = $trabajo->fields['usr_nombre'];
					$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['trabajos'][$trabajo->fields['id_trabajo']]['categoria'] = $trabajo->fields['categoria'];

					if (is_array($detalle_trabajos[$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']])) {
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['duracion'] += $duracion_escalonada_actual;
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['valor'] += $valor_escalonada_actual;
					} else {
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['duracion'] += $duracion_escalonada_actual;
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['valor'] += $valor_escalonada_actual;
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['usuario'] = $trabajo->fields['usr_nombre'];
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['categoria'] = $trabajo->fields['categoria'];
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['tarifa'] = (!empty($tarifa) ? $tarifa : 0 );
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'][$trabajo->fields['id_usuario']]['descuento'] = $this->escalonadas[$x_escalonada]['descuento'];
					}

					if ($duracion_hora_restante > 0 || $cobro_total_duracion == $this->escalonadas[$x_escalonada]['tiempo_final']) {
						$detalle_trabajos['detalle_escalonadas'][++$x_escalonada] = array();
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['usuarios'] = array();
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['trabajos'] = array();

						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['totales'] = array();
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['totales']['duracion'] = 0;
						$detalle_trabajos['detalle_escalonadas'][$x_escalonada]['totales']['valor'] = 0;

						if ($duracion_hora_restante > 0) {
							$duracion = $duracion_hora_restante;
						} else {
							break;
						}
					} else {
						break;
					}
				}

				$cobro_total_honorario_cobrable += $valor_trabajo;
				if ($duracion_trabajo > 0) {
					$tarifa_hh = $valor_trabajo_hh / $duracion_trabajo;
					$tarifa_hh_estandar = $valor_trabajo_estandar / $duracion_trabajo;
				} else {
					$tarifa_hh = 0;
					$tarifa_hh_estandar = 0;
				}

				$detalle_trabajos['trabajos'][$trabajo->fields['id_trabajo']]['usr_nombre'] = $trabajo->fields['usr_nombre'];
				$detalle_trabajos['trabajos'][$trabajo->fields['id_trabajo']]['categoria'] = $trabajo->fields['categoria'];
				$detalle_trabajos['trabajos'][$trabajo->fields['id_trabajo']]['duracion'] = $trabajo->fields['duracion_cobrada'];
				$detalle_trabajos['trabajos'][$trabajo->fields['id_trabajo']]['tarifa'] = $tarifa_hh;
				$detalle_trabajos['trabajos'][$trabajo->fields['id_trabajo']]['importe'] = $valor_trabajo + $aporte_monto_trabajo;

				$trabajo->Edit('id_moneda', $this->fields['id_moneda']);
				$trabajo->Edit('fecha_cobro', date('Y-m-d H:i:s'));
				$trabajo->Edit('tarifa_hh', $tarifa_hh);
				$trabajo->Edit('monto_cobrado', $valor_trabajo + $aporte_monto_trabajo);
				$trabajo->Edit('costo_hh', $tarifa_hh_estandar);
				$trabajo->Edit('tarifa_hh_estandar', $tarifa_hh_estandar);
				$trabajo->Edit('duracion_retainer', $duracion_retainer_trabajo);
				$trabajo->Write();
			} else {
				continue;
			}
		}
		$total_minutos_tmp = ( $cobro_total_duracion * 60 );

		return array($cobro_total_honorario_cobrable, $total_minutos_tmp, $detalle_trabajos);
	}

	// La variable $mantener_porcentaje_impuesto es importante en la migracion de datos donde no importa el datos
	// actual guardado en la configuracion sino el dato traspasado
	function GuardarCobro($emitir = false, $mantener_porcentaje_impuesto = false) {
		if ($this->fields['estado'] != 'CREADO' AND $this->fields['estado'] != 'EN REVISION' AND $this->fields['estado'] != '')
			return "No se puede guardar " . __('el cobro') . " ya que ya se encuentra emitido. Usted debe volver " . __('el cobro') . " a estado creado o en revisión para poder actualizarlo";
		// Carga de asuntos del cobro
		$this->LoadAsuntos();

		//Tipo de cambios del cobro de (cobro_moneda)
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		// Datos moneda base
		$moneda_base = Utiles::MonedaBase($this->sesion);

		// Variables con los subtotales del cobro
		$cobro_total_minutos = 0; // Total de minutos trabajados para el cobro (cobrables)
		$cobro_total_horas = 0; // Total de horas trabajadas para el cobro (cobrables)
		$cobro_total_honorario_hh = 0; // Valor total de las HH trabajados para el cobro
		$cobro_total_honorario_hh_estandar = 0; // Valor estandar de las HH trabajadas para el cobro
		$cobro_total_honorario_cobrable = 0; // Valor real que se va a cobrar (según forma de cobro), sin descuentos
		$cobro_total_gastos = 0; // Valor total de los gastos (egresos) del cobro
		$cobro_total_honorarios_hh_incluidos_retainer = 0; //Honorarios que dejan de cobrarse a HH pq están incluidos en retainer
		#$this->fields['id_moneda_monto'] es la moneda a la que se pone el monto, ej retainer por 100 USD aunque la tarifa este en dolares
		$cobro_monto_moneda_cobro = ($this->fields['monto_contrato'] * $cobro_moneda->moneda[$this->fields['id_moneda_monto']]['tipo_cambio']) / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'];

		//Decimales
		$moneda_del_cobro = new Moneda($this->sesion);
		$moneda_del_cobro->Load($this->fields['id_moneda']);
		$decimales = $moneda_del_cobro->fields['cifras_decimales'];

		if ($this->fields['forma_cobro'] == 'FLAT FEE' || $this->fields['forma_cobro'] == 'RETAINER' || $this->fields['forma_cobro'] == 'PROPORCIONAL') {
			$cobro_total_honorario_cobrable = $cobro_monto_moneda_cobro;
		}

		// Si es necesario calcular el impuesto por separado se actualiza el porcentaje de impuesto que se cobra.
		$contrato = new Contrato($this->sesion);
		if ($contrato->Load($this->fields['id_contrato']) && $emitir) {
			$contrato->Edit('notificado_monto_excedido_ult_cobro', '0');
			$contrato->Edit('notificado_hr_excedida_ult_cobro', '0');
			$contrato->Write();
		}

		if (!$mantener_porcentaje_impuesto) {
			if (( ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) ) && $contrato->fields['usa_impuesto_separado']) {
				$this->Edit('porcentaje_impuesto', (method_exists('Conf', 'GetConf') ? Conf::GetConf($this->sesion, 'ValorImpuesto') : Conf::ValorImpuesto()));
			} else {
				$this->Edit('porcentaje_impuesto', '0');
			}
			if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoPorGastos') ) || ( method_exists('Conf', 'UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) ) && $contrato->fields['usa_impuesto_gastos']) {
				$this->Edit('porcentaje_impuesto_gastos', (method_exists('Conf', 'GetConf') ? Conf::GetConf($this->sesion, 'ValorImpuestoGastos') : Conf::ValorImpuestoGastos()));
			} else {
				$this->Edit('porcentaje_impuesto_gastos', '0');
			}
		}
		$query = "SELECT SQL_CALC_FOUND_ROWS tramite.id_tramite,
                                   tramite.tarifa_tramite,
                                   tramite.id_moneda_tramite,
                                   tramite.fecha,
                                   tramite.codigo_asunto,
                                   tramite.id_tramite_tipo,
                                   tramite_tipo.glosa_tramite as glosa_tramite,
                                   tramite.id_moneda_tramite_individual,
                                   tramite.tarifa_tramite_individual
                               FROM tramite
                               JOIN tramite_tipo USING( id_tramite_tipo )
                               WHERE tramite.id_cobro = '" . $this->fields['id_cobro'] . "'
                               ORDER BY tramite.fecha ASC";
		if (!$mantener_porcentaje_impuesto) {
			$lista_tramites = new ListaTramites($this->sesion, '', $query);
		}

		for ($z = 0; $z < $lista_tramites->num; $z++) {
			$tramite = $lista_tramites->Get($z);

			if ($tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa'] == '') {
				$tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa'] = Funciones::TramiteTarifa($this->sesion, $tramite->fields['id_tramite_tipo'], $this->fields['id_moneda'], $tramite->fields['codigo_asunto']);

				$tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_defecto'] = Funciones::TramiteTarifaDefecto($this->sesion, $tramite->fields['id_tramite_tipo'], $this->fields['id_moneda']);

				$tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_estandar'] = Funciones::MejorTramiteTarifa($this->sesion, $tramite->fields['id_tramite_tipo'], $this->fields['id_moneda'], $this->fields['id_cobro']);
			}
			$tramite->Edit('id_moneda_tramite', $this->fields['id_moneda']);
			if ($tramite->fields['tarifa_tramite_individual'] > 0) {
				$valor_tramite = number_format($tramite->fields['tarifa_tramite_individual'] * ( $cobro_moneda->moneda[$tramite->fields['id_moneda_tramite_individual']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['id_moneda']]['tipo_cambio'] ), $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], '.', '');
			} else {
				$valor_tramite = $tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa'];
			}
			$tramite->Edit('tarifa_tramite', $valor_tramite);
			$tramite->Edit('tarifa_tramite_defecto', $tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_defecto']);
			$tramite->Edit('tarifa_tramite_estandar', $tarifa_tramite[$tramite->fields['glosa_tramite']]['tarifa_estandar']);

			if (!$tramite->Write()) {
				return 'Error, trámite #' . $tramite->fields['id_tramite'] . ' no se pudo guardar';
			}
		}

		/*
		 * En el caso de que en un cobro Retainer se definen los usuarios de los cuales
		 * las horas se descuentan del retainer con preferencia hay que modificar el orden
		 * con el cual se recorre la lista de los trabajos.
		 * Eso vamos a conseguir con la definición de las variables $select_retainer_usuarios y $orden_retainer_usuarios
		 */
		if (method_exists('Conf', 'GetConf')
			&& Conf::GetConf($this->sesion, 'RetainerUsuarios')
			&& $this->fields['retainer_usuarios'] != ""
			&& $this->fields['forma_cobro'] == 'RETAINER') {
			$select_retainer_usuarios = "IF( trabajo.id_usuario IN ( " . $this->fields['retainer_usuarios'] . " ), '1', '2' ) as incluir_en_retainer, ";
			$orden_retainer_usuarios = "incluir_en_retainer, ";
		} else {
			$select_retainer_usuarios = "";
			$orden_retainer_usuarios = "";
		}

		if ($this->fields['opc_ver_valor_hh_flat_fee']) {
			$dato_monto_cobrado = " ( trabajo.tarifa_hh * TIME_TO_SEC( trabajo.duracion_cobrada ) ) / 3600 ";
		} else {
			$dato_monto_cobrado = " trabajo.monto_cobrado ";
		}

		// Se seleccionan todos los trabajos del cobro, se incluye que sea cobrable ya que a los trabajos visibles
		// tambien se consideran dentro del cobro, tambien se incluye el valor del retainer del trabajo.
		$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada,
					trabajo.descripcion,
					trabajo.fecha,
					trabajo.id_usuario,
					$dato_monto_cobrado as monto_cobrado,
					trabajo.id_moneda as id_moneda_trabajo,
					trabajo.id_trabajo,
					trabajo.tarifa_hh,
					trabajo.cobrable,
					trabajo.visible,
					trabajo.codigo_asunto,
					$select_retainer_usuarios
					CONCAT_WS(' ', nombre, apellido1) as nombre_usuario
				FROM trabajo
					LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
				WHERE trabajo.id_cobro = '" . $this->fields['id_cobro'] . "'
				AND trabajo.id_tramite=0
				ORDER BY $orden_retainer_usuarios trabajo.fecha ASC";
		if (!$mantener_porcentaje_impuesto) {
			$lista_trabajos = new ListaTrabajos($this->sesion, '', $query);
		} else {
			$lista_trabajos->num = 0;
		}

		if ($this->fields['forma_cobro'] == 'ESCALONADA') {
			list($cobro_total_honorario_cobrable, $cobro_total_minutos) = $this->MontoHonorariosEscalonados($lista_trabajos);
		} else {
			for ($z = 0; $z < $lista_trabajos->num; $z++) {
				$trabajo = $lista_trabajos->Get($z);
				list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) : '0');
				$duracion_minutos = $h * 60 + $m;
				$id_usuario = $trabajo->fields['id_usuario'];

				//se inicializa el valor del retainer del trabajo
				$retainer_trabajo_minutos = 0;

				// Se obtiene la tarifa del profesional que hizo el trabajo (sólo si no se tiene todavía).
				// Si el config "GuardarTarifaAlIngresoDeHora" existe saca la tarifa del registro de tarifas
				// por trabajo, si no saca lo del contrato actual
				if (UtilesApp::GetConf($this->sesion, 'GuardarTarifaAlIngresoDeHora')) {
					// Según Tarifa del contrato
					$profesional[$id_usuario]['tarifa'] = Funciones::TrabajoTarifa($this->sesion, $trabajo->fields['id_trabajo'], $this->fields['id_moneda']);
					// Según Tarifa estándar del sistema
					$profesional[$id_usuario]['tarifa_defecto'] = Funciones::TarifaDefecto($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda']);
					// Según tarifa estándar de sistema y si no existe tarifa en moneda indicada buscar mejor tarifa en otra moneda
					$profesional[$id_usuario]['tarifa_hh_estandar'] = Funciones::MejorTarifa($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda'], $this->fields['id_cobro']);
				} else if ($profesional[$id_usuario]['tarifa'] == '') {
					$profesional[$id_usuario]['tarifa'] = Funciones::Tarifa($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda'], $trabajo->fields['codigo_asunto']);

					$profesional[$id_usuario]['tarifa_defecto'] = Funciones::TarifaDefecto($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda']);

					$profesional[$id_usuario]['tarifa_hh_estandar'] = Funciones::MejorTarifa($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda'], $this->fields['id_cobro']);
				}

				// Se calcula el valor del trabajo, según el tiempo trabajado y la tarifa
				if ($trabajo->fields['cobrable'] == '1') {
					$valor_trabajo = $duracion * $profesional[$id_usuario]['tarifa'];
					$valor_trabajo_estandar = $duracion * $profesional[$id_usuario]['tarifa_hh_estandar'];
				} else {
					$valor_trabajo = 0;
					$valor_trabajo_estandar = 0;
				}
				// Se suman los valores del trabajo a las variables del cobro
				$cobro_total_honorario_hh += $valor_trabajo;
				$cobro_total_honorario_hh_estandar += $valor_trabajo_estandar;

				if ($trabajo->fields['cobrable'] == '1') {
					$cobro_total_minutos += $duracion_minutos;
					$cobro_total_horas += $duracion;
				}

				// El valor a cobrar del trabajo dependerá de la forma de cobro
				switch ($this->fields['forma_cobro']) {
					case 'FLAT FEE':
						$valor_a_cobrar = 0;
						break;
					case 'PROPORCIONAL':
						// Se calculan después, pero necesitan los valores de los totales que se suman en este ciclo.
						continue;
						break;
					case 'RETAINER':
						if ($this->fields['retainer_horas'] != '') {
							if ($cobro_total_minutos < $this->fields['retainer_horas'] * 60) {
								$valor_a_cobrar = 0;
								//se agrega el valor del retainer en el trabajo para luego ser mostrado
								if ($trabajo->fields['cobrable'] == '1') {
									$retainer_trabajo_minutos = $duracion_minutos;
									$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo;
								}
							} else {
								$valor_a_cobrar = min($valor_trabajo, ($cobro_total_horas - $this->fields['retainer_horas']) * $profesional[$id_usuario]['tarifa']);
								//se agrega el valor del retainer en el trabajo para luego ser mostrado
								if ($trabajo->fields['cobrable'] == '1') {
									if ($cobro_total_minutos - ($this->fields['retainer_horas'] * 60) < $duracion_minutos) {
										$retainer_trabajo_minutos = $duracion_minutos - ($cobro_total_minutos - ($this->fields['retainer_horas'] * 60));
									} else {
										$retainer_trabajo_minutos = 0;
									}
									$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo - $valor_a_cobrar;
								}
							}
						} else {
							if ($cobro_total_honorario_hh < $this->fields['monto_contrato']) {
								$valor_a_cobrar = 0;
								$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo;
							} else {
								$valor_a_cobrar = min($valor_trabajo, $cobro_total_honorario_hh - $this->fields['monto_contrato']);
								$cobro_total_honorarios_hh_incluidos_retainer += $valor_trabajo - $valor_a_cobrar;
							}
						}
						break;
					default:
						$valor_a_cobrar = $valor_trabajo;
						break;
				}

				// Se suma el monto a cobrar
				$cobro_total_honorario_cobrable += $valor_a_cobrar;
				// Se guarda la información del cobro para este trabajo. se incluye minutos retainer en el trabajo
				$horas_retainer = floor(($retainer_trabajo_minutos) / 60);
				$minutos_retainer = sprintf("%02d", $retainer_trabajo_minutos % 60);
				$trabajo->Edit('id_moneda', $this->fields['id_moneda']);
				$trabajo->Edit('duracion_retainer', "$horas_retainer:$minutos_retainer:00");
				$trabajo->Edit('fecha_cobro', date('Y-m-d H:i:s'));
				$trabajo->Edit('tarifa_hh', $profesional[$id_usuario]['tarifa']);
				$trabajo->ActualizarTrabajoTarifa($this->fields['id_moneda'], $profesional[$id_usuario]['tarifa']);
				$trabajo->Edit('monto_cobrado', number_format($valor_a_cobrar, 6, '.', ''));
				$trabajo->Edit('costo_hh', $profesional[$id_usuario]['tarifa_defecto']);
				$trabajo->Edit('tarifa_hh_estandar', number_format($profesional[$id_usuario]['tarifa_hh_estandar'], $decimales, '.', ''));

				if (!$trabajo->Write(false)) {
					return 'Error, trabajo #' . $trabajo->fields['id_trabajo'] . ' no se pudo guardar';
				}
			} #End for cobros
		}

		if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
			for ($z = 0; $z < $lista_trabajos->num; ++$z) {
				$trabajo = $lista_trabajos->Get($z);
				list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) : '0') + ($s > 0 ? ($s / 3600) : '0');
				$duracion_minutos = $h * 60 + $m + $s / 60;

				$id_usuario=$trabajo->fields['id_usuario'];

				// Se obtiene la tarifa del profesional que hizo el trabajo (sólo si no se tiene todavía).
				if ($profesional[$id_usuario]['tarifa'] == '') {
					$profesional[$id_usuario]['tarifa'] = Funciones::Tarifa($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda'], $trabajo->fields['codigo_asunto']);

					$profesional[$id_usuario]['tarifa_defecto'] = Funciones::TarifaDefecto($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda']);

					$profesional[$id_usuario]['tarifa_hh_estandar'] = Funciones::MejorTarifa($this->sesion, $trabajo->fields['id_usuario'], $this->fields['id_moneda'], $this->fields['id_cobro']);
				}
				if ($trabajo->fields['cobrable'] == '0') {
					$valor_a_cobrar = 0;
					$retainer_trabajo_minutos = 0;
				} else if ($cobro_total_horas < $this->fields['retainer_horas']) {
					$valor_a_cobrar = 0;
					$retainer_trabajo_minutos = $duracion_minutos;
				} else {
					// Valor a cobrar proporcional a la fracción de horas del total asignadas a este trabajo.
					$retainer_trabajo_minutos = $this->fields['retainer_horas'] * 60 * $duracion_minutos / $cobro_total_minutos;
					$valor_a_cobrar = $profesional[$id_usuario]['tarifa'] * ($cobro_total_horas - $this->fields['retainer_horas']) * $duracion_minutos / $cobro_total_minutos;
				}

				// Se suma el monto a cobrar
				$cobro_total_honorario_cobrable += $valor_a_cobrar;
				// Se guarda la información del cobro para este trabajo. se incluye minutos y segundos retainer en el trabajo
				$horas_retainer = floor($retainer_trabajo_minutos / 60);
				$minutos_retainer = sprintf("%02d", $retainer_trabajo_minutos % 60);
				$segundor_retainer = sprintf("%02d", round(60 * ($retainer_trabajo_minutos - floor($retainer_trabajo_minutos))));
				$trabajo->Edit('id_moneda', $this->fields['id_moneda']);
				if ($segundor_retainer == 60) {
					$segundor_retainer = 0;
					++$minutos_retainer;
					if ($minutos_retainer == 60) {
						$minutos_retainer = 0;
						++$horas_retainer;
					}
				}
				$trabajo->Edit('duracion_retainer', "$horas_retainer:$minutos_retainer:$segundor_retainer");
				$trabajo->Edit('monto_cobrado', number_format($valor_a_cobrar, 6, '.', ''));
				$trabajo->Edit('fecha_cobro', date('Y-m-d H:i:s'));
				$trabajo->Edit('tarifa_hh', $profesional[$id_usuario]['tarifa']);
				$trabajo->Edit('costo_hh', $profesional[$id_usuario]['tarifa_defecto']);
				$trabajo->Edit('tarifa_hh_estandar', number_format($profesional[$id_usuario]['tarifa_hh_estandar'], $decimales, '.', ''));
				if (!$trabajo->Write(false)) {
					return 'Error, trabajo #' . $trabajo->fields['id_trabajo'] . ' no se pudo guardar';
				}
			}
		}

		$cobro_total_honorario_cobrable_original = $cobro_total_honorario_cobrable;
		if ($this->fields['monto_ajustado'] > 0) {
			$cobro_total_honorario_cobrable = $this->fields['monto_ajustado'];
			for ($z = 0; $z < $lista_trabajos->num; ++$z) {
				$trabajo = $lista_trabajos->Get($z);
				if ($cobro_total_honorario_cobrable_original > 0) {
					$factor = $cobro_total_honorario_cobrable / $cobro_total_honorario_cobrable_original;
				} else {
					$factor = 1;
				}
				$trabajo->ActualizarTrabajoTarifa($trabajo->fields['id_moneda'], number_format($trabajo->fields['tarifa_hh'] * $factor, 6, '.', ''));
				$trabajo->Edit('tarifa_hh', number_format($trabajo->fields['tarifa_hh'] * $factor, 6, '.', ''));
				list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) : '0');
				$monto_cobrado = number_format($trabajo->fields['tarifa_hh'] * $duracion, 6, '.', '');
				$trabajo->Edit('monto_cobrado', $monto_cobrado);
				$trabajo->Write();
			}
		}

		#GASTOS del Cobro
		if (!UtilesApp::GetConf($this->sesion, 'NuevoModuloGastos')) {
			$no_generado = '';
			if ($this->fields['id_gasto_generado']) {
				$no_generado = ' AND cta_corriente.id_movimiento != ' . $this->fields['id_gasto_generado'];
			}

			$query = "SELECT SQL_CALC_FOUND_ROWS cta_corriente.descripcion,
						cta_corriente.fecha,
						cta_corriente.id_moneda,
						cta_corriente.egreso,
						cta_corriente.monto_cobrable,
						cta_corriente.ingreso,
						cta_corriente.id_movimiento,
						cta_corriente.codigo_asunto
					FROM cta_corriente
						LEFT JOIN asunto USING(codigo_asunto)
					WHERE cta_corriente.id_cobro='" . $this->fields['id_cobro'] . "'
						AND (egreso > 0 OR ingreso > 0)
						AND cta_corriente.incluir_en_cobro = 'SI'
						$no_generado
					ORDER BY cta_corriente.fecha ASC";

			if (!$mantener_porcentaje_impuesto) {
				$lista_gastos = new ListaGastos($this->sesion, '', $query);
			} else {
				$lista_gastos->num = 0;
			}

			for ($v = 0; $v < $lista_gastos->num; $v++) {
				$gasto = $lista_gastos->Get($v);

				//cobro_total_gastos en moneda cobro
				if ($gasto->fields['egreso'] > 0) {
					$cobro_total_gastos_egreso += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				} elseif ($gasto->fields['ingreso'] > 0) {
					$cobro_total_gastos_provision += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'];
				}
				//cobro_base_gastos en moneda base
				if ($gasto->fields['egreso'] > 0) {
					$cobro_base_gastos_egreso += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $moneda_base['tipo_cambio']; #revisar 15-05-09
				} elseif ($gasto->fields['ingreso'] > 0) {
					$cobro_base_gastos_provision += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $moneda_base['tipo_cambio']; #revisar 15-05-09
				}


				if (!empty($gasto->fields['codigo_asunto']) && empty($codigo_asunto_cualquiera)) {
					$codigo_asunto_cualquiera = $gasto->fields['codigo_asunto'];
				} else if (!empty($trabajo->fields['codigo_asunto'])) {
					$codigo_asunto_cualquiera = $trabajo->fields['codigo_asunto'];
				} else if (empty($codigo_asunto_cualquiera)) {
					$codigo_asunto_cualquiera = $this->asuntos[0];
				}
			}
			$cobro_total_gastos = $cobro_total_gastos_egreso - $cobro_total_gastos_provision;
			$cobro_base_gastos = $cobro_base_gastos_egreso - $cobro_base_gastos_provision;


			/* Si las Provisiones superan al Gasto, se debe generar un Gasto por esa cantidad, de modo que total_gastos quede en 0, y se debe crear una provisión igual a ese gasto, para incluir en cobro futuro */
			if ($cobro_total_gastos < 0) {


				$moneda_total = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
				$moneda_total->Load($this->fields['opc_moneda_total'] > 0 ? $this->fields['opc_moneda_total'] : 1);
				$monto_provision_restante = number_format(0.00 - $cobro_total_gastos, $moneda_total->fields['cifras_decimales'], '.', '');


				if (UtilesApp::GetConf($this->sesion, 'NuevoMetodoGastoProvision')) {
					/* require('PhpConsole.php');
					  PhpConsole::start(true, true, dirname(__FILE__)); */
					//En vez de generar un gasto ficticio, divido un gasto en dos.
					//Decido cual es la provision que voy a dividir
					$queryprovision = "select id_movimiento from cta_corriente WHERE cta_corriente.id_cobro='" . $this->fields['id_cobro'] . "'     and ingreso is not null and egreso is null order by ingreso desc limit 0,1";
					//debug($queryprovision);
					$resultprovision = mysql_query($queryprovision, $this->sesion->dbh);

					list($id_provision_objetivo) = mysql_fetch_array($resultprovision, $this->sesion->dbh);

					$provision_original = new Gasto($this->sesion);
					$provision_original->Load($id_provision_objetivo);


					$provision_original_ingreso = $provision_original->fields['ingreso'];
					$provision_original_cobrable = $provision_original->fields['monto_cobrable'];
					$provision_original->Edit('ingreso', $provision_original_ingreso - $monto_provision_restante);
					$provision_original->Edit('monto_cobrable', $provision_original_cobrable - $monto_provision_restante);


					//debug($provision_original);
					$cobro_total_gastos = $cobro_total_gastos_egreso;
					$cobro_base_gastos = $cobro_base_gastos_egreso;
				} else {
					//METODO VIEJO: SE NETEA CON DOS MOVIMIENTOS IMAGINARIOS
					$cobro_total_gastos = 0;
					$cobro_base_gastos = 0;
					$gas = new Gasto($this->sesion);
					if ($this->fields['id_gasto_generado']) {
						$gas->Load($this->fields['id_gasto_generado']);
					}

					$gas->Edit('id_moneda', $this->fields['opc_moneda_total']);
					$gas->Edit('codigo_asunto', $codigo_asunto_cualquiera);
					$gas->Edit('egreso', $monto_provision_restante);
					$gas->Edit('monto_cobrable', $monto_provision_restante);
					$gas->Edit('ingreso', 0);
					$gas->Edit('id_cobro', $this->fields['id_cobro']);
					$gas->Edit('codigo_cliente', $this->fields['codigo_cliente']);
					$gas->Edit('descripcion', __("Saldo aprovisionado restante tras Cobro #") . $this->fields['id_cobro']);
					$gas->Edit('incluir_en_cobro', 'SI');
					$gas->Edit('fecha', date('Y-m-d 00:00:00'));
					$gas->Write();
					$this->Edit('id_gasto_generado', $gas->fields['id_movimiento']);
				}



				$provision = new Gasto($this->sesion);
				if ($this->fields['id_provision_generada']) {
					$provision->Load($this->fields['id_provision_generada']);
				}

				$provision->Edit('id_moneda', $this->fields['opc_moneda_total']);
				$provision->Edit('codigo_asunto', $codigo_asunto_cualquiera);
				$provision->Edit('egreso', 0);
				$provision->Edit('ingreso', $monto_provision_restante);
				$provision->Edit('monto_cobrable', $monto_provision_restante);

				//debug('Sobran '.$monto_provision_restante);

				$provision->Edit('id_cobro', 'NULL');
				$provision->Edit('codigo_cliente', $this->fields['codigo_cliente']);
				$provision->Edit('descripcion', __("Saldo aprovisionado restante tras Cobro #") . $this->fields['id_cobro']);
				$provision->Edit('incluir_en_cobro', 'SI');
				$provision->Edit('fecha', date('Y-m-d 00:00:00'));
				if (!UtilesApp::GetConf($this->sesion, 'NuevoMetodoGastoProvision')) {
					$provision->Write();
				}
				//debug(print_r($provision));

				$this->Edit('id_provision_generada', $provision->fields['id_movimiento']);
			} else {
				$gas = new Gasto($this->sesion);
				if ($this->fields['id_gasto_generado']) {
					if ($gas->Load($this->fields['id_gasto_generado'])) {
						$gas->Eliminar();
					}
				}
				$provision = new Gasto($this->sesion);
				if ($this->fields['id_provision_generada']) {
					if ($provision->Load($this->fields['id_provision_generada'])) {
						$provision->Eliminar();
					}
				}
			}
		}
		#Obtenemos el saldo_final de GASTOS diferencia de: saldo_inicial - (la suma de los gastos-provisiones de este cobro)
		#En moneda OPC opciones ver
		if (!$mantener_porcentaje_impuesto) {
			$saldo_final_gastos = $this->SaldoFinalCuentaCorriente();
		}

		#Carga del cliente del cobro
		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($this->fields['codigo_cliente']);

		#Calculo de la cuenta corriente del cliente para el cobro
		if ($cliente->Loaded() && !$mantener_porcentaje_impuesto) {
			$saldo_cta_corriente = $cliente->TotalCuentaCorriente();
		}

		if (!$moneda_del_cobro) {
			$moneda_del_cobro = new Moneda($this->sesion);
			$moneda_del_cobro->Load($this->fields['id_moneda']);
		}

		#DESCUENTOS
		if ($this->fields['tipo_descuento'] == 'PORCENTAJE') {
			$cobro_descuento = ($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable) * $this->fields['porcentaje_descuento'] / 100;
			$cobro_total = ($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable) - $cobro_descuento;
			$cobro_total = round($cobro_total, $moneda_del_cobro->fields['cifras_decimales']);
			$cobro_honorarios_menos_descuento = $cobro_total_honorario_cobrable - $cobro_descuento;
			//FFF: no redondear descuento, trae problemas con la moneda UF -> CLP
			//$this->Edit('descuento', number_format($cobro_descuento, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], ".", ""));
			$this->Edit('descuento', number_format($cobro_descuento, 6, ".", ""));
		} else {
			$cobro_honorarios_menos_descuento = $cobro_total_honorario_cobrable - ($this->fields['descuento']);
			$cobro_total = ($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable) - ($this->fields['descuento']);
			$cobro_total = round($cobro_total, $moneda_del_cobro->fields['cifras_decimales']);
		}
		//Valido CAP
		if ($this->fields['forma_cobro'] == 'CAP') {
			$cap_descuento = 0;
			$contrato = new Contrato($this->sesion);
			$contrato->Load($this->fields['id_contrato']);
			$sumatoria_cobros = $this->TotalCobrosCap('', $this->fields['id_moneda']) + $cobro_honorarios_menos_descuento;

			if ($sumatoria_cobros > $cobro_monto_moneda_cobro) { //Es decir que lo cobrado ha superado el valor del cap
				$cap_descuento = min($sumatoria_cobros - $cobro_monto_moneda_cobro, + $cobro_honorarios_menos_descuento);
			}
		}
		if ($cap_descuento > 0) {
			$cap_descuento = round($cap_descuento, $moneda_del_cobro->fields['cifras_decimales']);
			$cobro_total = $cobro_total - $cap_descuento;
			$cobro_descuento = $this->fields['descuento'] + $cap_descuento;
			$this->Edit('descuento', number_format($cobro_descuento, 6, ".", ""));
		}

		// Si es necesario calcular el impuesto por separado
		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);
		if (( ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) ) && $contrato->fields['usa_impuesto_separado']) {
			$cobro_total *= 1 + $this->fields['porcentaje_impuesto'] / 100.0;
		}

		// Se guarda la información del cobro

		$this->Edit('monto_original', number_format($cobro_total_honorario_cobrable_original, 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('monto_subtotal', number_format($this->CalculaMontoTramites($this) + $cobro_total_honorario_cobrable, 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('monto', number_format($cobro_total, 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('monto_trabajos', number_format($cobro_total_honorario_cobrable, 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('monto_tramites', number_format($this->CalculaMontoTramites($this), 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('monto_thh', number_format($cobro_total_honorario_hh, 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('monto_thh_estandar', number_format($cobro_total_honorario_hh_estandar, 6/* $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'] */, ".", ""));
		$this->Edit('total_minutos', $cobro_total_minutos);


		if (UtilesApp::GetConf($this->sesion, 'NuevoMetodoGastoProvision')) {
			$this->Edit('saldo_final_gastos', number_format($saldo_final_gastos_egreso, 6, ".", ""));
			$gastos_cobro = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro'], array('listar_detalle'), true);
		} else {
			$this->Edit('saldo_final_gastos', number_format($saldo_final_gastos, 6, ".", ""));
			$gastos_cobro = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
		}

		$this->Edit('subtotal_gastos', number_format($gastos_cobro['gasto_total'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], ".", ""));
		$this->Edit('impuesto_gastos', number_format($gastos_cobro['gasto_impuesto'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], ".", ""));
		$this->Edit('monto_gastos', number_format($gastos_cobro['gasto_total_con_impuesto'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], ".", ""));

		$this->Edit('impuesto', number_format(($this->fields['monto_subtotal'] - $this->fields['descuento']) * $this->fields['porcentaje_impuesto'] / 100, $cobro_moneda->moneda[$this->fields['id_moneda']]['cifras_decimales'], ".", ""));

		$this->Edit('saldo_cta_corriente', $saldo_cta_corriente);

		// Guardamos datos de la moneda base
		$this->Edit('id_moneda_base', $moneda_base['id_moneda']);
		$this->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']); #revisar 15-05-2009

		if ($this->Write()) {
			if (UtilesApp::GetConf($this->sesion, 'SeEstaCobrandoEspecial')) {
				$se_esta_cobrando = "Honorarios Profesionales\n";
				$se_esta_cobrando .= "Periodo Comprendido: \n";

				if ($this->fields['fecha_ini'] != '0000-00-00' && !empty($this->fields['fecha_ini'])) {
					$se_esta_cobrando_fecha_ini = Utiles::sql2date($this->fields['fecha_ini']);
					$se_esta_cobrando .=__('Desde') . ': ' . $se_esta_cobrando_fecha_ini . "\n";
				}
				if ($this->fields['fecha_fin'] != '0000-00-00' && !empty($this->fields['fecha_fin'])) {
					$se_esta_cobrando_fecha_fin = Utiles::sql2date($this->fields['fecha_fin']);
					$se_esta_cobrando .=__('Hasta') . ': ' . $se_esta_cobrando_fecha_fin . "\n";
				}
				$se_esta_cobrando .= "Tarifa Cobrada: ";
				$se_esta_cobrando .= $cobro_moneda->moneda[$this->fields['id_moneda']]['simbolo'] . " ";
				$se_esta_cobrando .= $this->fields['monto'];

				$this->Edit('se_esta_cobrando', $se_esta_cobrando);
				$this->Write();
			}
			if ($emitir) {
				if ($provision && $provision_original && UtilesApp::GetConf($this->sesion, 'NuevoMetodoGastoProvision')) {

					if ($provision_original) {
						$provision_original->Write();
					}


					if ($provision) {
						$provision->Write();
					}
					//debug('Genero provision y provision original');
					$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro'], array(), 0, true, true);
					$x_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro'], array('listar_detalle'), true);
				} else {
					$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
					$x_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
				}

				if ($provision_original) {
					$adelanto = new Documento($this->sesion);
					$adelanto->Edit('id_tipo_documento', '0');
					$adelanto->Edit('codigo_cliente', $this->fields['codigo_cliente']);
					$adelanto->Edit('id_contrato', $this->fields['id_contrato']);
					$adelanto->Edit('glosa_documento', __('Adelanto Provisión N°') . ' ' . $provision_original->fields['id_movimiento'] . ' (' . $provision_original->fields['descripcion'] . ')');
					$adelanto->Edit('id_moneda', $this->fields['opc_moneda_total']);
					$adelanto->Edit('id_moneda_base', $this->fields['id_moneda_base']);
					$adelanto->Edit('monto', -1 * $provision_original->fields['monto_cobrable']);
					$adelanto->Edit('saldo_pago', -1 * $provision_original->fields['monto_cobrable']);
					$adelanto->Edit('gastos_pagados', 'NO');
					$adelanto->Edit('honorarios_pagados', 'NO');
					$adelanto->Edit('monto_base', -1 * $provision_original->fields['monto_cobrable']);
					$adelanto->Edit('numero_doc', $provision_original->fields['numero_documento']);
					$adelanto->Edit('pago_gastos', 1);
					$adelanto->Edit('pago_honorarios', 0);
					$adelanto->Edit('es_adelanto', 1);
					$adelanto->Edit('fecha', date('Y-m-d'));
					$adelanto->Write();
					$provision_original->Delete();
				}

				//Documentos
				$documento = new Documento($this->sesion, '', '');
				$documento->Edit('id_tipo_documento', '2');
				$documento->Edit('codigo_cliente', $this->fields['codigo_cliente']);
				$documento->Edit('id_contrato', $this->fields['id_contrato']);
				$documento->Edit('glosa_documento', __('Cobro N°') . ' ' . $this->fields['id_cobro']);
				$documento->Edit('id_cobro', $this->fields['id_cobro']);
				$documento->Edit('id_moneda', $this->fields['opc_moneda_total']);
				$documento->Edit('id_moneda_base', $this->fields['id_moneda_base']);
				//Se revisa pagos
				if ($x_resultados['monto_gastos'][$this->fields['opc_moneda_total']] > 0) {
					$this->Edit('gastos_pagados', 'NO');
					$documento->Edit('gastos_pagados', 'NO');
				} else {
					$this->Edit('gastos_pagados', 'SI');
					$documento->Edit('gastos_pagados', 'SI');
				}
				if ($x_resultados['monto_honorarios'][$this->fields['opc_moneda_total']] > 0) {
					$this->Edit('honorarios_pagados', 'NO');
					$documento->Edit('honorarios_pagados', 'NO');
				} else {
					$this->Edit('honorarios_pagados', 'SI');
					$documento->Edit('honorarios_pagados', 'SI');
				}
				$documento->Edit('impuesto', $x_resultados['monto_iva'][$this->fields['opc_moneda_total']]);
				$documento->Edit('subtotal_honorarios', $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']]);
				$documento->Edit('subtotal_gastos', $x_gastos['subtotal_gastos_con_impuestos'] ? number_format($x_gastos['subtotal_gastos_con_impuestos'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '') : '0');
				$documento->Edit('subtotal_gastos_sin_impuesto', $x_gastos['subtotal_gastos_sin_impuestos'] ? number_format($x_gastos['subtotal_gastos_sin_impuestos'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '') : '0');
				$documento->Edit('descuento_honorarios', $x_resultados['descuento'][$this->fields['opc_moneda_total']]);
				$documento->Edit('subtotal_sin_descuento', (string) number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']] - $x_resultados['descuento'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', ''));
				$documento->Edit('honorarios', $x_resultados['monto'][$this->fields['opc_moneda_total']]);
				$documento->Edit('saldo_honorarios', $x_resultados['monto'][$this->fields['opc_moneda_total']]);
				$documento->Edit('monto', $x_resultados['monto_cobro_original_con_iva'][$this->fields['opc_moneda_total']]);
				if ($this->fields['forma_cobro'] == 'FLAT FEE') {
					$documento->Edit('monto_trabajos', number_format($x_resultados['monto_contrato'][$this->fields['opc_moneda_total']], $decimales, ".", ""));
				} else {
					$documento->Edit('monto_trabajos', number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']], $decimales, ".", ""));
				}
				$documento->Edit('monto_tramites', number_format($x_resultados['monto_tramites'][$this->fields['opc_moneda_total']], $decimales, ".", ""));
				$documento->Edit('gastos', $x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
				$documento->Edit('saldo_gastos', $x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
				$documento->Edit('monto_base', $x_resultados['monto_cobro_original_con_iva'][$this->fields['id_moneda_base']]);
				$documento->Edit('fecha', date('Y-m-d'));

				if ($documento->Write()) {
					$documento->BorrarDocumentoMoneda();
					$query_documento_moneda = "REPLACE INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
					SELECT  :iddocumento,
						cobro_moneda.id_moneda,
						cobro_moneda.tipo_cambio
					FROM cobro
					JOIN cobro_moneda ON cobro.id_cobro = cobro_moneda.id_cobro
					WHERE cobro.id_cobro =:idcobro";

					//	$resp = mysql_query($query_documento_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_documento_moneda, __FILE__, __LINE__, $this->sesion->dbh);

					try {
						$this->sesion->pdodbh->beginTransaction();
						$logstatement = $this->sesion->pdodbh->prepare($query_documento_moneda);
						$logstatement->bindParam(':idcobro', $this->fields['id_cobro'], PDO::PARAM_INT);
						$logstatement->bindParam(':iddocumento', $documento->fields['id_documento'], PDO::PARAM_INT);
						$logstatement->execute();
						$this->sesion->pdodbh->commit();
					} catch (PDOException $e) {
						if ($this->sesion->usuario->TienePermiso('SADM')) {
							$Slim = Slim::getInstance('default', true);
							$arrayPDOException = array('File' => $e->getFile(), 'Line' => $e->getLine(), 'Mensaje' => $e->getMessage(), 'Query' => $query_documento_moneda, 'Trace' => json_encode($e->getTrace()), 'Parametros' => json_encode($logstatement));
							$Slim->view()->setData($arrayPDOException);
							$Slim->applyHook('hook_error_sql');
						}
						Utiles::errorSQL($query_documento_moneda, "", "", NULL, "", $e);
					}





					$query_factura = " UPDATE factura_cobro SET id_documento = '" . $documento->fields['id_documento'] . "' WHERE id_cobro = '" . $this->fields['id_cobro'] . "' AND id_documento IS NULL";
					$resp = mysql_query($query_factura, $this->sesion->dbh) or Utiles::errorSQL($query_factura, __FILE__, __LINE__, $this->sesion->dbh);
				}
			}
		} else {
			return __('Error no se pudo guardar ') . __('cobro') . ' # ' . $this->fields['id_cobro'];
		}
		if (!$this->Write()) {
			return __('Error no se pudo guardar ') . __('cobro') . ' # ' . $this->fields['id_cobro'];
		}
		return '';
	}

	/* Función One-shot que vuelve a crear un documento como cuando fue emitido */

	function ReiniciarDocumento() {
		$x_resultados = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro'], array(), 0, false);
		$x_gastos = UtilesApp::ProcesaGastosCobro($this->sesion, $this->fields['id_cobro']);
		//PENDIENTE: al final hay $documento->Edit('monto_base',number_format(($cobro_base_honorarios+$cobro_base_gastos),$decimales_base,".",""));
		//cobro_base_honorarios y cobro_base_gastos se fue calculando 1 a 1 transformando a base.
		//Tipo de cambios del cobro de (cobro_moneda)
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->Load($this->fields['id_cobro']);

		//Documentos
		$documento = new Documento($this->sesion, '', '');
		$documento->LoadByCobro($this->fields['id_cobro']);

		$documento->Edit('id_tipo_documento', '2');
		$documento->Edit('codigo_cliente', $this->fields['codigo_cliente']);
		$documento->Edit('glosa_documento', __('Cobro N°') . ' ' . $this->fields['id_cobro']);
		$documento->Edit('id_cobro', $this->fields['id_cobro']);
		$documento->Edit('id_moneda', $this->fields['opc_moneda_total']);
		$documento->Edit('id_moneda_base', $this->fields['id_moneda_base']);

		$contrato = new Contrato($this->sesion);
		$contrato->Load($this->fields['id_contrato']);

		//Se revisa pagos
		if ($x_resultados['monto_gastos'][$this->fields['opc_moneda_total']] > 0) {
			$this->Edit('gastos_pagados', 'NO');
			$documento->Edit('gastos_pagados', 'NO');
		} else {
			$this->Edit('gastos_pagados', 'SI');
			$documento->Edit('gastos_pagados', 'SI');
		}
		if ($x_resultados['monto_honorarios'][$this->fields['opc_moneda_total']] > 0) {
			$this->Edit('honorarios_pagados', 'NO');
			$documento->Edit('honorarios_pagados', 'NO');
		} else {
			$this->Edit('honorarios_pagados', 'SI');
			$documento->Edit('honorarios_pagados', 'SI');
		}
		$documento->Edit('impuesto', $x_resultados['monto_iva'][$this->fields['opc_moneda_total']]);
		$documento->Edit('subtotal_honorarios', $x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']]);
		$documento->Edit('subtotal_gastos', $x_gastos['subtotal_gastos_con_impuestos'] ? number_format($x_gastos['subtotal_gastos_con_impuestos'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '') : '0');
		$documento->Edit('subtotal_gastos_sin_impuesto', $x_gastos['subtotal_gastos_sin_impuestos'] ? number_format($x_gastos['subtotal_gastos_sin_impuestos'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', '') : '0');
		$documento->Edit('descuento_honorarios', $x_resultados['descuento'][$this->fields['opc_moneda_total']]);
		$documento->Edit('subtotal_sin_descuento', (string) number_format($x_resultados['monto_subtotal'][$this->fields['opc_moneda_total']] - $x_resultados['descuento'][$this->fields['opc_moneda_total']], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', ''));
		$documento->Edit('honorarios', $x_resultados['monto'][$this->fields['opc_moneda_total']]);
		$documento->Edit('saldo_honorarios', $x_resultados['monto'][$this->fields['opc_moneda_total']]);
		$documento->Edit('monto', $x_resultados['monto_cobro_original_con_iva'][$this->fields['opc_moneda_total']]);
		if ($this->fields['forma_cobro'] == 'FLAT FEE') {
			$documento->Edit('monto_trabajos', number_format($x_resultados['monto_contrato'][$this->fields['opc_moneda_total']], $decimales, ".", ""));
		} else {
			$documento->Edit('monto_trabajos', number_format($x_resultados['monto_trabajos'][$this->fields['opc_moneda_total']], $decimales, ".", ""));
		}
		$documento->Edit('monto_tramites', number_format($x_resultados['monto_tramites'][$this->fields['opc_moneda_total']], $decimales, ".", ""));
		$documento->Edit('gastos', $x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
		$documento->Edit('saldo_gastos', $x_resultados['monto_gastos'][$this->fields['opc_moneda_total']]);
		$documento->Edit('monto_base', $x_resultados['monto_cobro_original_con_iva'][$this->fields['id_moneda_base']]);
		$documento->Edit('fecha', date('Y-m-d'));
		if (!$documento->Loaded()) {
			$documento->Write();
		}
		$query = "SELECT nd.id_neteo_documento, dp.id_moneda
			FROM neteo_documento nd JOIN documento dp ON nd.id_documento_pago = dp.id_documento
			WHERE nd.id_documento_cobro = '{$documento->fields['id_documento']}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, $this->sesion->dbh);

		$saldo_honorarios = $documento->fields['honorarios'];
		$saldo_gastos = $documento->fields['gastos'];

		while (list($id_neteo, $id_moneda) = mysql_fetch_array($resp)) {
			$neteo = new NeteoDocumento($this->sesion);
			$neteo->Load($id_neteo);

			$neteo->Edit('valor_cobro_honorarios', number_format($neteo->fields['valor_pago_honorarios'] * $cobro_moneda->moneda[$id_moneda]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', ''));
			$neteo->Edit('valor_cobro_gastos', number_format($neteo->fields['valor_cobro_gastos'] * $cobro_moneda->moneda[$id_moneda]['tipo_cambio'] / $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['tipo_cambio'], $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', ''));

			$saldo_honorarios -= $neteo->fields['valor_cobro_honorarios'];
			$saldo_gastos -= $neteo->fields['valor_cobro_gastos'];

			$neteo->Write();
		}

		$documento->Edit('saldo_honorarios', number_format($saldo_honorarios, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', ''));
		$documento->Edit('saldo_gastos', number_format($saldo_gastos, $cobro_moneda->moneda[$this->fields['opc_moneda_total']]['cifras_decimales'], '.', ''));

		if ($documento->Write()) {
			$documento->BorrarDocumentoMoneda();
			$query_documento_moneda = "REPLACE INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
				SELECT '" . $documento->fields['id_documento'] . "',
					cobro_moneda.id_moneda,
					cobro_moneda.tipo_cambio
				FROM cobro
				JOIN cobro_moneda ON cobro.id_cobro = cobro_moneda.id_cobro
				WHERE cobro.id_cobro = '" . $this->fields['id_cobro'] . "'";
			$resp = mysql_query($query_documento_moneda, $this->sesion->dbh) or Utiles::errorSQL($query_documento_moneda, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

	/*
	  Suma de CAP para todos los cobros creados de acuerdo a un contrato
	  return SUM()
	 */

	function TotalCobrosCap($id_contrato = '', $id_moneda_cobros_cap = 'contrato.id_moneda_monto') {
		if (!$id_contrato) {
			$id_contrato = $this->fields['id_contrato'];
		}
		$contrato = new Contrato($this->sesion);
		$contrato->Load($id_contrato);
		if ($contrato->fields['forma_cobro'] <> 'CAP') {
			return 0;
		}


		//if($this->fields['id_cobro'])
		//$where_cobro = "AND cobro.id_cobro!=".$this->fields['id_cobro'];
		//else
		$where_cobro = '';

		$query = "SELECT (((cobro.monto_trabajos-cobro.descuento)*cobro.tipo_cambio_moneda)/cobro_moneda.tipo_cambio) AS monto_cap
							FROM cobro
							JOIN contrato ON cobro.id_contrato = contrato.id_contrato
							JOIN cobro_moneda ON cobro_moneda.id_moneda = $id_moneda_cobros_cap
							WHERE
							cobro.id_contrato = $id_contrato
							AND cobro.id_cobro = cobro_moneda.id_cobro
							AND cobro.forma_cobro = 'CAP' AND cobro.estado != 'CREADO' AND cobro.estado != 'EN REVISION'
							AND contrato.fecha_inicio_cap <= cobro.fecha_emision
							$where_cobro
							GROUP BY cobro.id_cobro";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$monto_total_cap = 0;
		while (list($monto_cap) = mysql_fetch_array($resp)) {
			$monto_total_cap += $monto_cap;
		}
		return $monto_total_cap;
	}

	/*
	  Retorna total de Horas del cobro
	  parámetro $id_cobro
	  retorna $total_horas_cobro
	 */

	function TotalHorasCobro($id_cobro) {
		$total_horas_cobro = 0;
		if ($id_cobro > 0) {
			$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 AS total_horas_cobro
									FROM trabajo AS t2
									LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
									WHERE cobro.id_cobro = $id_cobro AND t2.cobrable=1 AND t2.id_tramite=0";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($total_horas_cobro) = mysql_fetch_array($resp);
		}

		return $total_horas_cobro;
	}

	/*
	  Asocia los trabajos al cobro que se está creando
	  parametros fecha_ini; fecha_fin; id_contrato
	 */

	function PrepararCobro($fecha_ini = '0000-00-00', $fecha_fin, $id_contrato, $emitir_obligatoriamente = false, $id_proceso, $monto = '', $id_cobro_pendiente = '', $con_gastos = false, $solo_gastos = false, $incluye_gastos = true, $incluye_honorarios = true, $cobro_programado = false) {
		$incluye_gastos = empty($incluye_gastos) ? '0' : '1';
		$incluye_honorarios = empty($incluye_honorarios) ? '0' : '1';

		$contrato = new Contrato($this->sesion, '', '', 'contrato', 'id_contrato');
		if ($contrato->Load($id_contrato)) {
			#Se elimina el borrador actual si es que existe
			$contrato->EliminarBorrador($incluye_gastos, $incluye_honorarios);

			$hito = 0;
			if (!empty($id_cobro_pendiente)) {
				$query = "SELECT fecha_cobro, monto_estimado, hito FROM cobro_pendiente WHERE id_cobro_pendiente='$id_cobro_pendiente'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($fecha_hito, $monto_hito, $hito) = mysql_fetch_array($resp);
				if ($hito) {
					$emitir_obligatoriamente = true;
				} else {
					$fecha_fin = $fecha_hito;
				}
			}

			//si es obligatorio, incluye+hay honorarios, o incluye+hay gastos, se genera el cobro
			$genera = $emitir_obligatoriamente;
			if (!$genera) {
				$wip = $contrato->ProximoCobroEstimado($fecha_ini, $fecha_fin, $contrato->fields['id_contrato']);

				if (!empty($incluye_honorarios)) {
					if ($wip[0] > 0 || $contrato->fields['forma_cobro'] != 'TASA' && $contrato->fields['forma_cobro'] != 'CAP') {
						$genera = true;
					}
					if ($wip[1] > 0) { //si tiene trámites
						$genera = true;
					}
				}
				if (!empty($incluye_gastos) || $con_gastos) {
					if ($wip[3] > 0) {
						$genera = true;
					}
				}
			}

			if ($genera) {
				$moneda_base = Utiles::MonedaBase($this->sesion);
				$moneda = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
				$moneda->Load($contrato->fields['id_moneda']);

				if (( ( ( method_exists('Conf', 'LoginDesdeSitio') && Conf::LoginDesdeSitio() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'LoginDesdeSitio') ) ) && !$this->sesion->usuario->fields['id_usuario']) || !$this->sesion->usuario->fields['id_usuario']) {
					if (( method_exists('Conf', 'TieneTablaVisitante') && Conf::TieneTablaVisitante() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'TieneTablaVisitante') )) {
						$query = "SELECT id_usuario FROM usuario WHERE id_visitante = 0 ORDER BY id_usuario LIMIT 1";
					} else {
						$query = "SELECT id_usuario FROM usuario ORDER BY id_usuario LIMIT 1";
					}
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($id_usuario_cobro) = mysql_fetch_array($resp);

					$this->Edit('id_usuario', $id_usuario_cobro);
				} else {
					$this->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
				}
				$this->Edit('codigo_cliente', $contrato->fields['codigo_cliente']);
				$this->Edit('id_contrato', $contrato->fields['id_contrato']);
				$this->Edit('id_moneda', $contrato->fields['id_moneda']);
				$this->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
				$this->Edit('forma_cobro', $hito ? 'FLAT FEE' : $contrato->fields['forma_cobro']);

				// Pasar configuración de escalonadas ...
				$this->Edit('esc1_tiempo', $contrato->fields['esc1_tiempo']);
				$this->Edit('esc1_id_tarifa', $contrato->fields['esc1_id_tarifa']);
				$this->Edit('esc1_monto', $contrato->fields['esc1_monto']);
				$this->Edit('esc1_id_moneda', $contrato->fields['esc1_id_moneda']);
				$this->Edit('esc1_descuento', $contrato->fields['esc1_descuento']);

				$this->Edit('esc2_tiempo', $contrato->fields['esc2_tiempo']);
				$this->Edit('esc2_id_tarifa', $contrato->fields['esc2_id_tarifa']);
				$this->Edit('esc2_monto', $contrato->fields['esc2_monto']);
				$this->Edit('esc2_id_moneda', $contrato->fields['esc2_id_moneda']);
				$this->Edit('esc2_descuento', $contrato->fields['esc2_descuento']);

				$this->Edit('esc3_tiempo', $contrato->fields['esc3_tiempo']);
				$this->Edit('esc3_id_tarifa', $contrato->fields['esc3_id_tarifa']);
				$this->Edit('esc3_monto', $contrato->fields['esc3_monto']);
				$this->Edit('esc3_id_moneda', $contrato->fields['esc3_id_moneda']);
				$this->Edit('esc3_descuento', $contrato->fields['esc3_descuento']);

				$this->Edit('esc4_tiempo', $contrato->fields['esc4_tiempo']);
				$this->Edit('esc4_id_tarifa', $contrato->fields['esc4_id_tarifa']);
				$this->Edit('esc4_monto', $contrato->fields['esc4_monto']);
				$this->Edit('esc4_id_moneda', $contrato->fields['esc4_id_moneda']);
				$this->Edit('esc4_descuento', $contrato->fields['esc4_descuento']);

				//este es el monto fijo, pero si no se inclyen honorarios no va
				$monto = empty($monto) ? $contrato->fields['monto'] : $monto;
				if (empty($incluye_honorarios)) {
					$monto = '0';
				}
				if ($hito) {
					$monto = $monto_hito;
				}
				$this->Edit('monto_contrato', $monto);

				$this->Edit('retainer_horas', $contrato->fields['retainer_horas']);
				$this->Edit('retainer_usuarios', $contrato->fields['retainer_usuarios']);
				#Opciones
				$this->Edit('id_carta', $contrato->fields['id_carta']);
				$this->Edit('id_formato', $contrato->fields['id_formato']);
				$this->Edit("opc_ver_modalidad", $contrato->fields['opc_ver_modalidad']);
				$this->Edit("opc_ver_profesional", $contrato->fields['opc_ver_profesional']);
				$this->Edit("opc_ver_gastos", $contrato->fields['opc_ver_gastos']);
				$this->Edit("opc_ver_concepto_gastos", $contrato->fields['opc_ver_concepto_gastos']);
				$this->Edit("opc_ver_morosidad", $contrato->fields['opc_ver_morosidad']);
				$this->Edit("opc_ver_resumen_cobro", $contrato->fields['opc_ver_resumen_cobro']);
				$this->Edit("opc_ver_descuento", $contrato->fields['opc_ver_descuento']);
				$this->Edit("opc_ver_tipo_cambio", $contrato->fields['opc_ver_tipo_cambio']);
				$this->Edit("opc_ver_solicitante", $contrato->fields['opc_ver_solicitante']);
				$this->Edit("opc_ver_numpag", $contrato->fields['opc_ver_numpag']);
				$this->Edit("opc_ver_carta", $contrato->fields['opc_ver_carta']);
				$this->Edit("opc_papel", $contrato->fields['opc_papel']);
				$this->Edit("opc_restar_retainer", $contrato->fields['opc_restar_retainer']);
				$this->Edit("opc_ver_detalle_retainer", $contrato->fields['opc_ver_detalle_retainer']);
				$this->Edit("opc_ver_valor_hh_flat_fee", $contrato->fields['opc_ver_valor_hh_flat_fee']);
				$this->Edit("opc_ver_detalles_por_hora_iniciales", $contrato->fields['opc_ver_detalles_por_hora_iniciales']);
				$this->Edit("opc_ver_detalles_por_hora_categoria", $contrato->fields['opc_ver_detalles_por_hora_categoria']);
				$this->Edit("opc_ver_detalles_por_hora_tarifa", $contrato->fields['opc_ver_detalles_por_hora_tarifa']);
				$this->Edit("opc_ver_detalles_por_hora_importe", $contrato->fields['opc_ver_detalles_por_hora_importe']);
				$this->Edit("opc_ver_detalles_por_hora", $contrato->fields['opc_ver_detalles_por_hora']);
				$this->Edit("opc_ver_profesional_iniciales", $contrato->fields['opc_ver_profesional_iniciales']);
				$this->Edit("opc_ver_profesional_categoria", $contrato->fields['opc_ver_profesional_categoria']);
				$this->Edit("opc_ver_profesional_tarifa", $contrato->fields['opc_ver_profesional_tarifa']);
				$this->Edit("opc_ver_profesional_importe", $contrato->fields['opc_ver_profesional_importe']);
				/**
				 * Configuración moneda del cobro
				 */
				$moneda_cobro_configurada = $contrato->fields['opc_moneda_total'];

				// Si incluye solo gastos, utilizar la moneda configurada para ello
				if ($incluye_gastos && !$incluye_honorarios) {
					$moneda_cobro_configurada = $contrato->fields['opc_moneda_gastos'];
				}

				$this->Edit("opc_moneda_total", $moneda_cobro_configurada);
				/* */

				$this->Edit("opc_ver_asuntos_separados", $contrato->fields['opc_ver_asuntos_separados']);
				$this->Edit("opc_ver_horas_trabajadas", $contrato->fields['opc_ver_horas_trabajadas']);
				$this->Edit("opc_ver_cobrable", $contrato->fields['opc_ver_cobrable']);
				// Guardamos datos de la moneda base
				$this->Edit('id_moneda_base', $moneda_base['id_moneda']);
				$this->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']);
				$this->Edit('etapa_cobro', '4');
				$this->Edit('codigo_idioma', $contrato->fields['codigo_idioma'] != '' ? $contrato->fields['codigo_idioma'] : 'es');
				$this->Edit('id_proceso', $id_proceso);
				#descuento
				$this->Edit("tipo_descuento", $contrato->fields['tipo_descuento']);
				$this->Edit("descuento", $contrato->fields['descuento']);
				$this->Edit("porcentaje_descuento", $contrato->fields['porcentaje_descuento']);
				$this->Edit("id_moneda_monto", $contrato->fields['id_moneda_monto']);
				$this->Edit("opc_ver_columna_cobrable", $contrato->fields['opc_ver_columna_cobrable']);

				if ($fecha_ini != '' && $fecha_ini != '0000-00-00') {
					$this->Edit('fecha_ini', $fecha_ini);
				}

				if ($fecha_fin != '') {
					$this->Edit('fecha_fin', $fecha_fin);
				}

				if ($solo_gastos == true) {
					$this->Edit('solo_gastos', 1);
				}

				$this->Edit("incluye_honorarios", $incluye_honorarios);
				$this->Edit("incluye_gastos", $incluye_gastos);

				if ($this->Write()) {
					####### AGREGA ASUNTOS AL COBRO #######
					$contrato->AddCobroAsuntos($this->fields['id_cobro']);

					####### MONEDA COBRO #######
					$cobro_moneda = new CobroMoneda($this->sesion);
					$cobro_moneda->ActualizarTipoCambioCobro($this->fields['id_cobro']);

					###### GASTOS ######
					if (UtilesApp::Getconf($this->sesion, 'UsaFechaDesdeCobranza')) {
						$and_fecha .= "AND cta_corriente.fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
					} else {
						$and_fecha .= "AND cta_corriente.fecha <= '$fecha_fin'";
					}

					if (!empty($incluye_gastos)) {
						if ($solo_gastos == true) {
							$where = '(cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)';
						} else {
							$where = '1';
						}

						$query_gastos = "SELECT cta_corriente.* FROM cta_corriente
												LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto OR cta_corriente.codigo_asunto IS NULL
												WHERE $where
												AND (cta_corriente.id_cobro IS NULL)
												AND cta_corriente.incluir_en_cobro = 'SI'
												AND cta_corriente.cobrable = 1
												AND cta_corriente.codigo_cliente = '" . $contrato->fields['codigo_cliente'] . "'
												AND (asunto.id_contrato = '" . $contrato->fields['id_contrato'] . "')
												AND cta_corriente.fecha <= '$fecha_fin'";
						if($fecha_ini!='') $query_gastos.="AND cta_corriente.fecha >= '$fecha_ini'";
						$lista_gastos = new ListaGastos($this->sesion, '', $query_gastos);
						for ($v = 0; $v < $lista_gastos->num; $v++) {
							$gasto = $lista_gastos->Get($v);

							$cta_gastos = new Objeto($this->sesion, '', '', 'cta_corriente', 'id_movimiento');
							if ($cta_gastos->Load($gasto->fields['id_movimiento'])) {
								$cta_gastos->Edit('id_cobro', $this->fields['id_cobro']);
								$cta_gastos->Write();
							}
						}
					}

					### TRABAJOS ###
					if (!empty($incluye_honorarios)) {
						if ($solo_gastos != true) {
							$emitir_trabajo = new Objeto($this->sesion, '', '', 'trabajo', 'id_trabajo');
							$where_up = '1';
							if ($fecha_ini == '' || $fecha_ini == '0000-00-00') {
								$where_up .= " AND fecha <= '$fecha_fin' ";
							} else {
								$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
							}
							$query2 = "SELECT * FROM trabajo
													JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
													JOIN contrato ON asunto.id_contrato = contrato.id_contrato
													LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
													WHERE	$where_up
													AND contrato.id_contrato = '" . $contrato->fields['id_contrato'] . "'
													AND cobro.estado IS NULL";
							#echo $query2.'<br><br>';
							$lista_trabajos = new ListaTrabajos($this->sesion, '', $query2);
							for ($x = 0; $x < $lista_trabajos->num; $x++) {
								$trabajo = $lista_trabajos->Get($x);
								$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
								$emitir_trabajo->Edit('id_cobro', $this->fields['id_cobro']);
								$emitir_trabajo->Write();
							}


							$emitir_tramite = new Objeto($this->sesion, '', '', 'tramite', 'id_tramite');
							$where_up = '1';
							if ($fecha_ini == '' || $fecha_ini == '0000-00-00') {
								$where_up .= " AND fecha <= '$fecha_fin' ";
							} else {
								$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
							}
							$query_tramites = "SELECT * FROM tramite
																		JOIN asunto ON tramite.codigo_asunto = asunto.codigo_asunto
																		JOIN contrato ON asunto.id_contrato = contrato.id_contrato
																		LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
																		WHERE $where_up
																		AND contrato.id_contrato = '" . $contrato->fields['id_contrato'] . "'
																		AND cobro.estado IS NULL";
							$lista_tramites = new ListaTrabajos($this->sesion, '', $query_tramites);
							for ($y = 0; $y < $lista_tramites->num; $y++) {
								$tramite = $lista_tramites->Get($y);
								$emitir_tramite->Load($tramite->fields['id_tramite']);
								$emitir_tramite->Edit('id_cobro', $this->fields['id_cobro']);
								$emitir_tramite->Write();
							}
						}
					}

					### COBROS PENDIENTES ###
					$cobro_pendiente = new CobroPendiente($this->sesion);
					if (!empty($id_cobro_pendiente)) {
						if ($cobro_pendiente->Load($id_cobro_pendiente)) {
							$cobro_pendiente->AsociarCobro($this->sesion, $this->fields['id_cobro']);
						}
					}
					#Se ingresa la anotación en el historial
					if ($this->fields['estado'] != 'COBRO CREADO') {
						$his = new Observacion($this->sesion);
						$his->Edit('fecha', date('Y-m-d H:i:s'));
						$his->Edit('comentario', __('COBRO CREADO'));
						$his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
						$his->Edit('id_cobro', $this->fields['id_cobro']);
						$his->Write();
					}

					$this->GuardarCobro();
				}
			} #END cobro
		} #END contrato
		return $this->fields['id_cobro'];
	}

	/*
	  String to number
	 */

	function StrToNumber($str) {
		$legalChars = "%[^0-9\-\ ]%";
		$str = preg_replace($legalChars, "", $str);
		return number_format((float) $str, 0, ',', '.');
	}

	/*
	  GeneraProceso, obtiene un id de proceso para cada generacion de cobros.
	 */

	function EsCobrado() {
		if (!$this->fields['estado'] || $this->fields['estado'] == 'CREADO' || $this->fields['estado'] == 'EN REVISION') {
			return false;
		} else {
			return true;
		}
	}

	function GeneraProceso() {
		$query = "INSERT INTO cobro_proceso SET fecha=NOW(), id_usuario = '" . $this->sesion->usuario->fields['id_usuario'] . "' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return mysql_insert_id($this->sesion->dbh);
	}

	/*
	  Obtiene un id_cobro para un asunto y trabajo que se encuentre en el periodo
	 */

	function ObtieneCobroByCodigoAsunto($codigo_asunto, $fecha_trabajo) {
		$query = "SELECT cobro.id_cobro FROM cobro
								JOIN cobro_asunto ON cobro.id_cobro = cobro_asunto.id_cobro
								WHERE cobro_asunto.codigo_asunto = '$codigo_asunto'
								AND cobro.estado IN ('CREADO','EN REVISION')
								AND cobro.incluye_honorarios = 1
								AND if(fecha_ini != '0000-00-00' OR fecha_ini IS NOT NULL, cobro.fecha_ini <= '$fecha_trabajo' AND cobro.fecha_fin >= '$fecha_trabajo', cobro.fecha_fin >= '$fecha_trabajo')
								ORDER BY id_cobro DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_cobro) = mysql_fetch_array($resp);
		if ($id_cobro) {
			return $id_cobro;
		} else {
			return false;
		}
	}

	/*
	  Calcula el saldo inicial de la cta. corriente
	  considera todos cobros <> creado excluyendo el cobro actual.
	  todos los cobros con fecha emision inferior a la del cobro actual
	  id_contrato igual al del cobro actual
	  Devuelve valor en Moneda de Vista
	 */

	function SaldoInicialCuentaCorriente() {
		#El tipo de moneda de la vista de este cobro
		$moneda = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda->Load($this->fields['opc_moneda_total']);

		$query = "SELECT opc_moneda_total,saldo_final_gastos FROM cobro
							WHERE estado <> 'CREADO' AND estado <> 'EN REVISION' AND id_cobro <> '" . $this->fields['id_cobro'] . "'
							AND codigo_cliente = '" . $this->fields['codigo_cliente'] . "'
							AND fecha_emision < '" . $this->fields['fecha_emision'] . "'
							AND id_contrato = '" . $this->fields['id_contrato'] . "' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$lista_cobros = new ListaCobros($this->sesion, '', $query);
		$saldo_inicial_gastos = 0;
		for ($i = 0; $i < $lista_cobros->num; $i++) {
			$cobro_list = $lista_cobros->Get($i);
			$moneda_cobro = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
			$moneda_cobro->Load($cobro_list->fields['opc_moneda_total']);

			$saldo_inicial_gastos += $cobro_list->fields['saldo_final_gastos'] * $moneda_cobro->fields['tipo_cambio'] / $moneda->fields['tipo_cambio']; #error gasto 12
		}
		return $saldo_inicial_gastos ? $saldo_inicial_gastos : 0;
	}

	/*
	  Calcula el saldo_final_gastos, este corresponde al saldo_inicial - saldo_cobro (suma de gastos - provisiones)
	 */

	function SaldoFinalCuentaCorriente() {
		//Moneda del cobro
		$moneda = new Objeto($this->sesion, '', '', 'prm_moneda', 'id_moneda');
		$moneda->Load($this->fields['opc_moneda_total']);

		$query = "SELECT SQL_CALC_FOUND_ROWS * FROM cta_corriente
							WHERE id_cobro = '" . $this->fields['id_cobro'] . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
							ORDER BY fecha ASC";
		$lista_gastos = new ListaGastos($this->sesion, '', $query);
		$saldo_gastos = 0;
		for ($i = 0; $i < $lista_gastos->num; $i++) {
			$gasto = $lista_gastos->Get($i);
			//sacamos el valor del tipo de cambio usado en el cobro
			$query = "SELECT cobro_moneda.id_cobro, cobro_moneda.id_moneda, cobro_moneda.tipo_cambio
							FROM cobro_moneda
							WHERE cobro_moneda.id_cobro=" . $this->fields['id_cobro'] . "
								AND cobro_moneda.id_moneda=" . $gasto->fields['id_moneda'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			$cobro_moneda = mysql_fetch_array($resp);
			if ($gasto->fields['egreso'] > 0) {
				$saldo_gastos += $gasto->fields['monto_cobrable'] * $cobro_moneda['tipo_cambio'] / $moneda->fields['tipo_cambio']; #error gasto 14
			} elseif ($gasto->fields['ingreso'] > 0) {
				$saldo_gastos -= $gasto->fields['monto_cobrable'] * $cobro_moneda['tipo_cambio'] / $moneda->fields['tipo_cambio']; #error gasto 15
			}
		}
		$saldo_inicial = 0;
		$saldo_inicial = $this->SaldoInicialCuentaCorriente();
		$saldo_final_gastos = $saldo_inicial - $saldo_gastos;
		return $saldo_final_gastos;
	}

	function TienePagosAdelantos() {
		$query = "
			SELECT COUNT(*) AS nro_pagos
			FROM documento AS doc_pago
			JOIN neteo_documento ON doc_pago.id_documento = neteo_documento.id_documento_pago
			JOIN documento AS doc ON doc.id_documento = neteo_documento.id_documento_cobro
			WHERE doc.id_cobro = " . $this->fields['id_cobro'];
		$pagos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$nro_pagos = mysql_fetch_assoc($pagos);
		return $nro_pagos['nro_pagos'] > 0;
	}

	function DetalleProfesional() {
		global $contrato;
		if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'OrdenarPorTarifa') ) || ( method_exists('Conf', 'OrdenarPorTarifa') && Conf::OrdenarPorTarifa() ))) {
			$order_categoria = "t.tarifa_hh DESC, ";
		} else if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'OrdenarPorCategoriaNombreUsuario') ) || ( method_exists('Conf', 'OrdenarPorCategoriaNombreUsuario') && Conf::OrdenarPorCategoriaNombreUsuario() ))) {
			$order_categoria = "u.id_categoria_usuario, u.nombre, u.apellido1, u.id_usuario, ";
		} else if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'OrdenarPorCategoriaUsuario') ) || ( method_exists('Conf', 'OrdenarPorCategoriaUsuario') && Conf::OrdenarPorCategoriaUsuario() ))) {
			$order_categoria = "cu.orden, u.id_usuario, ";
		} else if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'SepararPorUsuario') ) || ( method_exists('Conf', 'SepararPorUsuario') && Conf::SepararPorUsuario() ))) {
			$order_categoria = "u.id_categoria_usuario, u.id_usuario, ";
		} else if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'OrdenarPorCategoriaDetalleProfesional') ) || ( method_exists('Conf', 'OrdenarPorCategoriaDetalleProfesional') && Conf::OrdenarPorCategoriaDetalleProfesional() ))) {
			$order_categoria = "u.id_categoria_usuario DESC, ";
		} else if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'OrdenarPorFechaCategoria') ) || ( method_exists('Conf', 'OrdenarPorFechaCategoria') && Conf::OrdenarPorFechaCategoria() ))) {
			$order_categoria = "t.fecha, u.id_categoria_usuario, u.id_usuario, ";
		} else {
			$order_categoria = "";
		}

		$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) FROM trabajo WHERE cobrable = 1 AND id_cobro = '" . $this->fields['id_cobro'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_horas_cobro) = mysql_fetch_array($resp);

		if (!method_exists('Conf', 'MostrarHorasCero') && !( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'MostrarHorasCero') )) {
			if ($this->fields['opc_ver_horas_trabajadas']) {
				$where_horas_cero = " AND t.duracion > '0000-00-00 00:00:00' ";
			} else {
				$where_horas_cero = " AND t.duracion_cobrada > '0000-00-00 00:00:00' ";
			}
		}

		if (UtilesApp::Getconf($this->sesion, 'DejarTarifaCeroRetainerPRC')) {
			$query_tarifa = "SELECT SUM( ( TIME_TO_SEC(t2.duracion_cobrada) - TIME_TO_SEC( duracion_retainer ) ) * t2.tarifa_hh ) / SUM( TIME_TO_SEC(t2.duracion_cobrada) - TIME_TO_SEC( duracion_retainer ) )
												FROM trabajo AS t2 WHERE t2.id_cobro = '" . $this->fields['id_cobro'] . "'
												 AND t2.id_usuario = u.id_usuario
												 AND t2.cobrable = 1";
		} else {
			$query_tarifa = "SELECT SUM( ( TIME_TO_SEC(t2.duracion_cobrada)  ) * t2.tarifa_hh ) / SUM( TIME_TO_SEC(t2.duracion_cobrada)  )
												FROM trabajo AS t2 WHERE t2.id_cobro = '" . $this->fields['id_cobro'] . "'
												 AND t2.id_usuario = u.id_usuario
												 AND t2.cobrable = 1";
		}

		$query = "	SELECT
										t.id_usuario as id_usuario,
										t.codigo_asunto as codigo_asunto,
										cu.id_categoria_usuario as id_categoria_usuario,
										cu.glosa_categoria as glosa_categoria,
										u.username as username,
										CONCAT_WS(' ',u.nombre,u.apellido1) as nombre_usuario,
										SUM( TIME_TO_SEC(duracion_cobrada)/3600 ) as duracion_cobrada,
										SUM( TIME_TO_SEC(duracion)/3600 ) as duracion_trabajada,
										SUM( (TIME_TO_SEC(duracion)-TIME_TO_SEC(duracion_cobrada))/3600 ) as duracion_descontada,
										SUM( TIME_TO_SEC( duracion_retainer )/3600 ) as duracion_retainer,
										(
											$query_tarifa
										) as tarifa,
										SUM(t.monto_cobrado) as monto_cobrado_escalonada
									FROM trabajo as t
									JOIN usuario as u ON u.id_usuario=t.id_usuario
									LEFT JOIN prm_categoria_usuario as cu ON u.id_categoria_usuario=cu.id_categoria_usuario
									WHERE t.id_cobro = '" . $this->fields['id_cobro'] . "'
										AND t.visible = 1
										AND t.id_tramite = 0
										$where_horas_cero
									GROUP BY t.codigo_asunto, t.id_usuario
									ORDER BY $order_categoria t.fecha ASC, t.descripcion ";
		//echo $query; exit;
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$contrato_horas = $this->fields['retainer_horas'];

		if ($total_horas_cobro > 0) {
			$factor_proporcional = ( $total_horas_cobro - $contrato_horas ) / $total_horas_cobro;
		} else {
			$factor_proporcional = 1;
		}
		if ($factor_proporcional < 0) {
			$factor_proporcional = 0;
		}
		$array_profesionales = array();
		$array_resumen_profesionales = array();
		while ($row = mysql_fetch_assoc($resp)) {
			$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) as duracion_incobrables
									FROM trabajo
								 WHERE id_cobro = '" . $this->fields['id_cobro'] . "'
								 	 AND visible = 1
								 	 AND cobrable = 0
								 	 AND id_tramite = 0
								 	 AND id_usuario = '" . $row['id_usuario'] . "'
								 	 AND codigo_asunto = '" . $row['codigo_asunto'] . "'";
			$resp2 = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($row['duracion_incobrables']) = mysql_fetch_array($resp2);

			if (!is_array($array_resumen_profesionales[$row['id_usuario']])) {
				$array_resumen_profesionales[$row['id_usuario']]['id_categoria_usuario'] = $row['id_categoria_usuario'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_categoria'] = $row['glosa_categoria'];
				$array_resumen_profesionales[$row['id_usuario']]['nombre_usuario'] = $row['nombre_usuario'];
				$array_resumen_profesionales[$row['id_usuario']]['username'] = $row['username'];
				$array_resumen_profesionales[$row['id_usuario']]['tarifa'] = $row['tarifa'];
				$array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada'] = $row['duracion_cobrada'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada'] = $row['duracion_trabajada'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_trabajada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_descontada'] = $row['duracion_descontada'] + $row['duracion_incobrables'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_descontada']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables'] = $row['duracion_incobrables'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] = $row['duracion_retainer'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);

				if ($this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee']) {
					$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] = 0;
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = '0:00';
					$array_resumen_profesionales[$row['id_usuario']]['valor_tarificada'] = 0;
					$array_resumen_profesionales[$row['id_usuario']]['flatfee'] = $row['duracion_cobrada'] - $row['duracion_incobrables'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_flatfee'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['flatfee']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] = ( $row['duracion_cobrada'] - $row['duracion_incobrables'] );
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);
				} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$array_resumen_profesionales[$row['id_usuario']]['monto_cobrado_escalonada'] = $row['monto_cobrado_escalonada'];
				} else if ($this->fields['forma_cobro'] == 'RETAINER') {
					$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] = ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
				} else if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					if (method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
						$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] = ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
					} else {
						$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] = ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) * $factor_proporcional;
					}
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
				} else {
					$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] = $row['duracion_cobrada'] - $row['duracion_incobrables'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
				}
			} else {
				$array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada'] += $row['duracion_cobrada'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_cobrada']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada'] += $row['duracion_trabajada'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_trabajada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_trabajada']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_descontada'] += $row['duracion_descontada'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_descontada']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables'] += $row['duracion_incobrables'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_incobrables']);
				$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] += $row['duracion_retainer'];
				$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);

				if ($this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee']) {
					$array_resumen_profesionales[$row['id_usuario']]['flatfee'] += $row['duracion_cobrada'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_flatfee'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['flatfee']);
					$array_resumen_profesionales[$row['id_usuario']]['duracion_retainer'] += ( $row['duracion_cobrada'] - $row['duracion_incobrables'] );
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_retainer'] = Utiles::Decimal2GLosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_retainer']);
				} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
					$array_resumen_profesionales[$row['id_usuario']]['monto_cobrado_escalonada'] += $row['monto_cobrado_escalonada'];
				} else if ($this->fields['forma_cobro'] == 'RETAINER') {
					$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] += ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
				} else if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
					if (method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'ResumenProfesionalVial')) {
						$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] += ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
					} else {
						$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] += ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) * $factor_proporcional;
					}
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
				} else {
					$array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada'] += $row['duracion_cobrada'] - $row['duracion_incobrables'];
					$array_resumen_profesionales[$row['id_usuario']]['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_resumen_profesionales[$row['id_usuario']]['duracion_tarificada']);
				}
			}

			$query = "SELECT SUM( TIME_TO_SEC( duracion_cobrada )/3600 ) as duracion_incobrables
									FROM trabajo
								 WHERE id_cobro = '" . $this->fields['id_cobro'] . "'
								 	 AND visible = 1
								 	 AND cobrable = 0
								 	 AND id_tramite = 0
								 	 AND codigo_asunto = '" . $row['codigo_asunto'] . "'
								 	 AND id_usuario = '" . $row['id_usuario'] . "'";
			$resp3 = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($row['duracion_incobrables']) = mysql_fetch_array($resp3);

			$total_horas += $row['duracion_cobrada'];
			$array_profesional_usuario = array();
			$array_profesional_usuario['id_categoria_usuario'] = $row['id_categoria_usuario'];
			$array_profesional_usuario['glosa_categoria'] = $row['glosa_categoria'];
			$array_profesional_usuario['nombre_usuario'] = $row['nombre_usuario'];
			$array_profesional_usuario['username'] = $row['username'];
			$array_profesional_usuario['duracion_cobrada'] = $row['duracion_cobrada'];
			$array_profesional_usuario['glosa_duracion_cobrada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_cobrada']);
			$array_profesional_usuario['duracion_cobrada'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_cobrada']);
			$array_profesional_usuario['duracion_trabajada'] = $row['duracion_trabajada'];
			$array_profesional_usuario['glosa_duracion_trabajada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_trabajada']);
			$array_profesional_usuario['duracion_trabajada'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_trabajada']);
			$array_profesional_usuario['duracion_descontada'] = $row['duracion_descontada'] + $row['duracion_incobrables'];
			$array_profesional_usuario['glosa_duracion_descontada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_descontada']);
			$array_profesional_usuario['duracion_descontada'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_descontada']);
			$array_profesional_usuario['duracion_incobrables'] = $row['duracion_incobrables'];
			$array_profesional_usuario['glosa_duracion_incobrables'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_incobrables']);
			$array_profesional_usuario['duracion_incobrables'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_incobrables']);
			$array_profesional_usuario['duracion_retainer'] = $row['duracion_retainer'];
			$array_profesional_usuario['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_retainer']);
			$array_profesional_usuario['duracion_retainer'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_retainer']);
			$array_profesional_usuario['tarifa'] = $row['tarifa'];

			if ($this->fields['forma_cobro'] == 'FLAT FEE' && !$this->fields['opc_ver_valor_hh_flat_fee']) {
				$array_profesional_usuario['duracion_tarificada'] = 0;
				$array_profesional_usuario['glosa_duracion_tarificada'] = '0:00';
				$array_profesional_usuario['valor_tarificada'] = 0;
				$array_profesional_usuario['flatfee'] = $row['duracion_cobrada'];
				$array_profesional_usuario['duracion_retainer'] = $row['duracion_cobrada'];
				$array_profesional_usuario['glosa_duracion_retainer'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_retainer']);
				$array_profesional_usuario['duracion_retainer'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_retainer']);
			} else if ($this->fields['forma_cobro'] == 'ESCALONADA') {
				$array_profesional_usuario['monto_cobrado_escalonada'] = $row['monto_cobrado_escalonada'];
			} else if ($this->fields['forma_cobro'] == 'RETAINER') {
				$array_profesional_usuario['duracion_tarificada'] = ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) - $row['duracion_retainer'];
				$array_profesional_usuario['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_tarificada']);
				$array_profesional_usuario['duracion_tarificada'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_tarificada']);
				$array_profesional_usuario['valor_tarificada'] = $array_profesional_usuario['duracion_tarificada'] * $row['tarifa'];
			} else if ($this->fields['forma_cobro'] == 'PROPORCIONAL') {
				$array_profesional_usuario['duracion_tarificada'] = ( $row['duracion_cobrada'] - $row['duracion_incobrables'] ) * $factor_proporcional;
				$array_profesional_usuario['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_tarificada']);
				$array_profesional_usuario['duracion_tarificada'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_tarificada']);
				$array_profesional_usuario['valor_tarificada'] = $array_profesional_usuario['duracion_tarificada'] * $row['tarifa'];
			} else {
				$array_profesional_usuario['duracion_tarificada'] = $row['duracion_cobrada'] - $row['duracion_incobrables'];
				$array_profesional_usuario['glosa_duracion_tarificada'] = Utiles::Decimal2GlosaHora($array_profesional_usuario['duracion_tarificada']);
				$array_profesional_usuario['duracion_tarificada'] = Utiles::GlosaHora2Multiplicador($array_profesional_usuario['glosa_duracion_tarificada']);
				$array_profesional_usuario['valor_tarificada'] = $array_profesional_usuario['duracion_tarificada'] * $row['tarifa'];
			}
			if (!is_array($array_profesionales[$row['codigo_asunto']])) {
				$array_profesionales[$row['codigo_asunto']] = array();
			}
			$array_profesionales[$row['codigo_asunto']][$row['id_usuario']] = $array_profesional_usuario;
		}

		$resumen_valor_hh = 0;
		foreach ($array_resumen_profesionales as $id_usuario => $data) {
			$array_resumen_profesionales[$id_usuario]['duracion_cobrada'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_cobrada']);
			$array_resumen_profesionales[$id_usuario]['duracion_trabajada'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_trabajada']);
			$array_resumen_profesionales[$id_usuario]['duracion_descontada'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_descontada']);
			$array_resumen_profesionales[$id_usuario]['duracion_incobrables'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_incobrables']);
			$array_resumen_profesionales[$id_usuario]['duracion_retainer'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_retainer']);
			if ($this->fields['forma_cobro'] == 'FLAT FEE' && $this->fields['opc_ver_valor_hh_flat_fee']) {
				$array_resumen_profesionales[$id_usuario]['duracion_tarificada'] = $array_resumen_profesionales[$id_usuario]['duracion_cobrada'] - $array_resumen_profesional[$id_usuario]['duracion_incobrables'];
			} else {
				$array_resumen_profesionales[$id_usuario]['duracion_tarificada'] = Utiles::GlosaHora2Multiplicador($data['glosa_duracion_tarificada']);
			}$array_resumen_profesionales[$id_usuario]['valor_tarificada'] = $array_resumen_profesionales[$id_usuario]['duracion_tarificada'] * $data['tarifa'];

			$resumen_valor_hh += $data['duracion_cobrada'] * $data['tarifa'];
		}
		if ($resumen_valor_hh > 0) {
			$factor_ajuste = $this->fields['monto_subtotal'] / $resumen_valor_hh;
		} else {
			$factor_ajuste = 1;
		}

		return array($array_profesionales, $array_resumen_profesionales, $factor_ajuste);
	}

	function MontoFacturado() {
		$query = "SELECT if(f.id_documento_legal NOT IN (2),'INGRESO','EGRESO') as modo
						,f.total
						,m1.cifras_decimales as cifras_decimales_ini
						,m1.tipo_cambio as tipo_cambio_ini
						,m2.cifras_decimales as cifras_decimales_fin
						,m2.tipo_cambio as tipo_cambio_fin
					FROM factura f join factura_cobro fc using (id_factura)
					LEFT JOIN prm_moneda m1 ON m1.id_moneda = f.id_moneda
					LEFT JOIN prm_moneda m2 ON m2.id_moneda = '" . $this->fields['opc_moneda_total'] . "'
					WHERE f.id_estado NOT IN (3,5)
					AND fc.id_cobro = '" . $this->fields['id_cobro'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$ingreso = 0;
		$egrso = 0;
		$monto_facturado = 0;
		while ($row = mysql_fetch_assoc($resp)) {
			$total = UtilesApp::CambiarMoneda($row['total'], $row['tipo_cambio_ini'], $row['cifras_decimales_ini'], $row['tipo_cambio_fin'], $row['cifras_decimales_fin'], false);
			if ($row['modo'] == 'INGRESO') {
				$ingreso += $total;
			} else {
				$egreso += $total;
			}
		}
		$monto_facturado = $ingreso - $egreso;
		return $monto_facturado;
	}

	function DiferenciaCobroConFactura() {
		$calculos_cobro = UtilesApp::ProcesaCobroIdMoneda($this->sesion, $this->fields['id_cobro']);
		$monto_cobrado = $calculos_cobro['monto_total_cobro'][$calculos_cobro['opc_moneda_total']];
		$monto_facturado = $this->MontoFacturado();
		$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
		$idioma->Load($this->fields['codigo_idioma']);
		$monto_cobrado = number_format($monto_cobrado, $calculos_cobro['cifras_decimales_opc_moneda_total'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
		$monto_facturado = number_format($monto_facturado, $calculos_cobro['cifras_decimales_opc_moneda_total'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

		$mensaje = '';
		if ($monto_cobrado != $monto_facturado) {
			$moneda = new Moneda($this->sesion);
			$moneda->Load($this->fields['opc_moneda_total']);
			$simbolo = $moneda->fields['simbolo'];
			$mensaje = __('El monto liquidado') . ' (' . $simbolo . ' ' . $monto_cobrado . ') ' . __('no coincide con el monto facturado ') . '(' . $simbolo . ' ' . $monto_facturado . ')';
		}
		return $mensaje;
	}

	/**
	 *
	 * @param obj $sesion opcional, la sesion autenticada
	 * @param int $id_cobro
	 * @return array
	 */
	function DetallePagoContrato($sesion = null, $id_cobro = null) {
		if ($sesion == null) {
		 	$sesion = $this->sesion;
		}

		if ($id_cobro == null) {
			$id_cobro = $this->fields[$this->campo_id];
		}

		$nuevomodulofactura = UtilesApp::GetConf($this->sesion, 'NuevoModuloFactura');

	 	$queryadelantos = "SELECT
								IFNULL(documento.id_contrato, 0) AS id_contrato,
								-1 * saldo_pago * IF(
									documento.id_moneda = '{$this->fields['opc_moneda_total']}',
									1,
									cm1.tipo_cambio / cm2.tipo_cambio
								) AS saldo_adelanto,
								cliente.id_contrato AS contrato_default,
								documento.pago_gastos,
								documento.pago_honorarios
							FROM `documento`
							INNER JOIN cliente ON documento.codigo_cliente = cliente.codigo_cliente
							INNER JOIN cobro_moneda cm1 ON
								cm1.id_moneda = documento.id_moneda
								AND cm1.id_cobro = '{$this->fields['id_cobro']}'
							INNER JOIN cobro_moneda cm2 ON
								cm2.id_moneda = '{$this->fields['opc_moneda_total']}'
								AND cm2.id_cobro = '{$this->fields['id_cobro']}'
							WHERE
								es_adelanto = 1
								AND documento.codigo_cliente = '{$this->fields['codigo_cliente']}'";


		$monto_adelantos_sin_asignar = array(
			'total' => 0,
			'honorarios' => 0,
			'gastos' => 0,
			'legacy' => 0
		);

		$adelantos_sin_asignar = $this->sesion->pdodbh->query($queryadelantos);

		foreach ($adelantos_sin_asignar as $adelanto) {
			$saldo_adelanto = $adelanto['saldo_adelanto'];

			// Lógica Legacy para FAYCA, los adelantos se muestran solo si pertenecen al contrato del cobro o al del cliente
			if (($adelanto['id_contrato'] == $this->fields['id_contrato']) ||
					($adelanto['id_contrato'] == 0 && $adelanto['contrato_default'] == $this->fields['id_contrato']))  {
				$monto_adelantos_sin_asignar['legacy'] += $saldo_adelanto;
			}

			// Lógica general, se muestran adelantos del contrato del cobro o adelantos sin contrato (para todos)
			if ($adelanto['id_contrato'] == $this->fields['id_contrato'] || $adelanto['id_contrato'] == 0) {
				$monto_adelantos_sin_asignar['total'] += $saldo_adelanto;

				if ($adelanto['pago_honorarios']) {
					$monto_adelantos_sin_asignar['honorarios'] += $saldo_adelanto;
				}
				if ($adelanto['pago_gastos']) {
					$monto_adelantos_sin_asignar['gastos'] += $saldo_adelanto;
				}
			}
		}
		$saldo_pagos = 0;
		$saldo_adelantos = 0;

		if ($this->TienePagosAdelantos()) {
			$query = "SELECT
							doc_pago.es_adelanto,
							doc_pago.glosa_documento,
							(neteo_documento.valor_cobro_honorarios + neteo_documento.valor_cobro_gastos) * -1 AS monto,
							doc_pago.fecha,
							prm_moneda.tipo_cambio
						FROM documento AS doc_pago
						INNER JOIN neteo_documento ON doc_pago.id_documento = neteo_documento.id_documento_pago
						INNER JOIN documento AS doc ON doc.id_documento = neteo_documento.id_documento_cobro
						INNER JOIN prm_moneda ON prm_moneda.id_moneda = doc_pago.id_moneda
						WHERE doc.id_cobro = " . $this->fields['id_cobro'];
			$pagos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			while ($pago = mysql_fetch_assoc($pagos)) {
				$fila_adelanto_ = str_replace('%descripcion%', substr($pago['glosa_documento'], 0, 30 + strpos(' ', substr($pago['glosa_documento'], 30, 50))) . ' (' . $pago['fecha'] . ')', $html);
				$monto_pago = $pago['monto'];
				$monto_pago_simbolo = $moneda['simbolo'] . $espacio_moneda . number_format($monto_pago, $moneda['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
				$fila_adelanto_ = str_replace('%saldo_pago%', $monto_pago_simbolo, $fila_adelanto_);

				$saldo += (float) $monto_pago;
				if ($pago['es_adelanto'] == 1) {
					$saldo_adelantos += (float) $monto_pago;
				} else {
					$saldo_pagos+= (float) $monto_pago;
				}
				$fila_adelantos .= $fila_adelanto_;
			}
		}

		//$facturasRS=$this->ArrayFacturasDelContrato($this->sesion,$nuevomodulofactura);
		$totales = $this->ArrayTotalesDelContrato;

		$saldo_total_cobro = $totales[$id_cobro]['saldo_honorarios'] + $totales[$id_cobro]['saldo_gastos'];
		$saldo_total_adeudado = $totales['contrato']['saldo_honorarios'] + $totales['contrato']['saldo_gastos'];
		$saldo_total_contrato = $saldo_total_adeudado - $saldo_total_cobro;

		return array(
			'montoadelantosinasignar' => $monto_adelantos_sin_asignar['legacy'],
			'monto_adelantos_sin_asignar_total' => $monto_adelantos_sin_asignar['total'],
			'monto_adelantos_sin_asignar_honorarios' => $monto_adelantos_sin_asignar['honorarios'],
			'monto_adelantos_sin_asignar_gastos' => $monto_adelantos_sin_asignar['gastos'],
			'saldo' => $saldo,
			'saldo_adelantos' => $saldo_adelantos,
			'saldo_pagos' => $saldo_pagos,
			'fila_adelantos' => $fila_adelantos,
			'saldo_total_contrato' => $saldo_total_contrato,
			'saldo_total_cobro' => $saldo_total_cobro,
			'saldo_total_adeudado' => $saldo_total_adeudado,
		);
	}


}
}

if (!class_exists('ListaCobros')) {
	class ListaCobros extends Lista {
		function ListaCobros($sesion, $params, $query) {
			$this->Lista($sesion, 'Cobro', $params, $query);
		}
	}
}
