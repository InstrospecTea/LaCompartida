<?php

require_once Conf::ServerDir() . '/modulos/ical_Creator/ICS_Creation.php';

//Tarea implementa un buscador con columnas din�micas
class Tarea extends Objeto {

	// Sesion PHP
	var $sesion = null;
	public $estados = array('Por Asignar', 'Asignada', 'En Desarrollo', 'Por Revisar', 'Lista');

	function Tarea($sesion, $fields = "", $params = "") {
		$this->tabla = "tarea";
		$this->campo_id = "id_tarea";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function query($opciones, $id_usuario) {
		$where = '';

		$relacion = array();
		if ($opciones['tareas_encargado']) {
			$relacion[] = "cliente.id_usuario_encargado=" . $id_usuario;
		}

		if ($opciones['tareas_mandante']) {
			$relacion[] = " tarea.usuario_generador = '" . $id_usuario . "' ";
		}
		if ($opciones['tareas_responsable']) {
			$relacion[] = " tarea.usuario_encargado = '" . $id_usuario . "' ";
		}
		if ($opciones['tareas_revisor']) {
			$relacion[] = " tarea.usuario_revisor = '" . $id_usuario . "' ";
		}
		if ($opciones['otras_tareas']) {
			$relacion[] = " ( (tarea.usuario_encargado <> '" . $id_usuario . "' OR tarea.usuario_encargado IS NULL) AND (tarea.usuario_generador <> '" . $id_usuario . "' OR tarea.usuario_generador IS NULL) AND (tarea.usuario_revisor <> '" . $id_usuario . "' OR tarea.usuario_revisor IS NULL) )";
		}

		if (!empty($relacion)) {
			$where .= ' AND (' . implode(' OR ', $relacion) . ') ';
		} else {
			$where .= ' AND (0) ';
		}

		if ($opciones['codigo_cliente']) {
			$where .= " AND tarea.codigo_cliente = '" . $opciones['codigo_cliente'] . "' ";
		}
		if ($opciones['codigo_asunto']) {
			$where .= " AND tarea.codigo_asunto = '" . $opciones['codigo_asunto'] . "' ";
		}

		if ($opciones['fecha_desde'] && $opciones['fecha_hasta']) {
			$where .= " AND (tarea.fecha_entrega BETWEEN '" . Utiles::fecha2sql($opciones['fecha_desde']) . "' AND '" . Utiles::fecha2sql($opciones['fecha_hasta']) . "')";
		} else if ($opciones['fecha_desde']) {
			$where .= " AND tarea.fecha_entrega >= '" . Utiles::fecha2sql($opciones['fecha_desde']) . "'";
		} else if ($opciones['fecha_hasta']) {
			$where .= " AND tarea.fecha_entrega <= '" . Utiles::fecha2sql($opciones['fecha_hasta']) . "'";
		}

		if ($opciones['id_usuario_involucrado']) {
			$where .= " AND (
						encargado.id_usuario = {$opciones['id_usuario_involucrado']} OR
						generador.id_usuario = {$opciones['id_usuario_involucrado']} OR
						revisor.id_usuario = {$opciones['id_usuario_involucrado']}) ";
		}

		if (is_array($opciones['estado'])) {
			foreach ($opciones['estado'] as $estado) {
				$conjunto_estados[] .= " tarea.estado = '" . $estado . "' ";
			}
			if (!empty($conjunto_estados)) {
				$where .= ' AND (' . implode(' OR ', $conjunto_estados) . ') ';
			}
		}


		$query = " SELECT SQL_CALC_FOUND_ROWS
						tarea.id_tarea,
						tarea.prioridad,
						tarea.nombre,
						tarea.detalle,
						CONCAT_WS(' ', encargado.apellido1,CONCAT(encargado.apellido2,','),encargado.nombre) AS encargado,
						CONCAT_WS(' ', generador.apellido1,CONCAT(generador.apellido2,','),generador.nombre) AS generador,
						CONCAT_WS(' ', revisor.apellido1,CONCAT(revisor.apellido2,','),revisor.nombre) AS revisor,

						CONCAT(LEFT(encargado.nombre,1),LEFT(encargado.apellido1,1),LEFT(encargado.apellido2,1)) AS mini_encargado,
						CONCAT(LEFT(generador.nombre,1),LEFT(generador.apellido1,1),LEFT(generador.apellido2,1)) AS mini_generador,
						CONCAT(LEFT(revisor.nombre,1),LEFT(revisor.apellido1,1),LEFT(revisor.apellido2,1)) AS mini_revisor,

						encargado.username as username_encargado,
						generador.username as username_generador,
						revisor.username as username_revisor,

						encargado.id_usuario as id_encargado,
						generador.id_usuario as id_generador,
						revisor.id_usuario as id_revisor,
						tarea.fecha_entrega,
						tarea.estado,
						tarea.tiempo_estimado,
						cliente.glosa_cliente,
						asunto.glosa_asunto
					FROM tarea
						LEFT JOIN usuario AS generador ON (tarea.usuario_generador = generador.id_usuario)
						LEFT JOIN usuario AS encargado ON (tarea.usuario_encargado = encargado.id_usuario)
						LEFT JOIN usuario AS revisor ON (tarea.usuario_revisor = revisor.id_usuario)
						JOIN cliente ON tarea.codigo_cliente = cliente.codigo_cliente
						JOIN asunto  ON tarea.codigo_asunto = asunto.codigo_asunto
					WHERE 1 $where ";

		return $query;
	}

