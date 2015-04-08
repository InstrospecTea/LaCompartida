<?php

require_once dirname(__FILE__) . '/../conf.php';

class Tarifa extends Objeto {

	public static $llave_carga_masiva = 'glosa_tarifa';

	function Tarifa($sesion, $fields = "", $params = "") {
		$this->tabla = "tarifa";
		$this->campo_id = "id_tarifa";
		$this->campo_glosa = "glosa_tarifa";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	// Carga a través de ID

	function LoadById($id_tarifa) {
		$query = "SELECT id_tarifa FROM tarifa WHERE id_tarifa = '$id_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByGlosa($glosa) {
		$query = "SELECT id_tarifa FROM tarifa WHERE glosa_tarifa = '$glosa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	// Cargar la tarifa por defecto
	function LoadDefault() {
		$query = " SELECT id_tarifa FROM tarifa WHERE tarifa_defecto = 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_tarifa) = mysql_fetch_array($resp);
		return $this->Load($id_tarifa);
	}

	// Elimina Tarifas

	function Eliminar() {
		if (!$this->Loaded()) {
			return false;
		}
		$query = "SELECT COUNT(*) FROM contrato WHERE contrato.id_tarifa = '" . $this->fields['id_tarifa'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$query = "SELECT codigo_cliente FROM contrato WHERE id_tarifa = '" . $this->fields['id_tarifa'] . "' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($contrato) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar una') . ' ' . __('tarifa') . ' ' . __('que tiene un contrato asociado. Código contrato asociado: ') . $contrato;
			return false;
		}
		$query = "DELETE FROM usuario_tarifa WHERE id_tarifa = '" . $this->fields['id_tarifa'] . "'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM categoria_tarifa WHERE id_tarifa = '" . $this->fields['id_tarifa'] . "'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM tarifa WHERE id_tarifa = '" . $this->fields['id_tarifa'] . "'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	// Carga la tarifa para el usuario y moneda correspondiente

	function LoadByUsuarioMoneda($id_usuario, $id_moneda, $id_tarifa) {
		$query = "SELECT id_usuario_tarifa FROM usuario_tarifa WHERE id_usuario = '$id_usuario' AND id_moneda = '$id_moneda' AND id_tarifa = '$id_tarifa' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}

	// Limpia Tarifa por defecto

	function TarifaDefecto($id_tarifa) {
		$query = "UPDATE tarifa SET tarifa_defecto = 0 WHERE id_tarifa != '$id_tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	// Retorna ID tarifa declarada como defecto

	function SetTarifaDefecto() {
		$query = "SELECT id_tarifa FROM tarifa WHERE tarifa_defecto = 1 LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_tarifa) = mysql_fetch_array($resp);
		return $id_tarifa;
	}

	/**
	 * Crea o retorna una tarifa donde todos los usuarios tienen el mismo valor.
	 * @param type $valor
	 * @param type $id_moneda
	 * @param type $id_tarifa
	 * @return int id de la tarifa
	 */
	function GuardaTarifaFlat($valor, $id_moneda, $id_tarifa = null) {
		$valor = str_replace(',', '.', $valor);
		$moneda = new Moneda($this->sesion);
		$moneda->Load($id_moneda);
		$glosa = 'Tarifa Flat por ' . $moneda->fields['simbolo'] . number_format($valor, $moneda->fields['cifras_decimales'], '.', '');

		if (!empty($id_tarifa)) {
			$this->Load($id_tarifa);
			if ($this->Loaded() && $this->fields['tarifa_flat'] == $valor && $this->fields['glosa_tarifa'] == $glosa) {
			$this->verificaTerifaFlatUsuarios($id_tarifa, $id_moneda, $valor);
				return $id_tarifa;
			}
		}

		$query = "SELECT {$this->campo_id} FROM {$this->tabla} where glosa_tarifa = '{$glosa}' AND tarifa_flat = $valor ";
		$resp = mysql_query($query, $this->sesion->dbh);
		$tarifa = mysql_fetch_assoc($resp);
		if ($tarifa !== false) {
			$this->verificaTerifaFlatUsuarios($tarifa[$this->campo_id], $id_moneda, $valor);
			return $tarifa[$this->campo_id];
		}

		$this->fields[$this->campo_id] = null;
		$this->Edit('tarifa_flat', $valor);
		$this->Edit('guardado', 1);
		$this->Edit('glosa_tarifa', $glosa);
		if (!$this->Write()) {
			return false;
		}

		$id_tarifa = $this->fields[$this->campo_id];

		$this->verificaTerifaFlatUsuarios($id_tarifa, $id_moneda, $valor);

		//eliminar tarifas flat huachas (se crea 1 por contrato flat, y quedan flotando al cambiarse a variable)
		$query = "SELECT t.id_tarifa FROM tarifa t LEFT JOIN contrato c ON t.id_tarifa = c.id_tarifa WHERE c.id_contrato IS NULL AND t.tarifa_flat IS NOT NULL";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($id_tarifa_eliminar) = mysql_fetch_array($resp)) {
			if ($id_tarifa_eliminar == $id_tarifa) {
				continue;
			}
			$this->Load($id_tarifa_eliminar);
			$this->Eliminar();
		}
		$this->Load($id_tarifa);

		return $id_tarifa;
	}

	/**
	 * Guardar tarifas de usuarios y categorias si no existen
	 * @param type $id_tarifa
	 * @param type $id_moneda
	 * @param type $valor
	 */
	function verificaTerifaFlatUsuarios($id_tarifa, $id_moneda, $valor) {
		$uCrireria = new Criteria($this->sesion);
		$uCrireria
				->add_select('usuario.id_usuario')
				->add_from('usuario')
				->add_left_join_with('usuario_tarifa', CriteriaRestriction::and_clause(
						CriteriaRestriction::equals('usuario.id_usuario', 'usuario_tarifa.id_usuario'),
						CriteriaRestriction::equals('usuario_tarifa.id_tarifa', $id_tarifa)
				))
				->add_restriction(CriteriaRestriction::is_null('usuario_tarifa.id_tarifa'))
		;

		$usuarios = $uCrireria->run();
		if (!empty($usuarios)) {
			$usuario_tarifa = new UsuarioTarifa($this->sesion);
			foreach ($usuarios as $usuario) {
				$usuario_tarifa->GuardarTarifa($id_tarifa, $usuario['id_usuario'], $id_moneda, $valor);
			}
		}

		$cCrireria = new Criteria($this->sesion);
		$cCrireria
				->add_select('PCU.id_categoria_usuario')
				->add_from('prm_categoria_usuario', 'PCU')
				->add_left_join_with(array('categoria_tarifa', 'CT'), CriteriaRestriction::and_clause(
						CriteriaRestriction::equals('PCU.id_categoria_usuario', 'CT.id_categoria_usuario'),
						CriteriaRestriction::equals('CT.id_tarifa', $id_tarifa)
				))
				->add_restriction(CriteriaRestriction::is_null('CT.id_tarifa'))
		;

		$categorias = $cCrireria->run();
		if (!empty($categorias)) {
			$categoria_tarifa = new CategoriaTarifa($this->sesion);
			foreach ($categorias as $categoria) {
				$categoria_tarifa->GuardarTarifa($id_tarifa, $categoria['id_categoria_usuario'], $id_moneda, $valor);
			}
		}
	}

}
