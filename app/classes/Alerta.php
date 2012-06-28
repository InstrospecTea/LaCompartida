<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Alerta
{
	var $sesion = null;

	function Alerta($sesion)
	{
		$this->sesion = $sesion;
	}
	function AlertaGeneral()
	{
		$dia = date("N"); # 6 = Sábado, 7 = Domingo;
		if($dia == 6 || $dia == 7)
			return;

		$query = "SELECT id_asunto FROM asunto WHERE activo=1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_asunto) = mysql_fetch_array($resp))
			$this->AlertaAsunto($id_asunto);

		$query = "SELECT usuario.id_usuario FROM usuario LEFT JOIN usuario_permiso USING (id_usuario) WHERE activo=1 AND codigo_permiso='PRO'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_persona) = mysql_fetch_array($resp))
			$this->AlertaProfesional($id_persona);
	}
	
	

	function AlertaPersona($id_persona)
	{
		#Profesional + Asuntos que manda
		$this->AlertaProfesional($id_persona);

		$query = "SELECT id_asunto FROM asunto WHERE activo=1 AND id_encargado = '$id_persona'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id_asunto) = mysql_fetch_array($resp))
		{
			$this->AlertaAsunto($id_asunto);
		}
	}

	function AlertaAsunto($id_asunto)
	{
		$asunto = new Asunto($this->sesion);
		$asunto->Load($id_asunto);

		$total_monto = $asunto->TotalMonto();
		$total_horas_trabajadas = $asunto->TotalHorasTrabajadas(true);

		if(($total_monto > $asunto->fields['limite_monto']) && ($asunto->fields['limite_monto'] > 0))
			$asunto->AlertaAdministrador("$total_monto Se ha alcanzado el límite de monto asignado (".$asunto->fields['limite_monto'].") en el asunto ".$asunto->fields['glosa_asunto'], $this->sesion);

		if(($total_horas_trabajadas > $asunto->fields['limite_hh']) && ($asunto->fields['limite_hh'] > 0 ))
			$asunto->AlertaAdministrador("$total_horas_trabajadas  Se ha alcanzado el límite de horas hombre asignadas (".$asunto->fields['limite_hh'].") en el asunto ".$asunto->fields['glosa_asunto'], $this->sesion);

		if(($total_monto > $asunto->fields['alerta_monto']) && ($asunto->fields['alerta_monto'] > 0))
			$asunto->AlertaAdministrador("$total_monto Se ha alcanzado el límite de alerta de monto de ".$asunto->fields['alerta_monto']." asignado en el asunto ".$asunto->fields['glosa_asunto'], $this->sesion);

		if(($total_horas_trabajadas > $asunto->fields['alerta_hh']) &&  ($asunto->fields['alerta_hh'] > 0))
			$asunto->AlertaAdministrador("$total_horas_trabajadas Se ha alcanzado el lílerocca77@hotmail.commite de alerta de horas hombre de ".$asunto->fields['alerta_hh']." asignado en el asunto ".$asunto->fields['glosa_asunto'], $this->sesion);

		if(($total_monto * ($asunto->fields['alerta_porctje_lim_monto']/100) > $asunto->fields['limite_monto']) && ($asunto->fields['limite_monto'] > 0))
			$asunto->AlertaAdministrador("$total_monto Se ha alcanzado el limite de alerta de porcentaje de monto de ".$asunto->fields['alerta_porctje_lim_monto']."% asignado en el asunto ".$asunto->fields['glosa_asunto'], $this->sesion);

		if(($total_horas_trabajadas * ($asunto->fields['alerta_porctje_lim_hh']/100) > $asunto->fields['limite_hh']) && ($asunto->fields['limite_hh'] > 0))
			$asunto->AlertaAdministrador("$total_horas_trabajadas Se ha alcanzado el limite de alerta de porcentaje de horas hombre de ".$asunto->fields['alerta_porctje_lim_hh']."% asignado en el asunto ".$asunto->fields['glosa_asunto'], $this->sesion);
	}

	function EnviarAlertaProfesional($id_persona,$mensaje,$sesion)
	{
		$query = "SELECT email,CONCAT_WS(' ',nombre,apellido1) as nombre FROM usuario WHERE usuario.activo=1 AND id_usuario = '$id_persona'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($email,$nombre) = mysql_fetch_array($resp);

		$mensaje = __('Usuario').": $nombre\n".__('Alerta').": $mensaje";

		$from = Conf::AppName();

		Utiles::Insertar( $sesion, __("Alerta")." $from", $mensaje, $email, $nombre);

		$query = "SELECT email FROM usuario WHERE usuario.activo=1 AND id_usuario IN (SELECT id_encargado FROM trabajo LEFT JOIN asunto USING (codigo_asunto) WHERE trabajo.fecha > DATE_SUB(NOW(), INTERVAL 10 DAY) AND trabajo.id_usuario=$id_persona)";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($email) = mysql_fetch_array($resp))
			
		Utiles::Insertar( $sesion, __("Alerta")." ".Conf::AppName(), $mensaje, $email, '');
		
	}
	function AlertaProfesional($id_persona,$opc,$sesion)
	{
		$prof = new Usuario($this->sesion);
		$prof->LoadId($id_persona);

		if($prof->fields['restriccion_min'] > 0)
			if($this->HorasUltimaSemana($id_persona) < $prof->fields['restriccion_min'])
				echo __("No ha ingresado el minimo");
				//$this->EnviarAlertaProfesional($id_persona,"El usuario no ha ingresado el mínimo de horas durante la última semana",$sesion);
		if($prof->fields['restriccion_max'] > 0)
			if($this->HorasUltimaSemana($id_persona) > $prof->fields['restriccion_max'])
				//$this->EnviarAlertaProfesional($id_persona,"El usuario ha superado el máximo de horas trabajadas durante la última semana",$sesion);
				echo __("Usuario exede el Maximo Permitido");
		if($prof->fields['retraso_max'] > 0 && date("N") > 1) //1 es lunes, los lunes no mando la alerta
		{
			$query = "SELECT 24*(TO_DAYS(NOW()) - TO_DAYS(MAX(fecha))) FROM trabajo WHERE id_usuario='$id_persona'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($horas_retraso) = mysql_fetch_array($resp);
			if($horas_retraso > $prof->fields['retraso_max'])
				//$this->EnviarAlertaProfesional($id_persona,"El usuario ha superado el tiempo máximo sin ingresar horas.",$sesion);
				echo __("ha superado el tiempo sin ingresar horas");
		}
		
	}

	function HorasUltimaSemana($id_usuario)
	{
		$query = "SELECT SUM(TIME_TO_SEC(duracion))/3600 FROM trabajo WHERE fecha < DATE_SUB(NOW(), INTERVAL 7 DAY) AND id_usuario = '$id_usuario'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($horas_ultima_semana) = mysql_fetch_array($resp);

		return $horas_ultima_semana;
	}
}

?>
