<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Usuario.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

define('CONCAT_RUT_DV_USUARIO', 'CONCAT(rut,IF(dv_rut="" OR dv_rut IS NULL, "", CONCAT("-", dv_rut)))');

class UsuarioExt extends Usuario {

	public static $llave_carga_masiva = 'username';
	public static $campos_carga_masiva = array(
		CONCAT_RUT_DV_USUARIO => 'RUT', //todo: agregar tipo "rut" para validar? (solo si esta configurado como rut)
		'nombre' => array(
			'titulo' => 'Nombre',
			'requerido' => true,
			'unico' => 'nombre_completo'
		),
		'apellido1' => array(
			'titulo' => 'Apellido Paterno',
			'requerido' => true,
			'unico' => 'nombre_completo'
		),
		'apellido2' => array(
			'titulo' => 'Apellido Materno',
			'unico' => 'nombre_completo'
		),
		'username' => array(
			'titulo' => 'Código',
			'unico' => true
		),
		'email' => array(
			'titulo' => 'Email',
			'requerido' => true,
			'tipo' => 'email',
			'unico' => true
		),
		'telefono1' => 'Teléfono 1',
		'telefono2' => 'Teléfono 2',
		'admin' => array(
			'titulo' => 'Es Administrador',
			'tipo' => 'bool'
		),
		'id_categoria_usuario' => array(
			'titulo' => 'Categoría de Usuario',
			'requerido' => true,
			'relacion' => 'CategoriaUsuario',
			'creable' => true
		),
		'id_area_usuario' => array(
			'titulo' => 'Área de Usuario',
			'relacion' => 'AreaUsuario',
			'creable' => true,
			'defval' => 1
		)
	);
	public $tabla = 'usuario';
	public $campo_id = 'id_usuario';
	var $secretarios = null;

	function Loaded() {
		if ($this->fields['id_usuario']) {
			return true;
		}

		return false;
	}

	function LoadSecretario($id, $secretario = null) {
		if (empty($secretario))
			$secretario = $this->fields['id_usuario'];
		$query = "SELECT id_profesional FROM usuario_secretario
							WHERE id_secretario = '" . $secretario . "'
							AND id_profesional = '$id' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($id_profesional = mysql_fetch_assoc($resp))
			return true;
		else
			return false;
	}

	function Revisa($id) {
		$query = "SELECT id_revisado FROM usuario_revisor
							WHERE id_revisor = '" . $this->fields['id_usuario'] . "'
							AND id_revisado = '$id' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		if ($id_revisado = mysql_fetch_assoc($resp))
			return true;
		else
			return false;
	}

	//Agrega Vacaciones del Usuario
	function GuardarVacacion($fecha_ini, $fecha_fin) {
		if ($fecha_ini == "" || $fecha_fin == "")
			return false;

		$query = "INSERT INTO usuario_vacacion (id_usuario,id_usuario_creador,fecha_inicio,fecha_fin)";
		$query .= "	VALUES(" . $this->fields['id_usuario'] . ", " . $this->sesion->usuario->fields['id_usuario'] . ", '" . Utiles::fecha2sql($fecha_ini) . "', '" . Utiles::fecha2sql($fecha_fin) . "')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$arr1 = array('id_usuario' => $this->fields['id_usuario'], 'vacaciones' => '');
		$arr2 = array('id_usuario' => $this->fields['id_usuario'], 'vacaciones' => 'agregado<br />f. inicio: ' . $fecha_ini . "<br />f. fin: " . $fecha_fin);

		self::GuardaCambiosUsuario($arr1, $arr2);

		return true;
	}

	//Lista las vacaciones de un usuario
	function ListaVacacion($id_usuario) {
		$query = "SELECT id, fecha_inicio, fecha_fin FROM usuario_vacacion WHERE id_usuario = '" . $id_usuario . "' ORDER BY fecha_inicio desc";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$vacaciones_usuario = array();
		$i = 0;
		while (list($id, $fecha_ini, $fecha_fin) = mysql_fetch_array($resp)) {
			$vacaciones_usuario[$i]['id'] = $id;
			$vacaciones_usuario[$i]['fecha_inicio'] = Utiles::sql2date($fecha_ini);
			$vacaciones_usuario[$i]['fecha_fin'] = Utiles::sql2date($fecha_fin);
			$i++;
		}
		return $vacaciones_usuario;
	}

