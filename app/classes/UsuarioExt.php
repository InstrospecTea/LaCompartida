<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class UsuarioExt extends Usuario
{
	var $secretarios = null;

	function LoadSecretario($id)
	{
		$query = "SELECT id_profesional FROM usuario_secretario
							WHERE id_secretario = '".$this->fields['id_usuario']."'
							AND id_profesional = '$id' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if($id_profesional = mysql_fetch_assoc($resp))
			return true;
		else
			return false;
	}
	
	function Revisa($id)
	{
		$query = "SELECT id_revisado FROM usuario_revisor
							WHERE id_revisor = '".$this->fields['id_usuario']."'
							AND id_revisado = '$id' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		if($id_revisado = mysql_fetch_assoc($resp))
			return true;
		else
			return false;
	}
	
	//Agrega Vacaciones del Usuario
	function GuardarVacacion($fecha_ini, $fecha_fin)
	{
		$query = "INSERT INTO usuario_vacacion (id_usuario,id_usuario_creador,fecha_inicio,fecha_fin)";
		$query .= "	VALUES(".$this->fields['id_usuario'].", ".$this->sesion->usuario->fields['id_usuario'].", '".Utiles::fecha2sql($fecha_ini)."', '".Utiles::fecha2sql($fecha_fin)."')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	//Lista las vacaciones de un usuario
	function ListaVacacion($id_usuario)
	{
		$query = "SELECT id, fecha_inicio, fecha_fin FROM usuario_vacacion WHERE id_usuario = '".$id_usuario."' ORDER BY fecha_inicio desc";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$vacaciones_usuario = array();
		$i = 0;
		while( list($id, $fecha_ini, $fecha_fin) = mysql_fetch_array($resp) )
		{
			$vacaciones_usuario[$i]['id'] = $id;
			$vacaciones_usuario[$i]['fecha_inicio'] = Utiles::sql2date($fecha_ini);
			$vacaciones_usuario[$i]['fecha_fin'] = Utiles::sql2date($fecha_fin);
			$i++;
		}
		return $vacaciones_usuario;
	}
	//Elimina Vacaciones
	function EliminaVacacion($ide,$id_usuario)
	{
		$query = "DELETE FROM usuario_vacacion WHERE id = ".$ide." AND id_usuario = ".$id_usuario." LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
	
	function GuardarSecretario($ids)
	{
		//Se obtienen los datos actuales
		$query = "SELECT id_profesional FROM usuario_secretario WHERE id_secretario='".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$lista_actual = array();
		while( list($id_profesional) = mysql_fetch_array($resp) )
		{
			$lista_actual[] = $id_profesional;
		}
		//Se eliminan todas
		$query = "DELETE FROM usuario_secretario WHERE id_secretario='".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$lista_nuevos = array();
		if(count($ids) > 0)
		{
			foreach($ids as $id_profesional => $value)
			{
				$query = "INSERT INTO usuario_secretario
									SET id_secretario=".$this->fields['id_usuario'].", id_profesional = '$value'
									ON DUPLICATE KEY UPDATE id_secretario='".$this->fields['id_usuario']."', id_profesional='$value'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				$lista_nuevos[] = $value;
			}
			
			//Registrar el cambio de secretarios para el usuario
			if( count($lista_actual) <> count($lista_nuevos) )
			{
				$lista_nuevos = implode(',', $lista_nuevos);
				$lista_actual = implode(',', $lista_actual);
				$this->GuardaCambiosRelacionUsuario($this->fields['id_usuario'], 'secretarios', $lista_actual, $lista_nuevos);
			}
		}
		return true;
	}
	function GuardarRevisado($ids)
	{
		//Se obtienen los datos actuales
		$query = "SELECT id_revisado FROM usuario_revisor WHERE id_revisor='".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$lista_actual = array();
		while( list($id_revisado) = mysql_fetch_array($resp) )
		{
			$lista_actual[] = $id_revisado;
		}
		
		//Se eliminan todos para luego insertar
		$query = "DELETE FROM usuario_revisor WHERE id_revisor='".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$lista_nuevos = array();
		if($ids)
		{
			$ids = explode('::',$ids);
			if(count($ids) > 0)
			{
				foreach($ids as $value)
				{
					$query = "INSERT INTO usuario_revisor
										SET id_revisor=".$this->fields['id_usuario'].", id_revisado = '$value'";
					$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					$lista_nuevos[] = $value;
				}
				
				//Registrar el cambio de secretarios para el usuario
				if( count($lista_actual) <> count($lista_nuevos) )
				{
					$lista_nuevos = implode(',', $lista_nuevos);
					$lista_actual = implode(',', $lista_actual);
					$this->GuardaCambiosRelacionUsuario($this->fields['id_usuario'], 'revisores', $lista_actual, $lista_nuevos);
				}
			}
		}
		return true;
	}
	function GuardarTarifaSegunCategoria( $id,$id_categoria_usuario )
	{
		$query = "SELECT id_tarifa, id_moneda, tarifa FROM categoria_tarifa WHERE id_categoria_usuario=".$id_categoria_usuario." ORDER BY id_moneda";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		
		while( list( $id_tarifa, $id_moneda, $tarifa)=mysql_fetch_array($resp) )
		{
			$query2 = "INSERT usuario_tarifa SET id_usuario = '".$id."', id_moneda = '".$id_moneda."', tarifa = ".$tarifa.", id_tarifa = '".$id_tarifa."' 
									ON DUPLICATE KEY UPDATE tarifa = '".$tarifa."'";
			$resp2 = mysql_query($query2, $this->sesion->dbh ) or Utiles::errorSQL($query2,__FILE__,__LINE__,$this->sesion->dbh);
		}
		return true;
	}
	function Costo($id_moneda)
	{
		$query = "SELECT costo FROM usuario_costo WHERE id_usuario='".$this->fields['id_usuario']."' AND id_moneda='$id_moneda'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if($costo = mysql_fetch_assoc($resp))
			return $costo['costo'];
		else
			return 0;
	}
	function Moneda($id_moneda)
	{
		$query = "SELECT tarifa FROM usuario_tarifa WHERE id_usuario='".$this->fields['id_usuario']."' AND id_moneda='$id_moneda'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if($tarifa = mysql_fetch_assoc($resp))
			return $tarifa['tarifa'];
		else
			return 0;
	}
	function GuardarCosto($id_moneda, $costo)
	{
		$query = "INSERT INTO usuario_costo
							SET id_usuario=".$this->fields['id_usuario'].", id_moneda='$id_moneda', costo = '$costo'
							ON DUPLICATE KEY UPDATE id_usuario='".$this->fields['id_usuario']."', id_moneda='$id_moneda', costo = '$costo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
	function GuardarMoneda($id_moneda, $tarifa)
	{
		if($tarifa == 0 || !$tarifa)
			$tarifa = 1;

		$query = "INSERT INTO usuario_tarifa
							SET id_usuario=".$this->fields['id_usuario'].", id_moneda='$id_moneda', tarifa = '$tarifa'
							ON DUPLICATE KEY UPDATE id_usuario='".$this->fields['id_usuario']."', id_moneda='$id_moneda', tarifa = '$tarifa'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
	function HorasTrabajadasEsteMes($id_usuario='',$tipo_dato = 'horas_trabajadas')
	{
		if(!$id_usuario)
			$id_usuario = $this->fields['id_usuario'];


		switch($tipo_dato)
		{
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
							WHERE EXTRACT(YEAR_MONTH FROM fecha) = EXTRACT(YEAR_MONTH FROM NOW())
							AND id_usuario=$id_usuario";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($horas) = mysql_fetch_array($resp);
		list($h,$m,$s) = split(":",$horas);
		if( method_exists('Conf','GetConf') )
		{
			if( Conf::GetConf($this->sesion,'TipoIngresoHoras') == 'decimal' )
			{
				return UtilesApp::Time2Decimal("$h:$m");
			}
		}
		else if (method_exists('Conf','TipoIngresoHoras'))
		{
			if ( Conf::TipoIngresoHoras() == 'decimal' )
			{
				return UtilesApp::Time2Decimal("$h:$m");
			}
		}
		return "$h:$m";
	}
	
	#Calcula las horas trabado esta semana
	function HorasTrabajadasEsteSemana($id_usuario='',$semana_actual)
	{
		if(!$id_usuario)
			$id_usuario = $this->fields['id_usuario'];
	$query = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(duracion)))
							FROM trabajo
							WHERE YEARWEEK(fecha,1)=YEARWEEK('$semana_actual',1)
							AND id_usuario=$id_usuario";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($horas) = mysql_fetch_array($resp);
		list($h,$m,$s) = split(":",$horas);
		if(empty($h)&&empty($m)){ $h='00'; $m='00';}
		if( method_exists('Conf','GetConf') )
		{
			if( Conf::GetConf($this->sesion,'TipoIngresoHoras') == 'decimal' )
			{
				return UtilesApp::Time2Decimal("$h:$m");
			}
		}
		else if (method_exists('Conf','TipoIngresoHoras'))
		{
			if ( Conf::TipoIngresoHoras() == 'decimal' )
			{
				return UtilesApp::Time2Decimal("$h:$m");
			}
		}
		return "$h:$m";
	}
	
	#eliminar usuario
	function Eliminar()
	{

		#Valida si no tiene algún cliente asociado como encargado comercial o encargado
		$query = "SELECT COUNT(*) FROM contrato WHERE contrato.id_usuario_responsable = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT codigo_cliente FROM contrato WHERE id_usuario_responsable = '".$this->fields['id_usuario']."' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cliente) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un').' '.__('usuario').' '.__('que es Encargado Comercial de un ').__('cliente').'. '.__('Cliente').' -'.$cliente;
			return false;
		}
		#Valida si no tiene algún trabajo relacionado
		$query = "SELECT COUNT(*) FROM trabajo WHERE id_usuario = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('No se puede eliminar un').' '.__('usuario').' '.__('que tiene trabajos asociados.');
			return false;
		}
		#Valida si no tiene algún gasto asociado
		$query = "SELECT COUNT(*) FROM cta_corriente WHERE cta_corriente.id_usuario = '".$this->fields['id_usuario']."' OR cta_corriente.id_usuario_orden = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('No se puede eliminar un').' '.__('usuario').' '.__('que tiene gastos asociados.');
			return false;
		}
		$query = "DELETE FROM usuario_permiso WHERE id_usuario = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$query = "DELETE FROM usuario_costo WHERE id_usuario = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$query = "DELETE FROM usuario_secretario WHERE id_secretario = '".$this->fields['id_usuario']."' OR id_profesional = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		$query = "DELETE FROM usuario WHERE id_usuario = '".$this->fields['id_usuario']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
	
	/*Imprime el Select de los usuarios a quien revisa*/
	function select_revisados()
	{
		$query = 
			"SELECT usuario.id_usuario, CONCAT_WS(' ',nombre,apellido1,apellido2) AS nombre
			FROM 
			usuario JOIN usuario_revisor ON (usuario.id_usuario = usuario_revisor.id_revisado)
			WHERE id_revisor = '".$this->fields['id_usuario']."' AND usuario.activo = 1 AND usuario.id_usuario <> '".$this->fields['id_usuario']."'
			ORDER BY usuario.nombre, usuario.apellido1";
				$select = Html::SelectQuery($this->sesion,$query,"usuarios_revisados",'', "multiple style='height: 100px;width:200px'","","220");
		$input = "<input type=hidden name=arreglo_revisados id=arreglo_revisados value='' />";
		$html = $select.$input;
		return $html;
	}
	/*Imprime el Select de los usuarios a los que podría revisar*/
	function select_no_revisados()
	{
		$query_otros = 
			"SELECT usuario.id_usuario, CONCAT_WS(' ',nombre,apellido1,apellido2) AS nombre
			FROM usuario
			WHERE id_usuario NOT IN ( 
				SELECT usuario_revisor.id_revisado 
				FROM usuario_revisor 
				WHERE id_revisor = '".$this->fields['id_usuario']."'  ) AND activo = 1 AND usuario.id_usuario <> '".$this->fields['id_usuario']."'
				ORDER BY usuario.nombre, usuario.apellido1";
		$html = Html::SelectQuery($this->sesion,$query_otros,"usuarios_fuera",'', " style='width:200px'","","220"); 
		return $html;
	}
	
	/*Compara arreglo usuario y guarda cambios realizados*/
	function GuardaCambiosUsuario($arr1, $arr2)
	{
		$usuario_activo = $this->sesion->usuario->fields['id_usuario'];
		$arr_diff = array();
		foreach($arr1 as $indice1 => $valor1)
		{
			if( $arr2[$indice1] != $valor1 )
			{
				if( $valor1 == '0' && $arr2[$indice1] == '' )
					continue;
				$arr_diff[$indice1] = array('valor_original'=> $valor1, 'valor_actual'=> $arr2[$indice1]);
				$query = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
				$query .= " VALUES('".$arr1['id_usuario']."','".$usuario_activo."','".$indice1."','".$valor1."','".$arr2[$indice1]."',NOW())";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}
		}
		return $arr_diff;
	}
	
	/* Guarda cambios en los permisos */
	function GuardaCambiosRelacionUsuario($id_usuario = null, $dato, $actuales, $nuevos)
	{
		$usuario_activo = $this->sesion->usuario->fields['id_usuario'];
		$query = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
		$query .= " VALUES('".$id_usuario."','".$usuario_activo."','".$dato."','".$actuales."','".$nuevos."',NOW())";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}

	/*Lista de permisos usuario*/
	function ListaPermisosUsuario($id_usuario)
	{
		$query .= "SELECT codigo_permiso FROM usuario_permiso WHERE id_usuario='".$id_usuario."' AND codigo_permiso NOT IN ('ALL')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$lista = array();
		while( list($codigo) = mysql_fetch_array($resp) )
		{
			$lista[] = $codigo;
		}
		return $lista;
	}
}
?>
