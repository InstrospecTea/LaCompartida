<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once 'Cliente.php';
require_once 'Asunto.php';

class Gasto extends Objeto {

	function Gasto($sesion, $fields = "", $params = "") {
		$this->tabla = "cta_corriente";
		$this->campo_id = "id_movimiento";
		#$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function Check() {
		# Los gastos dependiendo de si son generales o no, van a diferentes tablas. 
		# La tabla por defecto es cta_corriente
		# Además a los gastos asociados a un asunto se les calcula un monto descontado que es con la tasa de cambio del dia en que se anoto. Esto es para que no cambie el monto que se descuenta si es que cambia la tasa.
		if ($this->changes[general] == 1) {
			$this->tabla = "gasto_general";
			$this->campo_id = "id_gasto_general";
			unset($this->changes[general]);
		} else {
#			$this->tabla = "";
#			$this->campo_id = "id_gasto";

			if ($this->fields[id_moneda] > 0) {
				$query = "SELECT tipo_cambio FROM prm_moneda WHERE id_moneda = " . $this->fields[id_moneda];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($tasa) = mysql_fetch_array($resp);
			}

#			$monto = $this->fields[ingreso] * $tasa;
#			$asunto = new Asunto($this->sesion);
#			$asunto->LoadByCodigo($this->fields[codigo_asunto]);
#			$cliente = new Cliente($this->sesion);
#			$cliente->LoadByCodigo($asunto->fields[codigo_cliente]);
#			if($cliente)
#				$cuenta_corriente = $cliente->TotalCuentaCorriente();
#            $this->Edit("ingreso_descontado",$monto);

			unset($this->changes['general']);
		}

		return true;
	}

	function Load($id) {
		$this->Check();
		return Objeto::Load($id);
	}

	function Write() {
		if ($this->Loaded()) {
			$query = "SELECT fecha, codigo_cliente, codigo_asunto, egreso, ingreso, monto_cobrable, descripcion, id_moneda  
					FROM cta_corriente WHERE id_movimiento = " . $this->fields['id_movimiento'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($fecha, $codigo_cliente, $codigo_asunto, $egreso, $ingreso, $monto_cobrable, $descripcion, $id_moneda) = mysql_fetch_array($resp);
			
			if ($this->fields['egreso'] > 0) {
				$query_tipo_ingreso = $this->fields['egreso'];
				$query_valor_ingreso = $egreso;
			} else if ($this->fields['ingreso'] > 0) {
				$query_tipo_ingreso = $this->fields['ingreso'];
				$query_valor_ingreso = $ingreso;
			}
			
			$query = "INSERT INTO gasto_historial 
						( id_movimiento, fecha, id_usuario, accion, fecha_movimiento, fecha_movimiento_modificado, codigo_cliente, codigo_cliente_modificado, codigo_asunto, codigo_asunto_modificado, ingreso, ingreso_modificado, monto_cobrable, monto_cobrable_modificado, descripcion, descripcion_modificado, id_moneda, id_moneda_modificado) 
					VALUES( " . $this->fields['id_movimiento'] . ", NOW(), '" . $this->sesion->usuario->fields['id_usuario'] . "', 'MODIFICAR', '" . $fecha . "', '" . $this->fields['fecha'] . "', '" . $codigo_cliente . "', '" . $this->fields['codigo_cliente'] . "', '" . $codigo_asunto . "', '" . $this->fields['codigo_asunto'] . "', '" . $query_valor_ingreso . "', '" . $query_tipo_ingreso . "', '" . $monto_cobrable . "', '" . $this->fields['monto_cobrable'] . "', '" . addslashes($descripcion) . "', '" . addslashes($this->fields['descripcion']) . "', " . $id_moneda . ", " . $this->fields['id_moneda'] . ")";
		} else {
			$query = "SELECT MAX(id_movimiento) FROM cta_corriente";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_movimiento) = mysql_fetch_array($resp);
			$id_movimiento++;
			
			if ($this->fields['egreso'] > 0) {
				$query_tipo_ingreso = $this->fields['egreso'];
			} else if ($this->fields['ingreso'] > 0) {
				$query_tipo_ingreso = $this->fields['ingreso'];
			}
			
			$query = "INSERT INTO gasto_historial 
						( id_movimiento, fecha, id_usuario, accion, fecha_movimiento_modificado, codigo_cliente_modificado, codigo_asunto_modificado, ingreso_modificado, monto_cobrable_modificado, descripcion_modificado, id_moneda_modificado)
					VALUES( " . $id_movimiento . ", NOW(), '" . $this->sesion->usuario->fields['id_usuario'] . "', 'CREAR', '" . $this->fields['fecha'] . "', '" . $this->fields['codigo_cliente'] . "', '" . $this->fields['codigo_asunto'] . "','" . $query_tipo_ingreso . "', '" . $this->fields['monto_cobrable'] . "', '" . addslashes($this->fields['descripcion']) . "', " . $this->fields['id_moneda'] . ")";
		}
		if (parent::Write()) {
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			return true;
		}
		return false;
	}

	function Eliminar() {
		if ($this->Loaded()) {
			$query = "DELETE FROM cta_corriente WHERE id_movimiento=" . $this->fields['id_movimiento'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			if ($resp) {
				if ($this->fields['egreso'] > 0) {
					$query_tipo_ingreso = $this->fields['egreso'];
				} else if ($this->fields['ingreso'] > 0) {
					$query_tipo_ingreso = $this->fields['ingreso'];
				}
				
				$query = "INSERT INTO gasto_historial
								( id_movimiento, fecha, accion, id_usuario, fecha_movimiento, codigo_cliente, codigo_asunto, ingreso, monto_cobrable, descripcion, id_moneda)
							VALUES( " . $this->fields['id_movimiento'] . ", NOW(), 'ELIMINAR', " . $this->sesion->usuario->fields['id_usuario'] . ", '" . $this->fields['fecha'] . "', '" . $this->fields['codigo_cliente'] . "', '" . $this->fields['codigo_asunto'] . "', '" . $query_tipo_ingreso . "', '" . $this->fields['monto_cobrable'] . "', '" . addslashes($this->fields['descripcion']) . "', " . $this->fields['id_moneda'] . ")";
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
		else
			return false;

		return true;
	}

	/*
	  Guarda los datos de pago de los gastos cuando en paso 6 cobro se chequea como pagados.
	 */

	function GuardaPagoGastosDelCobro($id_cobro, $fecha_pago, $documento_pago, $id) {
		#Actualiza los egresos segun sus datos
		$query = "UPDATE cta_corriente SET fecha_pago = '$fecha_pago', documento_pago = '$documento_pago', monto_pago = egreso, pagado = 1, id_movimiento_pago = '$id'
				WHERE id_cobro = '$id_cobro' AND id_movimiento_pago IS NULL";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/*
	  Elimina Ingreso desde un gasto asociado, verificando que no existan otros gastos asociados a el.
	 */

	function EliminaIngreso($id_gasto) {
		$query = "SELECT COUNT(*) FROM cta_corriente WHERE id_movimiento_pago = '" . $this->fields[id_movimiento] . "' 
					AND id_movimiento != '$id_gasto'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cont) = mysql_fetch_array($resp);
		if ($cont > 0) {
			return false;
		} else {
			$query = "DELETE FROM cta_corriente WHERE id_movimiento = '" . $this->fields[id_movimiento] . "' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			return true;
		}
	}

}

#end Class

class ListaGastos extends Lista {

	function ListaGastos($sesion, $params, $query) {
		$this->Lista($sesion, 'Gasto', $params, $query);
	}

}
