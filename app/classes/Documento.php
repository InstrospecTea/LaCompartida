<?php

require_once dirname(__FILE__) . '/../conf.php';

class Documento extends Objeto {

	public $editable_fields=array();


	function __construct($sesion, $fields = "", $params = "") {
		$this->tabla = "documento";
		$this->campo_id = "id_documento";
		#$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;

		$describe=$this->sesion->pdodbh->query( "SHOW COLUMNS FROM   {$this->tabla}");
		$this->editable_fields=$describe->fetchALL(PDO::FETCH_COLUMN,0 );
		unset($this->editable_fields[array_search($this->campo_id,$this->editable_fields)]);

	}

	function LoadByCobro($id_cobro, $fields = null) {
		$query = "SELECT id_documento FROM documento WHERE id_cobro = '$id_cobro' AND tipo_doc='N';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if ($id) {
			return $this->Load($id, $fields);
		}
		return false;
	}

	function AnularMontos() {
		$this->EliminarNeteos();
		$anular = array(
			'subtotal_honorarios',
			'subtotal_gastos',
			'descuento_honorarios',
			'subtotal_sin_descuento',
			'honorarios',
			'saldo_honorarios',
			'monto',
			'gastos',
			'saldo_gastos',
			'monto_base',
			'impuesto',
			'saldo_pago'
		);
		foreach ($anular as $a) {
			$this->Edit($a, '0');
		}
		$this->Edit('honorarios_pagados', 'NO');
		$this->Edit('gastos_pagados', 'NO');
		$this->Write();
	}

	function BorrarDocumentoMoneda() {
		$query = "DELETE FROM documento_moneda WHERE id_documento = '" . $this->fields['id_documento'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
	}

	function ActualizarDocumentoMoneda($tipo_cambio = array()) {
		$query = "DELETE FROM documento_moneda WHERE id_documento = '" . $this->fields['id_documento'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if (empty($tipo_cambio)) {
			$query = "INSERT INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
					SELECT '" . $this->fields['id_documento'] . "', id_moneda, tipo_cambio
					FROM prm_moneda WHERE 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		} else {
			foreach ($tipo_cambio as $id_moneda => $tc) {
				$query = "INSERT INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
					VALUES (" . $this->fields['id_documento'] . "," . $id_moneda . "," . $tc . ");";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
	}

	function TipoCambioDocumento(& $sesion, $id_documento, $id_moneda) {
		$query = "SELECT tipo_cambio FROM documento_moneda WHERE id_documento = '$id_documento' AND id_moneda = '$id_moneda' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($tc) = mysql_fetch_array($resp);

		return $tc;
	}

	function IngresoDocumentoPago($pagina, $id_cobro, $codigo_cliente, $monto, $id_moneda, $tipo_doc,
		$numero_doc = "", $fecha = '', $glosa_documento = "", $id_banco = "", $id_cuenta = "",
		$numero_operacion = "", $numero_cheque = "", $ids_monedas_documento = array(),
		$tipo_cambios_documento = array(), $arreglo_pagos_detalle = array(), $id_factura_pago = null,
		$adelanto = null, $pago_honorarios = null, $pago_gastos = null, $usando_adelanto = false,
		$id_contrato = null, $pagar_facturas = false, $id_usuario_ingresa = null, $id_usuario_orden = null,
		$id_solicitud_adelanto = null, $codigo_asunto = null) {

		list($dtemp, $mtemp, $atemp) = explode("-", $fecha);
		if (strlen($dtemp) == 2) {
			$fecha = Utiles::fecha2sql($fecha);
		}

		$query = "SELECT activo FROM cliente WHERE codigo_cliente='" . $codigo_cliente . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($activo) = mysql_fetch_array($resp);

		$monto = str_replace(',', '.', $monto);

		/* Es pago, asi que monto es negativo */
		$multiplicador = -1.0;
		$moneda = new Moneda($this->sesion);
		$moneda->Load($id_moneda);
		$moneda_base = Utiles::MonedaBase($this->sesion);
		$monto_base = $monto * $moneda->fields['tipo_cambio'] / $moneda_base['tipo_cambio'];
		$out_neteos = "";

		if ($usando_adelanto) {
			$id_documento = $this->fields['id_documento'];
			//resetea el saldo y aplica los neteos q lo recalculan

			$this->Edit("saldo_pago", $moneda->getFloat($this->fields['monto'], false));
			if ($this->Write()) {
				$this->AgregarNeteos($id_documento, $arreglo_pagos_detalle, $id_moneda, $moneda, $out_neteos, $pagar_facturas);
			}
		} else {

			$this->Edit("monto_base", number_format($monto_base * $multiplicador, max(2,$moneda_base['cifras_decimales']), ".", ""));

			$this->Edit("monto", $moneda->getFloat($monto * $multiplicador, false));

			$query = "SELECT SUM(valor_pago_honorarios + valor_pago_gastos) total
						FROM  neteo_documento
						WHERE id_documento_pago = '{$this->fields[$this->campo_id]}'
						GROUP BY id_documento_pago";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			$neteo = mysql_fetch_assoc($resp);
			if (!empty($neteo['total'])) {
				$this->Edit('saldo_pago', $moneda->getFloat($this->fields['monto'] + $neteo['total'], false));
			} else {
				$this->Edit('saldo_pago', $moneda->getFloat($this->fields['monto'], false));
			}

			if ($id_cobro) {
				$this->Edit("id_cobro", $id_cobro);
			}
			$this->Edit('tipo_doc', $tipo_doc);
			$this->Edit("numero_doc", $numero_doc?$numero_doc:"");
			$this->Edit("id_moneda", $id_moneda);
			$this->Edit("fecha", $fecha);
			$this->Edit("glosa_documento", $glosa_documento);
			$this->Edit("codigo_cliente", $codigo_cliente);
			$this->Edit("id_banco", $id_banco);
			$this->Edit("id_cuenta", $id_cuenta);
			$this->Edit("numero_operacion", $numero_operacion);
			$this->Edit("numero_cheque", $numero_cheque);
			$this->Edit("id_factura_pago", $id_factura_pago ? $id_factura_pago : "NULL" );

			if ($pago_retencion) {
				$this->Edit("pago_retencion", "1");
			}

			$this->Edit("es_adelanto", empty($adelanto) ? '0' : '1');
			if (!empty($adelanto)) {
				$this->Edit("id_contrato", $id_contrato);

				$query = "SELECT id_contrato FROM asunto WHERE '".$codigo_asunto."'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($id_contrato) = mysql_fetch_array($resp);

				if(empty($id_contrato) || $id_contrato == 'NULL'){
					$codigo_asunto = 'NULL';
				} else if(empty($codigo_asunto)) {
					$query = "SELECT codigo_asunto FROM asunto
						WHERE id_contrato = '$id_contrato'
						AND codigo_cliente = '$codigo_cliente'
						LIMIT 1";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					list($codigo_asunto) = mysql_fetch_array($resp);
				}


				$this->Edit('codigo_asunto', $codigo_asunto);
			}
			$this->Edit("pago_honorarios", empty($pago_honorarios) ? '0' : '1');
			$this->Edit("pago_gastos", empty($pago_gastos) ? '0' : '1');

			if(!empty($id_solicitud_adelanto) && $id_solicitud_adelanto!='') {
				$this->Edit('id_solicitud_adelanto', $id_solicitud_adelanto);
				if(!in_array('id_solicitud_adelanto',$this->editable_fields)) {
					$this->sesion->pdodbh->exec("alter table {$this->tabla} add `id_solicitud_adelanto` int(11) unsigned NOT NULL");
				}
			}
			if ($this->Write()) {
				$id_documento = $this->fields['id_documento'];
				$ids_monedas = explode(',', $ids_monedas_documento);
				$tipo_cambios = is_array($tipo_cambios_documento)? $tipo_cambios_documento : explode(',', $tipo_cambios_documento);
				$tipo_cambio = array();
				foreach ($tipo_cambios as $key => $tc) {
					$tipo_cambio[$ids_monedas[$key]] = $tc;
				}

				$primer_tipo_cambio = reset($tipo_cambio);
				if (!empty($primer_tipo_cambio)) {
					$this->ActualizarDocumentoMoneda($tipo_cambio);
				}
				$msg = empty($adelanto) ? __('Pago ingresado con �xito') : __('Adelanto ingresado con �xito');

					$pagina->addInfo($msg);



				$this->AgregarNeteos($id_documento, $arreglo_pagos_detalle, $id_moneda, $moneda, $out_neteos);
				 $this->Write();

			} else {
				 throw new Exception($this->error);
			}

		}

		$out_neteos = "<table border=1><tr> <td>Id Cobro</td><td>Faltaba</td> <td>Aportaba y Devolv�</td> <td>Pas� a Faltar</td> <td>Ahora aporto</td> <td>Ahora falta </td> </tr>" . $out_neteos . "</table>";
		//echo $out_neteos;
	/*echo '<pre>';
			print_r($this->fields);
			echo '</pre>';*/

		return $id_documento;
	}

	/**
	 * Prepara el objeto para ser guardado, corregir fechas, definir defaults, etc
	 */
	public function Prepare() {
		if (empty($this->fields['codigo_cliente'])) {
			$this->Edit("codigo_cliente", 'NULL');
		}
		if (array_key_exists('id_usuario_ingresa', $this->fields) && empty($this->fields['id_usuario_ingresa'])) {
			$this->Edit("id_usuario_ingresa", 'NULL');
		}
		if (array_key_exists('id_usuario_orden', $this->fields) && empty($this->fields['id_usuario_orden'])) {
			$this->Edit("id_usuario_orden", 'NULL');
		}
		if (array_key_exists('id_solicitud_adelanto', $this->fields) && empty($this->fields['id_solicitud_adelanto'])) {
			$this->Edit("id_solicitud_adelanto", 'NULL');
		}
			$this->Edit("id_contrato", empty($this->fields['id_contrato']) ? 'NULL' : $this->fields['id_contrato']);

		if(!$this->Loaded()) {
			$this->Edit("saldo_pago", $this->fields['monto']);
		}
	}

	function AgregarNeteos($id_documento, $arreglo_pagos_detalle, $id_moneda, $moneda, &$out_neteos, $pagar_facturas = false, $rehacer_neteos = false) {
		//Si se ingresa el documento, se ingresan los pagos
		foreach ($arreglo_pagos_detalle as $key => $data) {
			$moneda_documento_cobro = new Moneda($this->sesion);
			$moneda_documento_cobro->Load($data['id_moneda']);

			// Guardo los saldos, para indicar cuales fueron actualizados
			$id_cobro_neteado = $data['id_cobro'];
			$documento_cobro_aux = new Documento($this->sesion);
			if ($documento_cobro_aux->LoadByCobro($id_cobro_neteado)) {
				$saldo_honorarios_anterior = $documento_cobro_aux->fields['saldo_honorarios'];
				$saldo_gastos_anterior = $documento_cobro_aux->fields['saldo_gastos'];
			}

			$id_documento_cobro = $documento_cobro_aux->fields['id_documento'];
			$pago_honorarios = $data['monto_honorarios'];
			$pago_gastos = $data['monto_gastos'];
			$cambio_pago = $moneda->fields['tipo_cambio'];
			$cambio_cobro = $cambio_pago;
			if ($documento_cobro_aux->fields['id_moneda'] != $moneda->fields['id_moneda']) {
				$moneda_actual_cobro = new Moneda($this->sesion);
				$moneda_actual_cobro->Load($documento_cobro_aux->fields['id_moneda']);
				$cambio_cobro = $moneda_actual_cobro->fields['tipo_cambio'];
			}
			$decimales_cobro = $moneda_documento_cobro->fields['cifras_decimales'];
			$decimales_pago = $moneda->fields['cifras_decimales'];

			if (!$pago_gastos) {
				$pago_gastos = 0;
			}
			if (!$pago_honorarios) {
				$pago_honorarios = 0;
			}

			$neteo_documento = new NeteoDocumento($this->sesion);
			//Si el neteo exist�a, est� siendo modificado y se debe partir de 0:
			if ($neteo_documento->Ids($id_documento, $id_documento_cobro)) {
				if (!is_array($pagar_facturas)) {
					$out_neteos .= $neteo_documento->Reestablecer();
				}
			} else {
				$out_neteos .= "<tr><td>No</td><td>0</td><td>0</td>";
			}

			//Luego se modifica
			if ($pago_honorarios != 0 || $pago_gastos != 0) {
				$out_neteos .= $neteo_documento->Escribir($pago_honorarios, $pago_gastos, $cambio_pago, $cambio_cobro, $decimales_pago, $decimales_cobro, $id_cobro_neteado, $pagar_facturas, $rehacer_neteos);
			}

			/* Compruebo cambios en saldos para mostrar mensajes de actualizacion */
			$documento_cobro_aux = new Documento($this->sesion);
			if ($documento_cobro_aux->Load($id_documento_cobro)) {
				if ($saldo_honorarios_anterior != $documento_cobro_aux->fields['saldo_honorarios']) {
					$cambios_en_saldo_honorarios[] = $id_documento_cobro;
				}
				if ($saldo_gastos_anterior != $documento_cobro_aux->fields['saldo_gastos']) {
					$cambios_en_saldo_gastos[] = $id_documento_cobro;
				}

				$cobro_neteado = new Cobro($this->sesion);
				$cobro_neteado->Load($id_cobro_neteado);
				$cobro_neteado->CambiarEstadoSegunFacturas();
			}
		}

		$this->RecalcularSaldoPago($id_documento);
	}

	function RecalcularSaldoPago($id_documento) {
		$documento = new Documento($this->sesion);
		$documento->Load($id_documento);
		$query = "SELECT SUM(valor_pago_honorarios) + SUM(valor_pago_gastos)
			FROM neteo_documento
			WHERE id_documento_pago = $id_documento";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($suma) = mysql_fetch_array($resp);
		$documento->Edit('saldo_pago', number_format($documento->fields['monto'] + $suma, 6, '.', ''));
		return $documento->Write();
	}

	/**
	 * Devuelve los gastos y honorarios descontando los pagos realizados
	 * @return array
	 */
	function calcularSaldosCobro() {
		$query = "SELECT
			D.honorarios - IFNULL(SUM(ND.valor_cobro_honorarios), 0) AS saldo_honorarios,
			D.gastos - IFNULL(SUM(ND.valor_cobro_gastos), 0) AS saldo_gastos
			FROM documento AS D
			LEFT JOIN neteo_documento AS ND ON ND.id_documento_cobro = D.id_documento
			WHERE D.id_documento = {$this->fields['id_documento']}
			GROUP BY D.id_documento";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return mysql_fetch_assoc($resp);
	}

	function EliminarNeteos() {
		$neteo_documento = new NeteoDocumento($this->sesion);
		$query = "SELECT neteo_documento.id_neteo_documento AS id
					FROM neteo_documento
					WHERE neteo_documento.id_documento_pago = '" . $this->fields['id_documento'] . "'
					OR neteo_documento.id_documento_cobro = '" . $this->fields['id_documento'] . "';";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while (list($id) = mysql_fetch_array($resp)) {
			if ($neteo_documento->Load($id)) {
				//No importan los decimales
				$neteo_documento->Reestablecer(2);
				$neteo_documento->Delete();
			}
		}
	}

	function EliminarNeteo($id_cobro) {
		$neteo_documento = new NeteoDocumento($this->sesion);
		$query = "SELECT neteo_documento.id_neteo_documento AS id
					FROM neteo_documento
					JOIN documento ON neteo_documento.id_documento_cobro = documento.id_documento
					WHERE neteo_documento.id_documento_pago = '" . $this->fields['id_documento'] . "'
					AND documento.id_cobro = '" . $id_cobro . "';";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while (list($id) = mysql_fetch_array($resp)) {
			if ($neteo_documento->Load($id)) {
				//No importan los decimales
				$neteo_documento->Reestablecer(2);
				$neteo_documento->Delete();
			}
		}
	}

	function ObtenerIdNeteo($id_cobro) {
		$query = "SELECT neteo_documento.id_neteo_documento AS id
					FROM neteo_documento
					JOIN documento ON neteo_documento.id_documento_cobro = documento.id_documento
					WHERE neteo_documento.id_documento_pago = '" . $this->fields['id_documento'] . "'
					AND documento.id_cobro = '" . $id_cobro . "';";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		list($id) = mysql_fetch_array($resp);
		return $id;
	}

	function MontoUsadoAdelanto($id_cobro = null) {
		$monto = 0;

		if (Conf::GetConf($this->sesion, 'NuevoModuloFactura')) {
			$query = "SELECT
					ccfm.monto_bruto - ccfm.saldo AS monto
				FROM cta_cte_fact_mvto ccfm
				INNER JOIN factura_pago fp ON fp.id_factura_pago = ccfm.id_factura_pago
				INNER JOIN neteo_documento nd ON nd.id_neteo_documento = fp.id_neteo_documento_adelanto
				INNER JOIN documento dc ON nd.id_documento_cobro = dc.id_documento
				WHERE nd.id_documento_pago = '{$this->fields['id_documento']}'
					AND dc.id_cobro = '{$id_cobro}'";
		} else {
			$query = "SELECT
					neteo_documento.valor_pago_honorarios + neteo_documento.valor_pago_gastos AS monto
				FROM neteo_documento
				INNER JOIN documento ON documento.id_documento = neteo_documento.id_documento_cobro
				WHERE
					documento.id_cobro = '{$id_cobro}'
					AND neteo_documento.id_documento_pago = '{$this->fields['id_documento']}'";
		}

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($resp);

		return $monto;
	}

	function EliminarDocumentoMoneda() {
		$query = "DELETE FROM documento_moneda
					WHERE id_documento = '" . $this->fields['id_documento'] . "';";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
	}

	function mayor_fecha($fecha1, $fecha2) {
		$f1 = explode('-', $fecha1);
		$f1 = mktime(0, 0, 0, $f1[1], $f1[2], $f1[0]);

		$f2 = explode('-', $fecha2);
		$f2 = mktime(0, 0, 0, $f2[1], $f2[2], $f2[0]);

		if ($f1 > $f2) {
			return $fecha1;
		}
		return $fecha2;
	}

	function FechaPagos() {
		$max_fecha = '';
		$query = "SELECT documento.fecha
					FROM neteo_documento
					JOIN documento ON (neteo_documento.id_documento_pago = documento.id_documento)
					WHERE neteo_documento.id_documento_cobro = '" . $this->fields['id_documento'] . "';";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while (list($fecha) = mysql_fetch_array($resp)) {
			if ($fecha) {
				if ($max_fecha == '') {
					$max_fecha = $fecha;
				} else {
					$max_fecha = $this->mayor_fecha($fecha, $max_fecha);
				}
			}
		}
		return $max_fecha;
	}

	function ListaPagos() {
		$out = '';
		$query = "SELECT neteo_documento.id_documento_pago AS id, valor_cobro_honorarios as honorarios, valor_cobro_gastos as gastos, pago_retencion, es_adelanto
					FROM neteo_documento
					JOIN documento ON documento.id_documento=neteo_documento.id_documento_pago
					WHERE neteo_documento.id_documento_cobro ='" . $this->fields['id_documento'] . "'
				UNION
					SELECT id_documento AS id, honorarios, subtotal_gastos AS gastos, pago_retencion, es_adelanto
					FROM documento left join neteo_documento on  documento.id_documento=neteo_documento.id_documento_pago
					WHERE  tipo_doc !=  'N'
					AND neteo_documento.id_neteo_documento IS NULL
					AND id_cobro ='" . $this->fields['id_cobro'] . "'";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$Form = new Form();
		$Html = &$Form->Html;
		while (list($id, $honorarios, $gastos, $pago_retencion, $es_adelanto) = mysql_fetch_array($resp)) {
			if (!$id) {
				continue;
			}
			if ($honorarios != 0) {
				$honorarios = 'Honorarios: ' . $honorarios;
			} else {
				$honorarios = '';
			}
			if ($gastos != 0) {
				$gastos = 'Gastos: ' . $gastos;
			} else {
				$gastos = '';
			}

			$nombre = (empty($es_adelanto) ? __('Documento #') : __('Adelanto #')) . $id;
			$td1_style = 'white-space: nowrap; text-align: left;';
			$td2_style = 'color: #333333; font-size: 10px; text-align: right;';
			$link_style = 'color: blue; font-size: 11px;';

			/**
			 * Si el documento no tiene honorarios ni gastos, es un documento sin neteo
			 * y se debe poder eliminar por el SADM
			 */
			$eliminar_documento = empty($honorarios) && empty($gastos) && $this->sesion->usuario->Es('SADM');
			if (!Conf::GetConf($this->sesion, 'NuevoModuloFactura') || $eliminar_documento) {
				$nombre = $Html->link($nombre, 'javascript:void(0)', array('onclick' => "EditarPago($id)", 'style' => $link_style));
				$btn_link = $Form->image_link('cruz_roja.gif', false, array('onclick' => "EliminaDocumento($id)", 'target' => '_parent', 'title' => 'Eliminar'));
				$out .= $Html->tag('tr',
						$Html->tag('td', $nombre, array('style' => $td1_style)) .
						$Html->tag('td', "$honorarios $gastos", array('style' => $td2_style)) .
						$Html->tag('td', $btn_link)
				);
			} else {
				$out .= $Html->tag('tr',
						$Html->tag('td', $nombre, array('style' => $td1_style)) .
						$Html->tag('td', "$honorarios $gastos", array('style' => $td2_style)) .
						$Html->tag('td')
				);
			}
			if ($pago_retencion) {
				$out .= $Html->tag('tr', $Html->tag('td', '( Pago retenci�n impuestos )', array('style' => $td1_style, 'colspan' => 3)));
			}
		}
		return $out;
	}

	function tabla($filas) {
		echo "<table border=1> <tr>";
		echo "<th>ID</th>";
		echo "<th>Cobro</th>";
		echo "<th>Glosa</th>";
		echo "<th>Moneda</th>";
		echo "<th>Monto</th>";
		echo "<th>Honorarios</th>";
		echo "<th>Gastos</th>";
		echo "<th>Saldo H</th>";
		echo "<th>Saldo G</th>";
		echo "<th>Saldo P</th>";
		echo "<th>H P</th>";
		echo "<th>G P</th>";
		echo "</tr>";
		echo $filas;
		echo "</table>";
	}

	function tabla_neteos($filas) {
		echo "<table border=1> <tr>";
		echo "<th>ID</th>";
		echo "<th>ID doc cobro</th>";
		echo "<th>ID doc pago</th>";
		echo "<th>moneda_cobro</th>";
		echo "<th>cobro honorarios</th>";
		echo "<th>cobro gastos</th>";
		echo "<th>moneda_pago</th>";
		echo "<th>pago honorarios</th>";
		echo "<th>pago gastos</th>";
		echo "</tr>";
		echo $filas;
		echo "</table>";
	}

	function fakeWrite() {
		$out = "<tr>";
		$out .= "<td>" . $this->fields['id_documento'] . "</td>";
		$out .= "<td>" . $this->fields['id_cobro'] . "</td>";
		$out .= "<td>" . $this->fields['glosa_documento'] . "</td>";
		$out .= "<td>" . $this->fields['id_moneda'] . "</td>";
		$out .= "<td>" . $this->fields['monto'] . "</td>";
		$out .= "<td>" . $this->fields['honorarios'] . "</td>";
		$out .= "<td>" . $this->fields['gastos'] . "</td>";
		$out .= "<td>" . $this->fields['saldo_honorarios'] . "</td>";
		$out .= "<td>" . $this->fields['saldo_gastos'] . "</td>";
		$out .= "<td>" . $this->fields['saldo_pago'] . "</td>";
		$out .= "<td>" . $this->fields['honorarios_pagados'] . "</td>";
		$out .= "<td>" . $this->fields['gastos_pagados'] . "</td>";
		$out .= "</tr>";
		return $out;
	}

	//Actualiza la informaci�n de TODOS los Documentos de TODOS los Cobros Emitidos [Advertencia: Deja todos los pagos en 0]
	function ReiniciarDocumentos($sesion, $write = 0) {
		$out = '';
		$out_cobros = '';
		$out_neteos = '';

		$query = "SELECT cobro.id_cobro FROM cobro WHERE cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' AND cobro.estado IS NOT NULL";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		$out_cobros .= "<table border=1>";

		$out_cobros .= "<tr> <th> ID </th> <th> Estado </th> <th> Doc </th> <th> Moneda </th> <th> Honorarios </th> <th> Gastos </th> <th> Moneda Total </th> <th> Honorarios MT </th> <th> Gastos MT</th> <th>Pagado Hon</th> <th> Pagado Gas</th> <th> Doc Pago Hon </th> <th> Doc Pago Gas </th></tr> ";


		while (list($id_cobro) = mysql_fetch_array($resp)) {
			$cobro = new Cobro($sesion);
			$cobro_moneda = new CobroMoneda($sesion);
			$cobro_moneda->Load($id_cobro);


			$out_cobros .= "<tr> <td> $id_cobro </td>";

			if ($cobro->Load($id_cobro)) {

				$out_cobros .= "<td>" . $cobro->fields['estado'] . "</td>";
				$documento = new Documento($sesion);

				if ($documento->LoadByCobro($id_cobro)) {
					$out_cobros .= "<td>" . $documento->fields['id_documento'] . "</td>";
				} else {
					$out_cobros .= "<td>" . "NULL" . "</td>";
				}

				// GASTOS del Cobro
				$cobro_total_gastos = 0;

				$query = "SELECT SQL_CALC_FOUND_ROWS cta_corriente.descripcion, cta_corriente.fecha,cta_corriente.id_moneda,cta_corriente.egreso,cta_corriente.ingreso,cta_corriente.id_movimiento,cta_corriente.codigo_asunto
				FROM cta_corriente
				LEFT JOIN asunto USING(codigo_asunto)
				WHERE cta_corriente.id_cobro='" . $id_cobro . "' AND (egreso > 0 OR ingreso > 0) AND cta_corriente.incluir_en_cobro = 'SI'
				ORDER BY cta_corriente.fecha ASC";
				$lista_gastos = new ListaGastos($sesion, '', $query);

				for ($v = 0; $v < $lista_gastos->num; $v++) {
					$gasto = $lista_gastos->Get($v);

					//cobro_total_gastos en moneda cobro
					if ($gasto->fields['egreso'] > 0) {
						$cobro_total_gastos += $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] /
								$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
					} else if ($gasto->fields['ingreso'] > 0) {
						$cobro_total_gastos -= $gasto->fields['monto_cobrable'] * $cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio'] / $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
					}
				}

				if (( ( method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarImpuestoSeparado') ) ) && $cobro->fields['porcentaje_impuesto']) {
					$cobro_total_gastos *= (1 + $cobro->fields['porcentaje_impuesto'] / 100);
				}

				#HONORARIOS del cobro
				if ($cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'] != 0) {
					$aproximacion_monto = number_format($cobro->fields['monto'], $cobro_moneda->moneda[$cobro->fields['id_moneda']]['cifras_decimales'], '.', '');
					$cobro_total_honorarios = $aproximacion_monto * $cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'] /
							$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
				}


				$out_cobros .= "<td>" . $cobro->fields['id_moneda'] . "</td>";
				$out_cobros .= "<td>" . $cobro->fields['monto'] . "</td>";
				$out_cobros .= "<td>" . $cobro->fields['monto_gastos'] . "</td>";
				$out_cobros .= "<td>" . $cobro->fields['opc_moneda_total'] . "</td>";

				#Documento de Cobro

				$documento->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
				$documento->Edit('codigo_cliente', $cobro->fields['codigo_cliente']);
				$documento->Edit('id_cobro', $cobro->fields['id_cobro']);
				$documento->Edit('glosa_documento', "Documento de " . __('Cobro') . " #" . $cobro->fields['id_cobro']);

				$moneda_total = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
				$moneda_total->Load($cobro->fields['opc_moneda_total'] > 0 ? $cobro->fields['opc_moneda_total'] : 1);
				$decimales = $moneda_total->fields['cifras_decimales'];

				$moneda_base = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
				$moneda_base->Load($cobro->fields['id_moneda_base'] > 0 ? $cobro->fields['id_moneda_base'] : 1);
				$decimales_base = $moneda_base->fields['cifras_decimales'];

				$documento->Edit('monto', number_format(($cobro_total_honorarios + $cobro_total_gastos), $decimales, ".", ""));
				$documento->Edit('honorarios', number_format($cobro_total_honorarios, $decimales, ".", ""));
				$documento->Edit('gastos', number_format($cobro_total_gastos, $decimales, ".", ""));

				$cambio_cobro = $cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'];
				$cambio_base = $cobro_moneda->moneda[$cobro->fields['id_moneda_base']]['tipo_cambio'];

				$monto_base = ($cobro_total_honorarios + $cobro_total_gastos) * $cambio_cobro / $cambio_base;

				$documento->Edit('monto_base', number_format($monto_base, $decimales_base, ".", ""));

				$out_cobros .= "<td>" . $documento->fields['honorarios'] . "</td>";
				$out_cobros .= "<td>" . $documento->fields['gastos'] . "</td>";

				if ($cobro->fields['honorarios_pagados'] == 'SI' || $documento->fields['honorarios'] <= 0) {
					$documento->Edit('saldo_honorarios', '0');
					$documento->Edit('honorarios_pagados', 'SI');
				} else {
					$documento->Edit('saldo_honorarios', number_format($cobro_total_honorarios, $decimales, ".", ""));
					$documento->Edit('honorarios_pagados', 'NO');
				}

				if ($cobro->fields['gastos_pagados'] == 'SI' || $documento->fields['gastos'] <= 0) {
					$documento->Edit('saldo_gastos', '0');
					$documento->Edit('gastos_pagados', 'SI');
				} else {
					$documento->Edit('saldo_gastos', number_format($cobro_total_gastos, $decimales, ".", ""));
					$documento->Edit('gastos_pagados', 'NO');
				}

				$out_cobros .= "<td>" . $documento->fields['honorarios_pagados'] . "</td>";
				$out_cobros .= "<td>" . $cobro->fields['gastos_pagados'] . "</td>";

				# PAGOS
				$pago_honorarios = false;
				$pago_gastos = false;
				$monto_pago = 0;
				$monto_pago_base = 0;

				if ($cobro->fields['id_doc_pago_honorarios']) {
					$out_cobros .= "<td>" . $cobro->fields['id_doc_pago_honorarios'] . "</td>";
				} else if ($documento->fields['honorarios_pagados'] == 'SI' && $documento->fields['honorarios'] > 0) {
					$out_cobros .= "<td>" . "NUEVO" . "</td>";
					$pago_honorarios = true;
					$moneda_pago = $documento->fields['id_moneda'];
					$monto_pago += $documento->fields['honorarios'];

					$monto_pago_base += $documento->fields['honorarios'] * $cambio_cobro /
							$cambio_base;
				} else {
					$out_cobros .= "<td>" . "Null" . "</td>";
				}

				if ($cobro->fields['id_doc_pago_gastos']) {
					$out_cobros .= "<td>" . $cobro->fields['id_doc_pago_gastos'] . "</td>";
				} else if ($documento->fields['gastos_pagados'] == 'SI' && $documento->fields['gastos'] > 0) {
					$out_cobros .= "<td>" . "NUEVO" . "</td>";
					$pago_gastos = true;
					$moneda_pago = $documento->fields['id_moneda'];
					$monto_pago += $documento->fields['gastos'];

					$monto_pago_base += $documento->fields['gastos'] * $cambio_cobro /
							$cambio_base;
				} else {
					$out_cobros .= "<td>" . "Null" . "</td>";
				}

				if ($pago_honorarios || $pago_gastos) {
					$documento_pago = new Documento($sesion);
					$documento_pago->Edit('glosa_documento', "Documento de Pago para Cobro #" . $cobro->fields['id_cobro']);
					$documento_pago->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
					$documento_pago->Edit('codigo_cliente', $cobro->fields['codigo_cliente']);
					$documento_pago->Edit('tipo_doc', 'P');
					$documento_pago->Edit('monto', number_format($monto_pago * -1.0, $decimales, ".", ""));
					$documento_pago->Edit('monto_base', number_format($monto_pago_base * -1.0, $decimales, ".", ""));

					if ($write) {
						$documento_pago->Write();
						if ($pago_honorarios) {
							$cobro->Edit('id_doc_pago_honorarios', $documento_pago->fields['id_documento']);
						}
						if ($pago_gastos) {
							$cobro->Edit('id_doc_pago_gastos', $documento_pago->fields['id_documento']);
						}
						$cobro->Write();
					}
					$out .= $documento_pago->fakeWrite();
				}

				if ($write) {
					$documento->Write();
				}

				//NETEOS
				$neteo = new NeteoDocumento($sesion);
				if ($cobro->fields['id_doc_pago_honorarios']) {
					$doc_pago_honorarios = new Documento($sesion);
					$doc_pago_honorarios->Load($cobro->fields['id_doc_pago_honorarios']);

					$cambio_pago = $cobro_moneda->moneda[$doc_pago_honorarios->fields['id_moneda']]['tipo_cambio'];
					$out_neteos .= $neteo->NeteoCompleto($documento, $doc_pago_honorarios, 1, $cambio_cobro, $cambio_pago, $write);
				}

				if ($cobro->fields['id_doc_pago_gastos']) {
					$doc_pago_gastos = new Documento($sesion);
					$doc_pago_gastos->Load($cobro->fields['id_doc_pago_gastos']);

					$cambio_pago = $cobro_moneda->moneda[$doc_pago_gastos->fields['id_moneda']]['tipo_cambio'];
					$out_neteos .= $neteo->NeteoCompleto($documento, $doc_pago_gastos, 0, $cambio_cobro, $cambio_pago, $write);
				}

				$out_cobros .= "</tr>";

				$out .= $documento->fakeWrite();
			}
		}
		//echo $this->tabla($out);
		//echo $this->tabla_neteos($out_neteos);
	}

	function SumaPagos() {
		$query = "SELECT SUM( valor_cobro_honorarios ) FROM neteo_documento WHERE id_documento_cobro = '" . $this->fields['id_documento'] . "' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($suma_pagos) = mysql_fetch_array($resp);

		$moneda_doc = new Moneda($this->sesion);
		$moneda_doc->Load($this->fields['id_moneda']);

		$suma_pagos = number_format($suma_pagos, $moneda_doc->fields['cifras_decimales'], '.', '');

		if ($suma_pagos > 0) {
			return $suma_pagos;
		} else {
			return "0";
		}
	}

	function EliminarDesdeFacturaPago($id_factura_pago) {

		$query = "SELECT id_documento FROM documento WHERE id_factura_pago = '$id_factura_pago'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);

		if (!$id) {
			return false;
		}
		$this->Load($id);

		$this->EliminarNeteos();
		$query_p = "DELETE from cta_corriente WHERE cta_corriente.documento_pago = '" . $id . "' ";
		mysql_query($query_p, $this->sesion->dbh) or Utiles::errorSQL($query_p, __FILE__, __LINE__, $this->sesion->dbh);

		$query_id_cobro = "SELECT id_cobro FROM documento WHERE id_factura_pago = '$id_factura_pago'";
		$resp = mysql_query($query_id_cobro, $this->sesion->dbh) or Utiles::errorSQL($query_id_cobro, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_cobro_temp) = mysql_fetch_array($resp);

		if ($this->Delete()) {
			if ($id_cobro_temp) {
				$cobrotmp = new Cobro($this->sesion);
				$cobrotmp->Load($id_cobro_temp);
				if ($cobrotmp->Loaded()) {
					$cobrotmp->CambiarEstadoAnterior();
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Obtiene el saldo disponible de los adelantos.
	 * @param type $codigo_cliente
	 * @param type $id_contrato
	 * @param boolean $pago_honorarios indica ci se quiere pagar honorarios.
	 * @param boolean $pago_gastos indica si se quiere pagar gastos.
	 * @param type $id_moneda
	 * @param type $tipos_cambio
	 * @return string
	 */
	function SaldoAdelantosDisponibles($codigo_cliente, $id_contrato, $pago_honorarios, $pago_gastos, $id_moneda = null, $tipos_cambio = null) {
		$monedas = Moneda::GetMonedas($this->sesion, null, true);
		if (empty($tipos_cambio)) {
			$tipos_cambio = array();
			foreach ($monedas as $id => $moneda) { //uf:20000, us:500, idmoneda:us. adelanto de 100 uf -> us4000
				$tipos_cambio[$id] = $moneda['tipo_cambio'];
			}
		}
		$cambios = array();
		foreach ($tipos_cambio as $id => $cambio) {
			$cambios[$id] = $id_moneda ? $cambio / $tipos_cambio[$id_moneda] : $cambio;
		}
		$where_contrato = '';
		if ($id_contrato) {
			$where_contrato = " AND (id_contrato = '$id_contrato' OR id_contrato IS NULL) ";
		}
		$query = "SELECT saldo_pago, documento.id_moneda, prm_moneda.tipo_cambio
			FROM documento
			JOIN prm_moneda ON documento.id_moneda = prm_moneda.id_moneda
			WHERE es_adelanto = 1 AND codigo_cliente = '$codigo_cliente'
			$where_contrato AND saldo_pago < 0";
		if ($pago_honorarios) {
			$query.= ' AND pago_honorarios = 1';
		}
		if ($pago_gastos) {
			$query.= ' AND pago_gastos = 1';
		}

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$saldo = 0;
		while (list($saldo_pago, $moneda_pago, $tipo_cambio) = mysql_fetch_array($resp)) {
			if ($id_moneda) {
				$tipo_cambio = $cambios[$moneda_pago];
			}
			$saldo += -$saldo_pago * $tipo_cambio;
		}
		if (!$saldo) {
			return '';
		}
		if ($id_moneda) {
			return $monedas[$id_moneda]['simbolo'] . ' ' . number_format($saldo, 2);
		}
		return $saldo;
	}

	function GenerarPagosDesdeAdelantos($id_documento_cobro, $facturas = null, $id_adelanto = null, $rehacer_neteos = false) {
		$documento_cobro = new Documento($this->sesion);
		$documento_cobro->Load($id_documento_cobro);

		$codigo_cliente = $documento_cobro->fields['codigo_cliente'];
		$id_contrato = $documento_cobro->fields['id_contrato'];

		$honorarios = 0;
		$gastos = 0;
		if (empty($facturas)) {
			$honorarios = $documento_cobro->fields['honorarios'];
			$gastos = $documento_cobro->fields['gastos'];
		} else {
			foreach ($facturas as $id_factura => $total_factura) {
				$factura = new Factura($this->sesion);
				$factura->Load($id_factura);
				$factor = $total_factura / $factura->fields['total'];
				$h = $factura->fields['subtotal'] * (1 + $factura->fields['porcentaje_impuesto'] / 100);
				$honorarios += ($h * $factor);
				$gastos += ($factura->fields['total'] - $h) * $factor;
			}
		}

		if (!$honorarios && !$gastos) {
			return true;
		}

		$out_neteos = '';
		$moneda_adelanto = new Moneda($this->sesion);
		$moneda_cobro = new Moneda($this->sesion);
		$id_cobro = $documento_cobro->fields['id_cobro'];
		$moneda_cobro->Load($documento_cobro->fields['id_moneda']);

		$query = "SELECT id_documento, -saldo_pago, pago_honorarios, pago_gastos, documento.id_moneda
			FROM documento
			WHERE es_adelanto = 1 AND codigo_cliente = '$codigo_cliente' AND (id_contrato = '$id_contrato' OR id_contrato IS NULL) AND saldo_pago < 0";
		if ($honorarios == 0) {
			$query .= ' AND pago_gastos = 1';
		} else if ($gastos == 0) {
			$query .= ' AND pago_honorarios = 1';
		}
		if ($id_adelanto) {
			$query .= " AND id_documento = $id_adelanto";
		}
		$query .= ' ORDER BY pago_honorarios ASC, pago_gastos ASC, fecha_creacion ASC';
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		//tengo los adelantos del cliente con saldo positivo, primero los q solo pagan honorarios, despues los solo gastos, y despues los mixtos, cada grupo ordenado por fecha
		while (list($id_adelanto, $saldo_pago, $pago_honorarios, $pago_gastos, $id_moneda_adelanto) = mysql_fetch_array($resp)) {
			$moneda_adelanto->Load($id_moneda_adelanto);
			$honorarios_convertidos = $honorarios * $moneda_cobro->fields['tipo_cambio'] / $moneda_adelanto->fields['tipo_cambio'];
			$gastos_convertidos = $moneda_adelanto->getFloat($gastos * $moneda_cobro->fields['tipo_cambio'] / $moneda_adelanto->fields['tipo_cambio']);

			$monto_honorarios = 0;
			if ($honorarios > 0 && $pago_honorarios == 1) {
				$monto_honorarios = $moneda_adelanto->getFloat($rehacer_neteos ? $honorarios_convertidos : min($saldo_pago, $honorarios_convertidos));
				$saldo_pago -= $monto_honorarios;
				$honorarios_convertidos -= $monto_honorarios;
			}

			$monto_gastos = 0;
			if ($gastos > 0 && $pago_gastos == 1) {
				$monto_gastos = $moneda_adelanto->getFloat($rehacer_neteos ? $gastos_convertidos : min($saldo_pago, $gastos_convertidos));
				$saldo_pago -= $monto_gastos;
				$gastos_convertidos -= $monto_gastos;
			}
			$honorarios = $honorarios_convertidos * $moneda_adelanto->fields['tipo_cambio'] / $moneda_cobro->fields['tipo_cambio'];
			$gastos = $gastos_convertidos * $moneda_adelanto->fields['tipo_cambio'] / $moneda_cobro->fields['tipo_cambio'];

			if ($monto_honorarios > 0 || $monto_gastos > 0) {
				$neteos = array(array(
								'id_moneda' => $documento_cobro->fields['id_moneda'],
								'id_documento_cobro' => $id_documento_cobro,
								'monto_honorarios' => $monto_honorarios,
								'monto_gastos' => $monto_gastos,
								'id_cobro' => $id_cobro
				));

				$this->AgregarNeteos($id_adelanto, $neteos, $id_moneda_adelanto, $moneda_adelanto, $out_neteos, $facturas, $rehacer_neteos);
			}

			if ($gastos == 0 && $honorarios == 0) {
				break;
			}
		}

		$cobro = new Cobro($this->sesion);
		$cobro->Load($id_cobro);
		$cobro->CambiarEstadoSegunFacturas();
		return true;
	}

}
