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
require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
require_once Conf::ServerDir().'/../app/classes/Tarifa.php';

class Migracion
{
	var $sesion = null;
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
			$this->logs[] = "El asunto con c贸digo " . $asunto->fields["codigo_asunto"] . " ya existe.";
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
			$cliente_existente = new Cliente($this->sesion);
			$contrato_existente = new Contrato($this->sesion);
			/*
			if(!empty($asunto->fields['id_contrato']))
			{
				$contrato_existente->Load($asunto->fields['id_contrato']);
			}
			else if (!empty($asunto->fields['id_contrato_indep']))
			{
				$contrato_existente->Load($asunto->fields['id_contrato_indep']);
			}
			
			*/
			

			//CARGAR CONTRATO DEL CLIENTE
			if(!empty($asunto_existente->fields['id_contrato']))
			{
				$contrato_existente->Load($asunto_existente->fields['id_contrato']);
			}
			else if (!empty($asunto_existente->fields['id_contrato_indep']))
			{
				$contrato_existente->Load($asunto_existente->fields['id_contrato_indep']);
			}
			else
			{
				$contrato_cliente		= new Contrato($this->sesion);
				$cliente_existente->LoadByCodigoSecundario($cliente->fields['codigo_cliente_secundario']);
				$contrato_cliente->Load($cliente_existente->fields['id_contrato']);
				if($contrato_cliente->loaded())
				{
					if($asunto_existente->fields['id_contrato'] != $cliente->fields['id_contrato'])
						$contrato_existente->Load($asunto_existente->fields['id_contrato']);
					else if($asunto_existente->fields['id_contrato_indep'] > 0 && ($asunto_existente->fields['id_contrato_indep'] != $cliente->fields['id_contrato']))
						$contrato_existente->Load($asunto_existente->fields['id_contrato_indep']);

				}
				else
				{
					// esta opci髇 se debe usar para el caso que solo existe un contrato por cliente
					$query = "SELECT id_contrato FROM contrato WHERE codigo_cliente='".$cliente_existente->fields['codigo_cliente']."'";
					$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
					list($id_cliente_existente) = mysql_fetch_array($resp);
					$contrato_cliente		= new Contrato($this->sesion);
					$contrato_existente->Load($id_cliente_existente);
				}
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


			self::AgregarAsunto($asunto, $contrato, NULL, NULL, $cliente);
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
	
	public function Query2ArrayCobros($responseCobros)
	{
		$cobros = array();
		while ($cobro = mysql_fetch_assoc($responseCobros))
		{
			$cobros[] = array
			(
				'id_contrato' => $cobro['id_contrato'],
				'fecha_ini' => $cobro['fecha_ini'],
				'fecha_fin' => $cobro['fecha_fin']
			);
		}
		$this->GenerarCobros($cobros);
	}
	
	public function Query2Gastos($responseGastos)
	{
		$gastos = array();
		while($gasto_ = mysql_fetch_assoc($responseGastos))
		{
			$gasto = new Gasto($this->sesion);
			$gasto->guardar_fecha = false;

			foreach ($gasto_ as $key => $val)
			{
				$keys = explode('_FFF_', $key);
				$gasto->Edit($keys[1], $val);
			}

			if ($this->ValidarGasto($gasto))
			{
				$this->GenerarGasto($gasto);
			}
			else
			{
				echo "Error al generar el gasto";
			}
		}
	}

	function Query2ObjetoTarifa($response)
	{

		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$tarifa = new Tarifa($sesion);
			//$tarifa->guardar_fecha = false;
			$forzar_insert = true;

			foreach( $row as $key => $val )
			{
				$tarifa->Edit($key,$val);
			}

			if (!$this->Write($tarifa, $forzar_insert))
			{
				echo "Error al generar el tarifa<br/>\n";
				return false;
			}
			else
			{
				echo "<br>tarifa creada --> ";
				print_r($tarifa->fields);
			}

		}
	}

