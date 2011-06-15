<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';

//Tarea implementa un buscador con columnas dinámicas
class Tarea extends Objeto
{
	// Sesion PHP
    var $sesion = null;


	function Tarea($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tarea";
		$this->campo_id = "id_tarea";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function query($opciones, $id_usuario)
	{
		$where = '';

		$relacion = array();
		if($opciones['tareas_encargado'])
		  $relacion[] = "cliente.id_usuario_encargado=".$id_usuario;
		if($opciones['tareas_mandante'])
			$relacion[] = " tarea.usuario_generador = '".$id_usuario."' ";
		if($opciones['tareas_responsable'])
			$relacion[] = " tarea.usuario_encargado = '".$id_usuario."' ";
		if($opciones['tareas_revisor'])
			$relacion[] = " tarea.usuario_revisor = '".$id_usuario."' ";
		if($opciones['otras_tareas'])
			$relacion[] = " ( (tarea.usuario_encargado <> '".$id_usuario."' OR tarea.usuario_encargado IS NULL) AND (tarea.usuario_generador <> '".$id_usuario."' OR tarea.usuario_generador IS NULL) AND (tarea.usuario_revisor <> '".$id_usuario."' OR tarea.usuario_revisor IS NULL) )";

		if(!empty($relacion))
			$where .= ' AND ('.implode(' OR ',$relacion).') ';
		else
			$where .= ' AND (0) ';

		if($opciones['codigo_cliente'])
			$where .= " AND tarea.codigo_cliente = '".$opciones['codigo_cliente']."' ";
		if($opciones['codigo_asunto'])
			$where .= " AND tarea.codigo_asunto = '".$opciones['codigo_asunto']."' ";

		if($opciones['fecha_desde'] && $opciones['fecha_hasta'])
			$where .= " AND (tarea.fecha_entrega BETWEEN '".Utiles::fecha2sql($opciones['fecha_desde'])."' AND '".Utiles::fecha2sql($opciones['fecha_hasta'])."')";
		else if($opciones['fecha_desde'])
			$where .= " AND tarea.fecha_entrega >= '".Utiles::fecha2sql($opciones['fecha_desde'])."'";
		else if($opciones['fecha_hasta'])
			$where .= " AND tarea.fecha_entrega <= '".Utiles::fecha2sql($opciones['fecha_hasta'])."'";


		if(is_array($opciones['estado']))
		{
			foreach($opciones['estado'] as $estado)
				$conjunto_estados[] .= " tarea.estado = '".$estado."' ";
			if(!empty($conjunto_estados))
				$where .= ' AND ('.implode(' OR ',$conjunto_estados).') ';
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

	function  getTiempoIngresado()
	{
		if(!$this->fields['id_tarea'])
			return '';
		$query = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(duracion_avance))) AS total FROM tarea_comentario WHERE id_tarea= ".$this->fields['id_tarea'];
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total) = mysql_fetch_array($resp);

		if( method_exists('Conf','GetConf') )
		{
			if( Conf::GetConf($this->sesion,'TipoIngresoHoras') == 'decimal' )
				{
					return UtilesApp::Time2Decimal($total);
				}
		}
		if (method_exists('Conf','TipoIngresoHoras'))
		{
				if(Conf::TipoIngresoHoras()=='decimal')
				{
					return UtilesApp::Time2Decimal($total);
				}
		}
		$tiempo = explode(':',$total);

		if($tiempo[0] || $tiempo[1])
			return $tiempo[0].':'.$tiempo[1].':00';
		return '00:00:00';
	}