	function getTiempoIngresado() {
		if (!$this->fields['id_tarea']) {
			return '';
		}
		$query = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(duracion_avance))) AS total FROM tarea_comentario WHERE id_tarea={$this->fields['id_tarea']}";
		extract($this->queryOrError($query, false));

		if (Conf::GetConf($this->sesion, 'TipoIngresoHoras') == 'decimal') {
			return UtilesApp::Time2Decimal($total);
		}
		if (Conf::TipoIngresoHoras() == 'decimal') {
			return UtilesApp::Time2Decimal($total);
		}
		$tiempo = explode(':', $total);

		if ($tiempo[0] || $tiempo[1]) {
			return $tiempo[0] . ':' . $tiempo[1] . ':00';
		}
		return '00:00:00';
	}

	function IconoEstado($estado, $verboso = false) {
		$l = 'O';
		$color = '#000';

		if ($estado == 'Por Asignar') {
			$l = 'P';
			$color = 'rgb(238,119,0)';
		} else if ($estado == 'Asignada') {
			$l = 'A';
			$color = 'rgb(0,153,153)';
		} else if ($estado == 'En Desarrollo') {
			$l = 'D';
			$color = 'rgb(0,136,0)';
		} else if ($estado == 'Por Revisar') {
			$l = 'R';
			$color = 'rgb(144,3,163)';
		} else if ($estado == 'Lista') {
			$l = 'L';
			$color = 'rgb(0,0,170)';
		}

		if ($verboso)
			return __('Estado') . ': <span style="color: ' . $color . '" >' . $estado . '</span>';
		return '<span style="color: ' . $color . '" title="Estado: ' . $estado . '" ><b>' . $l . '</b></span>';
	}

	function Write() {
		$this->error = "";
		if (!$this->Check()) {
			return false;
		}

		$es_update = $this->Loaded();
		if ($es_update) {
			$query = "UPDATE {$this->tabla} SET ";
			if ($this->guardar_fecha) {
				$valores[] = 'fecha_modificacion = NOW()';
			}
		} else {
			$query = "INSERT INTO {$this->tabla} SET ";
			if ($this->guardar_fecha) {
				$valores[] = 'fecha_creacion = NOW()';
			}
		}

		$c = 0;
		foreach ($this->fields as $key => $val) {
			if ($this->changes[$key]) {
				$do_update = true;
				if ($c > 0) {
					$query .= ",";
				}
				if ($val != 'NULL') {
					$query .= "$key = '" . addslashes($val) . "'";
				} else {
					$query .= "$key = NULL ";
				}
				$c++;
			}
		}

		if ($es_update) {
			$query .= " WHERE " . $this->campo_id . "='" . $this->fields[$this->campo_id] . "'";
		}

		try {
			$this->sesion->pdodbh->query($query);
		} catch (PDOException $e) {
			Utiles::errorSQL($query, '', '', $this->sesion->pdodbh, '', $e);
			return false;
		}
		if (!$es_update) {
			$this->fields[$this->campo_id] = $this->sesion->pdodbh->lastInsertId();
			$this->enviarMailNotificaciones();
		}

		if ($this->fields['orden_estado'] == 2 || $this->fields['estado'] == 'Asignada') {
			$estado = " Se le ha asignado la siguiente tarea:";
			$where = "id_usuario=" . $this->fields['usuario_encargado'];
		} else if ($this->fields['orden_estado'] == 4) {
			$estado = " La siguiente tarea ha sido desarrollada y est� a la espera de su revisi�n:";
			$where = "id_usuario=" . $this->fields['usuario_revisor'];
		} else if ($this->fields['orden_estado'] == 5) {
			$estado = " Se ha revisado la siguiente tarea:";
			$where = "id_usuario=" . $this->fields['usuario_encargado'] . " OR id_usuario=" . $this->fields['usuario_revisor'] . " OR id_usuario=" . $this->fields['usuario_generador'];
		} else {
			$where = " id_usuario=0";
		}

		$query = " SELECT c.glosa_cliente as nombre_cliente, a.glosa_asunto
								FROM tarea AS t
								JOIN cliente AS c ON t.codigo_cliente=c.codigo_cliente
								JOIN asunto AS a ON t.codigo_asunto=a.codigo_asunto
								WHERE id_tarea=" . $this->fields['id_tarea'];
		extract($this->queryOrError($query, false));
		if ($glosa_asunto) {
			$texto_asunto = "Asunto: {$glosa_asunto}<br/>";
		}

		$query = "SELECT id_usuario as id, CONCAT_WS(' ', nombre, apellido1, apellido2) AS nombre, email FROM usuario WHERE activo=1 AND " . $where;
		$users = $this->queryOrError($query);
		foreach ($users as $user) {
			extract($user);
			/* --- no se usan las variables recibidos ---
			  $query2 = "SELECT rut, dv_rut, password FROM usuario WHERE id_usuario=".$id;
			  $resp2 = mysql_query($query2,$this->sesion->dbh) or Utiles::errorSQL($query2,$this->sesion->dbh);
			  list($rut,$dv_rut,$password)=mysql_fetch_array($resp2);
			 */

			$url = Conf::Server() . Conf::RootDir() . '/app/interfaces/agregar_tarea.php?popup=1&id_tarea=' . $this->fields['id_tarea'];
			$subject = '[Tarea] ' . $this->fields['nombre'];
			$mensaje = "Estimado Sr. {$nombre}, <br/><br/>{$estado} <br><br> Cliente: {$nombre_cliente}<br/> {$texto_asunto} Tarea: {$this->fields['nombre']}<br/><br/> Para ingresar haga clic (<a href={$url}>aqu�</a>).";

			Utiles::Insertar($this->sesion, $subject, $mensaje, $email, $nombre, false);
		}
		return true;
	}

	/**
	 * Find all tasks
	 * Return an array with next elements:
	 * 	code, name, client_code, matter_code
	 */
	function findAll() {
		$tasks = array();

		$sql = "SELECT `tasks`.`id_tarea` AS `code`, `tasks`.`nombre` AS `name`, `tasks`.`codigo_cliente` AS `client_code`,
			`tasks`.`codigo_asunto` AS `matter_code`
			FROM `tarea` AS `tasks`
			ORDER BY `tasks`.`nombre` ASC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->execute();

		while ($task = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($tasks, array(
				'code' => $task->code,
				'name' => !empty($task->name) ? $task->name : null,
				'client_code' => !empty($task->client_code) ? $task->client_code : null,
				'matter_code' => !empty($task->matter_code) ? $task->matter_code : null
				)
			);
		}

		return $tasks;
	}

	/**
	 * Busca las novedades dada una tarea $id_tarea
	 * @param id_usuario
	 * @param id_tarea
	 * */
	function getNovedades($id_usuario, $id_tarea) {
		$query = sprintf("SELECT
								tarea.id_tarea,
								COUNT(tarea_comentario.id_comentario) AS comentarios,
								COUNT(tarea_comentario_usuario.id_comentario) AS vistos
							FROM tarea
								JOIN tarea_comentario ON (tarea_comentario.id_tarea = tarea.id_tarea)
								JOIN usuario
								LEFT JOIN tarea_comentario_usuario ON (tarea_comentario_usuario.id_comentario = tarea_comentario.id_comentario AND tarea_comentario_usuario.id_usuario = '%d')
							WHERE usuario.id_usuario = '%d' AND tarea.id_tarea = '%d'
							GROUP BY tarea.id_tarea", $id_usuario, $id_usuario, $id_tarea);

		return $this->queryOrError($query, false);
	}

	/**
	 * Se envia un mail con una invitaci�n para google calendar al registrar una nueva Tarea
	 * */
	function enviarMailNotificaciones() {
		$invitados = array();
		if ($this->fields['usuario_registro'] != 'NULL') {
			$invitados[] = $this->fields['usuario_registro'];
		}
		if ($this->fields['usuario_encargado'] != 'NULL') {
			$invitados[] = $this->fields['usuario_encargado'];
		}
		if ($this->fields['usuario_revisor'] != 'NULL') {
			$invitados[] = $this->fields['usuario_revisor'];
		}
		if ($this->fields['usuario_generador'] != 'NULL') {
			$invitados[] = $this->fields['usuario_generador'];
		}
		$invitados = array_unique($invitados);
		if (sizeof($invitados) !== 0) {
			return;
		}

		$query_usuarios_emails = sprintf("SELECT
											u.email,
											CONCAT_WS(' ',u.nombre, u.apellido1) nombre
										FROM usuario u
										WHERE
											u.id_usuario IN (%s);", implode(",", $invitados));
		$correos = array();
		$rows = $this->queryOrError($query);
		foreach ($rows as $row) {
			$correos[] = array('nombre' => $row['nombre'], 'mail' => trim($row['email']));
		}
		$ICS_data = NEW ICS_Creation();
		$nombre_tarea = $this->fields['nombre'];
		$detalle = $this->fields['detalle'];
		$ICS_data->addEvent($this->fields['fecha_entrega'], $this->fields['fecha_entrega'], "Nueva tarea " . $nombre_tarea, $detalle, '');
		$event_data = utf8_encode($ICS_data->render_Event());
		$attachment = array(
			'data_string' => $event_data,
			'filename' => 'invitacion.ics',
			'base_encode' => '7bit'
		);
		$query = sprintf("SELECT glosa_cliente FROM cliente WHERE codigo_cliente='%s'", $this->fields['codigo_cliente']);
		$rows = $this->queryOrError($query);
		foreach ($rows as $row) {
			$glosa_cliente = $row['glosa_cliente'];
		}

		$query = sprintf("SELECT glosa_asunto FROM asunto WHERE codigo_asunto='%s'", $this->fields['codigo_asunto']);
		$rows = $this->queryOrError($query);
		foreach ($rows as $row) {
			$glosa_asunto = $row['glosa_asunto'];
		}

		$query = sprintf("SELECT CONCAT_WS(' ',nombre, apellido1) as nombre FROM usuario WHERE id_usuario=%d", $this->fields['usuario_encargado']);
		$rows = $this->queryOrError($query);
		foreach ($rows as $row) {
			$usuario_encargado_nombre = $row['nombre'];
		}

		$query = sprintf("SELECT CONCAT_WS(' ',nombre, apellido1) as nombre FROM usuario WHERE id_usuario=%d", $this->fields['usuario_revisor']);
		$rows = $this->queryOrError($query);
		foreach ($rows as $row) {
			$usuario_revisor_nombre = $row['nombre'];
		}

		$query = sprintf("SELECT CONCAT_WS(' ',nombre, apellido1) as nombre FROM usuario WHERE id_usuario=%d", $this->fields['usuario_generador']);
		$rows = $this->queryOrError($query);
		foreach ($rows as $row) {
			$usuario_generador_nombre = $row['nombre'];
		}

		$subject = sprintf('Se ha creado una nueva tarea (%s) para el cliente (%s)', $nombre_tarea, $glosa_cliente);
		$body = sprintf("Estimado usuario,<br><br> Se ha registrado una nueva tarea <b>%s</b> para el cliente %s (%s) y asunto %s (%s).<br><br> Responsable: %s<br>Revisor: %s<br>Mandante: %s<br><br>Mensaje generado por The Time Billing.", $nombre_tarea, $glosa_cliente, $this->fields['codigo_cliente'], $glosa_asunto, $this->fields['codigo_asunto'], $usuario_encargado_nombre, $usuario_revisor_nombre, $usuario_generador_nombre);

		return Utiles::EnviarMail($this->sesion, $correos, $subject, $body, false, NULL, $attachment);
	}

	protected function queryOrError($query, $all = true) {
		try {
			$Statement = $this->sesion->pdodbh->query($query);
		} catch (PDOException $e) {
			Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->pdodbh);
		}
		if ($all) {
			$result = $Statement->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$result = $Statement->fetch(PDO::FETCH_ASSOC);
			$Statement->closeCursor();
		}
		return $result;
	}

}
