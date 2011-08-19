<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Asunto extends Objeto
{
	//Etapa actual del proyecto
	var $etapa = null;
	//Primera etapa del proyecto
	var $primera_etapa = null;

	var $monto = null;

	function Asunto($sesion, $fields = "", $params = "")
	{
		$this->tabla = "asunto";
		$this->campo_id = "id_asunto";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function LoadByCodigo($codigo)
	{
		$query = "SELECT id_asunto FROM asunto WHERE codigo_asunto='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByCodigoSecundario($codigo)
	{
		$query = "SELECT id_asunto FROM asunto WHERE codigo_asunto_secundario='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function CodigoACodigoSecundario($codigo_asunto)
	{ 
		if($codigo_asunto != '')
			{
				$query = "SELECT codigo_asunto_secundario FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($codigo_asunto_secundario)=mysql_fetch_array($resp);
				return $codigo_asunto_secundario;
			}
		else	
			return false;
	}
	
	function CodigoSecundarioACodigo($codigo_asunto_secundario)
	{
		if($codigo_asunto_secundario != '')
			{
				$query = "SELECT codigo_asunto FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario'";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($codigo_asunto)=mysql_fetch_array($resp);
				return $codigo_asunto;
			}
		else
			return false;
	}

	//función que asigna los codigos nuevos
	function AsignarCodigoAsunto($codigo_cliente,$glosa_asunto="")
	{
		$where_codigo_gastos="";
		if( method_exists('Conf','GetConf') ) 
			{
					if (Conf::GetConf($this->sesion,'CodigoEspecialGastos') ) 
					{
						if($glosa_asunto=='GASTOS' || $glosa_asunto=='Gastos')
						{
							$codigo_asunto=$codigo_cliente."-9999";
							return $codigo_asunto;
						}
						$where_codigo_gastos="AND asunto.glosa_asunto NOT LIKE 'gastos'";
					}
					if (Conf::GetConf($this->sesion,'TipoCodigoAsunto') )  //Formato XXXX-AAXX
						$anio="";
					else
						$anio="AND STR_TO_DATE(SUBSTR(codigo_asunto,6,2),'%y')=YEAR(NOW())";

			}
		else
			{
				if (method_exists('Conf','CodigoEspecialGastos'))
				{
					if (Conf::CodigoEspecialGastos())
					{
						if($glosa_asunto=='GASTOS' || $glosa_asunto=='Gastos')
						{
							$codigo_asunto=$codigo_cliente."-9999";
							return $codigo_asunto;
						}
						$where_codigo_gastos="AND asunto.glosa_asunto NOT LIKE 'gastos'";
					}
				}
				if (method_exists('Conf','TipoCodigoAsunto') == 1) //Formato XXXX-AAXX
				{
					if (Conf::TipoCodigoAsunto())
						$anio="";
					else
						$anio="AND STR_TO_DATE(SUBSTR(codigo_asunto,6,2),'%y')=YEAR(NOW())";
				}
				else
					$anio="";
			}
		$query = "SELECT SUBSTR(codigo_asunto,6) AS x FROM asunto WHERE asunto.codigo_cliente='$codigo_cliente' $anio $where_codigo_gastos ORDER BY x DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		$f=empty($codigo) ? 1 : $codigo+1;
		if(empty($codigo))
			$g=1;
		else
			$g=substr($codigo,2)+1;
		if( method_exists('Conf','GetConf') )
		{
			if (Conf::GetConf($this->sesion,'TipoCodigoAsunto') == 1)
				$codigo_asunto=$codigo_cliente.'-'.sprintf("%04d",$f);
                       else if (Conf::GetConf($this->sesion,'TipoCodigoAsunto') == 2)
                                $codigo_asunto=$codigo_cliente.'-'.sprintf("%03d",$f);
			else
				$codigo_asunto=$codigo_cliente.'-'.sprintf("%02d",Date('y')).sprintf("%02d",$g);

		}
		else if (method_exists('Conf','TipoCodigoAsunto'))
		{
			if (Conf::TipoCodigoAsunto())
				$codigo_asunto=$codigo_cliente.'-'.sprintf("%04d",$f);
			else
				$codigo_asunto=$codigo_cliente.'-'.sprintf("%02d",Date('y')).sprintf("%02d",$g);
		}
		else
			$codigo_asunto=$codigo_cliente.'-'.sprintf("%04d",$f);
		return $codigo_asunto;
	}

function AsignarCodigoAsuntoSecundario($codigo_cliente_secundario,$glosa_asunto="")
	{
		$where_codigo_gastos="";
		if( method_exists('Conf','GetConf') ) 
		{
				if (Conf::GetConf($this->sesion,'CodigoEspecialGastos') )
				{
					if($glosa_asunto=='GASTOS' || $glosa_asunto=='Gastos')
					{
						$codigo_asunto_secundario=$codigo_cliente_secundario."-9999";
						return $codigo_asunto;
					}
					$where_codigo_gastos="AND asunto.glosa_asunto NOT LIKE 'gastos'";
				}
				if (Conf::GetConf($this->sesion,'TipoCodigoAsunto') )
					$anio="";
				else
					$anio="AND STR_TO_DATE(SUBSTR(codigo_asunto_secundario,6,2),'%y')=YEAR(NOW())";
		}
		else
		{
			if (method_exists('Conf','CodigoEspecialGastos'))
			{
				if (Conf::CodigoEspecialGastos())
				{
					if($glosa_asunto=='GASTOS' || $glosa_asunto=='Gastos')
					{
						$codigo_asunto_secundario=$codigo_cliente_secundario."-9999";
						return $codigo_asunto;
					}
					$where_codigo_gastos="AND asunto.glosa_asunto NOT LIKE 'gastos'";
				}
			}
			if (method_exists('Conf','TipoCodigoAsunto'))
			{
				if (Conf::TipoCodigoAsunto())
					$anio="";
				else
					$anio="AND STR_TO_DATE(SUBSTR(codigo_asunto_secundario,6,2),'%y')=YEAR(NOW())";
			}
			else
				$anio="";
		}
		$query = "SELECT SUBSTR(codigo_asunto_secundario,6) AS x FROM asunto JOIN cliente USING( codigo_cliente ) WHERE cliente.codigo_cliente_secundario='$codigo_cliente_secundario' $anio $where_codigo_gastos ORDER BY x DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		$f=empty($codigo) ? 1 : $codigo+1;
		if(empty($codigo))
			$g=1;
		else
			$g=substr($codigo,2)+1;
		if( method_exists('Conf','GetConf') )
		{
			if( Conf::GetConf($this->sesion,'TipoCodigoAsunto') )
				$codigo_asunto=$codigo_cliente.'-'.sprintf("%04d",$f);
			else
				$codigo_asunto_secundario=$codigo_cliente_secundario.'-'.sprintf("%02d",Date('y')).sprintf("%02d",$g);
		}
		else if (method_exists('Conf','TipoCodigoAsunto'))
		{
			if (Conf::TipoCodigoAsunto())
				$codigo_asunto=$codigo_cliente.'-'.sprintf("%04d",$f);
			else
				$codigo_asunto_secundario=$codigo_cliente_secundario.'-'.sprintf("%02d",Date('y')).sprintf("%02d",$g);
		}
		else
			$codigo_asunto_secundario=$codigo_cliente_secundario.'-'.sprintf("%04d",$f);
		return $codigo_asunto_secundario;
	}
	
	//funcion que cambia todos los asuntos de un cliente
	function InsertarCodigoAsuntosPorCliente($codigo_cliente)
	{
		$query = "SELECT id_asunto FROM asunto WHERE codigo_cliente='$codigo_cliente'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		for($i=1;list($id) = mysql_fetch_array($resp);$i++)
		{
			$this->fields[$this->campo_id]=$id;
			$codigo_asunto=$codigo_cliente.'-'.sprintf("%04d",$i);
			$this->Edit("codigo_asunto",$codigo_asunto);
			$this->Write();
		}
		return true;
	}

	//funcion que actualiza todos los codigos de los clientes existentes (usar una vez para actualizar el registro)
	function ActualizacionCodigosAsuntos()
	{
		$query = "SELECT codigo_cliente FROM cliente";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		for($i=1;list($id) = mysql_fetch_array($resp);$i++)
		{
			if ($id!='NULL')
				$this->InsertarCodigoAsuntosPorCliente($id);
		}
		return true;
	}

	function TotalHoras($emitido = true)
	{
		$where = '';
		if(!$emitido)
			$where = "AND (t2.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado='EN REVISION')";
		
		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
							FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE 1 $where 
							AND t2.cobrable = 1
							AND t2.codigo_asunto='".$this->fields['codigo_asunto']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_horas_no_cobradas) = mysql_fetch_array($resp);
		return $total_horas_no_cobradas;
	}

	function TotalMonto($emitido = true)
	{
		$where = '';
		if(!$emitido)
			$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
			
		$query = "SELECT SUM((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa), prm_moneda.simbolo   
							FROM trabajo 
							JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
							JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda 
							LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa) 
							LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro 
							WHERE 1 $where  
							AND trabajo.cobrable = 1 
							AND trabajo.codigo_asunto='".$this->fields['codigo_asunto']."' GROUP BY trabajo.codigo_asunto"; 
							
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($total_monto_trabajado, $moneda) = mysql_fetch_array($resp);
		return array($total_monto_trabajado,$moneda);
	}
	
	function AlertaAdministrador($mensaje, $sesion)
	{
		$query = "SELECT CONCAT_WS(' ',nombre, apellido1, apellido2) as nombre, email 
								FROM usuario 
							 WHERE activo=1 AND id_usuario = '".$this->fields['id_encargado']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($nombre, $email) = mysql_fetch_array($resp);

		$from = Conf::AppName();

		if (method_exists('Conf','GetConf'))
		{
			$MailAdmin = Conf::GetConf($sesion, 'MailAdmin');
		}
		else if( method_exists('Conf','MailAdmin') )
		{
			$MailAdmin = Conf::MailAdmin();
		}

		$headers = "From: Time & Billing <$MailAdmin>\r\n" .
						 "Reply-To: $MailAdmin \r\n" .
						 'X-Mailer: PHP/' . phpversion();
						 
		Utiles::Insertar( $sesion,  __("Alerta")." ".__("ASUNTO")." - ".$this->fields['glosa_asunto']." | ".Conf::AppName(), $mensaje, $email, $nombre);
		Utiles::Insertar( $sesion,  __("Alerta")." ".__("ASUNTO")." - ".$this->fields['glosa_asunto']." | ".Conf::AppName(), $mensaje, $MailAdmin, $nombre);
		return true;
	}
	function Eliminar()
	{
		if(!$this->Loaded())
			return false;
		#Valida si no tiene algún trabajo relacionado
		$query = "SELECT COUNT(*) FROM trabajo WHERE codigo_asunto = '".$this->fields['codigo_asunto']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('No se puede eliminar un').' '.__('asunto').' '.__('que tiene trabajos asociados');
			return false;
		}
		
		$query = "SELECT Count(*) FROM cta_corriente WHERE codigo_asunto = '".$this->fields['codigo_asunto']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$this->error = __('No se puede eliminar un').' '.__('asunto').' '.__('que tiene gastos asociados');
			return false;
		}
		
		#solo se puede eliminar asuntos que no tengan cobros asociados
		$query = "SELECT COUNT(*) FROM cobro_asunto WHERE codigo_asunto = '".$this->fields['codigo_asunto']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT cobro.id_cobro 
									FROM cobro_asunto 
									JOIN cobro ON cobro.id_cobro = cobro_asunto.id_cobro 
									WHERE cobro_asunto.codigo_asunto = '".$this->fields['codigo_asunto']."'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($cobro) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un').' '.__('asunto').' '.__('que tiene cobros asociados') . ". " . 
					__('Cobro asociado') . __(': #'.$cobro);
			return false;
		}
		
		#solo se pueden eliminar asuntos que no tengan carpetas asociados
		$query = "SELECT COUNT(*) FROM carpeta WHERE codigo_asunto = '".$this->fields['codigo_asunto']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if($count > 0)
		{
			$query = "SELECT id_carpeta, glosa_carpeta FROM carpeta WHERE codigo_asunto = '".$this->fields['codigo_asunto']."'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utile::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($id_carpeta, $glosa_carpeta) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un').' '.__('asunto').' '.__('que tiene carpetas asociados. Carpeta asociado: #'.$id_carpeta.' ( '.$glosa_carpeta.' )');
			return false;
		}

		$query = "DELETE FROM asunto WHERE codigo_asunto = '".$this->fields['codigo_asunto']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}
}

class ListaAsuntos extends Lista
{
	function ListaAsuntos($sesion, $params, $query)
	{
		$this->Lista($sesion, 'Asunto', $params, $query);
	}
}
