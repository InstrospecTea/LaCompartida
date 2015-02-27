<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
require_once Conf::ServerDir().'/../app/classes/FacturaPago.php';
require_once Conf::ServerDir().'/../app/classes/CtaCteFact.php';

class NeteoDocumento extends Objeto
{
	function NeteoDocumento($sesion, $fields = "", $params = "")
	{
		$this->tabla = "neteo_documento";
		$this->campo_id = "id_neteo_documento";

		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function Ids($id_documento_pago,$id_documento_cobro)
	{
		if(!$id_documento_pago)
			return false;

		$query = "SELECT id_neteo_documento AS id
					FROM neteo_documento
					WHERE	id_documento_pago = '$id_documento_pago'
					AND		id_documento_cobro = '$id_documento_cobro'";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		if($id)
		{
			return $this->Load($id);
		}
		else
		{
			$this->Edit('id_documento_pago',$id_documento_pago);
			$this->Edit('id_documento_cobro',$id_documento_cobro);
		}
		return false;
	}

	//Establece un Neteo Completo entre los documentos
	function NeteoCompleto($documento_cobro,$documento_pago, $honorarios, $cambio_cobro, $cambio_pago, $write)
	{
		$neteo = new NeteoDocumento($this->sesion);

		if(!$neteo->Ids($documento_pago->fields['id_documento'], $documento_cobro->fields['id_documento']) )
		{
			$neteo->Edit('id_documento_pago',$documento_pago->fields['id_documento']);
			$neteo->Edit('id_documento_cobro',$documento_cobro->fields['id_documento']);
		}

		if($honorarios)
		{
			$neteo->Edit('valor_cobro_honorarios',$documento_cobro->fields['honorarios']);
			$neteo->Edit('valor_pago_honorarios',number_format($documento_cobro->fields['honorarios']*$cambio_cobro/$cambio_pago,0,".",""));
		}
		else
		{
			$neteo->Edit('valor_cobro_gastos',$documento_cobro->fields['gastos']);
			$neteo->Edit('valor_pago_gastos',number_format($documento_cobro->fields['gastos']*$cambio_cobro/$cambio_pago,0,".",""));
		}
		if($write)
			$neteo->Write();

		if($neteo->fields['id_neteo_documento'])
			$id = $neteo->fields['id_neteo_documento'];
		else
			$id = "Nuevo";

		$out  = "<tr> <td>".$id."</td><td>";
		$out .= $neteo->fields['id_documento_cobro']."</td><td>";
		$out .= $neteo->fields['id_documento_pago']."</td><td>";
		$out .= $documento_cobro->fields['id_moneda']."</td><td>";
		$out .= $neteo->fields['valor_cobro_honorarios']."</td><td>";
		$out .= $neteo->fields['valor_cobro_gastos']."</td><td>";
		$out .= $documento_pago->fields['id_moneda']."</td><td>";
		$out .= $neteo->fields['valor_pago_honorarios']."</td><td>";
		$out .= $neteo->fields['valor_pago_gastos']."</td></tr>";
		return $out;
	}

	function Reestablecer($decimales_cobro)
	{
		$out = "<tr><td>";
		if($this->Loaded())
		{
			$documento_cobro = new Documento($this->sesion);
			if($documento_cobro->Load($this->fields['id_documento_cobro']))
			{
				$out.= $documento_cobro->fields['id_cobro']."</td><td>";

				$saldo_cobro_honorarios = $documento_cobro->fields['saldo_honorarios'];
				$saldo_cobro_gastos = $documento_cobro->fields['saldo_gastos'];

				$out .= $saldo_cobro_honorarios."</td><td>";
				$out .= $this->fields['valor_cobro_honorarios']."</td><td>";

				$saldo_cobro_honorarios += $this->fields['valor_cobro_honorarios'];

				$out .= $saldo_cobro_honorarios."</td>";

				$saldo_cobro_gastos += $this->fields['valor_cobro_gastos'];

				if($saldo_cobro_gastos != 0)
					$documento_cobro->Edit('gastos_pagados','NO');
				if($saldo_cobro_honorarios != 0)
					$documento_cobro->Edit('honorarios_pagados','NO');

				$documento_cobro->Edit('saldo_gastos', number_format( $saldo_cobro_gastos, $decimales_cobro, '.' , ''));
				$documento_cobro->Edit('saldo_honorarios', number_format( $saldo_cobro_honorarios, $decimales_cobro, '.' , ''));

				$documento_cobro->Write();

				$documento_pago = new Documento($this->sesion);
				$documento_pago->Load($this->fields['id_documento_pago']);

				$documento_pago->Edit('saldo_pago', $documento_pago->fields['saldo_pago'] - $this->fields['valor_pago_honorarios'] - $this->fields['valor_pago_gastos']);
				$documento_pago->Write();

				$this->Edit('valor_cobro_honorarios','0');
				$this->Edit('valor_cobro_gastos','0');
				$this->Edit('valor_pago_honorarios','0');
				$this->Edit('valor_pago_gastos','0');
				$this->Write();
			}

			//Elimino la provisi�n que se pudo haber generado por pagar gastos
			$query = "DELETE from cta_corriente WHERE cta_corriente.id_neteo_documento = '".$this->fields['id_neteo_documento']."' ";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		}
		return $out;
	}

	function Escribir($pago_honorarios, $pago_gastos, $cambio_pago, $cambio_cobro, $decimales_pago, $decimales_cobro, $id_cobro, $pagar_facturas = false, $rehacer_neteos = false) {
		$out = '';
		if ((floatval($cambio_pago) == floatval($cambio_cobro)) || floatval($cambio_cobro) == 0) {
			$tasa = 1;
		} else {
			$tasa = floatval($cambio_pago) / floatval($cambio_cobro);
		}

		// emila la carga de las monedas, solo se usan para el formato
		$moneda_pago = new Moneda($this->sesion, array('id_moneda' => true, 'cifras_decimales' => $decimales_pago));
		$moneda_cobro = new Moneda($this->sesion, array('id_moneda' => true, 'cifras_decimales' => $decimales_cobro));

		$honorarios_acumulado = $pago_honorarios;
		$gastos_acumulado = $pago_gastos;
		if (is_array($pagar_facturas) && !$rehacer_neteos) {
			$honorarios_acumulado += $this->fields['valor_pago_honorarios'];
			$gastos_acumulado += $this->fields['valor_pago_gastos'];
		}

		$valor_pago_original = $moneda_pago->getFloat($pago_honorarios + $pago_gastos);
		$pago_gastos_noformat = $pago_gastos;
		$pago_gastos = $moneda_pago->getFloat($pago_gastos);
		$pago_honorarios_noformat = $pago_honorarios;
		$pago_honorarios = $moneda_pago->getFloat($pago_honorarios);

		$honorarios_acumulado_noformat = $honorarios_acumulado;
		$honorarios_acumulado = $moneda_pago->getFloat($honorarios_acumulado);
		$gastos_acumulado_noformat = $gastos_acumulado;
		$gastos_acumulado = $moneda_pago->getFloat($gastos_acumulado);

		$this->Edit('valor_pago_honorarios', $honorarios_acumulado);
		$this->Edit('valor_pago_gastos', $gastos_acumulado);

		$cobro_gastos = $moneda_cobro->getFloat($gastos_acumulado_noformat * $tasa);
		$cobro_honorarios = $moneda_cobro->getFloat($honorarios_acumulado_noformat * $tasa);


		$saldo_cobro_honorarios = 0;
		$saldo_cobro_gastos = 0;

		if ($rehacer_neteos) {
			$saldo_cobro_honorarios = $this->fields['valor_cobro_honorarios'];
			$saldo_cobro_gastos = $this->fields['valor_cobro_honorarios'];
		}

		$this->Edit('valor_cobro_gastos', $cobro_gastos);
		$this->Edit('valor_cobro_honorarios', $cobro_honorarios);

		if ($this->Write()) {
			$out .= "<td>";
			$out .= $cobro_honorarios . "</td><td>";

			$documento_cobro = new Documento($this->sesion);
			if ($documento_cobro->Load($this->fields['id_documento_cobro'])) {

				$saldo_cobro_honorarios += $documento_cobro->fields['saldo_honorarios'];
				$saldo_cobro_gastos += $documento_cobro->fields['saldo_gastos'];

				/* HONORARIOS */
				$saldo_cobro_honorarios -= $this->fields['valor_cobro_honorarios'];

				if (($saldo_cobro_honorarios <= 0 && $documento_cobro->fields['honorarios'] >= 0) || ($saldo_cobro_honorarios >= 0 && $documento_cobro->fields['honorarios'] <= 0)) {
					$documento_cobro->Edit('honorarios_pagados', 'SI');
				}
				$documento_cobro->Edit('saldo_honorarios', $moneda_cobro->getFloat($saldo_cobro_honorarios));


				$out .= $saldo_cobro_honorarios . "</td></tr>";

				/* GASTOS */
				$saldo_cobro_gastos -= $this->fields['valor_cobro_gastos'];
				if ($saldo_cobro_gastos <= 0) {
					$documento_cobro->Edit('gastos_pagados', 'SI');
				}
				$documento_cobro->Edit('saldo_gastos', $moneda_cobro->getFloat($saldo_cobro_gastos));

				/* PAGO */
				$documento_pago = new Documento($this->sesion);
				$documento_pago->Load($this->fields['id_documento_pago']);
				$saldo_pago = $documento_pago->fields['saldo_pago'];
				$saldo_pago += $valor_pago_original;

				$documento_cobro->Write();
				$documento_pago->Write();

				if ($documento_cobro->fields['saldo_gastos'] <= 0 && $documento_cobro->fields['saldo_honorarios'] == 0 && $id_cobro) {
					$cobro = new Cobro($this->sesion);
					$cobro->Load($id_cobro);
					if ($cobro->Loaded()) {
						$cobro->Edit('estado', 'PAGADO');
						$cobro->Write();
					}
				}

				if ($pago_gastos > 0 && !Conf::GetConf($this->sesion, 'NuevoModuloGastos')) {
					$provision = new Gasto($this->sesion);
					$provision->Edit('id_moneda', $documento_pago->fields['id_moneda']);
					$provision->Edit('ingreso', $pago_gastos);
					$provision->Edit('monto_cobrable', $pago_gastos);
					$provision->Edit('id_cobro', $id_cobro);
					$provision->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
					$provision->Edit('id_usuario_orden', $this->sesion->usuario->fields['id_usuario']);
					$provision->Edit('codigo_cliente', $documento_pago->fields['codigo_cliente']);
					$provision->Edit('numero_documento', $this->fields['id_documento_pago']);

					$query_gastos = "SELECT cta_corriente.codigo_asunto FROM cta_corriente
											WHERE (cta_corriente.id_cobro = '$id_cobro') LIMIT 1 ";
					$resp = mysql_query($query_gastos, $this->sesion->dbh) or Utiles::errorSQL($query_gastos, __FILE__, __LINE__, $this->sesion->dbh);
					list($codigo_asunto) = mysql_fetch_array($resp);
					if ($codigo_asunto) {
						$provision->Edit('codigo_asunto', $codigo_asunto);
					} else {
						$provision->Edit('codigo_asunto', 'NULL');
					}

					if ($id_cobro) {
						$provision->Edit('descripcion', "Pago de Gastos de Cobro #" . $id_cobro . " por Documento #" . $documento_pago->fields['id_documento']);
					} else {
						$provision->Edit('descripcion', "Pago de Gastos por Documento #" . $documento_pago->fields['id_documento'] . " para documento de cobro externo");
					}
					$provision->Edit('id_neteo_documento', $this->fields['id_neteo_documento'], true);
					$provision->Edit('incluir_en_cobro', 'NO');
					$provision->Edit('fecha', date('Y-m-d H:i:s'));
					$provision->Write();
				}

				if (Conf::GetConf($this->sesion, 'NuevoModuloFactura') && !empty($documento_pago->fields['es_adelanto'])) {
					$factura_pago = new FacturaPago($this->sesion);
					$factura_pago->LoadByNeteoAdelanto($this->fields[$this->campo_id]);

					if (!$factura_pago->Id()) {
						$query = "SELECT id_concepto FROM prm_factura_pago_concepto WHERE glosa = 'Adelanto'";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
						list($id_concepto) = mysql_fetch_array($resp);

						$factura_pago->Edit('id_concepto', $id_concepto);
						$factura_pago->Edit('fecha', date('Y-m-d H:i:s'));
						$factura_pago->Edit('id_neteo_documento_adelanto', $this->fields['id_neteo_documento']);

						//copiar del adelanto
						$factura_pago->Edit('codigo_cliente', $documento_pago->fields['codigo_cliente']);
						$factura_pago->Edit('tipo_doc', $documento_pago->fields['tipo_doc']);
						$factura_pago->Edit('nro_documento', $documento_pago->fields['numero_doc']);
						$factura_pago->Edit('nro_cheque', $documento_pago->fields['numero_cheque']);
						$factura_pago->Edit('descripcion', 'Adelanto #' . $documento_pago->fields['id_documento'] . ' - ' . $documento_pago->fields['glosa_documento']);
						$factura_pago->Edit('id_banco', $documento_pago->fields['id_banco']);
						$factura_pago->Edit('id_cuenta', $documento_pago->fields['id_cuenta']);
						$factura_pago->Edit('pago_retencion', $documento_pago->fields['pago_retencion']);
					}
					$factura_pago->Edit('id_moneda', $documento_pago->fields['id_moneda']);
					$factura_pago->Edit('monto', $moneda_pago->getFloat($pago_gastos + $pago_honorarios));
					$factura_pago->Edit('id_moneda_cobro', $documento_cobro->fields['id_moneda']);
					$factura_pago->Edit('monto_moneda_cobro', $moneda_cobro->getFloat($pago_gastos_noformat * $tasa + $pago_honorarios_noformat * $tasa));

					//agregarle columnas saldo_gastos y saldo_honorarios al factura_pago?
					$nueva = !$factura_pago->Id();

					if ($factura_pago->Write()) {
						$ccf = new CtaCteFact($this->sesion);
						$neteos = array();

						if (!$nueva && is_array($pagar_facturas) && !$rehacer_neteos) {
							$query = "SELECT f.id_factura, neteo.monto
								FROM cta_cte_fact_mvto_neteo AS neteo
									JOIN cta_cte_fact_mvto AS f ON f.id_cta_cte_mvto = neteo.id_mvto_deuda
										JOIN cta_cte_fact_mvto AS fp ON fp.id_cta_cte_mvto = neteo.id_mvto_pago
								WHERE fp.id_factura_pago = " . $factura_pago->Id();

							$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
							while (list($id_factura, $neteo) = mysql_fetch_array($resp)) {
								$neteos[] = array($id_factura, $neteo);
							}
						}

						if ($pagar_facturas && ($nueva || is_array($pagar_facturas))) {

							$saldo_pago = (float) $pago_gastos_noformat + (float) $pago_honorarios_noformat;
							$saldo_pago = $tasa * $saldo_pago;
							foreach ($pagar_facturas as $id_factura => $saldo_factura) {
								$monto = min($saldo_pago, $saldo_factura);
								$saldo_pago -= $monto;
								$neteos[] = array($id_factura, $monto);
								if ($saldo_pago <= 0) {
									break;
								}
							}

							if (is_array($pagar_facturas)) {
								$factura_pago->Edit('monto', $factura_pago->fields['monto'] + $monto);
							}
						}
						$pagina_fake = ''; //la belleza del TT
						$ccf->IngresarPago($factura_pago, $neteos, $id_cobro, $pagina_fake, '', '', empty($neteos), $rehacer_neteos);
					}
				}
			}
		}
		return $out;
	}

}