	//Elimina VacacionesSELECT EXTRACT(YEAR_MONTH FROM DATE_FORMat('2011-01-01 01:01:01') );
	function EliminaVacacion($ide, $id_usuario) {
		$query = "SELECT DATE_FORMAT(fecha_inicio, '%d/%m/%Y') as fecha_inicio, DATE_FORMAT(fecha_fin, '%d/%m/%Y') as fecha_fin, id_usuario_creador
					FROM usuario_vacacion WHERE id = $ide AND id_usuario = $id_usuario LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$vacacion = mysql_fetch_array($resp);

		$query = "DELETE FROM usuario_vacacion WHERE id = " . $ide . " AND id_usuario = " . $id_usuario . " LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$arr1 = array('id_usuario' => $this->fields['id_usuario'], 'vacaciones' => 'f. inicio: ' . $vacacion['fecha_inicio'] . "<br />f. fin: " . $vacacion['fecha_fin']);
		$arr2 = array('id_usuario' => $this->fields['id_usuario'], 'vacaciones' => 'eliminado');

		self::GuardaCambiosUsuario($arr1, $arr2);

		return true;
	}

	function GuardarSecretario($ids) {
		//Se obtienen los datos actuales
		$query = "SELECT id_profesional FROM usuario_secretario WHERE id_secretario='" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$lista_actual = array();
		while (list($id_profesional) = mysql_fetch_array($resp)) {
			$lista_actual[] = $id_profesional;
		}
		//Se eliminan todas
		$query = "DELETE FROM usuario_secretario WHERE id_secretario='" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$lista_nuevos = array();
		if (count($ids) > 0) {
			foreach ($ids as $id_profesional => $value) {
				$query = "INSERT INTO usuario_secretario
									SET id_secretario=" . $this->fields['id_usuario'] . ", id_profesional = '$value'
									ON DUPLICATE KEY UPDATE id_secretario='" . $this->fields['id_usuario'] . "', id_profesional='$value'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				$lista_nuevos[] = $value;
			}

			//Registrar el cambio de secretarios para el usuario
			if (count($lista_actual) <> count($lista_nuevos)) {
				$lista_nuevos = implode(',', $lista_nuevos);
				$lista_actual = implode(',', $lista_actual);
				$this->GuardaCambiosRelacionUsuario($this->fields['id_usuario'], 'secretarios', $lista_actual, $lista_nuevos);
			}
		}
		return true;
	}

	function GuardarRevisado($ids) {
		//Se obtienen los datos actuales
		$query = "SELECT id_revisado FROM usuario_revisor WHERE id_revisor='" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$lista_actual = array();
		while (list($id_revisado) = mysql_fetch_array($resp)) {
			$lista_actual[] = $id_revisado;
		}

		//Se eliminan todos para luego insertar
		$query = "DELETE FROM usuario_revisor WHERE id_revisor='" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$lista_nuevos = array();
		if ($ids) {
			$ids = explode('::', $ids);
			if (count($ids) > 0) {
				foreach ($ids as $value) {
					$query = "INSERT INTO usuario_revisor
										SET id_revisor=" . $this->fields['id_usuario'] . ", id_revisado = '$value'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					$lista_nuevos[] = $value;
				}

				//Registrar el cambio de secretarios para el usuario
				if (count($lista_actual) <> count($lista_nuevos)) {
					$lista_nuevos = implode(',', $lista_nuevos);
					$lista_actual = implode(',', $lista_actual);
					$this->GuardaCambiosRelacionUsuario($this->fields['id_usuario'], 'revisores', $lista_actual, $lista_nuevos);
				}
			}
		}
		return true;
	}

	function GuardarTarifaSegunCategoria($id, $id_categoria_usuario) {
		$query = "SELECT id_tarifa, id_moneda, tarifa FROM categoria_tarifa WHERE id_categoria_usuario=" . $id_categoria_usuario . " ORDER BY id_moneda";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while (list( $id_tarifa, $id_moneda, $tarifa) = mysql_fetch_array($resp)) {
			$query2 = "INSERT usuario_tarifa SET id_usuario = '" . $id . "', id_moneda = '" . $id_moneda . "', tarifa = " . $tarifa . ", id_tarifa = '" . $id_tarifa . "'
									ON DUPLICATE KEY UPDATE tarifa = '" . $tarifa . "'";
			$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
		}
		return true;
	}