	function Query2ObjetoUsuarioTarifa($response)
	{

		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$usuario_tarifa = new UsuarioTarifa($sesion);
			$usuario_tarifa->guardar_fecha = false;
			$forzar_insert = true;

			foreach( $row as $key => $val )
			{
				$usuario_tarifa->Edit($key,$val);
			}

			if (!$this->Write($usuario_tarifa, $forzar_insert))
			{
				echo "Error al generar el usuario_tarifa<br/>\n";
				return false;
			}
			else
			{
				echo "<br>usuario_tarifa creada --> ";
				print_r($usuario_tarifa->fields);
			}

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
				$this->logs[] = 'El c贸digo ingresado ya existe para otro cliente';
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
					$this->logs[] = __('Cliente').' '.__('Guardado con exito').'<br>'.__('Contrato guardado con 茅xito');
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
	
	public function GuardarContrato($cliente = null, $contrato = null, $contrato_existente = null, $cobros_pendientes = array(), $documentos_legales = array(), $asunto_existente = array())
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
		//--> VER DESDE LINEA 157 de AGREGAR_ASUNTOS
		//--> NO olvidar unir script clientes con el de asuntos
		if(!empty($asunto_existente))
		{
			$asunto_existente->Edit('id_contrato',$contrato_existente->fields['id_contrato']);
			$asunto_existente->Edit('id_contrato_indep',$contrato_existente->fields['id_contrato']);
			if (!self::Write($asunto_existente))
			{
				$this->logs[] = $contrato_existente->error;
				return false;
			}
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
		#permisos. Array('ADM','PRO',etc). 'ALL' siempre se incluye autom谩ticamente.
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
			$this->logs[] = '<br>Error ingreso usuario #'.$usuario->fields['id_usuario'].': username "'.$usuario->fields['username'].'"ya exist铆a';
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
			$this->log .= '<br>Usuario "'.$usuario->fields['username'].'" ingresado con 茅xito, su password es: '.$password;
		}
		else
		{
			$this->log .= "<br>Error en usuario: '".$usuario->fields['username']."': ".$usuario->error;
		}
	}

   /*
    * Agregar Trabajos
    * Se recive un Array de varios Objetos,
    * y se ingresa cada objeto durante la iteracion
    */
	