	function IconoEstado($estado, $verboso = false)
	{
		$l = 'O';
		$color = '#000';

		if($estado == 'Por Asignar')
		{
			$l = 'P';
			$color = 'rgb(238,119,0)';
		}
		else if($estado == 'Asignada')
		{
			$l = 'A';
			$color = 'rgb(0,153,153)';
		}
		else if($estado == 'En Desarrollo')
		{
			$l = 'D';
			$color = 'rgb(0,136,0)';
		}
		else if($estado == 'Por Revisar')
		{
			$l = 'R';
			$color = 'rgb(144,3,163)';		
		}
		else if($estado == 'Lista')
		{
			$l = 'L';
			$color = 'rgb(0,0,170)';
		}

		if($verboso)
			return __('Estado').': <span style="color: '.$color.'" >'.$estado.'</span>';	
		return '<span style="color: '.$color.'" title="Estado: '.$estado.'" ><b>'.$l.'</b></span>';	
	}
	
	
function Write()
	{
		$this->error = "";
		if(!$this->Check())
			return false;
		if($this->Loaded())
		{
			$query = "UPDATE ".$this->tabla." SET ";
			if($this->guardar_fecha)
				$query .= "fecha_modificacion=NOW(),";

			$c = 0;
			foreach ( $this->fields as $key => $val )
			{
				if( $this->changes[$key] )
				{
					$do_update = true;
					if($c > 0)
						$query .= ",";
					if($val != 'NULL')
						$query .= "$key = '".addslashes($val)."'";
					else
						$query .= "$key = NULL ";
					$c++;
				}
			}

			$query .= " WHERE ".$this->campo_id."='".$this->fields[$this->campo_id]."'";
			if($do_update) //Solo en caso de que se haya modificado algún campo
			{
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}
			else //Retorna true ya que si no quiere hacer update la función corrió bien
				return true;
		}
		else
		{
			$query = "INSERT INTO ".$this->tabla." SET ";
			if($this->guardar_fecha)
				$query .= "fecha_creacion=NOW(),";
			$c = 0;
			foreach ( $this->fields as $key => $val )
			{
				if( $this->changes[$key] )
				{
					if($c > 0)
						$query .= ",";
					if($val != 'NULL')
						$query .= "$key = '".addslashes($val)."'";
					else
						$query .= "$key = NULL ";
					$c++;
				}
			}
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			$this->fields[$this->campo_id] = mysql_insert_id($this->sesion->dbh);
		}
		if( $this->fields['orden_estado']==2 || $this->fields['estado'] == 'Asignada')
		{
			$estado = " Se le ha sido asignado la siguiente tarea:";
			$where = "id_usuario=".$this->fields['usuario_encargado'];
		}
		else if( $this->fields['orden_estado'] == 4)
		{
			$estado = " Se le ha desarrollado la siguiente tarea y está a la espera de su revisión:";
			$where = "id_usuario=".$this->fields['usuario_revisor'];
		}
		else if( $this->fields['orden_estado'] == 5)
		{
			$estado = " Se le ha revisado la siguiente tarea:";
			$where = "id_usuario=".$this->fields['usuario_encargado']." OR id_usuario=".$this->fields['usuario_revisor']." OR id_usuario=".$this->fields['usuario_generador'];
		}
		else
			$where = " id_usuario=0";
			
		$query = " SELECT c.glosa_cliente, a.glosa_asunto 
								FROM tarea AS t 
								JOIN cliente AS c ON t.codigo_cliente=c.codigo_cliente
								JOIN asunto AS a ON t.codigo_asunto=a.codigo_asunto
								WHERE id_tarea=".$this->fields['id_tarea'];
		$resp=mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($nombre_cliente,$glosa_asunto)=mysql_fetch_array($resp);
		
		
		if($glosa_asunto)
		$texto_asunto = "Asunto: ".$glosa_asunto."<br>";
		
		$query = "SELECT id_usuario, CONCAT_WS(' ', nombre, apellido1, apellido2) AS nombre, email FROM usuario WHERE activo=1 AND ".$where;
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		while(list($id,$nombre,$email) = mysql_fetch_array($resp))
		{
			$query2 = "SELECT rut, dv_rut, password FROM usuario WHERE id_usuario=".$id;
			$resp2 = mysql_query($query2,$this->sesion->dbh) or Utiles::errorSQL($query2,$this->sesion->dbh);
			list($rut,$dv_rut,$password)=mysql_fetch_array($resp2);
			
			$subject = "[Tarea] ".$this->fields['nombre'];
			$mensaje = "Estimado Sr. ".$nombre.", <br><br>".$estado." <br><br> Cliente: ".$nombre_cliente."<br> ".$texto_asunto." Tarea: ".$this->fields['nombre']."<br><br> Para ingresar haga clic (<a href=http://lab.lemontech.cl".Conf::RootDir()."/app/interfaces/agregar_tarea.php?popup=1&id_tarea=".$this->fields['id_tarea'].">aquí</a>).";
			
			Utiles::Insertar($this->sesion,$subject,$mensaje,$email,$nombre,false);
		}
		return true;
	}
}



class ListaTareas extends Lista
{
    function ListaTareas($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Tarea', $params, $query);
    }
}
