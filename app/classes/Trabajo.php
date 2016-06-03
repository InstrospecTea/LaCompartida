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
		if (!$this->fields['estadocobro'] && $this->fields['id_cobro']) {
			$cobro = new Cobro($this->sesion);
			$cobro->Load($this->fields['id_cobro']);
			$this->fields['estadocobro'] = $cobro->fields['estado'];
		}

		if ($this->fields['estadocobro'] <> 'CREADO'
			&& $this->fields['estadocobro'] <> 'EN REVISION'
			&& $this->fields['estadocobro'] != ''
			&& $this->fields['estadocobro'] <> 'SIN COBRO') {
			return __('Cobrado');
		}

		if ($this->fields['revisado'] == 1) {
			return __('Revisado');
		}

		return __('Abierto');
	}

	function Write($writeLog = true) {
		$this->Prepare();
		if (!$this->Check()) {
			return false;
		}
		$workService = new WorkService($this->sesion);
		$work = new Work();
		$work->fillFromArray($this->fields);
		$work->fillChangedFields($this->changes);

		try {
			$work = $workService->saveOrUpdate($work, $writeLog);
			$this->fields = $work->fields;
			return true;
		} catch(ServiceException $ex) {
			$newrelic = new NewRelic('TrabajoWrite');
			$newrelic->addMessage($ex->getMessage())
				->addMessage($ex->getFile())
				->addMessage($ex->getLine())
				->notice();
			return false;
		}
	}

	function Check() {
		if ($this->Loaded() && $this->changes['fecha'] && !in_array($this->fields['estado_cobro'], array('', 'SIN COBRO', 'CREADO', 'EN REVISION'))) {
			$this->error = 'No se puede mover un trabajo cobrado';
			return false;
		}

		if ($this->changes['fecha'] || $this->changes['id_usuario'] || $this->changes['id_trabajo'] || $this->changes['duracion']) {
			$horasenfecha = $this->HorasEnFecha(
				$this->fields['fecha'],
				$this->fields['id_usuario'],
				$this->fields['id_trabajo']
			);

			// la duración del trabajo que se está editanto se transforma a segundos
			$parsed = date_parse($this->fields['duracion']);
			$work_seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

			// la suma total de horas del día vienen en segundos según el método "HorasEnFecha"
			$works_seconds = $horasenfecha['duracion'];

			// se suman las horas del trabajo mas el total de horas del día
			$seconds_total_day = $works_seconds + $work_seconds;

			// 86400 = 24 horas
			if ($seconds_total_day >= 86400) {
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
		if (is_null($fecha)) {
			$fecha = date('Y-m-d');
		}
		$criteria = new Criteria($this->sesion);
		$criteria->add_select('SUM(TIME_TO_SEC(duracion))', 'duracion');
		$criteria->add_select('SUM(TIME_TO_SEC(duracion_cobrada))', 'duracion_cobrada');
		$criteria->add_from('trabajo');
		$clauses[] = CriteriaRestriction::equals('fecha', "'$fecha'");
		if (!is_null($id_usuario)) {
			$clauses[] = CriteriaRestriction::equals('id_usuario', "'$id_usuario'");
		}
		if (!empty($id_trabajo)) {
			$clauses[] = CriteriaRestriction::not_equal('id_trabajo', "'$id_trabajo'");
		}
		$criteria->add_restriction(
			CriteriaRestriction::and_clause($clauses)
		);
		$result = $criteria->run();
		return $result[0];
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
			$valor_estandar = Funciones::TarifaDefecto($this->sesion, $id_usuario, $id_moneda);

			$query_insert = "INSERT INTO trabajo_tarifa
												SET
													id_trabajo = '$id_trabajo',
													id_moneda = '$id_moneda',
													valor = '$valor',
													valor_estandar = '$valor_estandar'
												ON DUPLICATE KEY UPDATE valor = '$valor', valor_estandar = '$valor_estandar'";

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

	function ActualizarTrabajoTarifa($id_moneda, $valor, $id_trabajo = '', $valor_estandar) {
		if ($id_trabajo == '') {
			$id_trabajo = $this->fields['id_trabajo'];
		}
		$query = "INSERT INTO trabajo_tarifa
							SET
								id_trabajo = '$id_trabajo',
								id_moneda = '$id_moneda',
								valor = '$valor',
								valor_estandar = '$valor_estandar'
							ON DUPLICATE KEY UPDATE valor = '$valor', valor_estandar = '$valor_estandar'";
		mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	}

	function Eliminar() {
		/*if($this->sesion->usuario->fields[id_usuario] != $this->fields[id_usuario]) {
			$this->error = $this->sesion->usuario->fields[id_usuario]." A ".$this->fields[id_usuario]." No puede eliminar un trabajo que no fue agregado por usted";
			return false;
		}*/
		if ($this->Estado() == "Abierto") {

			$workService = new WorkService($this->sesion);
			$work = new Work();
			$work->fillFromArray($this->fields);
			$workService->delete($work);

			// Eliminar el Trabajo del Comentario asociado
			$query = "UPDATE tarea_comentario SET id_trabajo = NULL WHERE id_trabajo = '{$this->fields['id_trabajo']}'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

			$query = "DELETE FROM trabajo WHERE id_trabajo = '{$this->fields['id_trabajo']}'";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			// Si se pudo eliminar, loguear el cambio.

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

		for ($i = 1; $i <= $excel->sheets[0]['numCols']; $i++) {
			$nombre_columna = $excel->sheets[0]['cells'][$fila_base][$i];

			if (is_null($nombre_columna) || empty($nombre_columna)) {
				continue;
			}

			switch ($nombre_columna) {
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
			if (! $cobro->Load($id_cobro)) {
				return __('El cobro') . ' ' . _('que intenta modificar no se encuentra en el sistema') . '.';
			}
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
				$usuario->LoadByNick($abogado);

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
							$mensajes .= "<br />No se puede modificar el trabajo $id_trabajo ($descripcion) porque el codigo de asunto ingresado (cod: $asunto_data) no esta asociado al cobro.<br/>";
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
												id_usuario = '{$usuario->fields['id_usuario']}',
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
											fecha_accion = '" . date("Y-m-d H:i:s") . "',
											fecha_trabajo = '{$trabajo_original->fields['fecha']}',
											fecha_trabajo_modificado = '$fecha',
											descripcion = '" . addslashes($trabajo_original->fields['descripcion']) . "',
											descripcion_modificado = '" . addslashes($descripcion) . "',
											duracion_cobrada = '$duracion_cobrable',
											duracion_cobrada_modificado = '{$trabajo_original->fields['duracion_cobrada']}',
											id_usuario_trabajador = '{$trabajo_original->fields['id_usuario']}',
											id_usuario_trabajador_modificado = '{$usuario->fields['id_usuario']}',
											accion = 'SUBIR_XLS',
											codigo_asunto = '{$trabajo_original->fields['codigo_asunto']}',
											codigo_asunto_modificado = '$codigo_asunto',
											cobrable = '{$trabajo_original->fields['cobrable']}',
											cobrable_modificado = '$cobrable',
											id_trabajo_respaldo_excel = '$id_trabajo_respaldo_excel'";
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

					// Actualizar el trabajo.
					$query_usuario = $abogado == '' ? '' : "id_usuario = {$usuario->fields['id_usuario']}, ";
					$query_asunto = (($asunto_data == '' || $col_asunto == 23) && $codigo_asunto_escondido == '') ? '' : "codigo_asunto = '{$codigo_asunto}', ";
					$query_solicitante = $col_solicitante != 23 ? "solicitante = '" . addslashes($solicitante) . "'," : '';
					$_descripcion = addslashes($descripcion);

					$query = "UPDATE trabajo SET
						fecha = '{$fecha}',
						{$query_usuario}
						{$query_asunto}
						id_cobro = {$id_cobro},
						{$query_solicitante}
						descripcion = '{$_descripcion}',
						duracion_cobrada = '{$duracion_cobrable}'
					WHERE id_trabajo = '{$id_trabajo}'";

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
	function findAllWorksByUserId($id, $before = null, $after = null, $includeProject = false) {
		$works = array();

		$sql = "SELECT `work`.`id_trabajo` AS `id`, `work`.`fecha_creacion` AS `creation_date`,
			`work`.`fecha` AS `date`, TIME_TO_SEC(`work`.`duracion`)/60.0 AS `duration`, `work`.`descripcion` AS `notes`,
			`work`.`tarifa_hh` AS `rate`, `work`.`solicitante` AS `requester`,
			`work`.`codigo_actividad` AS `activity_code`, `work`.`id_area_trabajo` AS `area_code`,
			`client`.`codigo_cliente` AS `client_code`, `client`.`codigo_cliente_secundario` AS `secondary_client_code`,
			`matter`.`codigo_asunto` AS `matter_code`, `matter`.`codigo_asunto_secundario` AS `secondary_matter_code`,
			`work`.`codigo_tarea` AS `task_code`, `work`.`id_usuario` AS `user_id`,
			`work`.`cobrable` AS `billable`, `work`.`visible` AS `visible`, (ADDDATE(`work`.`fecha_creacion`, INTERVAL `user`.`retraso_max` DAY)) AS `date_read_only`, `charge`.`estado` AS `charge_status`,
			`work`.`revisado` AS `revised`,
			`matter`.`id_asunto`,
			`client`.`id_cliente`,
			`activity`.`id_actividad`,
			`user`.`retraso_max`,
			`matter`.`glosa_asunto`,
			`matter`.`activo` AS `asunto_activo`,
			`matter`.`id_tipo_asunto`,
			`matter`.`id_area_proyecto`,
			`language`.`codigo_idioma` AS `asunto_codigo_idioma`,
			`language`.`glosa_idioma` AS `asunto_glosa_idioma`
			FROM `trabajo` AS `work`
				INNER JOIN `asunto` AS `matter` ON `matter`.`codigo_asunto` = `work`.`codigo_asunto`
				INNER JOIN `usuario` AS `user` ON `user`.`id_usuario` = `work`.`id_usuario`
				LEFT JOIN `cobro` AS `charge` ON `charge`.`id_cobro` = `work`.`id_cobro`
				INNER JOIN `cliente` AS `client` ON `client`.`codigo_cliente` = `matter`.`codigo_cliente`
				LEFT JOIN `actividad` AS `activity` ON `activity`.`codigo_actividad` = `work`.`codigo_actividad`
				LEFT JOIN `prm_idioma` AS `language` ON `language`.`id_idioma`= `matter`.`id_idioma`
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
			if (!empty($work->date_read_only) && ($work->retraso_max > 0)) {
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

			$mapped_work = array(
				'id' => (int) $work->id,
				'creation_date' => !empty($work->creation_date) ? strtotime($work->creation_date) : null,
				'date' => !empty($work->date) ? strtotime($work->date) : null,
				'string_creation_date' => !empty($work->creation_date) ? date('Y-m-d', strtotime($work->creation_date)) : null,
				'string_date' => !empty($work->date) ? date('Y-m-d', strtotime($work->date)) : null,
				'duration' => !empty($work->duration) ? (float) $work->duration : null,
				'notes' => !empty($work->notes) ? $work->notes : null,
				'read_only' => $read_only,
				'user_id' => !empty($work->user_id) ? (int) $work->user_id : null,
				'billable' => !empty($work->billable) ? (int) $work->billable : 0,
				'visible' => !empty($work->visible) ? (int) $work->visible : 0
			);


			if ($includeProject) {
				$mapped_work['project'] = array(
					'id' => $work->id_asunto,
					'code' => $work->codigo_asunto,
					'name' => $work->glosa_asunto,
					'active' => $work->asunto_activo,
					'client_id' => $work->id_cliente,
					'project_area_id' => $work->id_area_proyecto,
					'project_type_id' => $work->id_tipo_asunto,
					'language_code' => $work->asunto_codigo_idioma,
					'language_name' => $work->asunto_glosa_idioma
				);
			}

			if (!empty($work->rate)) {
				$mapped_work['rate'] = $work->rate;
			}
			if (!empty($work->requester)) {
				$mapped_work['requester'] = $work->requester;
			}
			if (!empty($work->activity_code)) {
				$mapped_work['activity_code'] = $work->activity_code;
			}
			if (!empty($work->area_code)) {
				$mapped_work['area_code'] = $work->area_code;
			}
			if (!empty($work->client_code)) {
				$mapped_work['client_code'] = $work->client_code;
				$mapped_work['secondary_client_code'] = $work->secondary_client_code;
			}
			if (!empty($work->matter_code)) {
				$mapped_work['matter_code'] = $work->matter_code;
				$mapped_work['secondary_matter_code'] = $work->secondary_matter_code;
			}
			if (!empty($work->task_code)) {
				$mapped_work['task_code'] = $work->task_code;
			}

			// para API V2
			if (!empty($work->id_asunto)) {
				$mapped_work['id_asunto'] = (int) $work->id_asunto;
			}
			if (!empty($work->id_cliente)) {
				$mapped_work['id_cliente'] = (int) $work->id_cliente;
			}
			if (!empty($work->id_actividad)) {
				$mapped_work['id_actividad'] = (int) $work->id_actividad;
			}
			if (!empty($work->area_code)) {
				$mapped_work['id_area_trabajo'] = (int) $work->area_code;
			}
			if (!empty($work->codigo_tarea)) {
				$mapped_work['id_tarea'] = (int) $work->codigo_tarea;
			}
			// fin para API V2

			array_push($works, $mapped_work);
		}

		return $works;
	}

	/**
	 * validate data of a work
	 * Returns an array with next elements:
	 *  error (bool) and description (string) of error
	 */
	function validateDataOfWork($data) {

		if (!empty($data['id'])) {
			if ($this->Loaded()) {
				$created_date = $this->fields['fecha_creacion'];
				$charge_status = $this->Estado();
				if (($charge_status == 'Cobrado' || $charge_status == __('Cobrado'))) {
					return array('error' => true, 'description' => __("Work is already charged"));
				} else if ($charge_status == 'Revisado' || $charge_status == __('Revisado')) {
					return array('error' => true, 'description' => __("The work is revised"));
				}
			} else {
				return array('error' => true, 'description' => __("The work doesn't exist"));
			}
		} else {
			$created_date = $data['created_date'];
		}

		if (empty($data['duration']) || $data['duration'] == '00:00:00') {
			return array('error' => true, 'description' => __("The hours entered must be greater than 0"));
		}

		$duration = $data['duration'];
		if (!empty($this->fields['duracion'])) {
			$duration = $duration - $this->fields['duracion'];
		}

		if (empty($data['user_id'])) {
			return array('error' => true, 'description' => __("Invalid user ID"));
		} else {
			$User = new Usuario($this->sesion);
			if (!$User->LoadId($data['user_id'])) {
				return array('error' => true, 'description' => __("The user doesn't exist"));
			} else {
				if (!$this->validateWorkingDays($User->fields['dias_ingreso_trabajo'], $created_date, $data['date'])) {
					return array('error' => true, 'description' => $this->error . ' ' . strtotime('2013-04-02'));
				}
			}
		}

		if (!Trabajo::CantHorasDia($duration, $data['date'], $data['user_id'], $this->sesion)) {
			$total_minutes_per_day = Conf::GetConf($this->sesion, 'CantidadHorasDia') ? Conf::GetConf($this->sesion, 'CantidadHorasDia') : 1439;
			$total_minutes_per_day = date('H:i', mktime(0, $total_minutes_per_day, 0, 0, 0, 0));
			return array('error' => true, 'description' => __("You can not enter more than") . " $total_minutes_per_day " . __("hours per day"));
		}

		if (empty($data['notes'])) {
			return array('error' => true, 'description' => __("Invalid Description"));
		}

		if (Conf::GetConf($this->sesion, 'UsoActividades')) {
			if (!empty($data['activity_code'])) {
				$Activity = new Actividad($this->sesion);
				if (!$Activity->loadByCode($data['activity_code'])) {
					return array('error' => true, 'description' => __("The activity doesn't exist"));
				}
			}
		}

		if (Conf::GetConf($this->sesion, 'UsarAreaTrabajos')) {
			if (!empty($data['area_code'])) {
				$WorkArea = new AreaTrabajo($this->sesion);
				if (!$WorkArea->Load($data['area_code'])) {
					return array('error' => true, 'description' => __("The area code doesn't exist"));
				}
			} else {
				return array('error' => true, 'description' => __("Invalid area code"));
			}
		}

		if (empty($data['matter_code']))  {
			return array('error' => true, 'description' => __("Invalid matter code"));
		} else {
			$Matter = new Asunto($this->sesion);
			if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
				$Matter->LoadByCodigoSecundario($data['matter_code']);
			} else {
				$Matter->LoadByCodigo($data['matter_code']);
			}
			if (!$Matter->Loaded()) {
				return array('error' => true, 'description' => __("The matter doesn't exist"));
			} else {
				if (!$Matter->fields['activo']) {
					return array('error' => true, 'description' => __("The matter is not active"));
				}
			}
		}

		return array('error' => false, 'description' => '');
	}

	public function validateWorkingDays($income_working_days = null, $created_date = null, $date = null) {
		if ($income_working_days) {
			$deadline = strtotime($created_date) - ($income_working_days + 1) * 24 * 60 * 60;
			if ($deadline > strtotime($date)) {
				$this->error = __("You can not enter hours prior to") . " " . date('Y-m-d', $deadline + 24 * 60 * 60);
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

		// convert to latin1 encoding
		foreach ($data as $key => $value) {
			$data[$key] = utf8_decode($value);
		}

		$Matter = new Asunto($this->sesion);
		$matter_code = $data['matter_code'];

		if (Conf::GetConf($this->sesion, 'CodigoSecundario')) {
			$Matter->LoadByCodigoSecundario($matter_code);
			$matter_code = $Matter->fields['codigo_asunto'];
		} else {
			$Matter->LoadByCodigo($matter_code);
			$secondary_matter_code = $Matter->fields['codigo_asunto_secundario'];
		}

		if (!empty($data['billable'])) {
			$billable = 1;
			$visible = 1;
		} else {
			$billable = 0;
			$visible = $data['visible'];
		}

		// Si el asunto no es cobrable
		if ($Matter->Loaded() && $Matter->fields['cobrable'] == 0) {
			$billable = 0;
			$visible = 0;
		}

		$this->Edit('cobrable', $billable);
		$this->Edit('visible', $visible);

		$interval = (int) Conf::GetConf($this->sesion, 'Intervalo');
		$duration = $type_income_hour == 'decimal' ? UtilesApp::Decimal2Time($data['duration']) : $data['duration'];
		list($hh, $mm, $ss) = explode(':', $duration);

		$hh = (int) $hh;
		$mm = (int) $mm;
		$mm_interval = $mm;

		if (fmod($mm, $interval) != 0) {
			$mm_interval = ($mm - fmod($mm, $interval)) + $interval;
		}

		$duration_in_minutes = intval($hh) * 60 + intval($mm_interval);

		$duration_hh = str_pad(floor($duration_in_minutes / 60), 2, '0', STR_PAD_LEFT);
		$duration_mm = str_pad($duration_in_minutes % 60, 2, '0', STR_PAD_LEFT);

		$duration = "{$duration_hh}:{$duration_mm}:00";

		if ($this->Loaded()) {
			$update_rate_work = false;

			// revisar para codigo secundario
			if ($matter_code != $this->fields['codigo_asunto']) {
				$PreviousContract = new Contrato($this->sesion);
				$ModifiedContract = new Contrato($this->sesion);

				$PreviousContract->LoadByCodigoAsunto($this->fields['codigo_asunto']);
				$ModifiedContract->LoadByCodigoAsunto($matter_code);

				if ($PreviousContract->fields['id_tarifa'] != $ModifiedContract->fields['id_tarifa']) {
					$update_rate_work = true;
				}

				$change_matter = true;
			}
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
		$Statement->bindParam('user_id', $user_id );
		$Statement->execute();
		$user_data = $Statement->fetchObject();
		if (is_object($user_data)) {
			$this->Edit('id_categoria_usuario', !empty($user_data->id_categoria_usuario) ? $user_data->id_categoria_usuario : 'NULL');
		} else {
			$this->Edit('id_categoria_usuario', 'NULL');
		}

		$this->Edit('codigo_asunto', $matter_code);

		// Agregar valores de tarifa
		if ($this->Loaded() && $Matter->Loaded()) {
			$Contract = new Contrato($this->sesion);
			$Contract->Load($Matter->fields['id_contrato']);
			if ($Contract->Loaded()) {
				if (!$this->fields['tarifa_hh']) {
					$this->Edit('tarifa_hh', Funciones::Tarifa($this->sesion, $id_usuario, $Contract->fields['id_moneda'], $matter_code));
				}

				if (!$this->fields['costo_hh']) {
					$this->Edit('costo_hh', Funciones::TarifaDefecto($this->sesion, $data['user_id'], $Contract->fields['id_moneda']));
				}
			}
		}

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

		$this->Edit('id_usuario', $data['user_id']);
		$this->Edit('tarifa_hh', $data['rate']);

		if ($this->Write()) {
			if (!empty($data['user_id'])) {
				$sql = "UPDATE `usuario` AS `user` SET `user`.`retraso_max_notificado`= 0 WHERE `user`.`id_usuario`=:user_id";
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

	/**
	 * Find work by work id
	 * Returns an array with next elements:
	 *  id, creation_date, date, duration, notes
	 *  rate, read_only, requester, activity_code, area_code
	 *  client_code, matter_code, task_code, user_id
	 *  billable, visible
	 */
	function findById($id) {
		$_work = array();

		$sql = "SELECT `work`.`id_trabajo` AS `id`, `work`.`fecha_creacion` AS `creation_date`,
			`work`.`fecha` AS `date`, TIME_TO_SEC(`work`.`duracion`)/60.0 AS `duration`, `work`.`descripcion` AS `notes`,
			`work`.`tarifa_hh` AS `rate`, `work`.`solicitante` AS `requester`,
			`work`.`codigo_actividad` AS `activity_code`, `work`.`id_area_trabajo` AS `area_code`,
			`client`.`codigo_cliente` AS `client_code`, `client`.`codigo_cliente_secundario` AS `secondary_client_code`,
			`matter`.`codigo_asunto` AS `matter_code`, `matter`.`codigo_asunto_secundario` AS `secondary_matter_code`,
			`work`.`codigo_tarea` AS `task_code`, `work`.`id_usuario` AS `user_id`,
			`work`.`cobrable` AS `billable`, `work`.`visible` AS `visible`, (ADDDATE(`work`.`fecha_creacion`, INTERVAL `user`.`retraso_max` DAY)) AS `date_read_only`, `charge`.`estado` AS `charge_status`,
			`work`.`revisado` AS `revised`,
			`matter`.`id_asunto`,
			`client`.`id_cliente`,
			`activity`.`id_actividad`,
			`user`.`retraso_max`
			FROM `trabajo` AS `work`
				INNER JOIN `asunto` AS `matter` ON `matter`.`codigo_asunto` = `work`.`codigo_asunto`
				INNER JOIN `usuario` AS `user` ON `user`.`id_usuario` = `work`.`id_usuario`
				LEFT JOIN `cobro` AS `charge` ON `charge`.`id_cobro` = `work`.`id_cobro`
				INNER JOIN `cliente` AS `client` ON `client`.`codigo_cliente` = `matter`.`codigo_cliente`
				LEFT JOIN `actividad` AS `activity` ON `activity`.`codigo_actividad` = `work`.`codigo_actividad`
			WHERE `work`.`id_trabajo`=:id
			ORDER BY `work`.`id_trabajo` DESC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('id', $id);
		$Statement->execute();

		$date_now = strtotime('now');
		$work = $Statement->fetch(PDO::FETCH_OBJ);
		$read_only = 0;
		if (!empty($work->date_read_only) && ($work->retraso_max > 0)) {
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

		$_work = array(
			'id' => (int) $work->id,
			'creation_date' => !empty($work->creation_date) ? strtotime($work->creation_date) : null,
			'date' => !empty($work->date) ? strtotime($work->date) : null,
			'string_creation_date' => !empty($work->creation_date) ? date('Y-m-d', strtotime($work->creation_date)) : null,
			'string_date' => !empty($work->date) ? date('Y-m-d', strtotime($work->date)) : null,
			'duration' => !empty($work->duration) ? (float) $work->duration : null,
			'notes' => !empty($work->notes) ? $work->notes : null,
			'rate' => !empty($work->rate) ? (float) $work->rate : null,
			'read_only' => $read_only,
			'requester' => !empty($work->requester) ? $work->requester : null,
			'activity_code' => !empty($work->activity_code) ? $work->activity_code : null,
			'area_code' => !empty($work->area_code) ? $work->area_code : null,
			'client_code' => !empty($work->client_code) ? $work->client_code : null,
			'secondary_client_code' => !empty($work->secondary_client_code) ? $work->secondary_client_code : null,
			'matter_code' => !empty($work->matter_code) ? $work->matter_code : null,
			'secondary_matter_code' => !empty($work->secondary_matter_code) ? $work->secondary_matter_code : null,
			'task_code' => !empty($work->task_code) ? $work->task_code : null,
			'user_id' => !empty($work->user_id) ? (int) $work->user_id : null,
			'billable' => !empty($work->billable) ? (int) $work->billable : 0,
			'visible' => !empty($work->visible) ? (int) $work->visible : 0,
			'id_asunto' => !empty($work->id_asunto) ? (int) $work->id_asunto : null,
			'id_cliente' => !empty($work->id_cliente) ? (int) $work->id_cliente : null,
			'id_actividad' => !empty($work->id_actividad) ? (int) $work->id_actividad : null,
			'id_tarea' => !empty($work->codigo_tarea) ? (int) $work->codigo_tarea : null
		);

		return $_work;
	}

	function EditarMasivo($trabajos, $data) {
		$info = array();

		$permiso_revisor = $this->sesion->usuario->Es('REV');
		$permiso_profesional = $this->sesion->usuario->Es('PRO');

		$total_minutos_cobrables = 0;
		$total_minutos_trabajados = 0;
		foreach ($trabajos as $t) {
			// se calcula en minutos porque el intervalo es en minutos
			$total_minutos_cobrables += Utiles::time2decimal($t->fields['duracion_cobrada'])*60;
			$total_minutos_trabajados += Utiles::time2decimal($t->fields['duracion'])*60;
		}

		$asunto_cobrable = array();
		$cobros_regenerables = array();
		if($data['codigo_asunto'] || $data['codigo_asunto_secundario']) {
			$asunto = new Asunto($this->sesion);

			if (Conf::GetConf($this->sesion,'CodigoSecundario')) {
				$asunto->LoadByCodigoSecundario($data['codigo_asunto_secundario']);
				$data['codigo_asunto'] = $asunto->fields['codigo_asunto'];
			} else {
				$asunto->LoadByCodigo($data['codigo_asunto']);
			}
			$asunto_cobrable[$data['codigo_asunto']] = $asunto->fields['cobrable'] != 0;
		}

		//total escrito por usuario en minutos
		if (isset($data['total_duracion_cobrable_horas']) && isset($data['total_duracion_cobrable_minutos'])) {
			$tiempo_total_minutos_editado = ($data['total_duracion_cobrable_horas'] * 60) + $data['total_duracion_cobrable_minutos'];

			if($total_minutos_cobrables) {
				$divisor = $tiempo_total_minutos_editado / $total_minutos_cobrables;
			}
			else if($tiempo_total_minutos_editado > 0 && $total_minutos_cobrables == 0) {
				//Si el divisor es 0, cambiamos el divisor por el numero de trabajos. Más adelante se debe cambiar la duracion de cada trabajo (0) por 1 minuto.
				$divisor = $tiempo_total_minutos_editado / $total_minutos_trabajados;
				$forzar_editado_divisor_cero = true;
			}
		}

		$intervalo = Conf::GetConf($this->sesion, 'Intervalo');
		$tiempo_total_minutos_temporal = 0;
		$tiempo_trabajo_minutos_contador = 0;
		$num_trabajos = count($trabajos);

		foreach($trabajos as $t) {
			/*
			Ha cambiado el asunto del trabajo se setea nuevo Id_cobro de alguno que esté creado
			y corresponda al nuevo asunto y esté entre las fechas que corresponda, sino, se setea NULL
			*/
			if(isset($data['codigo_asunto']) && ($data['codigo_asunto'] != $t->fields['codigo_asunto'])) {
				$cobro = new Cobro($this->sesion);
				$id_cobro_cambio = $cobro->ObtieneCobroByCodigoAsunto($data['codigo_asunto'], $t->fields['fecha']);
				if(!$id_cobro_cambio) {
					$id_cobro_cambio = 'NULL';
				}
				else {
					$cobros_regenerables[] = $id_cobro_cambio;
				}

				if($t->fields['id_cobro']) {
					$cobros_regenerables[] = $t->fields['id_cobro'];
				}

				$t->Edit('id_cobro', $id_cobro_cambio);
				$t->Edit('codigo_asunto', $data['codigo_asunto']);
				$t->Edit('codigo_actividad', empty($data['codigo_actividad']) ? NULL : $data['codigo_actividad']);
			}

			if(isset($data['codigo_actividad']) && ($data['codigo_actividad'] != $t->fields['codigo_actividad'])) {
				$t->Edit('codigo_actividad', empty($data['codigo_actividad']) ? NULL : $data['codigo_actividad']);
			}

			if($t->fields['id_cobro']) {
				$cobros_regenerables[] = $t->fields['id_cobro'];
			}

			if((!$permiso_profesional || $permiso_revisor)) {
				if($data['cobrable'] != '') {
					if($data['cobrable'] == '0') {
						$t->Edit('cobrable', '0');
					}
					else {
						$t->Edit('cobrable', '1');
						$t->Edit('visible', '1');
					}
				}

				if($t->fields['cobrable'] == 0) {
					if($data['visible'] != '') {
						$t->Edit('visible', $data['visible']);
					} else {
						$t->Edit('visible', '1');
					}
				}
			}

			if(!array_key_exists($t->fields['codigo_asunto'], $asunto_cobrable)){
				$asunto = new Asunto($this->sesion);
				$asunto->LoadByCodigo($t->fields['codigo_asunto']);
				$asunto_cobrable[$t->fields['codigo_asunto']] = $asunto->fields['cobrable'] != 0;
			}
			if(!$asunto_cobrable[$t->fields['codigo_asunto']] && $t->fields['cobrable'] == 1) {
				//Si el asunto no es cobrable
				$t->Edit("cobrable", '0');
				$info[] = __('El Trabajo ').$t->fields['id_trabajo'].__(' se guardó como NO COBRABLE (Por Maestro).');
			}

			//Se modifica las horas cobrables de los trabajos con prorrateo según nuevo/total
			//Se tiene en cuenta que no puede quedar entremedio del intervalo
			if($forzar_editado_divisor_cero) {
				$total_minutos_cobrables = $total_minutos_trabajados;
			}

			if(isset($tiempo_total_minutos_editado)) {
				if($tiempo_total_minutos_editado != $total_minutos_cobrables || $forzar_editado_divisor_cero) {
					list($h, $m, $s) = split(':', $t->fields['duracion_cobrada']);
					$minutos = ($h * 60) + $m;
					//Si no tenia horas cobrables, se hace la proporcion de todo trabajo como si hubiese tenido 1 min.
					if($forzar_editado_divisor_cero)
					{
						list($h, $m, $s) = split(':', $t->fields['duracion']);
						$minutos = ($h * 60) + $m;
					}
					$tiempo_trabajo_minutos_contador += $minutos;
					$tiempo_trabajo_minutos_temporal = $tiempo_trabajo_minutos_contador * $divisor;

					$tiempo_trabajo_minutos_editado = $tiempo_trabajo_minutos_temporal - $tiempo_total_minutos_temporal;
					$tiempo_trabajo_minutos_editado -= ((1000 * $tiempo_trabajo_minutos_editado) % (1000 * $intervalo)) / 1000;

					if($i==($num_trabajos-1)) {
						$tiempo_trabajo_minutos_editado = $tiempo_total_minutos_editado - $tiempo_total_minutos_temporal;
					}
					else {
						$tiempo_total_minutos_temporal += $tiempo_trabajo_minutos_editado;
					}

					$t->Edit('duracion_cobrada', UtilesApp::Decimal2Time($tiempo_trabajo_minutos_editado / 60));

					if($tiempo_trabajo_minutos_editado >= 1440) {
						return array(
							'error' => __('No se pudo modificar los ').__('trabajo').'s. '.__('Una duración sobrepasó las 24 horas.')
						);
					}
				}
			}
		}

		$contadorModificados = 0;
		foreach ($trabajos as $t) {
			if($t->Write()) {
				$contadorModificados++;
			}
		}

		if($contadorModificados > 0) {
			//regenerar los cobros que se les agregaron o quitaron horas o trabajos
			$cobros_regenerables = array_unique($cobros_regenerables);
			$cobro = new Cobro($this->sesion);
			foreach ($cobros_regenerables as $id_cobro) {
				if ($cobro->Load($id_cobro)) {
					$cobro->GuardarCobro();
				}
			}
		}
		return array('modificados' => $contadorModificados, 'info' => $info);
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