	function Costo($id_moneda) {
		$query = "SELECT costo FROM usuario_costo WHERE id_usuario='" . $this->fields['id_usuario'] . "' AND id_moneda='$id_moneda'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($costo = mysql_fetch_assoc($resp))
			return $costo['costo'];
		else
			return 0;
	}

	function Moneda($id_moneda) {
		$query = "SELECT tarifa FROM usuario_tarifa WHERE id_usuario='" . $this->fields['id_usuario'] . "' AND id_moneda='$id_moneda'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($tarifa = mysql_fetch_assoc($resp))
			return $tarifa['tarifa'];
		else
			return 0;
	}

	function GuardarCosto($id_moneda, $costo) {
		$query = "INSERT INTO usuario_costo
							SET id_usuario=" . $this->fields['id_usuario'] . ", id_moneda='$id_moneda', costo = '$costo'
							ON DUPLICATE KEY UPDATE id_usuario='" . $this->fields['id_usuario'] . "', id_moneda='$id_moneda', costo = '$costo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function GuardarMoneda($id_moneda, $tarifa) {
		if ($tarifa == 0 || !$tarifa)
			$tarifa = 1;

		$query = "INSERT INTO usuario_tarifa
							SET id_usuario=" . $this->fields['id_usuario'] . ", id_moneda='$id_moneda', tarifa = '$tarifa'
							ON DUPLICATE KEY UPDATE id_usuario='" . $this->fields['id_usuario'] . "', id_moneda='$id_moneda', tarifa = '$tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function HorasTrabajadasEsteMes($id_usuario = '', $tipo_dato = 'horas_trabajadas', $fecha = '') {
		if (!$id_usuario)
			$id_usuario = $this->fields['id_usuario'];

		$mes = empty($fecha) ? date('Ym') : date('Ym', strtotime($fecha));

		switch ($tipo_dato) {
			case 'horas_castigadas':
				$td = '( TIME_TO_SEC(duracion) - TIME_TO_SEC(IFNULL(duracion_cobrada,0) ))';
				break;
			case 'horas_cobrables':
				$td = 'duracion_cobrada';
				break;
			default:
				$td = 'duracion';
		}
		$query = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC($td)))
							FROM trabajo
							WHERE EXTRACT(YEAR_MONTH FROM fecha) = '$mes'
							AND id_usuario=$id_usuario";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($horas) = mysql_fetch_array($resp);
		list($h, $m, $s) = explode(":", $horas);
		if (method_exists('Conf', 'GetConf')) {
			if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
				return UtilesApp::Time2Decimal("$h:$m");
			}
		} else if (method_exists('Conf', 'TipoIngresoHoras')) {
			if (Conf::TipoIngresoHoras() == 'decimal') {
				return UtilesApp::Time2Decimal("$h:$m");
			}
		}
		return "$h:$m";
	}

	#Calcula las horas trabado esta semana

	function HorasTrabajadasEsteSemana($id_usuario = '', $semana_actual) {
		if (!$id_usuario)
			$id_usuario = $this->fields['id_usuario'];
		$query = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(duracion)))
							FROM trabajo
							WHERE YEARWEEK(fecha,1)=YEARWEEK('$semana_actual',1)
							AND id_usuario=$id_usuario";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($horas) = mysql_fetch_array($resp);
		list($h, $m, $s) = explode(":", $horas);
		if (empty($h) && empty($m)) {
			$h = '00';
			$m = '00';
		}
		if (method_exists('Conf', 'GetConf')) {
			if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
				return UtilesApp::Time2Decimal("$h:$m");
			}
		} else if (method_exists('Conf', 'TipoIngresoHoras')) {
			if (Conf::TipoIngresoHoras() == 'decimal') {
				return UtilesApp::Time2Decimal("$h:$m");
			}
		}
		return "$h:$m";
	}

	#eliminar usuario

	function Eliminar() {

		#Valida si no tiene algún cliente asociado como encargado comercial o encargado
		$query = "SELECT COUNT(*) FROM contrato WHERE contrato.id_usuario_responsable = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$query = "SELECT codigo_cliente FROM contrato WHERE id_usuario_responsable = '" . $this->fields['id_usuario'] . "' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($cliente) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un') . ' ' . __('usuario') . ' ' . __('que es Encargado Comercial de un ') . __('cliente') . '. ' . __('Cliente') . ' -' . $cliente;
			return false;
		}
		#Valida si no tiene algún trabajo relacionado
		$query = "SELECT COUNT(*) FROM trabajo WHERE id_usuario = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$this->error = __('No se puede eliminar un') . ' ' . __('usuario') . ' ' . __('que tiene trabajos asociados.');
			return false;
		}
		#Valida si no tiene algún gasto asociado
		$query = "SELECT COUNT(*) FROM cta_corriente WHERE cta_corriente.id_usuario = '" . $this->fields['id_usuario'] . "' OR cta_corriente.id_usuario_orden = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$this->error = __('No se puede eliminar un') . ' ' . __('usuario') . ' ' . __('que tiene gastos asociados.');
			return false;
		}
		$query = "DELETE FROM usuario_permiso WHERE id_usuario = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM usuario_costo WHERE id_usuario = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM usuario_secretario WHERE id_secretario = '" . $this->fields['id_usuario'] . "' OR id_profesional = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM usuario WHERE id_usuario = '" . $this->fields['id_usuario'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/* Imprime el Select de los usuarios a quien revisa */

	function select_revisados() {
		$query =
			"SELECT usuario.id_usuario, CONCAT_WS(' ',nombre,apellido1,apellido2) AS nombre
			FROM
			usuario JOIN usuario_revisor ON (usuario.id_usuario = usuario_revisor.id_revisado)
			WHERE id_revisor = '" . $this->fields['id_usuario'] . "' AND usuario.activo = 1 AND usuario.id_usuario <> '" . $this->fields['id_usuario'] . "'
			ORDER BY usuario.nombre, usuario.apellido1";
		$select = Html::SelectQuery($this->sesion, $query, "usuarios_revisados", '', "multiple style='height: 100px;width:200px'", "", "220");
		$input = "<input type=hidden name=arreglo_revisados id=arreglo_revisados value='' />";
		$html = $select . $input;
		return $html;
	}

	/* Imprime el Select de los usuarios a los que podría revisar */

	function select_no_revisados() {
		$query_otros =
			"SELECT usuario.id_usuario, CONCAT_WS(' ',nombre,apellido1,apellido2) AS nombre
			FROM usuario
			WHERE id_usuario NOT IN (
				SELECT usuario_revisor.id_revisado
				FROM usuario_revisor
				WHERE id_revisor = '" . $this->fields['id_usuario'] . "'  ) AND activo = 1 AND usuario.id_usuario <> '" . $this->fields['id_usuario'] . "'
				ORDER BY usuario.nombre, usuario.apellido1";
		$html = Html::SelectQuery($this->sesion, $query_otros, "usuarios_fuera", '', " style='width:200px'", "", "220");
		return $html;
	}

	/* Compara arreglo usuario y guarda cambios realizados */

	function GuardaCambiosUsuario($arr1 = array(), $arr2 = array()) {
		$usuario_activo = $this->sesion->usuario->fields['id_usuario'];
		$arr_diff = array();

		if (is_array($arr1)) {
			foreach ($arr1 as $indice1 => $valor1) {
				if ($arr2[$indice1] != $valor1) {
					if ($valor1 == '0' && $arr2[$indice1] == '')
						continue;
					$arr_diff[$indice1] = array('valor_original' => $valor1, 'valor_actual' => $arr2[$indice1]);

					/* Bitácora de usuario */
					$query = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
					$query .= " VALUES('" . $arr1['id_usuario'] . "','" . $usuario_activo . "','" . $indice1 . "','" . $valor1 . "','" . $arr2[$indice1] . "',NOW())";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				}
			}
		}
		return $arr_diff;
	}

	/* Guarda cambios en los permisos */

	function GuardaCambiosRelacionUsuario($id_usuario = null, $dato, $actuales, $nuevos) {
		$usuario_activo = $this->sesion->usuario->fields['id_usuario'];
		$query = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
		$query .= " VALUES('" . $id_usuario . "','" . $usuario_activo . "','" . $dato . "','" . $actuales . "','" . $nuevos . "',NOW())";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/* Lista de permisos usuario */

	function ListaPermisosUsuario($id_usuario) {
		$query .= "SELECT codigo_permiso FROM usuario_permiso WHERE id_usuario='" . $id_usuario . "' AND codigo_permiso NOT IN ('ALL')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		$lista = array();
		while (list($codigo) = mysql_fetch_array($resp)) {
			$lista[] = $codigo;
		}
		return $lista;
	}

	function TablaHistorial($usuario_historial) {


		echo '<table id="historial_tabla" style="display:none;" width="780px">
				<tr>
					<td colspan="3" style="font-size:11px;"><b>Fecha creación:</b> ';
		echo Utiles::sql2date($usuario->fields['fecha_creacion']);
		echo '</td>
				</tr>
				<tr>
					<td colspan="3" style="font-size:11px; font-weight:bold">Lista de modificaciones realizadas</td>
				</tr>
				<tr style="border:1px solid #454545">
					<td width="80px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Fecha</td>
					<td width="200px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Dato modificado</td>
					<td width="200px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Valor actual</td>
					<td width="200px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Valor anterior</td>
					<td width="250px" style="font-weight:bold; border:1px solid #ccc; text-align:center;">Modificado por</td>
				</tr>';
		if (!empty($usuario_historial)):


			foreach ($usuario_historial as $k => $historia):
				if (trim($historia['nombre_dato']) == 'categoría') {
					$glosa_actual = (!empty($historia['valor_actual'])) ? Utiles::Glosa($this->sesion, $historia['valor_actual'], 'glosa_categoria', 'prm_categoria_usuario', 'id_categoria_usuario') : 'sin asignación';
					$glosa_origen = (!empty($historia['valor_original'])) ? Utiles::Glosa($this->sesion, $historia['valor_original'], 'glosa_categoria', 'prm_categoria_usuario', 'id_categoria_usuario') : 'sin asignación';
				} else if (trim($historia['nombre_dato']) == 'área usuario') {
					$glosa_actual = (!empty($historia['valor_actual'])) ? Utiles::Glosa($this->sesion, $historia['valor_actual'], 'glosa', 'prm_area_usuario', 'id') : 'sin asignación';
					$glosa_origen = (!empty($historia['valor_original'])) ? Utiles::Glosa($this->sesion, $historia['valor_original'], 'glosa', 'prm_area_usuario', 'id') : 'sin asignación';
				} else if (trim($historia['nombre_dato']) == 'permisos') {
					$_permisos_anteriores = explode(",", $historia['valor_original']);
					$_permisos_nuevos = explode(",", $historia['valor_actual']);

					$_permisos_agregados = array_diff($_permisos_nuevos, $_permisos_anteriores);
					$_permisos_quitados = array_diff($_permisos_anteriores, $_permisos_nuevos);

					$_permisos_agregados_texto = "";
					foreach ($_permisos_agregados as $llave => $codigo) {
						if (strlen($_permisos_agregados_texto) > 0) {
							$_permisos_agregados_texto .= ", ";
						}
						$_permisos_agregados_texto .= Utiles::Glosa($this->sesion, $codigo, 'glosa', 'prm_permisos', 'codigo_permiso');
					}
					if (strlen($_permisos_agregados_texto) > 0) {
						$_permisos_agregados_texto = "Se agregó: " . $_permisos_agregados_texto . "<br />";
					}

					$_permisos_quitados_texto = "";
					foreach ($_permisos_quitados as $llave => $codigo) {
						if (strlen($_permisos_quitados_texto) > 0) {
							$_permisos_quitados_texto .= ", ";
						}
						$_permisos_quitados_texto .= Utiles::Glosa($this->sesion, $codigo, 'glosa', 'prm_permisos', 'codigo_permiso');
					}
					if (strlen($_permisos_quitados_texto) > 0) {
						$_permisos_quitados_texto = "Se eliminó: " . $_permisos_quitados_texto;
					}

					$glosa_origen = " - ";
					$glosa_actual = $_permisos_agregados_texto . $_permisos_quitados_texto;
				} else if (trim($historia['nombre_dato']) == 'usuario secretario de' || trim($historia['nombre_dato']) == 'usuario revisor de') {

					$_sde_actual = explode(",", $historia['valor_actual']);
					$_sde_original = explode(",", $historia['valor_original']);

					$_arr_sde_actual = array();
					$_arr_sde_original = array();

					foreach ($_sde_actual as $key => $id_actual) {
						$_arr_sde_actual[] = Utiles::Glosa($this->sesion, $id_actual, 'CONCAT_WS(" ", nombre, apellido1, apellido2) as nombre_completo', 'usuario', 'id_usuario');
					}

					foreach ($_sde_original as $key => $id_original) {
						$_arr_sde_original[] = Utiles::Glosa($this->sesion, $id_original, 'CONCAT_WS(" ", nombre, apellido1, apellido2) as nombre_completo', 'usuario', 'id_usuario');
					}

					$glosa_actual = (sizeof($_arr_sde_actual) ? implode("<br />", $_arr_sde_actual) : '');
					$glosa_origen = (sizeof($_arr_sde_original) ? implode("<br />", $_arr_sde_original) : '');
				} else if (trim($historia['nombre_dato']) == 'activo' || trim($historia['nombre_dato']) == 'alerta diaria' || trim($historia['nombre_dato']) == 'alerta semanal' || trim($historia['nombre_dato']) == 'resumen horas semanales de abogados revisados') {
					$glosa_actual = (!empty($historia['valor_actual'])) ? 'activo' : 'inactivo';
					$glosa_origen = (!empty($historia['valor_original'])) ? 'activo' : 'inactivo';
				} else {
					$glosa_actual = $historia["valor_actual"];
					$glosa_origen = $historia["valor_original"];
				}

				echo '	<tr>
							<td style="border:1px solid #ccc; text-align:center;">' . $historia['fecha'] . '</td>
							<td style="border:1px solid #ccc; text-align:center;">' . $historia['nombre_dato'] . '</td>
							<td style="border:1px solid #ccc; text-align: center">' . $glosa_actual . '</td>
							<td style="border:1px solid #ccc; text-align: center">' . $glosa_origen . '</td>
							<td style="border:1px solid #ccc; text-align: left">' . $historia['nombre'] . ' ' . $historia['ap_paterno'] . ' ' . $historia['ap_materno'] . '</td>
						</tr>';
			endforeach;
		endif;
		echo '</table>';
	}

	/**
	 * Lista los cambios realizados a usuarios de acuerdo a tipos indicados
	 */
	function ListaCambios($id_usuario) {
		/*
		 * Ej:
		 * AND nombre_dato IN ('id_categoria_usuario','activo');
		 * Otros datos
		 *
		 * dias_ingreso_trabajo
		 * permisos
		 * etc.
		 *
		 */
		$nombre_dato = "";
		if (method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'FiltroHistorialUsuarios') != '') {
			$filtros = explode(',', Conf::GetConf($this->sesion, 'FiltroHistorialUsuarios'));
			$filtros = implode("', '", $filtros);
			$nombre_dato = " AND nombre_dato IN ( '" . $filtros . "' )";
		}

		$query = "SELECT
						usuario_historial.id_usuario_creador,
						usuario_historial.nombre_dato,
						usuario_historial.valor_original,
						usuario_historial.valor_actual,
						usuario_historial.fecha,
						usuario.nombre,
						usuario.apellido1,
						usuario.apellido2
					FROM usuario_cambio_historial usuario_historial ";
		$query .= " JOIN usuario ON usuario.id_usuario = usuario_historial.id_usuario_creador";
		$query .= " WHERE usuario_historial.id_usuario = '" . $id_usuario . "' ";
		$query .= $nombre_dato;
		$query .= " ORDER BY fecha DESC";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$datos_modificados = array(
			'nombre' => 'nombre',
			'apellido1' => 'apellido paterno',
			'apellido2' => 'apellido materno',
			'username' => 'código usuario',
			'centro_de_costo' => 'centro de costo',
			'id_categoria_usuario' => 'categoría',
			'id_area_usuario' => 'área usuario',
			'telefono1' => 'teléfono',
			'email' => 'email',
			'activo' => 'activo',
			'secretarios' => 'usuario secretario de',
			'revisores' => 'usuario revisor de',
			'usuarios_revisados' => 'usuarios revisados',
			'alerta_diaria' => 'alerta diaria',
			'alerta_semanal' => 'alerta semanal',
			'retraso_max' => 'retraso máximo en el ingreso de horas',
			'restriccion_diario' => 'mínimo horas por día',
			'restriccion_min' => 'min HH.',
			'restriccion_max' => 'máx HH.',
			'alerta_revisor' => 'resumen horas semanales de abogados revisados',
			'restriccion_mensual' => 'restriccion mensual de horas',
			'dias_ingreso_trabajo' => 'plazo máximo (en días) para ingreso de trabajos'
		);

		while (list($id_usuario_creador, $nombre_dato, $valor_original, $valor_actual, $fecha, $nombre, $apellido1, $apellido2) = mysql_fetch_array($resp)) {
			$lista[] = array(
				'id_usuario_creador' => $id_usuario_creador,
				'nombre_dato' => ( $datos_modificados[$nombre_dato] ? $datos_modificados[$nombre_dato] : $nombre_dato ),
				'valor_original' => $valor_original,
				'valor_actual' => $valor_actual,
				'fecha' => Utiles::sql2date($fecha),
				'nombre' => $nombre,
				'ap_paterno' => $apellido1,
				'ap_materno' => $apellido2
			);
		}
		return $lista;
	}

	public function Validaciones($usuario_sin_modificar, $pagina, $validar_segun_conf = false) {
		if ($this->ExisteCodigo($usuario_sin_modificar['username'])) {
			$pagina->AddError(__('El Código Usuario ingresado ya existe.'));
		}

		if (empty($this->fields["nombre"]))
			$pagina->AddError(__('Debe ingresar el nombre del usuario'));
		if (empty($this->fields["apellido1"]))
			$pagina->AddError(__('Debe ingresar el apellido paterno del usuario'));
		if (empty($this->fields["email"]))
			$pagina->AddError(__('Debe ingresar el e-mail del usuario'));

		if ($validar_segun_conf) {
			if (empty($this->fields["username"]))
				$pagina->AddError(__('Debe ingresar el código usuario'));
			//if (empty($this->fields["apellido2"])) $pagina->AddError(__('Debe ingresar el apellido materno del usuario'));
			if (empty($this->fields["id_categoria_usuario"]))
				$pagina->AddError(__('Debe ingresar la categoría del usuario'));
			if (empty($this->fields["id_area_usuario"]))
				$pagina->AddError(__('Debe ingresar el área del usuario'));
		}
	}

	public function ExisteCodigo($username_anterior) {
		if (empty($this->fields["username"])) {
			return false;
		}
		$query = "SELECT count(*) FROM usuario WHERE username = '" . addslashes($this->fields["username"]) . "' AND username != '" . addslashes($username_anterior) . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cantidad) = mysql_fetch_array($resp);
		if ($cantidad > 0) {
			return true;
		}
		return false;
	}

	/**
	 * @param type $Sesion
	 * @return array Usuarios activos en el sistema
	 */
	public static function GetUsuariosActivos(&$Sesion) {
		return self::GetUsuarios($Sesion, "WHERE activo = 1");
	}

	/**
	 * @param type $Sesion
	 * @param string $where Condición para entregar el listado de usuarios
	 * @return array Usuarios del sistema según la condición del $where
	 */
	public static function GetUsuarios(&$Sesion, $where = '') {
		$query = "SELECT
					id_usuario,
					CONCAT_WS(', ', apellido1, nombre) as nombre,
					email
				FROM usuario
				$where
				ORDER BY apellido1";
		$result = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

		$usuarios = array();

		while ($usuario = mysql_fetch_array($result)) {
			$usuarios[] = $usuario;
		}

		return $usuarios;
	}

	/**
	 * Nombre completo del usuario dependiendo de las configuraciones
	 * @return string
	 */
	public function NombreCompleto() {
		if (!$this->Loaded()) {
			return '';
		}

		// Probar si quiere username o otra cosa
		$nombre = $this->fields['nombre'];
		if (!empty($this->fields['apellido1'])) {
			$nombre .= " {$this->fields['apellido1']}";
		}
		if (!empty($this->fields['apellido2'])) {
			$nombre .= " {$this->fields['apellido2']}";
		}

		return $nombre;
	}

	public function FillTemplate() {
		if (!$this->Loaded()) {
			return '';
		}

		// Buscar las entidades que no tienen relacion o clase
		$query = "SELECT * FROM prm_categoria_usuario WHERE id_categoria_usuario = '{$this->fields['id_categoria_usuario']}'";
		$categoria = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);

		return array(
			'NombreCompleto' => $this->NombreCompleto(),
			'Email' => $this->fields['email'],
			'NombreCategoria' => $categoria['glosa_categoria']
		);
	}

	public function TienePermiso($permiso) {
		return $this->permisos->Find('FindPermiso', array('codigo_permiso' => $permiso))->fields['permitido'];
	}

	public function LoadWithToken($token) {
		$query = "SELECT * FROM " . $this->tbl_usuario
			. " WHERE reset_password_token = '$token'"
			. " AND NOW() <= DATE_ADD(reset_password_sent_at, INTERVAL 1 HOUR)";

		return $this->LoadWithQuery($query);
	}

	public function PreCrearDato($data) {
		$data['rut'] = $data[CONCAT_RUT_DV_USUARIO];
		unset($data[CONCAT_RUT_DV_USUARIO]);
		if (Conf::GetConf($this->sesion, 'NombreIdentificador') == 'RUT') {
			$rutdv = explode('-', $data['rut']);
			$data['rut'] = preg_replace('/\D/', '', $rutdv[0]);
			$data['dv_rut'] = trim($rutdv[1]);
		}

		if (isset($data['admin'])) {
			$this->extra_fields = array('admin' => $data['admin']);
			unset($data['admin']);
		}

		$data['nombre'] = ucwords($data['nombre']);
		$data['apellido1'] = ucwords($data['apellido1']);
		if (isset($data['apellido2'])) {
			$data['apellido2'] = ucwords($data['apellido2']);
		}

		$data['password'] = md5('12345');
		$data['force_reset_password'] = '1';

		if (empty($data['username'])) {
			$data['username'] = $data['nombre'][0] . $data['apellido1'][0];
			if (isset($data['apellido2'])) {
				$data['username'] .= $data['apellido2'][0];
			}
		}

		return $data;
	}

	public function PostCrearDato() {
		$permisos = array('ALL', 'PRO');
		if (isset($this->extra_fields['admin']) && !empty($this->extra_fields['admin'])) {
			$permisos = array(
				'ADM', 'ALL', 'COB', 'DAT', 'REP', 'REV', 'TAR', 'SOC', 'OFI', 'PRO'
			);
		}
		$values = array();
		foreach ($permisos as $permiso) {
			$values[] = "({$this->fields['id_usuario']}, '$permiso')";
		}
		$query = 'INSERT IGNORE INTO usuario_permiso (id_usuario, codigo_permiso) VALUES ' . implode(', ', $values);
		return mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
	}

	/**
	 * Completa el objeto con los valores que vengan en $parametros
	 * 
	 * @param array $parametros entrega los campos y valores del objeto campo => valor
	 * @param boolean $edicion indica si se marcan los $parametros para edición
	 */
	function Fill($parametros, $edicion = false) {
		foreach ($parametros as $campo => $valor) {
			if (in_array($campo, $this->editable_fields)) {
				$this->fields[$campo] = $valor;

				if ($edicion) {
					$this->Edit($campo, $valor);
				}
			} else {
				$this->extra_fields[$campo] = $valor;
			}
		}
	}

	function Write() {
		$this->loaded = !empty($this->fields['id_usuario']);
		return parent::Write();
	}

	public static function QueryComerciales() {
		return "SELECT
							usuario.id_usuario,
							CONCAT_WS(' ', apellido1, apellido2, ',' , nombre)
						FROM usuario INNER JOIN usuario_permiso USING(id_usuario)
						WHERE codigo_permiso = 'SOC' ORDER BY apellido1";
	}
}
