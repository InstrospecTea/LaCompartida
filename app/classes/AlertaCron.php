<?php 

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Asunto.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

class Alerta {

	var $sesion = null;

	function Alerta($sesion) {
		$this->sesion = $sesion;
	}

	function AlertaGeneral() {
		$dia = date("N"); # 6 = Sábado, 7 = Domingo;
		if ($dia == 6 || $dia == 7)
			return;

		$query = "SELECT id_asunto FROM asunto WHERE activo=1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($id_asunto) = mysql_fetch_array($resp))
			$this->AlertaAsunto($id_asunto);

		$query = "SELECT usuario.id_usuario FROM usuario LEFT JOIN usuario_permiso USING (id_usuario) WHERE activo=1 AND codigo_permiso='PRO'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($id_persona) = mysql_fetch_array($resp))
			$this->AlertaProfesional($id_persona);
	}

	function AlertaPersona($id_persona) {
		#Profesional + Asuntos que manda
		$this->AlertaProfesional($id_persona);

		$query = "SELECT id_asunto FROM asunto WHERE activo=1 AND id_encargado = '$id_persona'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($id_asunto) = mysql_fetch_array($resp)) {
			$this->AlertaAsunto($id_asunto);
		}
	}

	function AlertaAsunto($id_asunto) {
		$asunto = new Asunto($this->sesion);
		$asunto->Load($id_asunto);

		$query = "SELECT id_contrato FROM asunto WHERE id_asunto='$id_asunto'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_contrato) = mysql_fetch_array($resp);

		$query = "SELECT MAX(fecha_fin) FROM cobro WHERE cobro.id_contrato = '$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha_ultimo_cobro) = mysql_fetch_array($resp);

		if ($asunto->fields['limite_monto'] > 0)
			list($total_monto, $moneda_total_monto) = $asunto->TotalMontoTrabajado();
		if ($asunto->fields['alerta_hh'] > 0) //Significa que el se requiere alerte desde el limite de horas desde el ultimo cobro
			$total_horas_ult_cobro = $asunto->TotalHorasNoCobradas($fecha_ultimo_cobro);
		//if($asunto->fields['alerta_monto'] > 0) //Significa que se requiere alerta por monto desde ultimo cobro
		list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $asunto->TotalMontoTrabajado($fecha_ultimo_cobro);

		if ($asunto->fields['limite_hh'] > 0)
			$total_horas_trabajadas = $asunto->TotalHorasNoCobradas();


		//Notificacion "Límite de monto"
		$total_monto = number_format($total_monto, 1);
		$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

		if (($total_monto > $asunto->fields['limite_monto']) && ($asunto->fields['limite_monto'] > 0) && ($asunto->fields['notificado_monto_excedido'] == 0)) {
			echo $asunto->fields['glosa_asunto'] . "Limite Monto : " . $asunto->fields['limite_monto'] . " >> Actual : " . $total_monto . "<br>" . $moneda_total_monto;
			$asunto->AlertaAdministrador("En el asunto " . $asunto->fields['glosa_asunto'] . " (Cliente " . $asunto->fields['codigo_cliente'] . ") Se ha alcanzado límite de MONTO asignado. Siendo el limite $moneda_total_monto" . $asunto->fields['limite_monto'] . " El monto actual es $moneda_total_monto $total_monto ", $this->sesion);
			$asunto->Edit('notificado_monto_excedido', '1');
			$asunto->Write();
		}

		//Notificacion "Límite de horas"
		if (($total_horas_trabajadas > $asunto->fields['limite_hh']) && ($asunto->fields['limite_hh'] > 0 ) && ($asunto->fields['notificado_hr_excedido'] == 0)) {
			echo $asunto->fields['glosa_asunto'] . "Limite Horas : " . $asunto->fields['limite_hh'] . " >> Actual : " . $total_horas_trabajadas . "<br>";
			$asunto->AlertaAdministrador("En el Asunto " . $asunto->fields['glosa_asunto'] . " (Cliente " . $asunto->fields['codigo_cliente'] . ") Se ha superado el limite de HORAS trabajadas. Siendo el limite " . $asunto->fields['limite_hh'] . " Hrs. se han trabajado $total_horas_trabajadas Hrs", $this->sesion);
			$asunto->Edit('notificado_hr_excedido', '1');
			$asunto->Write();
		}

		//Notificacion "Monto desde el último cobro"
		if (($total_monto_ult_cobro > $asunto->fields['alerta_monto']) && ($asunto->fields['alerta_monto'] > 0) && ($asunto->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
			echo $asunto->fields['glosa_asunto'] . "Limite Monto " . __('Último Cobro') . " : " . $asunto->fields['alerta_monto'] . " >> Actual :" . $total_monto_ult_cobro . "<br>" . $moneda_desde_ult_cobro;

			$asunto->AlertaAdministrador("En el Asunto " . $asunto->fields['glosa_asunto'] . " (Cliente " . $asunto->fields['codigo_cliente'] . ") Se ha superado el monto asignado desde el " . __('Último Cobro') . ". Siendo el limite $moneda_desde_ult_cobro" . $asunto->fields['alerta_monto'] . " El monto actual es $moneda_desde_ult_cobro $total_monto_ult_cobro ", $this->sesion);
			$asunto->Edit('notificado_monto_excedido_ult_cobro', '1');
			$asunto->Write();
		}

		//Notificacion "Horas desde el último cobro"
		if (($total_horas_ult_cobro > $asunto->fields['alerta_hh']) && ($asunto->fields['alerta_hh'] > 0) && ($asunto->fields['notificado_hr_excedida_ult_cobro'] == 0)) {
			echo $asunto->fields['glosa_asunto'] . "Limite Horas " . __('Último Cobro') . " : " . $asunto->fields['alerta_hh'] . " >> Actual : " . $total_horas_ult_cobro . "<br>";

			$asunto->AlertaAdministrador("En el Asunto " . $asunto->fields['glosa_asunto'] . " (Cliente " . $asunto->fields['codigo_cliente'] . ") Se ha superado el limite de horas trabajadas desde " . __('el último cobro') . ". Siendo el limite " . $asunto->fields['alerta_hh'] . " hrs. se han trabajado $total_horas_ult_cobro", $this->sesion);
			$asunto->Edit('notificado_hr_excedida_ult_cobro', '1');
			$asunto->Write();
		}

		/*
		  if(($total_monto * ($asunto->fields['alerta_porctje_lim_monto']/100) > $asunto->fields['limite_monto']) && ($asunto->fields['limite_monto'] > 0))
		  $asunto->AlertaAdministrador("$total_monto Se ha alcanzado el limite de alerta de porcentaje de monto de ".$asunto->fields['alerta_porctje_lim_monto']."% asignado en el asunto ".$asunto->fields['glosa_asunto']);

		  if(($total_horas_trabajadas * ($asunto->fields['alerta_porctje_lim_hh']/100) > $asunto->fields['limite_hh']) && ($asunto->fields['limite_hh'] > 0))
		  $asunto->AlertaAdministrador("$total_horas_trabajadas Se ha alcanzado el limite de alerta de porcentaje de horas hombre de ".$asunto->fields['alerta_porctje_lim_hh']."% asignado en el asunto ".$asunto->fields['glosa_asunto']);
		 */
	}

	function EnviarAlertaProfesional($id_persona, $mensaje, $sesion, $header = true) {
		
		if (is_numeric($id_persona)) {
			$query = "SELECT email, CONCAT_WS(' ', nombre, apellido1) as nombre FROM usuario WHERE usuario.activo=1 AND id_usuario = '$id_persona'";
			$resp = mysql_query($query);
			list($email, $nombre) = mysql_fetch_array($resp);
		} else {
			list($email, $nombre) = explode(':', $id_persona);
		}
		
		if ($header) {
			$mensaje = (!empty($nombre) ? "Usuario: $nombre \n" : "") . "Alerta: $mensaje";
		}

		$from =  html_entity_decode(Conf::AppName());

		$to = $email; // Mail a Usuario

		Utiles::Insertar($sesion, "Alerta $from", $mensaje, $to, $nombre);

		/*
		  $query = "SELECT email FROM usuario WHERE id_usuario IN (SELECT id_encargado FROM trabajo LEFT JOIN asunto USING (codigo_asunto) WHERE trabajo.fecha > DATE_SUB(NOW(), INTERVAL 10 DAY) AND trabajo.id_usuario=$id_persona)";
		  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		  while(list($email) = mysql_fetch_array($resp))
		  mail ( $email, "Alerta ".Conf::AppName(), $/
		 */
	}

	function AlertaProfesional($id_persona, $opc_mail, $sesion) {
		$prof = new Usuario($this->sesion);
		$prof->LoadId($id_persona);

		$mensaje_restriccion_minimo = "El usuario NO ha ingresado el MINIMO de horas durante los últimos 7 días";
		if (method_exists('Conf', 'GetConf')) {
			$mensaje_restriccion_minimo = Conf::GetConf($sesion, 'MensajeRestriccionSemanal');
		} else if (method_exists('Conf', 'MensajeRestriccionSemanal')) {
			$mensaje_restriccion_minimo = Conf::MensajeRestriccionSemanal();
		}

		if ($opc_mail == 'mail_hrs_semanales') {
			if ($prof->fields['restriccion_min'] > 0)
				if ($this->HorasUltimaSemana($id_persona) < $prof->fields['restriccion_min'])
					$this->EnviarAlertaProfesional($id_persona, $mensaje_restriccion_minimo, $sesion);
			if ($prof->fields['restriccion_max'] > 0)
				if ($this->HorasUltimaSemana($id_persona) > $prof->fields['restriccion_max'])
					$this->EnviarAlertaProfesional($id_persona, "El usuario HA superado el MÁXIMO de horas trabajadas durante los últimos 7 dias", $sesion);
		}
		else if ($opc_mail == 'mail_retrasos') {

			if ($prof->fields['retraso_max'] > 0) {
				$query = "SELECT 24*(TO_DAYS(NOW()) - TO_DAYS(MAX(fecha))) FROM trabajo WHERE id_usuario='$id_persona'";
				$resp = mysql_query($query);
				list($horas_retraso) = mysql_fetch_array($resp);
				if ($horas_retraso > $prof->fields['retraso_max'])
					$this->EnviarAlertaProfesional($id_persona, "El usuario ha superado el tiempo máximo sin ingresar horas.", $sesion);
			}
		}
		elseif ($opc_mail == 'mail_ingreso_hrs_mensuales') {
			// horas ingresadas el mes actual
			$mes = date('n');
			$ano = date('Y');
			if ($this->HorasMes($id_persona, $mes, $ano) < $prof->fields['restriccion_mensual'])
				$this->EnviarAlertaProfesional($id_persona, "HOY DEBEN QUEDAR LAS HORAS DEL MES INGRESADAS", $sesion);
		}
		elseif ($opc_mail == 'mail_cierre_cobranza') {
			// horas ingresadas el mes anterior
			$mes = date('n') - 1;
			$ano = date('Y');
			if ($mes == 0) {
				$mes = 12;
				--$ano;
			}
			if ($this->HorasMes($id_persona, $mes, $ano) < $prof->fields['restriccion_mensual'])
				$this->EnviarAlertaProfesional($id_persona, "SE INFORMA QUE ESTAMOS EN PROCESO CIERRE DE COBRANZAS, AQUELLOS ABOGADOS QUE NO HAN INGRESADOS TODAS SUS HORAS SE PROCEDERÁ A UNA RETENCIÓN DE SUELDO", $sesion);
		}
	}

	function HorasUltimaSemana($id_usuario) {
		$query = "SELECT SUM(TIME_TO_SEC(duracion))/3600 FROM trabajo WHERE
									fecha <= NOW() AND
									fecha > DATE_SUB(NOW(), INTERVAL 7 DAY)
									AND id_usuario = '$id_usuario'";
		$resp = mysql_query($query);
		list($horas_ultima_semana) = mysql_fetch_array($resp);
		return $horas_ultima_semana;
	}

	function HorasCobrablesUltimaSemana($id_usuario) {
		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 FROM trabajo WHERE
									fecha <= NOW() AND
									fecha > DATE_SUB(NOW(), INTERVAL 7 DAY)
									AND id_usuario = '$id_usuario' AND cobrable = 1";
		$resp = mysql_query($query);
		list($horas_ultima_semana) = mysql_fetch_array($resp);
		return $horas_ultima_semana;
	}

	function HorasMes($id_usuario, $mes, $ano) {
		$query = "SELECT SUM(TIME_TO_SEC(duracion))/3600
					FROM trabajo
					WHERE id_usuario = '$id_usuario'
						AND MONTH(fecha) = $mes
						AND YEAR(fecha) = $ano";
		$resp = mysql_query($query);
		list($horas_mes) = mysql_fetch_array($resp);
		return $horas_mes;
	}
		
	function enviarAvisoCobrosProgramados( $mensajes, $sesion )
	{
		$from = Conf::AppName();

		if(  method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MailAdmin') != '' )
		{
			$to = Conf::GetConf($sesion,'MailAdmin'); // Mail al admin
		}

		foreach( $mensajes as $id_usuario => $mensaje )
		{
			Utiles::Insertar( $sesion, "Aviso $from", $mensaje, $to, "Administrador");
		}
	}
}
?>
