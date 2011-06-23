<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../app/classes/Cliente.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';
require_once Conf::ServerDir().'/../app/classes/CobroPendiente.php';
require_once Conf::ServerDir().'/../app/classes/ContratoDocumentoLegal.php';
require_once Conf::ServerDir().'/../app/classes/Asunto.php';
require_once Conf::ServerDir().'/../app/classes/Cliente.php';
require_once Conf::ServerDir().'/../app/classes/Contrato.php';
require_once Conf::ServerDir().'/../app/classes/UsuarioExt.php';
require_once Conf::ServerDir().'/../app/classes/UsuarioExt.php';
require_once Conf::ServerDir().'/../app/classes/Moneda.php';

class Migracion
{
	var 
$sesion = null;
	var $logs = array();

	public function Migracion()
	{
		$this->sesion = new Sesion();
	}
	
	public function AgregarAsunto($asunto = null,  $contrato = null, $cobros_pendientes = array(), $documentos_legales = array(), $cliente = null)
	{
		if (empty($asunto))
		{
			$this->logs[] = "Faltan parametro asunto";
			return false;
		}

		if (empty($cliente))
		{
			$this->logs[] = "Faltan parametro cliente";
			return false;
		}
		
		$asunto_existente = new Asunto($this->sesion);

		if($asunto->fields['id_asunto'])
		{
			if(!$asunto->Load($asunto->fields['id_asunto']))
			{
				$pagina->AddError('No existe el asunto');

				return false;
			}			
		}
		else
		{
			$asunto_existente->LoadByCodigo($asunto->fields["codigo_asunto"]);
		}

		if($asunto_existente->Loaded())
		{
			$this->logs[] = "El asunto con código " . $asunto->fields["codigo_asunto"] . " ya existe.";
			return true;
		}
		else if (empty($asunto->fields["codigo_asunto"]))
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CodigoEspecialGastos') ) || ( method_exists('Conf','CodigoEspecialGastos') && Conf::CodigoEspecialGastos() ) )
			{
				$asunto->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto->fields["codigo_cliente"], $asunto->fields["glosa_asunto"]);
			}
			else
			{
				$asunto->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto->fields["codigo_cliente"]);
			}
		}

		$asunto_existente->Edit("id_usuario", $this->sesion->usuario->fields['id_usuario']);
		$asunto_existente->Edit("codigo_asunto", $asunto->fields["codigo_asunto"]);

	
		if (empty($cliente->fields['codigo_cliente_secundario']))
		{
			$cliente->Edit('codigo_cliente_secundario', $cliente->CodigoACodigoSecundario($asunto->fields["codigo_cliente"]));
		}

		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$asunto_existente->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . substr(strtoupper($asunto->fields['codigo_asunto_secundario']), -4));
		}
		else
		{
	
			if(!empty($asunto->fields["codigo_asunto_secundario"]))
			{
				$asunto_existente->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . strtoupper($asunto->fields["codigo_asunto_secundario"]));
			}
			else
			{
				$asunto_existente->Edit("codigo_asunto_secundario", $asunto->fields['codigo_asunto']);
			}
		}

		$asunto_existente->Edit("actividades_obligatorias", $asunto->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto_existente->Edit("mensual",$asunto->fields['mensual'] ? "SI" : "NO");

		$asunto_existente->Edit("glosa_asunto", $asunto->fields['glosa_asunto']);
		$asunto_existente->Edit("codigo_cliente", $asunto->fields['codigo_cliente']);
		$asunto_existente->Edit("id_tipo_asunto", 
		$asunto->fields['id_tipo_asunto']);
		$asunto_existente->Edit("id_area_proyecto", $asunto->fields['id_area_proyecto']);
	
		$asunto_existente->Edit("id_idioma", $asunto->fields['id_idioma']);
	
		$asunto_existente->Edit("descripcion_asunto", $asunto->fields['descripcion_asunto']);
		$asunto_existente->Edit("id_encargado", $asunto->fields['id_encargado']);
		$asunto_existente->Edit("contacto", $asunto->fields['asunto_contacto']);
		$asunto_existente->Edit("fono_contacto", $asunto->fields['fono_contacto']);
		$asunto_existente->Edit("email_contacto", $asunto->fields['email_contacto']);
		$asunto_existente->Edit("actividades_obligatorias", $asunto->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto_existente->Edit("activo", $asunto->fields['activo']);
		$asunto_existente->Edit("cobrable", $asunto->fields['cobrable']);
		$asunto_existente->Edit("mensual", $asunto->fields['mensual'] ? "SI":"NO");
		$asunto_existente->Edit("alerta_hh", $asunto->fields['alerta_hh']);
		$asunto_existente->Edit("alerta_monto", $asunto->fields['alerta_monto']);
		$asunto_existente->Edit("limite_hh", $asunto->fields['limite_hh']);
		$asunto_existente->Edit("limite_monto", $asunto->fields['limite_monto']);

		if ($contrato)
		{
			$contrato_existente = new Contrato($this->sesion);

	
			if(!empty($asunto->fields['id_contrato']))
			{
				$contrato_existente->Load($asunto->fields['id_contrato']);
			}
			else if (!empty($asunto->fields['id_contrato_indep']))
			{
				$contrato_existente->Load($asunto->fields['id_contrato_indep']);
			}
			self::GuardarContrato($cliente, $contrato, $contrato_existente, $cobros_pendientes, $documentos_legales, $asunto_existente);
		}
	}
	
	function SetDatosParametricos( $prm ) 
	{
		foreach( $prm as $key => $val ) {
			$query = "DELETE FROM $key";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
		foreach( $val['datos'] as $llave => $valor ) {
				if( $llave == 0 ) $datos_prm = "('".($llave+1)."','".$valor."')";
				else $datos_prm .= ",('".($llave+1)."','".$valor."')";
			}
			
			$query = "INSERT INTO $key ( ".$val['campo_id'].", ".$val['campo_glosa']." ) VALUES $datos_prm ";
			$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		}
	}
	
	
function ImprimirDataEnPantalla($response)
	{
	 	echo "<table border='1px solid black'>";
	 	$i=0;
	 	while( $row = mysql_fetch_assoc($response) )
	 		{
				echo "<tr>";
	 			if($i==0)
	 				{
	 					foreach( $row as $key => $val )
	 						echo "<th>$key</th>";
	 					echo "</tr><tr>";
					}
	 			foreach( $row as $key => $val )
	 				echo "<td>$val</th>";
				echo "</tr>";
	 			$i++;
	 		}
	 	echo "</table>";
	}
	
	function Query2ObjetosCliente($response)
	{
		$this->logs = array();
		while($row = 
		mysql_fetch_assoc($response))
		{
			//print_r($row);
			$sesion = new Sesion();
			$cliente = new Cliente($this->sesion);
			$contrato = new Contrato($this->sesion);
			
			$row['cliente_FFF_glosa_cliente'] 				= addslashes($row['cliente_FFF_glosa_cliente']);
			$row['contrato_FFF_factura_razon_social'] = addslashes($row['contrato_FFF_factura_razon_social']);
	
			$row['cliente_FFF_rsocial'] 							= addslashes($row['cliente_FFF_rsocial']);
			
			foreach( $row as $key => $val )
			{
				$keys = explode('_FFF_',$key);
				if( $keys[0] == 'cliente' )
					$cliente->Edit($keys[1],$val);
				else if( $keys[0] == 'contrato' )
					$contrato->Edit($keys[1],$val);
			}

	
			self::AgregarCliente($cliente, $contrato);
			
			//AgregarCliente($cliente, $contrato);
			//echo '<pre>';print_r($cliente->fields);echo '</pre>';
			//echo '<pre>';print_r($contrato->fields);echo '</pre>';
		}
		
		foreach($this->logs as $log)
		{
			echo $log . "<br/>";
		}
	}
	
	function Query2ObjetoAsunto($response)
	{
		$this->logs = array();
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$asunto = new Asunto($this->sesion);
			$contrato = new Contrato($this->sesion);
			$cliente = new Cliente($this->sesion);
		

			foreach( $row as $key => $val )
			{
				$keys = explode('_FFF_',$key);

				if( $keys[0] == 'asunto' )
					$asunto->Edit($keys[1],$val);
				else if( $keys[0] == 'contrato' )
					$contrato->Edit($keys[1],$val);
			}


			//self::AgregarAsunto($asunto, $contrato);
			self::AgregarAsunto($asunto, $contrato, NULL, NULL, $cliente);
			//echo '<pre>';print_r($asunto->fields);echo '</pre>';
			//echo '<pre>';print_r($contrato->fields);echo '</pre>';
		}
	}
 
	function Query2ObjetoUsuario($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();

			$usuario = new UsuarioExt($sesion);
			$usuario->guardar_fecha = false;
			$usuario->tabla = "usuario";
			
			$permisos = $this->PermisosSegunCategoria($row);
			$row      = $this->LimpiarCategoriaUsuario($row);
			
			if( $row['usuario_FFF_email'] == "" ) 
				$row['usuario_FFF_email'] = 'mail@estudio.pais';
			if( $row['usuario_FFF_rut'] == "" || trim($row['usuario_FFF_rut']) == "000" || trim($row['usuario_FFF_rut']) == "0000" ) 
				$row['usuario_FFF_rut']   = $row['usuario_FFF_id_usuario'];

			foreach( $row as $key => $val )
			{
				$keys = explode('_FFF_',$key);
				$usuario->Edit($keys[1],$val);
			}
	
			$this->AgregarUsuario($usuario, $permisos);
			//echo '<pre>';print_r($usuario->fields);echo '</pre>';
			//echo "<br><br>--------------------------------------------<br><br>";
		}
	}
	
	function LimpiarCategoriaUsuario( $data )
	{ 
		switch ( $data['usuario_FFF_id_categoria_usuario'] ) {
				case 'AC':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asociado'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario']) 			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ",$this->sesion->dbh));
										break;
				case 'AD':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Administrativo'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa LIKE 'Administra%' ",$this->sesion->dbh));
										break;
				case 'AJ':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asociado Junior'",$this->sesion->dbh));
										list($data['usuario_FFF_id_area_usuario']) 			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ",$this->sesion->dbh));
										break;
				case 'AM':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Administrativo'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa LIKE 'Administra%' ",$this->sesion->dbh));
										break;
				case 'AS':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asociado Senior'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario']) 			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ",$this->sesion->dbh));
										break;
				case 'AT':	list($data['usuario_FFF_id_categoria_usuario']) =  mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asistente'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ",$this->sesion->dbh));
										break;
				case 'NT':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'NT'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ",$this->sesion->dbh));
										break;
				case 'PO':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Procurador'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ",$this->sesion->dbh));
										break;
				case 'PR':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Practicante'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ",$this->sesion->dbh));
										break;
				case 'SE':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Secretaria'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa LIKE 'Administra%' ",$this->sesion->dbh));
										break;
				case 'SO':	list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Socio'",$this->sesion->dbh)); 
										list($data['usuario_FFF_id_area_usuario'])			= mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ",$this->sesion->dbh));
										break;
			}
		return $data;
	}
	
	function PermisosSegunCategoria($data)
	{
		$id = $data['usuario_FFF_id_usuario'];
		switch ( $data['usuario_FFF_id_categoria_usuario'] ) {
				case 'AC': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'AD': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'),
																		 array('permitido' => true,'codigo_permiso' => 'ADM'),
																		 array('permitido' => true,'codigo_permiso' => 'COB'),
																		 array('permitido' => true,'codigo_permiso' => 'DAT'),
																		 array('permitido' => true,'codigo_permiso' => 'OFI'),
																		 array('permitido' => true,'codigo_permiso' => 'REP'),
																		 array('permitido' => true,'codigo_permiso' => 'REV')
																		);
				case 'AJ': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'AM': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'),
																		 array('permitido' => true,'codigo_permiso' => 'ADM'),
																		 array('permitido' => true,'codigo_permiso' => 'COB'),
																		 array('permitido' => true,'codigo_permiso' => 'DAT'),
				 														 array('permitido' => true,'codigo_permiso' => 'OFI'),
				 														 array('permitido' => true,'codigo_permiso' => 'REP'),
																		 array('permitido' => true,'codigo_permiso' => 'REV')
																		);
				case 'AS': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'AT': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'PO': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'PR': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'SE': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'));
				case 'SO': $permisos = array(array('permitido' => true,'codigo_permiso' => 'PRO'),
																		 array('permitido' => true,'codigo_permiso' => 'ADM'),
																		 array('permitido' => true,'codigo_permiso' => 'COB'),
																		 array('permitido' => true,'codigo_permiso' => 'DAT'),
																		 array('permitido' => true,'codigo_permiso' => 'OFI'),
																		 array('permitido' => true,'codigo_permiso' => 'REP'),
																		 array('permitido' => true,'codigo_permiso' => 'REV'),
																		 array('permitido' => true,'codigo_permiso' => 'SOC')
																		);
			}
			return $permisos;
	}
	
	public function ExisteCodigoSecundario($cliente, $codigo_cliente_secundario)
	{
		if (empty($cliente) or empty($codigo_cliente_secundario))
		{
			return false;
		}

		$query_codigos = "SELECT codigo_cliente_secundario FROM cliente WHERE id_cliente != '" . $cliente . "'";
		$resp_codigos = mysql_query($query_codigos, $this->sesion->dbh) or Utiles::errorSQL($query_codigos,__FILE__,__LINE__,$this->sesion->dbh);
	
		while(list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos))
		{
			if($codigo_cliente_secundario == $codigo_cliente_secundario_temp)
			{ 
				$this->logs[] = 'El código ingresado ya existe para otro cliente';
				return true;
			}
		}
	}

	function AgregarCliente($cliente = null, $contrato = null, $cobros_pendientes = array(), $documentos_legales = array(), $agregar_asuntos_defecto = false)
	{
		$this->logs[] = "Ingresando cliente: " . $cliente->fields['codigo_cliente'];

		if (empty($cliente) or empty($contrato))
		{
			$this->logs[] = "Faltan parametro(s), cliente o asunto";
			return false;
		}
		
	    $id_usuario = $this->sesion->usuario->fields['id_usuario'];

			$codigo_obligatorio = true;
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CodigoObligatorio') ) || ( method_exists('Conf','CodigoObligatorio') && Conf::CodigoObligatorio() ) )
			{
				if(!Conf::CodigoObligatorio())
				{
					$codigo_obligatorio = false;
				}
			}

			$cliente_existente = new Cliente($this->sesion);
			$contrato_existente = new Contrato($this->sesion);


			if ($cliente->fields['id_cliente'])
	    {
				$cliente_existente->Load($cliente->fields['id_cliente']);
				$contrato_existente->Load($cliente_existente->fields['id_contrato']);
			}

		if($cliente_existente->Loaded())
		{
			if(empty($cliente->$fields['activo']))
			{
				$cliente_existente->InactivarAsuntos();
			}
			$loadasuntos = false;
		}
    else
		{
			if (empty($cliente->fields['codigo_cliente']))
			{
				$cliente->fields['codigo_cliente'] = 
				$cliente->AsignarCodigoCliente();
			}
			$loadasuntos = true;
		}


		if(!empty($cliente->fields["codigo_cliente_secundario"]) and self::ExisteCodigoSecundario($cliente->fields['id_cliente'], $cliente->fields["codigo_cliente_secundario"]))
		{ 
			return false;
		}

		if(self::GuardarCliente($cliente, $cliente_existente))
		{
			$this->logs[] = "Ingresando contrato";
			if(self::GuardarContrato($cliente, $contrato, $contrato_existente, $cobros_pendientes, $documentos_legales))
			{
				$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);
				if(self::Write($cliente))
				{
					$this->logs[] = __('Cliente').' '.__('Guardado con exito').'<br>'.__('Contrato guardado con éxito');
				}
				else
				{
					$this->logs[] = $cliente->error;
				}
			}
		}


		if ($agregar_asuntos_defecto)
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'AgregarAsuntosPorDefecto') != '' ) || ( method_exists('Conf','AgregarAsuntosPorDefecto') ) ) && $loadasuntos )
			{

				if( method_exists('Conf','GetConf') )
				{
					$asuntos = explode(';',Conf::GetConf($this->sesion,'AgregarAsuntosPorDefecto'));
				}
				else
				{
					$asuntos = Conf::AgregarAsuntosPorDefecto();
				}
	

				for($i=1;$i<count($asuntos);$i++)
				{	
					$asunto = new Asunto($this->sesion);
					$asunto->Edit('codigo_asunto',$asunto->AsignarCodigoAsunto($codigo_cliente));
					$asunto->Edit('codigo_asunto_secundario',$asunto->AsignarCodigoAsuntoSecundario($codigo_cliente_secundario));
					$asunto->Edit('glosa_asunto',$asuntos[$i]);
					$asunto->Edit('codigo_cliente',$codigo_cliente);
					$asunto->Edit('id_contrato',$contrato->fields['id_contrato']);
					$asunto->Edit('id_usuario',$id_usuario);
					$asunto->Edit('contacto',$contacto);

					$asunto->Edit("fono_contacto",$fono_contacto_contrato);
					$asunto->Edit("email_contacto",$email_contacto_contrato);
					$asunto->Edit("direccion_contacto",$direccion_contacto_contrato);
					$asunto->Edit("id_encargado",$id_usuario_encargado);
					if(!self::Write($asunto))
					{
						$this->logs[] = $asunto->error;
					}
				}
			}
		}
	}
	
	public function GuardarCliente($cliente = null, $cliente_existente = null)
	{
		$cliente_existente->Edit("glosa_cliente", $cliente->fields["glosa_cliente"]);
		$cliente_existente->Edit("codigo_cliente", $cliente->fields["codigo_cliente"]);
		$cliente_existente->Edit("codigo_cliente_secundario", empty($cliente->fields["codigo_cliente_secundario"]) ? $cliente->fields["codigo_cliente"] : $cliente->fields["codigo_cliente_secundario"]);
		$cliente_existente->Edit("id_moneda", $cliente->fields["id_moneda"]);
		$cliente_existente->Edit("activo", $cliente->fields["activo"] == 1 ? '1' : '0');
		$cliente_existente->Edit("id_usuario_encargado", $cliente->fields["id_usuario_encargado"]);
		$cliente_existente->Edit("id_grupo_cliente", !empty($cliente->fields["id_grupo_cliente"]) ? 
		$cliente->fields["id_grupo_cliente"] : 'NULL');

	
		if(!self::Write($cliente_existente))
		{
			$this->logs[] = $cliente->error;
			return false;
		}
		return true;
	}
	
	public function GuardarContrato($cliente = null, $contrato = null, $contrato_existente = null, $cobros_pendientes = array(), $documentos_legales = array(), $asuntos = array())
	{
		$moneda		= new Moneda($this->sesion);
		$contrato_existente->guardar_fecha = false;

		if($contrato->fields["forma_cobro"] != 'TASA' && $contrato->fields["monto"] == 0)
		{
			$this->logs[] =  __('Ud. a seleccionado forma de cobro:') . " " . $contrato->fields["forma_cobro"] . " " . __('y no ha ingresado monto');
		}
		else if($contrato->fields["forma_cobro"] == 'TASA')
		{
			$contrato->fields["monto"] = '0';
		}

	
		$contrato_existente->Edit("activo", $contrato->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato_existente->Edit("usa_impuesto_separado", $contrato->fields["impuesto_separado"] ? '1' : '0');
		$contrato_existente->Edit("usa_impuesto_gastos", $contrato->fields["impuesto_gastos"] ? '1' : '0');

		$contrato_existente->Edit("glosa_contrato",$contrato->fields["glosa_contrato"]);
		$contrato_existente->Edit("codigo_cliente",$cliente->fields["codigo_cliente"]); //MEJORAR: DEBE OBTENER EL CODIGO CLIENTE DIRECTAMENTE DEL OBJ CONTRATO
	
		$contrato_existente->Edit("id_usuario_responsable",$contrato->fields["id_usuario_responsable"]);
		$contrato_existente->Edit("centro_costo", $contrato->fields["centro_costo"]);
	
		$contrato_existente->Edit("observaciones",$contrato->fields["observaciones"]);


		if( method_exists('Conf','GetConf') )
		{
			if ( Conf::GetConf($this->sesion,'TituloContacto') )
			{
				$contrato_existente->Edit("titulo_contacto", $contrato->fields["titulo_contacto"]);
				$contrato_existente->Edit("contacto", $contrato->fields["nombre_contacto"]);
				$contrato_existente->Edit("apellido_contacto", $contrato->fields["apellido_contacto"]);
			}
		}
		else if( method_exists('Conf','TituloContacto') )
		{
			if(Conf::TituloContacto())
			{
				$contrato_existente->Edit("titulo_contacto", $contrato->fields["titulo_contacto"]);
				$contrato_existente->Edit("contacto", $contrato->fields["nombre_contacto"]);
				$contrato_existente->Edit("apellido_contacto",$contrato->fields["apellido_contacto"]);
			}
		}

		$contrato_existente->Edit("contacto", $contrato->fields["contacto"]);
		$contrato_existente->Edit("fono_contacto", $contrato->fields["fono_contacto_contrato"]);
		$contrato_existente->Edit("email_contacto",$contrato->fields["email_contacto_contrato"]);
		$contrato_existente->Edit("direccion_contacto", $contrato->fields["direccion_contacto_contrato"]);
		$contrato_existente->Edit("es_periodico", $contrato->fields["es_periodico"]);
		$contrato_existente->Edit("activo", $contrato->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato_existente->Edit("periodo_fecha_inicio", Utiles::fecha2sql($contrato->fields["periodo_fecha_inicio"]));
		$contrato_existente->Edit("periodo_repeticiones", $contrato->fields["periodo_repeticiones"]);
		$contrato_existente->Edit("periodo_intervalo", $contrato->fields["periodo_intervalo"]);
		$contrato_existente->Edit("periodo_unidad", $contrato->fields["codigo_unidad"]);
		$contrato_existente->Edit("monto", $contrato->fields["monto"]);
		$contrato_existente->Edit("id_moneda", $contrato->fields["id_moneda"]);
		$contrato_existente->Edit("forma_cobro", $contrato->fields["forma_cobro"]);
		$contrato_existente->Edit("fecha_inicio_cap", Utiles::fecha2sql($contrato->fields["fecha_inicio_cap"]));
		$contrato_existente->Edit("retainer_horas", $contrato->fields["retainer_horas"]);
		$contrato_existente->Edit("id_usuario_modificador", $this->sesion->usuario->fields['id_usuario']);
		$contrato_existente->Edit("id_carta", $contrato->fields["id_carta"] ? $contrato->fields["id_carta"] : 'NULL');
		$contrato_existente->Edit("id_formato", $contrato->fields["id_formato"]);
		$contrato_existente->Edit("id_tarifa", $contrato->fields["id_tarifa"] ? $contrato->fields["id_tarifa"] : 'NULL');
		$contrato_existente->Edit("id_tramite_tarifa", $contrato->fields["id_tramite_tarifa"] ? $contrato->fields["id_tramite_tarifa"] : 'NULL' );
		$contrato_existente->Edit("id_moneda_tramite", $id_moneda_tramite ? $id_moneda_tramite : '1' );

		#facturacion
		$contrato_existente->Edit("rut", $contrato->fields["factura_rut"]);
		$contrato_existente->Edit("factura_razon_social", $contrato->fields["factura_razon_social"]);
		$contrato_existente->Edit("factura_giro", $contrato->fields["factura_giro"]);
		$contrato_existente->Edit("factura_direccion", $contrato->fields["factura_direccion"]);
		$contrato_existente->Edit("factura_telefono", $contrato->fields["factura_telefono"]);
		$contrato_existente->Edit("cod_factura_telefono", $contrato->fields["cod_factura_telefono"]);

	
		#Opc contrato
		$contrato_existente->Edit("opc_ver_modalidad", $contrato->fields["opc_ver_modalidad"]);
		$contrato_existente->Edit("opc_ver_profesional",$contrato->fields["opc_ver_profesional"]);
		$contrato_existente->Edit("opc_ver_gastos", $contrato->fields["opc_ver_gastos"]);
		$contrato_existente->Edit("opc_ver_morosidad", $contrato->fields["opc_ver_morosidad"]);
		$contrato_existente->Edit("opc_ver_descuento", $contrato->fields["opc_ver_descuento"]);
		$contrato_existente->Edit("opc_ver_tipo_cambio", $contrato->fields["opc_ver_tipo_cambio"]);
		$contrato_existente->Edit("opc_ver_numpag", $contrato->fields["opc_ver_numpag"]);
		$contrato_existente->Edit("opc_ver_resumen_cobro", $contrato->fields["opc_ver_resumen_cobro"]);
		$contrato_existente->Edit("opc_ver_carta", $contrato->fields["opc_ver_carta"]);
		$contrato_existente->Edit("opc_papel", $contrato->fields["opc_papel"]);
		$contrato_existente->Edit("opc_moneda_total", $contrato->fields["opc_moneda_total"]);
		$contrato_existente->Edit("opc_moneda_gastos", $contrato->fields["opc_moneda_gastos"]);
		
		$contrato_existente->Edit("opc_ver_solicitante", $contrato->fields["opc_ver_solicitante"]);
	
		$contrato_existente->Edit("opc_ver_asuntos_separados", $contrato->fields["opc_ver_asuntos_separados"]);
		$contrato_existente->Edit("opc_ver_horas_trabajadas", $contrato->fields["opc_ver_horas_trabajadas"]);
		$contrato_existente->Edit("opc_ver_cobrable", $contrato->fields["opc_ver_cobrable"]);
	
		$contrato_existente->Edit("codigo_idioma", $contrato->fields["codigo_idioma"] != '' ? $contrato->fields["codigo_idioma"] : 'es');

		#descto
		$contrato_existente->Edit("tipo_descuento", $contrato->fields["tipo_descuento"]);
		if($contrato->fields["PORCENTAJE"])
		{
			$contrato_existente->Edit("porcentaje_descuento", !empty($contrato->fields["porcentaje_descuento"]) ? $contrato->fields["porcentaje_descuento"] : '0');
			$contrato_existente->Edit("descuento", '0');
		}
		else
		{
			$contrato_existente->Edit("descuento", !empty($contrato->fields["descuento"]) ? 
			$$contrato->fields["descuento"] : '0');
			$contrato_existente->Edit("porcentaje_descuento",'0');
		}
		$contrato_existente->Edit("id_moneda_monto", $contrato->fields["id_moneda_monto"]);
		$contrato_existente->Edit("alerta_hh", $contrato->fields["alerta_hh"]);
		$contrato_existente->Edit("alerta_monto", $contrato->fields["alerta_monto"]);
		$contrato_existente->Edit("limite_hh", $contrato->fields["limite_hh"]);
		$contrato_existente->Edit("limite_monto", $contrato->fields["limite_monto"]);
		$contrato_existente->Edit("separar_liquidaciones", $contrato->fields["separar_liquidaciones"]);
		
		if (!self::Write($contrato_existente))
		{
			$this->logs[] = $contrato_existente->error;
			return false;
		}

		//Asuntos
		$asuntos->Edit("id_contrato", $contrato_existente->fields["id_contrato"]);
		$asuntos->Edit("id_contrato_indep",$contrato_existente->fields['id_contrato']);
		if (!self::Write($asuntos))
		{
			$this->logs[] = $asuntos->error;
		}
		
		
		/*
		//Cobros pendientes
		CobroPendiente::EliminarPorContrato($this->sesion, $contrato_existente->fields['id_contrato']);
		foreach ($cobros_pendientes as $cobro_pendiente)
		{
			$cobro = new CobroPendiente($this->sesion);
			$cobro->Edit("id_contrato", $contrato_existente->fields["id_contrato"]);
			$cobro->Edit("fecha_cobro", Utiles::fecha2sql($cobro_pendiente->fields["fecha_cobro"]));
			$cobro->Edit("descripcion", $cobro_pendiente->fields["descripcion"]);
			$cobro->Edit("monto_estimado", $cobro_pendiente->fields["monto_estimado"]);
			if (!self::Write($cobro))
			{
				$this->logs[] = $cobro->error;
			}
		}

		//Documentos legales
	
		ContratoDocumentoLegal::EliminarDocumentosLegales($this->sesion, 
		$contrato_existente->fields['id_contrato']);
		foreach ($documentos_legales as $documento_legal) {
			$contrato_documento_legal = new ContratoDocumentoLegal($this->sesion);
			$contrato_documento_legal->Edit('id_contrato', $contrato_existente->fields["id_contrato"]);
			$contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["id_tipo_documento_legal"]);
			if (!empty($documento_legal->fields["honorarios"]))
			{
				$contrato_documento_legal->Edit('honorarios', 1);
			}
			if (!empty($documento_legal->fields["gastos_con_iva"]))
			{
				$contrato_documento_legal->Edit('gastos_con_impuestos', 1);
			}
			if (!empty($documento_legal->fields["gastos_sin_iva"]))
			{
				$contrato_documento_legal->Edit('gastos_sin_impuestos', 1);
			}
			$contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["documento_legal"]);
			if (!self::Write($contrato_documento_legal))
			{
				$this->logs[] = $contrato_documento_legal->error;
			}
		}
		*/

	}

	#Recibe un arreglo de items, construido de la forma
	# $items = array();
	# //foreach [nombre,rut,etc]...
	# $u = new Usuario($this->sesion);
	# $u->Edit('nombre',$nombre)
	# $u->Edit('rut',$rut);
	# $u->Edit ...
	# $items[$rut]['usuario'] = $u;
	# $items[$rut]['permisos'] = array('ADM','PRO');
	# //next
	#
	#  $secretarios = array( $rut_secretario => array($rut_jefe1, $rut_jefe2) )
	#  $revisores = array ( $rut_revisor => array($rut_revisado1, $rut_revisado2) )
	
	public function AgregarUsuarios($items = array(), $secretarios = array(), $revisores = array())
	{
		if(!empty($items))
		{
			foreach($items as $item)
				AgregarUsuario($item['usuario'],$item['permisos']);

			if(!empty($secretarios))
				foreach($secretarios as $rut_secretario => $ruts_jefes)
				{
					$secretario = $items[$rut_secretario]['usuario'];
					$ids_jefes = array();
	
					foreach($ruts_jefes as $rut_jefe)
						$ids_jefes[] = $items[$rut_jefe]['usuario']->fields['id_usuario'];
					$secretario->GuardarSecretario($ids_jefes);
				}

			if(!empty($revisores))
	
				foreach($revisores as $rut_revisor => $ruts_revisados)
				{
					$revisor = $items[$rut_revisor]['usuario'];
					$ids_revisados = array();

					foreach($ruts_revisados as $rut_revisado)
						$ids_revisados[] = $items[$rut_revisado]['usuario']->fields['id_usuario'];
						$revisor->GuardarRevisado($ids_revisados);
				}
		}
	}

	#Datos requeridos:
	
	#email
		#rut (Utiles::LimpiarRut($rut))
		#dv_rut
		#nombre
		
	#apellido1
		#apellido2
		#id_categoria_usuario
		#id_area_usuario
		
	#telefono1
		#telefono2
		#dir_calle
		#dir_numero
		#dir_depto
		
	#dir_comuna
		#activo
		#restriccion_min
		#restriccion_max
	
		
	#dias_ingreso_trabajo
		#retraso_max
		#restriccion_diario
		
	#alerta_diaria
		#alerta_semanal
		#alerta_revisor
		#id_moneda_costo
		
	#Datos opcionales:
		#username :: default = nombre + apellido1 + apellido2
		
	#password (en plaintext) :: default = Utiles::NewPassword();
		#Datos generados:
	
	#visible -> $activo==1 ? 1 : 0

	#Parametros:
		#permisos. Array('ADM','PRO',etc). 'ALL' siempre se incluye automáticamente.
		#secretario_de. Ids de los usuarios del cual este es secretario. (Por lo tanto, los secretarios se ingresan al final). Array('1'=>'1','2'=>'2').
		#usuarios_revisados. Ids de los usuarios a los cuales este revisa. Array('1'=>'1','2'=>'2')

	public function AgregarUsuario($usuario = null, $permisos = array(),$secretario_de = array(),$usuarios_revisados = array())
	{
		#Genero el username si no existe
		if(!$usuario->fields['username'])
			$usuario->Edit('username', $nombre.' '.$apellido1.' '.$apellido2);

		#Confirmo que el username no exista
		$query = "SELECT count(*) FROM usuario WHERE username = '".addslashes($usuario->fields['username'])."'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	
		list($cantidad) = mysql_fetch_array($resp);
		if($cantidad > 0)
		{
			$this->logs[] = '<br>Error ingreso usuario #'.$usuario->fields['id_usuario'].': username "'.$usuario->fields['username'].'"ya existía';
			return false;
		}

		#Confirmo que venga email
		if($usuario->fields['email'] == "")
		{
			$this->logs[] = '<br>Error ingreso usuario: debe ingresar email.';
			return false;
		}

		#Visible depende de activo
		$usuario->Edit('visible', $usuario->fields['activo']==1 ? '1' : '0');

		#Calculo el md5 del password provisto, o uno nuevo si no viene.
		if(!$usuario->fields['password'])
		{
			$password = Utiles::NewPassword();
			//$this->logs[] .= '<br>Usuario: username "'.$usuario->fields['username'].'" password = "'.$password.'"';
		}
		else
			$password = $usuario->fields['password'];
		#Ingreso el password
		
		$usuario->Edit('password', md5( $password ) );

		if(self::Write($usuario) )
		{ 
			#Cargar permisos 
			if( is_array($permisos) )
				foreach($permisos as $index => $permiso->fields)
					{
					if(!$usuario->EditPermisos($permiso))
						$this->logs[] = "<br>Error en permiso usuario: '".$usuario->fields['username']."': ".$usuario->error;	
					}
			
			$usuario->PermisoALL();

			#End Cargar permisos
			$usuario->GuardarSecretario($secretario_de);
			$usuario->GuardarRevisado($usuarios_revisados);

			$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'],$usuario->fields['id_categoria_usuario']);
			$this->log .= '<br>Usuario "'.$usuario->fields['username'].'" ingresado con éxito, su password es: '.$password;
		}
		else
		{
			$this->log .= "<br>Error en usuario: '".$usuario->fields['username']."': ".$usuario->error;
		}
	}
	
	function Loaded()
  {
 		if($this->fields[$this->campo_id])
        return true;
    return false;
  }
 
   /*
    * Agregar Trabajos
    * Se recive un Array de varios Objetos,
    * y se ingresa cada objeto durante la iteracion
    */
  public function AgregarHoras($items = null)
  {
      if(!empty($items))
      {
 					foreach($items as $item)
              AgregarUsuario($item['hora']);
      }
 	}

  public function AgregarHora($hora = null)
  {
      /*
       * Validar FK
 			 */
      #Instancio Clases a usar en validación de FK
      $usuario    = new Usuario($this->sesion);
      $asunto     = new Asunto($this->sesion);
 			$moneda     = new Moneda($this->sesion);

      #Confirmo que el id_usuario exista
      if(!$usuario->Load($hora->fields['id_usuario']))
      {
 				$this->log .= '<br>Error ingreso hora: id_usuario "'.$hora->fields['id_usuario'].'" no existe';
        return false;
      }

 
			#Confirmo que el codigo_asunto exista
      if(!$asunto->LoadByCodigo($hora->fields['codigo_asunto']))
      {
          $this->log .= '<br>Error ingreso hora: codigo_asunto "'.$hora->fields['codigo_asunto'].'" no existe';
					return false;
      }

      #Confirmo que el codigo_actividad exista
 			$query = "SELECT count(*) FROM actividad WHERE codigo_actividad = '".addslashes($hora->fields['codigo_actividad'])."'";
      $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
      list($cantidad) = mysql_fetch_array($resp);
      if($cantidad == 0)
      {
          $this->log .= '<br>Error ingreso hora: codigo_actividad "'.$hora->fields['codigo_actividad'].'" no existe';
          return false;
      }

 
			#Confirmo que el id_moneda exista
      if(!$moneda->Load($hora->fields['id_moneda']))
      {
          $this->log .= '<br>Error ingreso hora: id_moneda "'.$hora->fields['id_moneda'].'" no existe';
          return false;
 			}

      /*
       * Registrar información
       */
      $hora->Write();
}



  //Por defecto retona verdadero (no chequea nada al escribir)
  //Esta funcion debe ser sustituida en la clase que hereda
	function Check()
  {
 		return true;
  }
	
	//La funcion Write chequea que el objeto se pueda escribir al llamar a la funcion Check()
	public function Write($objeto)
	{
		$objeto->error = "";

		if(!$this->Check())
			return false;
	
		if($objeto->loaded)
		{
			$query = "UPDATE ".$objeto->tabla." SET ";
			if($objeto->guardar_fecha)
				$query .= "fecha_modificacion=NOW(),";
			
			$c = 0;
			foreach ( $objeto->fields as $key => $val )
			{
				if( $objeto->changes[$key] )
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
			
			$query .= " WHERE ".$objeto->campo_id."='".$objeto->fields[$objeto->campo_id] . "'";
			if($do_update) //Solo en caso de que se haya modificado algún campo
			{
				$resp = mysql_query($query, $this->sesion->dbh);
				if (!$resp)
				{
					$this->logs[] = mysql_errno($this->sesion->dbh) . " " . mysql_error($this->sesion->dbh);
					return false;
				}
				return true;
			}
			else //Retorna true ya que si no quiere hacer update la función corrió bien
				return true;
	
			#Utiles::CrearLog($this->sesion, "reserva", $this->fields[$campo_id], "MODIFICAR","",$query);
		}
		else
		{
			$query = "INSERT INTO ".$objeto->tabla." SET ";
			if($objeto->guardar_fecha)
				$query .= "fecha_creacion=NOW(),";
			
			$c = 0;
			$error_string = "";
	
			foreach ( $objeto->fields as $key => $val )
			{
				$error_string .= " $key: $val , ";
				if( $objeto->changes[$key] )
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
			//echo "<br><br><br>Query = ".$query;
			$resp = mysql_query($query, $this->sesion->dbh);
			if (!$resp)
			{
				$this->logs[] = mysql_errno($this->sesion->dbh) . " <b>Error explicacion:</b> " . mysql_error($this->sesion->dbh). " <b>Datos:</b> " .$error_string;
				return false;
			}
			$objeto->fields[$objeto->campo_id] = mysql_insert_id($this->sesion->dbh);
			#Utiles::CrearLog($objeto->sesion, "reserva", $objeto->fields['id_reserva'], "INSERTAR","",$query);
		}
		return true;
	}

}

?>