    function Query2ObjetoHora($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$trabajo = new Trabajo($sesion);
			$trabajo->guardar_fecha = false;

	
			foreach( $row as $key => $val )
			{
				$trabajo->Edit($key,$val);
			}
			$this->AgregarHora($trabajo);
			//echo '<pre>';print_r($usuario->fields);echo '</pre>';
			//echo "<br><br>--------------------------------------------<br><br>";
		}
	}

	public function AgregarHora($hora = null)
	{
		/*
		* Validar FK
		*/

		#Instancio Clases a usar en validaci贸n de FK
		$usuario    = new Usuario($this->sesion);
		$asunto     = new Asunto($this->sesion);
		$moneda     = new Moneda($this->sesion);
		
		#Confirmo que el id_usuario exista
		$id_usuario = (int)$hora->fields['id_usuario'];
		$hora->Edit('id_usuario', $id_usuario);
		
		$codigo_asunto = substr($hora->fields['codigo_asunto'],0,4).'-0'.substr($hora->fields['codigo_asunto'],-3);
		$hora->Edit('codigo_asunto', $codigo_asunto);
		
		
		#Confirmo que el id_moneda exista
		$id_moneda=9;
		$query = "SELECT id_moneda FROM prm_moneda WHERE glosa_moneda like '".$hora->fields['id_moneda']."%'";
		$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id_moneda) = mysql_fetch_array($resp);
		if(!$id_moneda)
		{
			//$this->log .= '<br>Error ingreso hora: id_moneda "'.$hora->fields['id_moneda'].'" no existe';
			echo '<br>Error ingreso hora: id_moneda "'.$hora->fields['id_moneda'].'" no existe';
			//return false;
		}
		$hora->Edit('id_moneda', $id_moneda);
		
		/*
		* Registrar informaci髇
		*/
		if($this->Write($hora))
		{
		  echo "<br>trabajo ingresado correctamente";
		}
		else
		{
			echo "<br><b>trabajo NO FUE ingresado</b>";
			//$this->log .= '<br>Error ingreso hora: el trabajo no fue ingresado';
		}
	}

	//La funcion Write chequea que el objeto se pueda escribir al llamar a la funcion Check()
	public function Write($objeto, $forzar_insert = false)
	{
		$objeto->error = "";

		if(!$objeto->Check())
			return false;
	
		if($objeto->Loaded() and !$forzar_insert)
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
			if($do_update) //Solo en caso de que se haya modificado alg煤n campo
			{
				$resp = mysql_query($query, $this->sesion->dbh);
				if (!$resp)
				{
					echo "<b>Error explicacion:</b> " . mysql_error($this->sesion->dbh). "<br/>";
					echo "<b>Datos:</b> " .$error_string . "<br/>";
					echo "<b>Query:</b> " . $query."</br>";
					return false;
				}
				return true;
			}
			else //Retorna true ya que si no quiere hacer update la funci贸n corri贸 bien
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
			
			$resp = mysql_query($query, $this->sesion->dbh);
			if (!$resp)
			{
				
				echo "<b>Error explicacion:</b> " . mysql_error($this->sesion->dbh). "<br/>";
				echo "<b>Datos:</b> " .$error_string . "<br/>";
				echo "<b>Query:</b> " . $query."</br>";
				return false;
			}
			$objeto->fields[$objeto->campo_id] = mysql_insert_id($this->sesion->dbh);
		}
		return true;
	}

	public function TieneTrabajos($id_contrato, $fecha_ini, $fecha_fin)
	{
		$query = "SELECT COUNT(*)  FROM trabajo JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto  LEFT JOIN contrato ON asunto.id_contrato=contrato.id_contrato WHERE contrato.id_contrato='$id_contrato'  AND trabajo.fecha < '" . date("Y-m-d", $fecha_fin) . "' AND trabajo.fecha > '" . date("Y-m-d", $fecha_ini) . "'";
		$res_nro_trabajos = mysql_query($query, $this->sesion->dbh);
		if (!$nro_trabajos)
		{
			$this->logs[] = "Error: " . mysql_error($this->sesion->dbh);
			return false;
		}
		list($nro_trabajos) = mysql_fetch_array($res_nro_trabajos);
		if ($nro_trabajos == 0)
		{
			return true;
		}
		return false;
	}
	
	function GenerarCobroBase($id_contrato, $fecha_ini, $fecha_fin, $usuario_generador, $cobro = null, $incluye_gastos = true, $incluye_honorarios = true, $con_gastos = false, $solo_gastos = false)
	{
		//Variable cobro puede venir null o como objeto con o sin ID.
		
		$cobro_guardado = true;

		$incluye_gastos = empty($incluye_gastos) ? '0' : '1';
		$incluye_honorarios = empty($incluye_honorarios) ? '0' : '1';

		if (empty($cobro))
		{
			$cobro = new Cobro($this->sesion);
			echo "Generando cobro con datos, Contrato ID: " . $id_contrato . " Fecha Inicio: " . $fecha_ini . " Fecha Fin: " . $fecha_fin . "</br>";
	
			$contrato = new Contrato($this->sesion, null, null, 'contrato', 'id_contrato');
			if(!$contrato->Load($id_contrato))
			{
				echo "Contrato con ID " . $id_contrato . " no existe</br>";
				return false;
			}
	
			$contrato->EliminarBorrador($incluye_gastos, $incluye_honorarios);
	
			$moneda_base = Utiles::MonedaBase($this->sesion);
			$moneda = new Objeto($this->sesion, null, null, 'prm_moneda', 'id_moneda');
			$moneda->Load($contrato->fields['id_moneda']);
		
			$cobro->Edit('id_usuario', $usuario_generador);
			$cobro->Edit('codigo_cliente', $contrato->fields['codigo_cliente']);
			$cobro->Edit('id_contrato', $contrato->fields['id_contrato']);
			$cobro->Edit('id_moneda', $contrato->fields['id_moneda']);
			$cobro->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
			$cobro->Edit('forma_cobro', $contrato->fields['forma_cobro']);
		
			//Este es el monto fijo, pero si no se inclyen honorarios no va
			$monto = empty($monto) ? $contrato->fields['monto'] : $monto;
			if(empty($incluye_honorarios)) $monto = '0';
			$cobro->Edit('monto_contrato', $monto);
		
			$cobro->Edit('retainer_horas', $contrato->fields['retainer_horas']);
			
			//Opciones
			$cobro->Edit('id_carta' ,$contrato->fields['id_carta']);
			$cobro->Edit("opc_ver_modalidad", $contrato->fields['opc_ver_modalidad']);
			$cobro->Edit("opc_ver_profesional", $contrato->fields['opc_ver_profesional']);
			$cobro->Edit("opc_ver_gastos", $contrato->fields['opc_ver_gastos']);
			$cobro->Edit("opc_ver_morosidad", $contrato->fields['opc_ver_morosidad']);
			$cobro->Edit("opc_ver_resumen_cobro", $contrato->fields['opc_ver_resumen_cobro']);
			$cobro->Edit("opc_ver_descuento", $contrato->fields['opc_ver_descuento']);
			$cobro->Edit("opc_ver_tipo_cambio", $contrato->fields['opc_ver_tipo_cambio']);
			$cobro->Edit("opc_ver_solicitante", $contrato->fields['opc_ver_solicitante']);
			$cobro->Edit("opc_ver_numpag", $contrato->fields['opc_ver_numpag']);
			$cobro->Edit("opc_ver_carta", $contrato->fields['opc_ver_carta']);
			$cobro->Edit("opc_papel", $contrato->fields['opc_papel']);
			$cobro->Edit("opc_restar_retainer", $contrato->fields['opc_restar_retainer']);
			$cobro->Edit("opc_ver_detalle_retainer", $contrato->fields['opc_ver_detalle_retainer']);
			$cobro->Edit("opc_ver_valor_hh_flat_fee", $contrato->fields['opc_ver_valor_hh_flat_fee']);
	
			//Configuraci髇 moneda del cobro
			$moneda_cobro_configurada = $contrato->fields['opc_moneda_total'];
			
			//Si incluye solo gastos, utilizar la moneda configurada para ello
			if ($incluye_gastos && !$incluye_honorarios)
			{
				$moneda_cobro_configurada = $contrato->fields['opc_moneda_gastos'];
			}

			$cobro->Edit("opc_moneda_total", $moneda_cobro_configurada);

			$cobro->Edit("opc_ver_asuntos_separados", $contrato->fields['opc_ver_asuntos_separados']);
			$cobro->Edit("opc_ver_horas_trabajadas", $contrato->fields['opc_ver_horas_trabajadas']);
			$cobro->Edit("opc_ver_cobrable", $contrato->fields['opc_ver_cobrable']);

			// Guardamos datos de la moneda base
			$cobro->Edit('id_moneda_base', $moneda_base['id_moneda']);
			$cobro->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']);
			$cobro->Edit('etapa_cobro','4');
			$cobro->Edit('codigo_idioma', $contrato->fields['codigo_idioma'] != '' ? $contrato->fields['codigo_idioma'] : 'es');
			$cobro->Edit('id_proceso', $cobro->GeneraProceso());

			//Descuento
			$cobro->Edit("tipo_descuento", $contrato->fields['tipo_descuento']);
			$cobro->Edit("descuento",$contrato->fields['descuento']);
			$cobro->Edit("porcentaje_descuento",$contrato->fields['porcentaje_descuento']);
			$cobro->Edit("id_moneda_monto",$contrato->fields['id_moneda_monto']);

			if(!empty($fecha_ini) and $fecha_ini != '0000-00-00')
			{
				$cobro->Edit('fecha_ini', $fecha_ini);
			}

			if(!empty($fecha_fin))
			{
				$cobro->Edit('fecha_fin', $fecha_fin);
			}
			
			if($solo_gastos)
			{
				$cobro->Edit('solo_gastos', 1);
			}
		
			$cobro->Edit("incluye_honorarios", $incluye_honorarios);
			$cobro->Edit("incluye_gastos", $incluye_gastos);
			
			if (!$this->Write($cobro))
			{
				echo "Error al guardar el cobro</br>";
				$cobro_guardado = false;
			}

			echo "Cobro " . $cobro->fields['id_cobro'] . " generado</br>";
			
			$contrato->AddCobroAsuntos($cobro->fields['id_cobro']);
		}

		if($cobro_guardado)
		{
			//Moneda cobro
			$cobro_moneda = new CobroMoneda($this->sesion);
			$cobro_moneda->ActualizarTipoCambioCobro($cobro->fields['id_cobro']);
	
			//Gastos
			if(!empty($incluye_gastos))
			{
				if( $solo_gastos == true )
				{
					$where = '(cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)';
				}
				else
				{
					$where = '1';
				}
	
				$query_gastos = "SELECT cta_corriente.*
					FROM cta_corriente
						LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto OR cta_corriente.codigo_asunto IS NULL
					WHERE $where
						AND (cta_corriente.id_cobro IS NULL)
						AND cta_corriente.incluir_en_cobro = 'SI'
						AND cta_corriente.cobrable = 1 
						AND cta_corriente.codigo_cliente = '".$cobro->fields['codigo_cliente']."'
						AND (asunto.id_contrato = '".$cobro->fields['id_contrato']."')
						AND cta_corriente.fecha <= '$fecha_fin'";
				$lista_gastos = new ListaGastos($this->sesion, null, $query_gastos);
				for($v=0; $v<$lista_gastos->num; $v++)
				{
					$gasto = $lista_gastos->Get($v);
					$cta_gastos = new Objeto($this->sesion, null, null, 'cta_corriente', 'id_movimiento');
					if($cta_gastos->Load($gasto->fields['id_movimiento']))
					{
						$cta_gastos->Edit('id_cobro', $cobro->fields['id_cobro']);
						$this->Write($cta_gastos);
					}
				}
			}
	
			//Trabajos
			if (!empty($incluye_honorarios) and !$solo_gastos)
			{
				echo "Asociando trabajos al cobro " . $cobro->fields['id_cobro'] . "</br>";
				$where_up = '1';
				if(empty($fecha_ini) or $fecha_ini == '0000-00-00')
				{
					$where_up .= " AND fecha <= '$fecha_fin' ";
				}
				else
				{
					$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
				}
	
				$query = "SELECT *
					FROM trabajo
						JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
					WHERE " . $where_up . "
						AND contrato.id_contrato = '" . $cobro->fields['id_contrato'] . "'
						AND cobro.estado IS NULL
						AND trabajo.id_cobro IS NULL";

				$lista_trabajos = new ListaTrabajos($this->sesion, null, $query);
				echo $lista_trabajos->num . " trabajos encontrados</br>";
				for($x=0; $x<$lista_trabajos->num; $x++)
				{
					$trabajo = $lista_trabajos->Get($x);
					$emitir_trabajo = new Objeto($this->sesion, null, null, 'trabajo', 'id_trabajo');
					$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
					$emitir_trabajo->Edit('id_cobro', $cobro->fields['id_cobro']);
					if (!$this->Write($emitir_trabajo))
					{
						echo "Error al modificar trabajo " . $trabajo->fields['id_trabajo'] . "</br>";
						continue;
					}
					echo "Trabajo " . $emitir_trabajo->fields['id_trabajo'] . " asociado al cobro " . $cobro->fields['id_cobro'] . "</br>";
				}
				
				
				$emitir_tramite = new Objeto($this->sesion, null, null, 'tramite', 'id_tramite');
				$where_up = '1';
				if($fecha_ini == '' || $fecha_ini == '0000-00-00')
				{
					$where_up .= " AND fecha <= '$fecha_fin' ";
				}
				else
				{
					$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
				}
				$query_tramites = "SELECT *
					FROM tramite 
						JOIN asunto ON tramite.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
					WHERE $where_up
						AND contrato.id_contrato = '".$cobro->fields['id_contrato']."'
						AND cobro.estado IS NULL";
				$lista_tramites = new ListaTrabajos($this->sesion, null, $query_tramites);
				for($y=0; $y<$lista_tramites->num; $y++)
				{
					$tramite = $lista_tramites->Get($y);
					$emitir_tramite->Load($tramite->fields['id_tramite']);
					$emitir_tramite->Edit('id_cobro', $cobro->fields['id_cobro']);
					$this->Write($emitir_tramite);
				}
			}
			
			//Se ingresa la anotaci髇 en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha',date('Y-m-d H:i:s'));
			$his->Edit('comentario',__('COBRO CREADO'));
			$his->Edit('id_usuario',$usuario_generador);
			$his->Edit('id_cobro',$cobro->fields['id_cobro']);
			$this->Write($his);
		}

		return $cobro->fields['id_cobro'];
	}

	public function ObtenerIDsCobroDeTrabajos()
	{
		$ids_cobro = array();
		$query = "SELECT id_cobro FROM trabajo WHERE id_cobro IS NOT NULL AND id_cobro NOT IN (SELECT id_cobro FROM cobro) GROUP BY id_cobro ORDER BY id_cobro";
		$res_ids_cobro = mysql_query($query, $this->sesion->dbh);
		while(list($id_cobro) = mysql_fetch_array($res_ids_cobro))
		{
			$ids_cobro[] = $id_cobro;
		}
		return $ids_cobro;
	}

	public function ObtenerTrabajosConIDCobro($id_cobro)
	{
		$trabajos = array();

		if (empty($id_cobro))
		{
			return $trabajos;
		}

		$query = "SELECT * FROM trabajo WHERE id_cobro = " . $id_cobro ;
		$lista_trabajos = new ListaTrabajos($this->sesion, null, $query);
		for($x=0; $x<$lista_trabajos->num; $x++)
		{
			$trabajos[] = $lista_trabajos->Get($x);
		}
		
		return $trabajos;
	}
	
	public function PertenecenAlContrato($trabajos)
	{
		$trabajo = $trabajos[0];
		$asunto = new Asunto($this->sesion);
		if (empty($trabajo->fields['codigo_asunto']))
		{
			echo "El trabajo " . $trabajo->fields['id_trabajo'] . " no contiene codigo asunto</br>";
			return array("error" => true, "contrato" => null);
		}
		$asunto->LoadByCodigo($trabajo->fields['codigo_asunto']);
		if (!$asunto->Loaded())
		{
			echo "Para el trabajo " . $trabajo->fields['id_trabajo'] . " no exite el asunto con codigo " . $trabajo->fields['codigo_asunto'] . "</br>";
			return array("error" => true, "contrato" => null);
		}
		return array("error" => false, "contrato" => $asunto->fields['id_contrato']);
	}
	
	public function ObtenerFechasInicioFin($id_cobro)
	{
		$query = "SELECT MAX(fecha), MIN(fecha) FROM trabajo WHERE id_cobro = " . $id_cobro; 
		list($fecha_ini, $fecha_fin) = mysql_fetch_array(mysql_query($query, $this->sesion->dbh));
		return array("inicio" => $fecha_ini, "fin" => $fecha_fin);
	}

	function GenerarCobrosBase()
	{
		//Buscar todos los trabajos que tengan ID cobro y que el ID cobro no exista en la tabla cobro
		$lista_ids_cobros = $this->ObtenerIDsCobroDeTrabajos();
		foreach ($lista_ids_cobros as $id_cobro)
		{
			$trabajos = $this->ObtenerTrabajosConIDCobro($id_cobro);
			//Comprueba que todos los trabajos pertenescan al mismo contrato
			$mismo_contrato = $this->PertenecenAlContrato($trabajos);
			if ($mismo_contrato['error']) continue;
			$id_contrato = $mismo_contrato['contrato'];
			$fechas = $this->ObtenerFechasInicioFin($id_cobro);
			$this->GenerarCobroBase($id_contrato, $fechas['inicio'], $fechas['fin'], $usuario_generador = 1, $cobro = null, $incluye_gastos = false, $incluye_honorarios = false);
		}
	}

	public function GenerarCobros($datos = array())
	{
		//Genera los cobros con los trabajos que tienen ID cobro
		$this->GenerarCobrosBase();
		
		if (!is_array($datos))
		{
			echo "Parametros incorrectos</br>";
			return false;
		}

		foreach ($datos as $indice => $dato)
		{
			if (empty($dato['id_contrato']) or empty($dato['fecha_ini']) or empty($dato['fecha_fin']))//empty($dato['cobro']) or //
			{
				echo "Si desea generar cobros entre fechas debe ingresar el contrato y las fechas, para la fila " . $indice . "</br>";
				continue;
			}
			$this->GenerarCobroBase($dato['id_contrato'], $dato['fecha_ini'], $dato['fecha_fin'], $usuario_generador = 1);
		}
	}

	public function GenerarPago($pago)
	{
		$ingreso = new Gasto($this->sesion);
		$ingreso->Load($pago->fields['id_movimiento']);

		$ingreso->Edit('fecha', $pago->fields['fecha_pago'] ? Utiles::fecha2sql($pago->fields['fecha_pago']) : "NULL");
		$ingreso->Edit("id_usuario", $pago->fields['id_usuario']);
		$ingreso->Edit("descripcion", $pago->fields['descripcion']);
		$ingreso->Edit("id_moneda", $pago->fields['id_moneda'] ? $pago->fields['id_moneda'] : $id_moneda);
		$ingreso->Edit("codigo_cliente", $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : "NULL");
		$ingreso->Edit("codigo_asunto", $pago->fields['codigo_asunto'] ? $pago->fields['codigo_asunto'] : "NULL");
		$ingreso->Edit("id_usuario_orden", $pago->fields['id_usuario_orden']);
		if(( method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsaMontoCobrable')) or (method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable()) && $pago->fields['monto_cobrable'] > 0)
		{
			$ingreso->Edit('ingreso',$pago->fields['monto_pago'] ? $pago->fields['monto_pago'] : $monto_cobrable );
		}
		else
		{
			$ingreso->Edit('ingreso', $pago->fields['monto_pago'] ? $pago->fields['monto_pago'] : '0');
		}
		if ((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsaMontoCobrable')) or (method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable()))
		{
			$ingreso->Edit('monto_cobrable', $pago->fields['monto_cobrable'] ? $pago->fields['monto_cobrable'] : $pago->fields['ingreso']);
		}
		else
		{
			$ingreso->Edit('monto_cobrable', $pago->fields['ingreso']);
		}
		$ingreso->Edit("documento_pago", $pago->fields['documento_pago'] ? $pago->fields['documento_pago'] : "NULL");
		if(!$this->Write($ingreso))
		{
			return false;
		}
		return $ingreso->fields['id_movimiento'];
	}

	public function GenerarGasto($gasto_generar, $pagado = false)
	{
		$gasto = new Gasto($this->sesion);
		
		if (!preg_match("/^[[:digit:]]+$/", $gasto_generar->fields['id_movimiento']) or $gasto_generar->fields['id_movimiento'] == 0)
		{
			$gasto_generar->Edit('id_movimiento', '999' . sprintf("%04d", (int)$gasto_generar->fields['id_movimiento']));
			echo "ID cambiado " . $gasto_generar->fields['id_movimiento'] . " al gasto<br/>\n";
		}
		
		$gasto->Load($gasto_generar->fields['id_movimiento']);

		if (!$gasto->Loaded())
		{
			echo "---Generando gasto---<br/>\n";
			$gasto->Edit("id_movimiento", $gasto_generar->fields['id_movimiento']);
			$forzar_insert = true;
		}
		else
		{
			echo "---Editando gasto ID " . $gasto->fields['id_movimiento'] . "---<br/>\n";
		}

		$cambio_asunto = $gasto_generar->fields['codigo_asunto'] != $gasto->fields['codigo_asunto'];

		if($gasto_generar->fields["cobrable"] == 1)
		{
			$gasto->Edit("cobrable", "1");
		}
		else
		{
			if ((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarGastosCobrable')) or ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable()))
			{
				$gasto->Edit("cobrable", "0");
			}
			else
			{	
				$gasto->Edit("cobrable", "1");
			}
		}

		if($gasto_generar->fields['con_impuesto'])
		{
			$gasto->Edit("con_impuesto", $gasto_generar->fields['con_impuesto']);
		}
		else
		{
			if ((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'UsarGastosConSinImpuesto')) or ( method_exists('Conf','UsarGastosConSinImpuesto') && Conf::UsarGastosConSinImpuesto()))
			{
				$gasto->Edit("con_impuesto", "NO");
			}
			else
			{
				$gasto->Edit("con_impuesto", "SI");
			}
		}

		if(!empty($gasto_generar->fields['ingreso']))
		{
			$monto = str_replace(',', '.', $gasto_generar->fields['ingreso']);
			$gasto->Edit("ingreso", $monto);
			$gasto->Edit("monto_cobrable", $monto);
		}

		if(!empty($gasto_generar->fields['egreso']))
		{
			$monto = str_replace(',', '.', $gasto_generar->fields['egreso']);
			$monto_cobrable = str_replace(',', '.', $gasto_generar->fields['monto_cobrable']);

			if ((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion, 'UsaMontoCobrable')) or (method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable()))
			{				
				if($monto <= 0)
				{
					$gasto->Edit("egreso", $monto_cobrable);
				}
				else
				{
					$gasto->Edit("egreso", $monto);
				}

				if($monto_cobrable  >= 0)
				{
					$gasto->Edit("monto_cobrable", $monto_cobrable);
				}
				else
				{
					$gasto->Edit("monto_cobrable", $monto);
				}
			}
			else
			{
				$gasto->Edit("egreso", $monto);
				$gasto->Edit("monto_cobrable", $monto);
			}
		}

		$gasto->Edit("fecha", Utiles::fecha2sql($gasto_generar->fields['fecha']));
		$gasto->Edit("id_usuario", $gasto_generar->fields['id_usuario']);
		$gasto->Edit("descripcion", $gasto_generar->fields['descripcion']);
		$gasto->Edit("id_moneda", $gasto_generar->fields['id_moneda']);
		$gasto->Edit("codigo_cliente",$gasto_generar->fields['codigo_cliente'] ? $gasto_generar->fields['codigo_cliente'] : "NULL");
		$gasto->Edit("codigo_asunto", $gasto_generar->fields['codigo_asunto'] ? $gasto_generar->fields['codigo_asunto'] : "NULL");
		$gasto->Edit("id_usuario_orden", $gasto_generar->fields['id_usuario_orden'] ? $gasto_generar->fields['id_usuario_orden'] : "NULL");
		$gasto->Edit("id_cta_corriente_tipo", $gasto_generar->fields['id_cta_corriente_tipo'] ? $gasto_generar->fields['id_cta_corriente_tipo'] : "NULL");
		$gasto->Edit("numero_documento",$gasto_generar->fields['numero_documento'] ? $gasto_generar->fields['numero_documento'] : "NULL");
		$gasto->Edit("numero_ot", $gasto_generar->fields['numero_ot'] ? $gasto_generar->fields['numero_ot'] : "NULL");

		if($pagado and !empty($gasto_generar->fields['egreso']))
		{
			$this->GenerarPago($gasto_generar);
		}
		else
		{
			$gasto->Edit('id_movimiento_pago', NULL);
		}

		if($cambio_asunto)
		{
			$gasto->Edit('id_cobro', 'NULL');
		}

		$gasto->Edit('id_proveedor', $gasto_generar->fields['id_proveedor'] ? $gasto_generar->fields['id_proveedor'] : "NULL");

		if (!$this->Write($gasto, $forzar_insert))
		{
			echo "Error al generar el gasto<br/>\n";
			return false;
		}
		
		echo "Gasto " . $gasto->fields['id_movimiento'] . " generado<br/>\n";
	}

	public function ValidarGasto($gasto)
	{
		$valido = true;
		if (!$this->ExisteCodigo($gasto->fields['codigo_asunto'], "asunto", "codigo_asunto"))
		{
			echo "No existe el codigo asunto " . $gasto->fields['codigo_asunto'] . " para el gasto " . $gasto->fields['id_movimiento'] . "<br/>\n";
			$valido = false;
		}
		if (!$this->ExisteCodigo($gasto->fields['id_usuario'], "usuario", "id_usuario"))
		{
			echo "No existe el usuario ID " . $gasto->fields['id_usuario'] . " para el gasto " . $gasto->fields['id_movimiento'] . "<br/>\n";
			$valido = false;
		}
		return $valido;
	}
	
	public function ExisteCodigo($codigo_asunto, $tabla, $campo)
	{
		$query = "SELECT COUNT(*) FROM " . $tabla . " WHERE " . $campo . " = " . $codigo_asunto;
		list($existe) = mysql_fetch_array(mysql_query($query, $this->sesion->dbh));
		if (empty($existe))
		{
			return false;
		}
		return true;
	}

	public function ExistenCodigo($lista_objetos, $tabla, $campo, $string = true)
	{
		$codigos_asunto = array();
		foreach ($lista_objetos as $objeto)
		{
			$codigos_asunto[] = (!$string) ? (int)$objeto->fields[$campo] : $objeto->fields[$campo];
		}
		$codigos_asunto = array_unique($codigos_asunto);

		if ($string)
		{
			$select = "(SELECT 'COD' " . $campo . " UNION SELECT '" . implode("' UNION SELECT '", $codigos_asunto) . "') AS COD";
		}
		else
		{
			$select = "(SELECT 0 " . $campo ." UNION SELECT " . implode(" UNION SELECT ", $codigos_asunto) . ") AS COD";
		}
		$campo_falso = ($string) ? "'COD'" : "0";

		$query = "SELECT
				" . $campo . "
			FROM
				" . $select . "
			WHERE
				" . $campo . " NOT IN (SELECT DISTINCT " . $campo . " FROM " . $tabla . ") AND
				" . $campo . " != " . $campo_falso;

		$response_codigos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$codigos_asunto = array();
		while (list($codigo_asunto) = mysql_fetch_array($response_codigos))
		{
			$codigos_asunto[] = $codigo_asunto;
		}
		
		$objetos_sin_asunto = array();

		if (!empty($codigos_asunto))
		{
			foreach ($codigos_asunto as $codigo_asunto)
			{
				foreach ($lista_objetos as $indice => $objeto)
				{
					if ($objeto->fields[$campo] == $codigo_asunto)
					{
						$objetos_sin_asunto[] = $indice;
					}
				}
			}

			echo "Los siguientes " . $campo . " no existen: " . implode(", ", $codigos_asunto) . "</br>";
		}

		return $objetos_sin_asunto;
	}

	public function ExtraerObjetoLista($lista, $lista_extraer)
	{
		if (!empty($lista_extraer))
		{
			foreach($lista_extraer as $extraer)
			{
				unset($lista[$extraer]);
			}
			echo "Eliminados de la lista objetos con indice: " . implode(", ", $lista_extraer) . "</br>";
		}
		return $lista;
	}
}

?>