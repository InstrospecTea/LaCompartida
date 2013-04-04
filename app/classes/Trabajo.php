<?php
require_once dirname(__FILE__) . '/../conf.php';

class Trabajo extends Objeto
{
	//Etapa actual del proyecto
	var $etapa = null;
	//Primera etapa del proyecto
	var $primera_etapa = null;

	var $monto = null;

	function Trabajo($sesion, $fields = "", $params = "") {
		$this->tabla = "trabajo";
		$this->campo_id = "id_trabajo";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function get_codigo_cliente()	{
		$query = "SELECT codigo_cliente
					FROM trabajo
						JOIN asunto ON asunto.codigo_asunto=trabajo.codigo_asunto
					WHERE id_trabajo = '{$this->fields[id_trabajo]}'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$row = mysql_fetch_assoc($resp);
		return $row['codigo_cliente'];
	}

	function Estado() {
		if (!$this->fields['estado_cobro'] && $this->fields['id_cobro'] && $this->fields['id_cobro']) {
			$cobro= new Cobro($this->sesion);
			$cobro->Load($this->fields['id_cobro']);
			$this->fields['estado_cobro'] = $cobro->fields['estado'];
		}
		if ($this->fields['estado_cobro'] <> "CREADO"
				&& $this->fields['estado_cobro'] <> "EN REVISION"
				&& $this->fields['estado_cobro'] != ''
				&& $this->fields['estado_cobro'] <> 'SIN COBRO') {
			return __("Cobrado");
		}
		if ($this->fields['revisado'] == 1) {
			return __("Revisado");
		}

		return __("Abierto");
	}

	function Write($ingreso_historial = true) {
		if ($this->Loaded()) {
			if ($ingreso_historial) {
				$query = "SELECT
										fecha, descripcion,
										duracion, duracion_cobrada,
										id_usuario, codigo_asunto, cobrable
									FROM trabajo
									WHERE id_trabajo = {$this->fields['id_trabajo']}";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($fecha, $descripcion, $duracion, $duracion_cobrada, $id_usuario, $codigo_asunto, $cobrable) = mysql_fetch_array($resp);

				$query = "INSERT INTO trabajo_historial
									SET
										id_trabajo = '{$this->fields['id_trabajo']}',
										id_usuario = '{$this->sesion->usuario->fields['id_usuario']}',
										fecha = '" . date("Y-m-d H:i:s") . "',
									 	fecha_trabajo = '$fecha',
									 	fecha_trabajo_modificado = '{$this->fields['fecha']}',
									 	descripcion = '" . mysql_real_escape_string(empty($descripcion) ? ' Sin descripcion' : $descripcion) . "',
									 	descripcion_modificado = '" . mysql_real_escape_string(empty($this->fields['descripcion'])? ' Sin descripcion' : $this->fields['descripcion']) . "',
									 	duracion = '".mysql_real_escape_string($duracion)."',
									 	duracion_modificado = '{$this->fields['duracion']}',
									 	duracion_cobrada = '" . mysql_real_escape_string($duracion_cobrada) . "',
									 	duracion_cobrada_modificado = '{$this->fields['duracion_cobrada']}',
									 	id_usuario_trabajador = '" . mysql_real_escape_string($id_usuario) . "',
									 	id_usuario_trabajador_modificado = '{$this->fields['id_usuario']}',
									 	accion = 'MODIFICAR',
									 	codigo_asunto = '" . mysql_real_escape_string($codigo_asunto) . "',
									 	codigo_asunto_modificado = '".mysql_real_escape_string($this->fields['codigo_asunto'])."',
									 	cobrable = '$cobrable',
									 	cobrable_modificado = '{$this->fields['cobrable']}'";
			}
		} else {
			if ($ingreso_historial) {
				// Creamos un trabajo nuevo, logueamos la creaciÃ³n.
				$query = "INSERT INTO trabajo_historial
									SET
										id_trabajo = '{$this->fields['id_trabajo']}',
										id_usuario = '{$this->sesion->usuario->fields['id_usuario']}',
										fecha = '" . date("Y-m-d H:i:s") . "',
									 	fecha_trabajo_modificado = '{$this->fields['fecha']}',
									 	descripcion_modificado = '" . mysql_real_escape_string(empty($this->fields['descripcion'])? ' Sin descripcion' : $this->fields['descripcion']) . "',
									 	duracion_modificado = '{$this->fields['duracion']}',
									 	duracion_cobrada_modificado = '{$this->fields['duracion_cobrada']}',
									 	id_usuario_trabajador_modificado = '{$this->fields['id_usuario']}',
									 	accion = 'CREAR',
									 	codigo_asunto_modificado = '".mysql_real_escape_string($this->fields['codigo_asunto'])."',
									 	cobrable_modificado = '{$this->fields['cobrable']}'";
			}
		}

		if (parent::Write()) {
			// Modificamos un trabajo que ya existÃ­a, logueamos el cambio.
			if( $ingreso_historial ) {
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}
			return true;
		} else {
			return false;
		}
	}

	function Check() {
		if ($this->Loaded() && $this->changes['fecha']
				&& !in_array($this->fields['estado_cobro'], array('', 'SIN COBRO', 'CREADO', 'EN REVISION'))) {
			$this->error = 'No se puede mover un trabajo cobrado';
			return false;
		}

		if ($this->changes['fecha'] || $this->changes['id_usuario']
			|| $this->changes['id_trabajo'] || $this->changes['duracion']) {
			$horasenfecha = $this->HorasEnFecha($this->fields['fecha'],
				$this->fields['id_usuario'], $this->fields['id_trabajo']);

			$duracion = $this->fields['duracion'];
			$duracionsegundos = strtotime($duracion) - strtotime('today');
			$totaldiacondicional = ($horasenfecha['duracion'] + $duracionsegundos);

			if ($totaldiacondicional >= 86400) {
				$this->error = 'No se puede trabajar más de 24 horas diarias';
				return false;
			}
		}

		return true;
	}

	public function ValidarDiasIngresoTrabajo(){
		if($this->sesion->usuario->fields['dias_ingreso_trabajo']){
			//solo validar la fecha tope si no tiene permiso de cobranza
			if ($this->sesion->usuario->TienePermiso('COB')) {
				return true;
			}

			$fecha_tope = time() - ($this->sesion->usuario->fields['dias_ingreso_trabajo'] + 1) * 24 * 60 * 60;
			if($fecha_tope > strtotime($this->fields['fecha'])){
				$this->error = 'No se puede ingresar horas anteriores al ' . date('Y-m-d', $fecha_tope + 24 * 60 * 60);
				return false;
			}
		}
		return true;
	}

	/*
	 * param $fecha fecha que se quiere verificar en formato 'YYYY-MM-DD'
	 * param $id_usuario id usuario, opcional
	 * param $id_trabajo id trabajo, opcional (para no contarlo 2 veces si se edita)
	 * return array $duracion, un array con llaves duracion y duracion_cobrada
	 */
	function HorasEnFecha($fecha = null, $id_usuario = null, $id_trabajo = null) {
		if ($fecha == null) {
			$fecha = date('Y-m-d');
		}

		$queryhoras = "SELECT
										SUM(TIME_TO_SEC(duracion)) AS duracion,
										SUM(TIME_TO_SEC(duracion_cobrada)) AS duracion_cobrada
									FROM trabajo
									WHERE fecha = '$fecha'";

		if ($id_usuario != null) {
			$queryhoras .= " AND id_usuario = '$id_usuario'";
		}
		if (!empty($id_trabajo)) {
			$queryhoras .= " AND id_trabajo != '$id_trabajo'";
		}
 		$duracion = $this->sesion->pdodbh->query($queryhoras)->fetchAll(PDO::FETCH_ASSOC);
		return $duracion[0];
	}

	function InsertarTrabajoTarifa() {
		$id_trabajo = $this->fields['id_trabajo'];
		$codigo_asunto = $this->fields['codigo_asunto'];
		$id_usuario = $this->fields['id_usuario'];
		$dbh = $this->sesion->dbh;

		$contrato = new Contrato($this->sesion);
		$contrato->LoadByCodigoAsunto($codigo_asunto);

		$query = "SELECT
								prm_moneda.id_moneda,
								(
									SELECT usuario_tarifa.tarifa
									FROM usuario_tarifa
									LEFT JOIN contrato ON contrato.id_tarifa = usuario_tarifa.id_tarifa
									LEFT JOIN asunto ON asunto.id_contrato = contrato.id_contrato
									WHERE
										usuario_tarifa.id_usuario = '$id_usuario'
										AND asunto.codigo_asunto = '$codigo_asunto'
										AND usuario_tarifa.id_moneda = prm_moneda.id_moneda
								)
								FROM prm_moneda";
		$resp = mysql_query($query, $dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $dbh);

		while (list($id_moneda, $valor) = mysql_fetch_array($resp)) {
			if (empty($valor)) {
				$valor = 0;
			}

			$query_insert = "INSERT INTO trabajo_tarifa
												SET
													id_trabajo = '$id_trabajo',
													id_moneda = '$id_moneda',
													valor = '$valor'
												ON DUPLICATE KEY UPDATE valor = '$valor'";

			mysql_query($query_insert, $dbh) or Utiles::errorSQL($query_insert,__FILE__,__LINE__,$dbh);

			if ($contrato->fields['id_moneda'] == $id_moneda) {
				$this->Edit("tarifa_hh", $valor);
				$this->Write();
			}
		}
	}

	function GetTrabajoTarifa($id_moneda, $id_trabajo = '') {
		if ($id_trabajo == '') {
			$id_trabajo = $this->fields['id_trabajo'];
		}
		$query = "SELECT valor
							FROM trabajo_tarifa
							WHERE
								id_trabajo = '$id_trabajo'
								AND id_moneda = '$id_moneda'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($valor) = mysql_fetch_array($resp);
		return $valor;
	}

	function ActualizarTrabajoTarifa($id_moneda, $valor, $id_trabajo = '') {
		if ($id_trabajo == '') {
			$id_trabajo = $this->fields['id_trabajo'];
		}
		$query = "INSERT INTO trabajo_tarifa
							SET
								id_trabajo = '$id_trabajo',
								id_moneda = '$id_moneda',
								valor = '$valor'
							ON DUPLICATE KEY UPDATE valor = '$valor'";
		mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	}

	function Eliminar() {
		/*if($this->sesion->usuario->fields[id_usuario] != $this->fields[id_usuario])
		{
			$this->error = $this->sesion->usuario->fields[id_usuario]." A ".$this->fields[id_usuario]." No puede eliminar un trabajo que no fue agregado por usted";
			return false;
		}*/
		if ($this->Estado() == "Abierto") {
			// Eliminar el Trabajo del Comentario asociado
			$query = "UPDATE tarea_comentario SET id_trabajo = NULL WHERE id_trabajo = '{$this->fields['id_trabajo']}'";;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

			$query = "DELETE FROM trabajo WHERE id_trabajo = '{$this->fields['id_trabajo']}'";;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			// Si se pudo eliminar, loguear el cambio.
			if ($resp) {
				$query = "INSERT INTO trabajo_historial
										(id_trabajo, id_usuario, fecha, fecha_trabajo, descripcion, duracion, duracion_cobrada, id_usuario_trabajador, accion, codigo_asunto, cobrable)
								 VALUES ('".$this->fields[id_trabajo]."','".$this->sesion->usuario->fields[id_usuario]."','".date("Y-m-d H:i:s")."','".$this->fields['fecha']."','".$this->fields['descripcion']."','".$this->fields['duracion']."','".$this->fields['duracion_cobrada']."',".$this->fields['id_usuario'].",'ELIMINAR','".$this->fields[codigo_asunto]."','".$this->fields[cobrable]."')";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}
		} else {
			$this->error = __("No se puede eliminar un trabajo que no está abierto");
			return false;
		}

		return true;
	}

	// Función que entrega false si supera las 23:59 horas trabajadas un usuario en un dia
	function CantHorasDia($duracion_trabajo, $dia, $id_usuario, $sesion) {
		list($h, $m, $s) = explode(':', $duracion_trabajo);
		$total_minutos = ($h * 60) + $m;

		$query = "SELECT trabajo.duracion
			FROM trabajo
			WHERE trabajo.fecha = '$dia' AND trabajo.id_usuario = '$id_usuario'";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		while ($duracion = mysql_fetch_array($resp)) {
			list($h, $m, $s) = explode(':', $duracion['duracion']);
			$total_minutos += ($h * 60) + $m;
		}

		$minutos_dia_total = Conf::GetConf($sesion, 'CantidadHorasDia') ? Conf::GetConf($sesion, 'CantidadHorasDia') : 1439;

		if ($total_minutos > $minutos_dia_total) {
			return false;
		} else {
			return true;
		}
	}

	static function FechaExcel2sql($dias) {
		// number of seconds in a day
		$seconds_in_a_day = 86400;
		// Unix timestamp to Excel date difference in seconds
		$ut_to_ed_diff = $seconds_in_a_day * 25569;

		return gmdate('Y-m-d',($dias * $seconds_in_a_day) - $ut_to_ed_diff);
	}

	static function ActualizarConExcel($archivo_data, $sesion) {
		/*
		Los campos que pueden ser modificados son:
			fecha
			solicitante (es opcional que aparezca en el excel)
			descripción
			duración cobrable
		*/
		$ingresado_por_decimales = false;

		if (UtilesApp::GetConf($sesion,'TipoIngresoHoras') == 'decimal') {
			$ingresado_por_decimales = true;
		}
		if (!$archivo_data["tmp_name"]) {
			return __('Debe seleccionar un archivo a subir.');
		}
		require_once Conf::ServerDir().'/classes/ExcelReader.php';

		$excel = new Spreadsheet_Excel_Reader();
		if (!$excel->read($archivo_data["tmp_name"])) {
			return __('Error, el archivo no se puede leer, intente nuevamente.');
		}

		$query = "SELECT MAX(id_trabajo) FROM trabajo";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($max_id_trabajo) = mysql_fetch_array($resp);

		// Las columnas están definidas en /interfaces/cobros_xls.php
		$col_id_trabajo = 1;
		$col_fecha_ini = 2;
		$col_fecha_med = 3;
		$col_fecha_fin = 4;

		// Desde aquí en adelante las columnas pueden estar en distintas posiciones.
		$col_solicitante = 23;
		$col_descripcion = 23;
		$col_duracion_cobrable = 23;
		$col_duracion_trabajada = 23;
		$col_abogado = 23;
		$col_asunto = 23;

		// Encontrar en qué fila están los títulos.
		$fila_base=1;
		while(!($excel->sheets[0]['cells'][$fila_base][$col_id_trabajo] == __('N°')
					&& in_array(trim($excel->sheets[0]['cells'][$fila_base][$col_fecha_ini]),array('Dia','Día','Day','Month','Mes')) )
					&& $fila_base<$excel->sheets[0]['numRows']) {
			++$fila_base;
		}
		//Paso de ubicación ini,med,fin (2,3,4) a glosa dia,mes,anyo (2,3,4)-> español (3,2,4)->inglés
		if (in_array(trim($excel->sheets[0]['cells'][$fila_base][$col_fecha_ini]), array('Dia', 'Día', 'Day'))) {
			$col_fecha_dia = $col_fecha_ini;
			$col_fecha_mes = $col_fecha_med;
		} else {
			$col_fecha_dia = $col_fecha_med;
			$col_fecha_mes = $col_fecha_ini;
		}
		$col_fecha_anyo = $col_fecha_fin;

		// Encontrar las posiciones de las columnas, usando los nombres de la base de datos.
		$nombre_descripcion_es = Utiles::glosa($sesion, 'descripcion', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_descripcion_en = Utiles::glosa($sesion, 'descripcion', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_solicitante_es = Utiles::glosa($sesion, 'solicitante', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_solicitante_en = Utiles::glosa($sesion, 'solicitante', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_cobrable_es = Utiles::glosa($sesion, 'duracion_cobrable', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_cobrable_en = Utiles::glosa($sesion, 'duracion_cobrable', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_trabajada_es = Utiles::glosa($sesion, 'duracion_trabajada', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_duracion_trabajada_en = Utiles::glosa($sesion, 'duracion_trabajada', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');
		$nombre_asunto_es = Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo');
		$nombre_asunto_en = Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo');
		$nombre_abogado_es = Utiles::glosa($sesion, 'abogado', 'glosa_es', 'prm_excel_cobro', 'nombre_interno');
		$nombre_abogado_en = Utiles::glosa($sesion, 'abogado', 'glosa_en', 'prm_excel_cobro', 'nombre_interno');

		for ($i = 2; $i < $excel->sheets[0]['numCols']; ++$i) {
			switch ($excel->sheets[0]['cells'][$fila_base][$i]) {
				case $nombre_descripcion_es:
					$col_descripcion = $i;
					break;
				case $nombre_descripcion_en:
					$col_descripcion = $i;
					break;
				case $nombre_solicitante_es:
					$col_solicitante = $i;
					break;
				case $nombre_solicitante_en:
					$col_solicitante = $i;
					break;
				case $nombre_duracion_cobrable_es:
					$col_duracion_cobrable = $i;
					break;
				case $nombre_duracion_cobrable_en:
					$col_duracion_cobrable = $i;
					break;
				case $nombre_duracion_trabajada_es:
					$col_duracion_trabajada = $i;
					break;
				case $nombre_duracion_trabajada_en:
					$col_duracion_trabajada = $i;
					break;
				case $nombre_abogado_es:
					$col_abogado = $i;
					break;
				case $nombre_abogado_en:
					$col_abogado = $i;
					break;
				case $nombre_asunto_es:
					$col_asunto = $i;
					break;
				case $nombre_asunto_en:
					$col_asunto = $i;
			}
		}

		if ($col_descripcion == 23 || $col_duracion_cobrable == 23 || $col_abogado == 23) {
			return __('Error, los nombres de las columnas no corresponden, por favor revise que está subiendo el archivo correcto.');
		}

		// Para dara feedback al usuario.
		$num_modificados = 0;
		$num_insertados = 0;
		$mensajes = '';
		$trabajos_en_hoja = array();
		$cobros_en_excel = array();

		// Leemos todas las hojas
		foreach ($excel->sheets as $hoja) {
			if ($hoja['cells'][1][$col_id_trabajo] == __('N°') ||
					$hoja['cells'][2][$col_id_trabajo] == __('N°') ||
					$hoja['cells'][3][$col_id_trabajo] == __('N°') ||
					$hoja['cells'][4][$col_id_trabajo] == __('N°') ) {
				continue;
			}

			// Busca numero de cobro
			for ($i = 1; $i < $hoja['numCols']; ++$i) {
				$posicion_principal_es = strlen(Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$posicion_principal_en = strlen(Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo'));

				if ( (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_es + 1) > 0 ) {
					$id_cobro = (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_es", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_es + 1);
				} else if( (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_en + 1) > 0 ) {
					$id_cobro = (int) substr(strstr($hoja['cells'][1][$i],Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_en", 'prm_excel_cobro', 'nombre_interno', 'grupo')),$posicion_principal_en + 1);
				}
			}

			if (!$id_cobro) {
				$mensajes .= "<br/>no se pude detectar el numero " . __("del cobro.");
				continue;
			}

			$cobro = new Cobro($sesion);
			$cobro->Load($id_cobro);
			$cobro->LoadAsuntos();
			$continuar = false;
			// Para cambiar el asunto en el caso de ver los asuntos por separado necesitamos una variable para
			// saber en que asunto estamos actualmente, por defecto la definemos vacio para indicar que los asuntos
			// no se muestran por separado.
			$codigo_asunto_escondido = '';

			for ($fila = 1; $fila <= $hoja['numRows']; ++$fila) {
				// Buscamos por indicaciones escondidos en el caso de asuntos por separado para saber en que asunto estamos
				// actualmente.
				if ($hoja['cells'][$fila][$col_fecha] == 'asuntos_separado') {
					$codigo_asunto_escondido = $hoja['cells'][$fila][$col_descripcion];
				}

				// Importan solo las filas que guardan trabajos.
				if (!is_numeric($hoja['cells'][$fila][$col_id_trabajo])
						&& ($hoja['cells'][$fila][$col_id_trabajo]
								|| !$hoja['cells'][$fila][$col_fecha_dia]
								|| !$hoja['cells'][$fila][$col_fecha_mes]
								|| !$hoja['cells'][$fila][$col_fecha_anyo]
								|| !$hoja['cells'][$fila][$col_descripcion]
								|| !$hoja['cells'][$fila][$col_duracion_cobrable]
							)) {
					continue;
				}

				// Para ignorar los lineas escondidas que se usan por el resumen profesional
				if ($continuar) {
					$continuar = false;
					continue;
				}

				if ($hoja['cells'][$fila][$col_fecha] == __('NO MODIFICAR ESTA COLUMNA')
						|| $hoja['cells'][$fila][$col_fecha_dia] == __('NO MODIFICAR ESTA COLUMNA')
						|| $hoja['cells'][$fila][$col_fecha_mes] == __('NO MODIFICAR ESTA COLUMNA') )	{
					$continuar = true;
					continue;
				}

				// Leemos los campos del trabajo en el excel
				$id_trabajo = $hoja['cells'][$fila][$col_id_trabajo];

				$descripcion = $hoja['cells'][$fila][$col_descripcion];

				//La fecha se genera concatenando.
				$error_en_fecha = '';

				$dia = intval($hoja['cells'][$fila][$col_fecha_dia]);
				$mes = intval($hoja['cells'][$fila][$col_fecha_mes]);

				if ($mes < 1 && $mes > 13) {
					$mensajes .= "Error en el trabajo $id_trabajo - Fecha: mes = {$hoja['cells'][$fila][$col_fecha_mes]}<br />";
					continue;
				}

				if ($dia < 1 && $dia > 32) {
					$mensajes .= "Error en el trabajo $id_trabajo - Fecha: día = {$hoja['cells'][$fila][$col_fecha_dia]}<br />";
					continue;
				}

				if ($hoja['cells'][$fila][$col_fecha_anyo] < 1930) {
					$mensajes .= "Error en el trabajo $id_cobro $id_trabajo - Fecha: año = {$hoja['cells'][$fila][$col_fecha_anyo]}<br />";
					continue;
				}

				if ($mes < 10) {
					$mes = "0$mes";
				}
				if ($dia < 10) {
					$dia = "0$dia";
				}

				$fecha = $hoja['cells'][$fila][$col_fecha_anyo].'-'.$mes.'-'.$dia;

				if ($col_solicitante != 23) {
					$solicitante = $hoja['cells'][$fila][$col_solicitante];
				}

				$abogado = $hoja['cells'][$fila][$col_abogado];
				// cargar usuario con su username
				$usuario = new Usuario($sesion);
				$usuario->LoadByNick( $abogado );

				if (!$usuario->fields['id_usuario'] || $abogado == '') {
					$mensajes .= "No se puede modificar el trabajo $id_trabajo ($descripcion) porque el $nombre_abogado_es ingresado no existe.<br />";
					continue;
				}
				// Excel guarda la duración como número, donde 1 es un día.

				$duracion_cobrable = UtilesApp::tiempoExcelASQL($hoja['cells'][$fila][$col_duracion_cobrable], $ingresado_por_decimales);
				if ($duracion_cobrable != '00:00:00') {
					$cobrable = 1;
				} else {
					$cobrable = 0;
				}

				// Si existe una columna duracion_trabajada, toma el valor si no ponga lo igual a la duracion cobrable.
				if ($col_duracion_trabajada != 23) {
					$duracion_trabajada = UtilesApp::tiempoExcelASQL($hoja['cells'][$fila][$col_duracion_trabajada], $ingresado_por_decimales);
				} else {
					$duracion_trabajada = $duracion_cobrable;
				}

				// Revisamos la variable $codigo_asunto_escondido:
				// Por sia caso que tiene valor sabemos que los asuntos se muestran por separado y que el asunto actual
				// es igual al valor de la variable, si no tiene valor tenemos que revisar la columna $col_asunto y averiguar
				//	si el valor indicado ahi esta valido.
			 	if ($codigo_asunto_escondido != '') {
			 		$codigo_asunto = $codigo_asunto_escondido;
			 	} else {
	 				// Por defecto seleccionamos el primer asunto del cobro, si es que el usuario ha indica un codigo,
					// veremos si esta coincide con codigo_asunto o codigo_asunto_secundario de uno de lo asuntos dentro
					// del cobro, y selecionamos el asunto correspondiente. Si no corresponde con nada avisamos al cliente
					// que el codigo ingresado no existe.
					$asunto_data = $hoja['cells'][$fila][$col_asunto];
					$codigo_asunto = $cobro->asuntos[0];
					if ($asunto_data != '') {
						$codigo_existe = false;
						foreach ($cobro->asuntos as $asunto => $data) {
							$asunto = new Asunto($sesion);
							$asunto->LoadByCodigo($data);
							if (substr($asunto->fields['codigo_asunto'],-4) == $asunto_data || $asunto->fields['codigo_asunto'] == $asunto_data) {
								$codigo_asunto = $data;
								$codigo_existe = true;
								break;
							} else if (substr($asunto->fields['codigo_asunto_secundario'],-4)==$asunto_data || $asunto->fields['codigo_asunto_secundario'] == $asunto_data) {
								$codigo_asunto = $data;
								$codigo_existe = true;
								break;
							}
						}
						if (!$codigo_existe) {
							$mensajes .= "<br />No se puede modificar el trabajo $id_trabajo ($descripcion) porque el codigo de asunto ingresado (cod: $asunto_data) no existe.<br/>";
							continue;
						}
					}
				}

				// Tratamos de cargar el trabajo antes de los cambios.
				$trabajo_original = new Trabajo($sesion);
				$trabajo_original->Load($id_trabajo);
				// Si no existe el original o no hay cambios ignoramos la línea.
				if (($id_trabajo > 0 && !$trabajo_original)
						|| ($trabajo_original->fields['fecha'] == $fecha
						&& ($col_solicitante == 23 || $trabajo_original->fields['solicitante'] == $solicitante)
						&& ($col_duracion_trabajada == 23 || $trabajo_original->fields['duracion'] == $duracion_trabajada )
						&& $trabajo_original->fields['descripcion'] == $descripcion
						&& (($col_asunto == 23 && $codigo_asunto_escondido == '') || $trabajo_original->fields['codigo_asunto'] == $codigo_asunto)
						&& ($trabajo_original->fields['duracion_cobrada'] == $duracion_cobrable || $trabajo_original->fields['duracion_cobrada'] == '' && $duracion_cobrable=='00:00:00')
						&& $trabajo_original->fields['id_usuario'] == $usuario->fields['id_usuario']
						&& $trabajo_original->fields['id_cobro'] == $id_cobro)) {
					continue;
				}

				if (!$id_trabajo) {
					$tarifa_hh = Funciones::Tarifa($sesion,$usuario->fields['id_usuario'],$cobro->fields['id_moneda'],$codigo_asunto);
					$costo_hh = Funciones::TarifaDefecto($sesion,$usuario->fields['id_usuario'],$cobro->fields['id_moneda']);
					$tarifa_hh_estandar = Funciones::MejorTarifa($sesion,$usuario->fields['id_usuario'],$cobro->fields['id_moneda'],$id_cobro);

						$query = "INSERT INTO trabajo
											SET
												codigo_asunto = '$codigo_asunto',
												id_cobro = '$id_cobro',
												id_usuario = {$usuario->fields['id_usuario']}'',
												descripcion = '$descripcion',
												fecha = '$fecha',
												duracion = '$duracion_trabajada',
												duracion_cobrada = '$duracion_cobrable',
												" . ($col_solicitante != 23 ? "solicitante = '" . addslashes($solicitante) . "'," : '') . "
												tarifa_hh = '$tarifa_hh',
												costo_hh = '$costo_hh',
												tarifa_hh_estandar = '$tarifa_hh_estandar',
												fecha_creacion = NOW(),
												fecha_modificacion = NOW()";
							mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
							++$num_insertados;
				} else {
					$estado_cobro = Utiles::Glosa($sesion, $trabajo_original->fields['id_cobro'], 'estado', 'cobro');
					if ($estado_cobro == 'No existe información') {
						continue;
					}
					if ($estado_cobro != 'CREADO' && $estado_cobro != 'EN REVISION'  && !$sesion->usuario->TienePermiso('SADM')) {
						$mensajes .= "No se puede modificar el trabajo $id_trabajo ($descripcion) porque " . __("el cobro") . " se encuentra en estado $estado_cobro.<br />";
					  continue;
					}

					// Respaldar el trabajo antes de modificarlo.
					$query = "INSERT INTO trabajo_respaldo_excel
										SET
											id_trabajo = '$id_trabajo',
											fecha = '{$trabajo_original->fields['fecha']}',
											codigo_asunto = '{$trabajo_original->fields['codigo_asunto']}',
											id_cobro = '{$trabajo_original->fields['id_cobro']}',
											id_usuario = '{$trabajo_original->fields['id_usuario']}',
											" . ($col_solicitante != 23 ? "solicitante = '" . addslashes($trabajo_original->fields['solicitante']) . "'," : '') . "
											descripcion = '" . addslashes($trabajo_original->fields['descripcion']) . "',
											duracion_cobrada = '{$trabajo_original->fields['duracion_cobrada']}'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

					// Anotar el cambio en el historial.
					$query = "SELECT MAX(id_trabajo_respaldo_excel) FROM trabajo_respaldo_excel";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
					list($id_trabajo_respaldo_excel) = mysql_fetch_array($resp);
					$query = "INSERT INTO trabajo_historial
										SET
											id_trabajo = '$id_trabajo',
											id_usuario = '{$sesion->usuario->fields['id_usuario']}',
											fecha = '" . date("Y-m-d H:i:s") . "',
											fecha_trabajo = '{$trabajo_original->fields['fecha']}',
											fecha_trabajo_modificado = '$fecha',
											descripcion = '" . addslashes($trabajo_original->fields['descripcion']) . "',
											descripcion_modificado = '" . addslashes($descripcion) . "',
											duracion_cobrada = '$duracion_cobrable',
											duracion_cobrada_modificado = '{$trabajo_original->fields['duracion_cobrada']}',
											id_usuario_trabajador = '{$trabajo_original->fields['id_usuario']}',
											id_usuario_trabajador_modificado = '{$usuario->fields['id_usuario']}',
											accion = 'SUBIR_XLS',
											codigo_asunt = '{$trabajo_original->fields['codigo_asunto']}',
											codigo_asunto_modificado = '$codigo_asunto',
											cobrable = '{$trabajo_original->fields['cobrable']}',
											cobrable_modificado = '$cobrable',
											id_trabajo_respaldo_excel = '$id_trabajo_respaldo_excel'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

					// Actualizar el trabajo.
					$query = "UPDATE trabajo
							SET fecha='$fecha',
									".($abogado==''?'':"id_usuario = ".$usuario->fields['id_usuario'].", ")."
									".((($asunto_data==''||$col_asunto==23)&&$codigo_asunto_escondido=='')?'':"codigo_asunto = '".$codigo_asunto."', ")."
									id_cobro = ".$id_cobro.",
									".($col_solicitante != 23?"solicitante='".addslashes($solicitante)."',":'')."
									descripcion='".addslashes($descripcion)."',
									".($col_duracion_trabajada != 23?"duracion='".$duracion_trabajada."',":'')."
									duracion_cobrada='$duracion_cobrable'
							WHERE id_trabajo='$id_trabajo'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
					++$num_modificados;
				}
			}
		}

		if ($num_modificados == 1) {
			if ($num_insertados == 1) {
				return "$mensajes$num_modificados trabajo actualizado.<br/>$num_insertados trabajo agregado.";
			} else {
				return "$mensajes$num_modificados trabajo actualizado.<br/>$num_insertados trabajos agregados.";
			}
		} else {
			if ($num_insertados == 1) {
				return "$mensajes$num_modificados trabajos actualizados.<br/>$num_insertados trabajo agregado.";
			} else {
				return "$mensajes$num_modificados trabajos actualizados.<br/>$num_insertados trabajos agregados.";
			}
		}
	}

	/**
	 * Find all works by user id
	 * Returns an array with next elements:
	 *  id, creation_date, date, duration, notes
	 *  rate, read_only, requester, activity_code, area_code
	 *  client_code, matter_code, task_code, user_id
	 *  billable, visible
	 */
	function findAllWorksByUserId($id, $before = null, $after = null) {
		$works = array();

		$sql = "SELECT `work`.`id_trabajo` AS `id`, `work`.`fecha_creacion` AS `creation_date`,
			`work`.`fecha` AS `date`, TIME_TO_SEC(`work`.`duracion`)/60.0 AS `duration`, `work`.`descripcion` AS `notes`,
			`work`.`tarifa_hh` AS `rate`, `work`.`solicitante` AS `requester`,
			`work`.`codigo_actividad` AS `activity_code`, `work`.`id_area_trabajo` AS `area_code`,
			`matter`.`codigo_cliente` AS `client_code`, `work`.`codigo_asunto` AS `matter_code`,
			`work`.`codigo_tarea` AS `task_code`, `work`.`id_usuario` AS `user_id`,
			`work`.`cobrable` AS `billable`, `work`.`visible` AS `visible`, (ADDDATE(`work`.`fecha_creacion`, INTERVAL `user`.`retraso_max` DAY)) AS `date_read_only`, `charge`.`estado` AS `charge_status`,
			`work`.`revisado` AS `revised`
			FROM `trabajo` AS `work`
				INNER JOIN `asunto` AS `matter` ON `matter`.`codigo_asunto` = `work`.`codigo_asunto`
				INNER JOIN `usuario` AS `user` ON `user`.`id_usuario` = `work`.`id_usuario`
				LEFT JOIN `cobro` AS `charge` ON `charge`.`id_cobro` = `work`.`id_cobro`
			WHERE `work`.`id_usuario`=:id AND `work`.`fecha` BETWEEN :after AND :before
			ORDER BY `work`.`id_trabajo` DESC";

		if (is_null($before) || is_null($after)) {
			$before = date('Y-m-d 00:00:00');
			$after = date('Y-m-d 23:59:59');
		}

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);
		$Statement->bindParam('before', $before);
		$Statement->bindParam('after', $after);
		$Statement->execute();

		$date_now = strtotime('now');
		while ($work = $Statement->fetch(PDO::FETCH_OBJ)) {
			$read_only = 0;
			if (!empty($work->date_read_only)) {
				if ($date_now >= strtotime($work->date_read_only)) {
					$read_only = 1;
				}
			}

			if (!in_array($work->charge_status, array('CREADO', 'EN REVISION', '', 'SIN COBRO', null))) {
				$read_only = 1;
			}

			if ($work->revised == '1') {
				$read_only = 1;
			}

			array_push($works,
				array(
					'id' => (int) $work->id,
					'creation_date' => !empty($work->creation_date) ? strtotime($work->creation_date) : null,
					'date' => !empty($work->date) ? strtotime($work->date) : null,
					'duration' => !empty($work->duration) ? (float) $work->duration : null,
					'notes' => !empty($work->notes) ? $work->notes : null,
					'rate' => !empty($work->rate) ? (float) $work->rate : null,
					'read_only' => $read_only,
					'requester' => !empty($work->requester) ? $work->requester : null,
					'activity_code' => !empty($work->activity_code) ? $work->activity_code : null,
					'area_code' => !empty($work->area_code) ? $work->area_code : null,
					'client_code' => !empty($work->client_code) ? $work->client_code : null,
					'matter_code' => !empty($work->matter_code) ? $work->matter_code : null,
					'task_code' => !empty($work->task_code) ? $work->task_code : null,
					'user_id' => !empty($work->user_id) ? (int) $work->user_id : null,
					'billable' => !empty($work->billable) ? (int) $work->billable : null,
					'visible' => !empty($work->visible) ? (int) $work->visible : null
				)
			);
		}

		return $works;
	}

	/**
	 * validate data of a work
	 * Returns an array with next elements:
	 *  error (bool) and description (string) of error
	 */
	function validateDataOfWork($data) {
		$created_date = date('Y-m-d H:i:s');

		if (!empty($data['id'])) {
			if ($this->Loaded()) {
				$created_date = $this->fields['fecha_creacion'];
				$charge_status = $this->Estado();
				if (($charge_status == 'Cobrado' || $charge_status == __('Cobrado'))) {
					return array('error' => true, 'description' => "Work is already charged");
				} else if ($charge_status == 'Revisado' || $charge_status == __('Revisado')) {
					return array('error' => true, 'description' => "The work is revised");
				}
			} else {
				return array('error' => true, 'description' => "The work doesn't exist");
			}
		}

		if (empty($data['duration']) || $data['duration'] == '00:00:00') {
			return array('error' => true, 'description' => "The hours entered must be greater than 0");
		}

		$duration = $data['duration'];
		if (!empty($this->fields['duracion'])) {
			$duration = $duration - $this->fields['duracion'];
		}

		if (empty($data['user_id'])) {
			return array('error' => true, 'description' => "Invalid user ID");
		} else {
			$User = new Usuario($this->sesion);
			if (!$User->LoadId($data['user_id'])) {
				return array('error' => true, 'description' => "The user doesn't exist");
			} else {
				if (!$this->validateWorkingDays($User->fields['dias_ingreso_trabajo'], $created_date, $data['date'])) {
					return array('error' => true, 'description' => $this->error . ' ' . strtotime('2013-04-02'));
				}
			}
		}

		if (!Trabajo::CantHorasDia($duration, $data['date'], $data['user_id'], $this->sesion)) {
			$total_minutes_per_day = Conf::GetConf($this->sesion, 'CantidadHorasDia') ? Conf::GetConf($this->sesion, 'CantidadHorasDia') : 1439;
			$total_minutes_per_day = date('H:i', mktime(0, $total_minutes_per_day, 0, 0, 0, 0));
			return array('error' => true, 'description' => "You can not enter more than {$total_minutes_per_day} hours per day");
		}

		if (empty($data['notes'])) {
			return array('error' => true, 'description' => "Invalid notes");
		}

		if (Conf::GetConf($this->sesion, 'UsoActividades')) {
			if (!empty($data['activity_code'])) {
				$Activity = new Actividad($this->sesion);
				if (!$Activity->loadByCode($data['activity_code'])) {
					return array('error' => true, 'description' => "The activity doesn't exist");
				}
			}
		}

		if (Conf::GetConf($this->sesion, 'UsarAreaTrabajos')) {
			if (!empty($data['area_code'])) {
				$WorkArea = new AreaTrabajo($this->sesion);
				if (!$WorkArea->Load($data['area_code'])) {
					return array('error' => true, 'description' => "The area code doesn't exist");
				}
			} else {
				return array('error' => true, 'description' => "Invalid area code");
			}
		}

		if (empty($data['matter_code']))  {
			return array('error' => true, 'description' => "Invalid matter code");
		} else {
			$Matter = new Asunto($this->sesion);
			if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
				$Matter->LoadByCodigoSecundario($data['matter_code']);
			} else {
				$Matter->LoadByCodigo($data['matter_code']);
			}
			if (!$Matter->Loaded()) {
				return array('error' => true, 'description' => "The matter doesn't exist");
			}
		}

		return array('error' => false, 'description' => '');
	}

	public function validateWorkingDays($income_working_days = null, $created_date = null, $date = null) {
		if ($income_working_days) {
			$deadline = strtotime($created_date) - ($income_working_days + 1) * 24 * 60 * 60;
			if ($deadline > strtotime($date)) {
				$this->error = "You can not enter hours prior to " . date('Y-m-d', $deadline + 24 * 60 * 60);
				return false;
			}
		}

		return true;
	}

	/**
	 * Save data
	 * returns a bool if the update or insert completed successfully
	 */
	function save($data) {
		$change_matter = false;
		$type_income_hour = Conf::GetConf($this->sesion, 'TipoIngresoHoras');
		$update_rate_work = true;

		$Matter = new Asunto($this->sesion);
		$matter_code = $data['matter_code'];

		if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
			$Matter->LoadByCodigoSecundario($matter_code);
			$matter_code = $Matter->fields['codigo_asunto'];
		} else {
			$Matter->LoadByCodigo($matter_code);
			$seconds_matter_code = $Matter->fields['codigo_asunto_secundario'];
		}

		$duration = $type_income_hour == 'decimal' ? UtilesApp::Decimal2Time($data['duration']) : $data['duration'];

		if ($this->Loaded()) {
			$update_rate_work = false;

			// revisar para codigo secundario
			if ($matter_code != $this->fields['codigo_asunto']) {
				$PreviousContract = new Contrato($sesion);
				$ModifiedContract = new Contrato($sesion);

				$PreviousContract->LoadByCodigoAsunto($this->fields['codigo_asunto']);
				$ModifiedContract->LoadByCodigoAsunto($matter_code);

				if ($PreviousContract->fields['id_tarifa'] != $ModifiedContract->fields['id_tarifa']) {
					$update_rate_work = true;
				}

				$change_matter = true;
			}

			$change_duration = strtotime($duration) != strtotime($this->fields['duracion']);
		}

		if ($change_matter) {
			$Charge = new Cobro($this->sesion);
			$charge_id = $Charge->ObtieneCobroByCodigoAsunto($matter_code, $this->fields['fecha']);
			$this->Edit('id_cobro', !empty($charge_id) ? $charge_id : 'NULL');
		}

		$this->Edit('duracion', $duration);
		$this->Edit('duracion_cobrada', $type_income_hour == 'decimal' ? UtilesApp::Decimal2Time($duration) : $duration);

		$sql = "SELECT `user`.`id_categoria_usuario` FROM `usuario` AS `user` WHERE `user`.`id_usuario`=:user_id";
		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('user_id', $user_id);
		$Statement->execute();
		$user_data = $Statement->fetchObject();
		if (is_object($user_data)) {
			$this->Edit('id_categoria_usuario', !empty($user_data->id_categoria_usuario) ? $user_data->id_categoria_usuario : 'NULL');
		} else {
			$this->Edit('id_categoria_usuario', 'NULL');
		}

		$this->Edit('codigo_asunto', $matter_code);

		$ordenado_por = Conf::GetConf($this->sesion, 'OrdenadoPor');
		if (in_array($ordenado_por, array(1, 2))) {
			$this->Edit('solicitante', addslashes($data['requester']));
		}

		if (Conf::GetConf($this->sesion, 'UsoActividades')) {
			$this->Edit('codigo_actividad', !empty($data['activity_code']) ? $data['activity_code'] : 'NULL');
		}

		if (Conf::GetConf($this->sesion, 'UsarAreaTrabajos')) {
			$this->Edit('id_area_trabajo', !empty($data['area_code']) ? $data['area_code'] : 'NULL');
		}

		if (Conf::GetConf($this->sesion, 'TodoMayuscula')) {
			$this->Edit('descripcion', strtoupper($data['notes']));
		} else {
			$this->Edit('descripcion', $data['notes']);
		}

		$cambio_fecha = strtotime($this->fields['fecha']) != strtotime($data['date']);
		$this->Edit('fecha', $data['date']);

		$this->Edit('codigo_tarea', !empty($data['task_code']) ? $data['task_code'] : 'NULL');

		if ($data['billable'] == 0) {
			$this->Edit('cobrable', 0);
			$this->Edit('visible', $data['visible']);
		} else {
			$this->Edit('cobrable', 1);
			$this->Edit('visible', 1);
		}

		// Si el asunto no es cobrable
		if ($asunto->fields['cobrable'] == 0) {
			$this->Edit('cobrable', 0);
			$this->Edit('visible', 0);
		}

		if (empty($data['id'])) {
			$this->Edit('id_usuario', $data['user_id']);
		}

		$this->Edit('tarifa_hh', $data['rate']);

		// Agregar valores de tarifa
		$asunto = new Asunto($this->sesion);
		$asunto->LoadByCodigo($this->fields['codigo_asunto']);
		$contrato = new Contrato($this->sesion);
		$contrato->Load($asunto->fields['id_contrato']);

		if (!$t->fields['costo_hh']) {
			$this->Edit('costo_hh', Funciones::TarifaDefecto($this->sesion, $data['user_id'], $contrato->fields['id_moneda']));
		}

		$this->Edit('codigo_asunto', $data['matter_code']);

		if ($this->Write(true)) {
			if (!empty($data['user_id'])) {
				$sql = "UPDATE `usuario` AS `user` SET `user`.`retraso_max_notificado`=0 WHERE `user`.`id_usuario`=:user_id";
				$Statement = $this->sesion->pdodbh->prepare($sql);
				$Statement->bindParam('user_id', $data['user_id']);
				$Statement->execute();
			}

			if ($update_rate_work == true) {
				$this->InsertarTrabajoTarifa();
			}
			return true;
		}

		return false;
	}
}

if (!class_exists('ListaTrabajos')) {
	class ListaTrabajos extends Lista
	{
		function ListaTrabajos($sesion, $params, $query)
		{
			$this->Lista($sesion, 'Trabajo', $params, $query);
		}
	}
}
