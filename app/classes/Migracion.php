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
	
	public function AgregarAsunto($asunto_generar,  $contrato_generar = null, $cobros_pendientes = array(), $documentos_legales = array())
	{
		if (empty($asunto_generar))
		{
			echo "Faltan parametro asunto\n";
			return false;
		}
		
		$asunto = new Asunto($this->sesion);
		$asunto->guardar_fecha = false;
		
		if (!$asunto->Load($asunto_generar->fields['id_asunto']))
		{
			$asunto->LoadByCodigo($asunto_generar->fields['codigo_asunto']);
		}

		if ($asunto->Loaded())
		{
			echo "--Editando asunto ID " . $asunto->fields["id_asunto"] . "\n";
		}
		else
		{
			echo "--Ingresando asunto\n";
		}

		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($asunto_generar->fields['codigo_cliente']);
		if (!$cliente->Loaded())
		{
			echo "No existe el cliente con codigo (" . $asunto_generar->fields['codigo_cliente'] . ")";
			return false;
		}
		
		$asunto->Edit("id_contrato", $cliente->fields['id_contrato']);

		if (empty($asunto_generar->fields["codigo_asunto"]))
		{
			if ((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CodigoEspecialGastos') ) || ( method_exists('Conf','CodigoEspecialGastos') && Conf::CodigoEspecialGastos()))
			{
				$asunto_generar->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto->fields["codigo_cliente"], $asunto_generar->fields["glosa_asunto"]);
			}
			else
			{
				$asunto_generar->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto_generar->fields["codigo_cliente"]);
			}
			echo "Asignado codigo asunto " . $asunto_generar->fields["codigo_asunto"];
		}

		$asunto->Edit("id_usuario",  empty($asunto_generar->fields['id_usuario']) ? "NULL" : $asunto_generar->fields['id_usuario']);
		$asunto->Edit("codigo_asunto", $asunto_generar->fields["codigo_asunto"]);

		if (empty($cliente->fields['codigo_cliente_secundario']))
		{
			$cliente->Edit('codigo_cliente_secundario', $cliente->CodigoACodigoSecundario($asunto->fields["codigo_cliente"]));
		}

		if (((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
		{
			$asunto->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . substr(strtoupper($asunto_generar->fields['codigo_asunto_secundario']), -4));
		}
		else
		{
			if(!empty($asunto_generar->fields["codigo_asunto_secundario"]))
			{
				$asunto->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . strtoupper($asunto_generar->fields["codigo_asunto_secundario"]));
			}
			else
			{
				$asunto->Edit("codigo_asunto_secundario", $asunto_generar->fields['codigo_asunto']);
			}
		}

		$asunto->Edit("actividades_obligatorias", $asunto_generar->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto->Edit("mensual",$asunto_generar->fields['mensual'] ? "SI" : "NO");

		$asunto->Edit("id_cobrador", empty($asunto_generar->fields['id_cobrador']) ? "NULL" : $asunto_generar->fields['id_cobrador']);

		$asunto->Edit("glosa_asunto", $asunto_generar->fields['glosa_asunto']);
		$asunto->Edit("codigo_cliente", $asunto_generar->fields['codigo_cliente']);
		$asunto->Edit("id_tipo_asunto", empty($asunto_generar->fields['id_tipo_asunto']) ? 1 : $asunto_generar->fields['id_tipo_asunto']);
		$asunto->Edit("id_area_proyecto", empty($asunto_generar->fields['id_area_proyecto']) ? 1 : $asunto_generar->fields['id_area_proyecto']);

		$asunto->Edit("id_moneda", $asunto_generar->fields['id_moneda']);
		$asunto->Edit("id_idioma", empty($asunto_generar->fields['id_idioma']) ? "NULL" : $asunto_generar->fields['id_idioma']);

		$asunto->Edit("descripcion_asunto", $asunto_generar->fields['descripcion_asunto']);
		$asunto->Edit("id_encargado", empty($asunto_generar->fields['id_encargado']) ? "NULL" : $asunto_generar->fields['id_encargado']);
		$asunto->Edit("contacto", $asunto_generar->fields['contacto']);
		$asunto->Edit("fono_contacto", $asunto_generar->fields['fono_contacto']);
		$asunto->Edit("email_contacto", $asunto_generar->fields['email_contacto']);
		$asunto->Edit("actividades_obligatorias", $asunto_generar->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto->Edit("activo", $asunto_generar->fields['activo']);
		$asunto->Edit("cobrable", $asunto_generar->fields['cobrable']);
		$asunto->Edit("mensual", $asunto_generar->fields['mensual'] ? "SI" : "NO");
		$asunto->Edit("alerta_hh", $asunto_generar->fields['alerta_hh']);
		$asunto->Edit("alerta_monto", $asunto_generar->fields['alerta_monto']);
		$asunto->Edit("limite_hh", $asunto_generar->fields['limite_hh']);
		$asunto->Edit("limite_monto", $asunto_generar->fields['limite_monto']);
		$asunto->Edit("giro", $asunto_generar->fields['giro']);
		$asunto->Edit("razon_social", $asunto_generar->fields['razon_social']);

		$asunto->Edit("fecha_creacion", $asunto_generar->fields['fecha_creacion']);

		if (!$this->ValidarAsunto($asunto))
		{
			echo "Error al guardar el asunto\n";
			return false;
		}
		
		if (!$this->Write($asunto))
		{
			echo "Error al guardar el asunto\n";
			return false;
		}

		echo "Asunto ID " . $asunto->fields['id_asunto'] . " guardado\n";

		if ($contrato_generar)
		{
			$contrato = new Contrato($this->sesion);
			$contrato->guardar_fecha = false;
			$contrato->Load($asunto->fields['id_contrato_indep']);
			
			if ($contrato->Loaded())
			{
				echo "Editando contrato ID " . $contrato->fields['id_contrato'] . "\n";
			}
			else
			{
				unset($contrato_generar->fields['id_contrato']);
				echo "Ingresando contrato para el asunto\n";
			}

			if ($this->GuardarContrato($contrato, $contrato_generar, $cliente, $cobros_pendientes, $documentos_legales))
			{
				$asunto->Edit("id_contrato", $contrato->fields['id_contrato']);
				$asunto->Edit("id_contrato_indep", $contrato->fields['id_contrato']);
				if ($this->Write($asunto))
				{
					echo "Guardado asunto con contrato independiente ID " . $asunto->fields['id_contrato_indep'] . "\n";
				}
				else
				{
					echo "Error al guardar asunto con contrato independiente\n";
				}
			}
		}
	}
	
	public function ValidarAsunto($asunto)
	{
		if (!$this->ValidarCodigo($asunto, "id_moneda", "prm_moneda"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($asunto, "id_usuario", "usuario"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($asunto, "id_encargado", "usuario", false, "id_usuario"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($asunto, "id_cobrador", "usuario", false, "id_usuario"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($asunto, "codigo_cliente", "cliente", false, "codigo_cliente", true))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($asunto, "id_tipo_asunto", "prm_tipo_proyecto", true, "id_tipo_proyecto"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($asunto, "id_area_proyecto", "prm_area_proyecto", true))
		{
			return false;
		}
		
		return $this->ValidarCodigo($asunto, "id_idioma", "prm_idioma");
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
		while ($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$cliente = new Cliente($this->sesion);
			$contrato = new Contrato($this->sesion);
			
			$row['cliente_FFF_glosa_cliente'] = addslashes($row['cliente_FFF_glosa_cliente']);
			$row['contrato_FFF_factura_razon_social'] = addslashes($row['contrato_FFF_factura_razon_social']);
			$row['cliente_FFF_rsocial'] = addslashes($row['cliente_FFF_rsocial']);
			
			foreach ($row as $key => $val)
			{
				$keys = explode('_FFF_', $key);
				if ($keys[0] == 'cliente')
				{
					$cliente->Edit($keys[1], $val);
				}
				else if ($keys[0] == 'contrato')
				{
					$contrato->Edit($keys[1], $val);
				}
			}

			$this->AgregarCliente($cliente, $contrato);
		}
	}
	
	function DefinirGruposPRC()
	{
		$codigos_de_cliente = array('0707','0871','0853','1292','1308','1437','0897','0825','1087','1368','0852',
																'0744','0759','1448','1363','0947','0605','0763','0663','0878','1055','1243',
																'1347','1404','1155','1118','0917','1202','0336','1042','1078','0140','0352',
																'0710','1177','0002','1450','1415','1360','1282','0471','0823','0184','0756',
																'0681','0584');
		$grupos_que_corresponden = array( 'GRUPO BACKUS','GRUPO BACKUS','GRUPO VALE','GRUPO VALE','GRUPO VALE',
																			'GRUPO SCOTIA','GRUPO SCOTIA','GRUPO SCOTIA','GRUPO SCOTIA','GRUPO SCOTIA',
																			'GRUPO AMOV','GRUPO AMOV','GRUPO AMOV','GRUPO CHINALCO','GRUPO CHINALCO',
																			'GRUPO CHINALCO','GRUPO AC CAPITALES','GRUPO AC CAPITALES','GRUPO AC CAPITALES',
																			'GRUPO GOLD','GRUPO GOLD','GRUPO GOLD','GRUPO GOLD','GRUPO GOLD','GRUPO WWG',
																			'GRUPO WWG','GRUPO WWG','GRUPO BBVA','GRUPO BBVA','GRUPO BBVA','GRUPO BBVA',
																			'GRUPO ENDESA','GRUPO ENDESA','GRUPO ENDESA','GRUPO BREADT','GRUPO BREADT',
																			'FAMILIA SARFATY','FAMILIA SARFATY','GRUPO ILASA','GRUPO ILASA', 'GRUPO BNP',
																			'GRUPO BNP','GRUPO URÍA','GRUPO URÍA','GRUPO GOURMET','GRUPO GOURMET');

		foreach( $codigos_de_cliente as $key => $value )
			{
				$query = "UPDATE cliente SET id_grupo_cliente = 
														( SELECT id_grupo_cliente 
																FROM grupo_cliente 
															 WHERE glosa_grupo_cliente LIKE '%".$grupos_que_corresponden[$key]."%' )
									 WHERE codigo_cliente = '".$value."' ";
				mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			}
	}
	
	function Query2ObjetoAsunto($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$sesion = new Sesion();
			$asunto = new Asunto($this->sesion);
			$contrato = new Contrato($this->sesion);

			foreach ($row as $key => $val)
			{
				$keys = explode('_FFF_', $key);

				if ($keys[0] == 'asunto')
				{
					$asunto->Edit($keys[1], $val);
				}
				else if ($keys[0] == 'contrato')
				{
					$contrato->Edit($keys[1], $val);
				}
			}
			$this->AgregarAsunto($asunto, $contrato);
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
		}
	}
	
	public function Query2ObjetoCobro($responseCobros)
	{
		$cobros = array();
		while ($cobro_ = mysql_fetch_assoc($responseCobros))
		{
			$cobro = new Cobro($this->sesion);
			$cobro->guardar_fecha = false;
			foreach ($cobro_ as $key => $val)
			{
				$keys = explode('_FFF_', $key);
				//Transformar codigo asunto a ID contrato
				if ($keys[1] == "codigo_asunto")
				{
					$id_contrato = $this->ObtenerIDContratoSegunCodigoAsunto($val);
					if (empty($id_contrato))
					{
						echo "No se encontro el ID de contrato para el cobro " . $cobro->fields['id_cobro'] . "\n";
						continue 2;
					}
					$keys[1] = "id_contrato";
					$val = $id_contrato;
				}
				$cobro->Edit($keys[1], $val);
			}
			$this->GenerarCobroBase($cobro);
		}

		//Buscar trabajos sin ID de cobro y genera sus cobros
		//$this->GenerarCobrosBase();
	}

	public function Query2ObjetoGasto($responseGastos)
	{
		while($gasto_ = mysql_fetch_assoc($responseGastos))
		{
			$gasto = new Gasto($this->sesion);
			$gasto->guardar_fecha = false;

			foreach ($gasto_ as $key => $val)
			{
				$keys = explode('_FFF_', $key);
				$gasto->Edit($keys[1], $val);
			}

			$this->GenerarGasto($gasto);
		}
	}
	
	public function Query2ObjetoFactura($responseFacturas)
	{
		while($factura_ = mysql_fetch_assoc($responseFacturas))
		{
			$factura = new Factura($this->sesion);
			$factura->guardar_fecha = false;

			foreach ($factura_ as $key => $val)
			{
				$keys = explode('_FFF_', $key);
				$factura->Edit($keys[1], $val);
			}

			$this->GenerarFactura($factura);
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
				echo "Error al generar el tarifa\n";
				return false;
			}
			else
			{
				echo "Tarifa creada\n";
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
				echo "Error al generar el usuario_tarifa\n";
				return false;
			}
			else
			{
				echo "Usuario_tarifa creada\n";
				print_r($usuario_tarifa->fields);
			}

		}
	}
	
	public function ObtenerIDContratoSegunCodigoAsunto($codigo_asunto)
	{
		if (empty($codigo_asunto))
		{
			echo "El codigo asunto esta vacio\n";
			return false;
		}
		
		$asunto = new Asunto($this->sesion);
		$asunto->LoadByCodigo($codigo_asunto);
		if ($asunto->Loaded())
		{
			return $asunto->fields['id_contrato'];
		}
		echo "El codigo asunto " . $codigo_asunto . " no existe\n";
		return false;
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
	
	public function ExisteCodigoSecundario($id_cliente, $codigo_cliente_secundario)
	{
		if (empty($codigo_cliente_secundario))
		{
			echo "Ingrese codigo secundario para verificar si existe\n";
			return true;
		}

		$query_codigos = "SELECT codigo_cliente_secundario FROM cliente";
		if (!empty($id_cliente))
		{
			$query_codigos .= " WHERE id_cliente != '" . $id_cliente . "'";
		}
		
		$resp_codigos = mysql_query($query_codigos, $this->sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $this->sesion->dbh);
		while(list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos))
		{
			if($codigo_cliente_secundario == $codigo_cliente_secundario_temp)
			{ 
				echo "El código ingresado ya existe para otro cliente\n";
				return true;
			}
		}
		
		return false;
	}

	function AgregarCliente($cliente_generar, $contrato_generar, $cobros_pendientes = array(), $documentos_legales = array(), $agregar_asuntos_defecto = false)
	{
		if (empty($cliente_generar) or empty($contrato_generar))
		{
			echo "Faltan parametro(s), cliente o contrato\n";
			return false;
		}

		$forzar_insert = false;

		$cliente = new Cliente($this->sesion);
		if (!$cliente->Load($cliente_generar->fields["id_cliente"]))
		{
			$cliente->LoadByCodigo($cliente_generar->fields["codigo_cliente"]);
		}

		if ($cliente->Loaded())
		{
			echo "Editando cliente ID " . $cliente->fields["id_cliente"] . "\n";
			if(empty($cliente->$fields['activo']))
			{
				$cliente->InactivarAsuntos();
			}
		}
		else
		{
			if (empty($cliente_generar->fields["id_cliente"]))
			{
				echo "Ingresando cliente\n";
			}
			else
			{
				echo "Ingresando cliente ID " . $cliente_generar->fields["id_cliente"] . "\n";
				$forzar_insert = true;
			}

			if (empty($cliente_generar->fields['codigo_cliente']))
			{
				$cliente_generar->fields['codigo_cliente'] = $cliente->AsignarCodigoCliente();
				echo "Cliente sin codigo, asignando nuevo codigo : " . $cliente_generar->fields['codigo_cliente']  . "\n";
			}
		}

		if(!empty($cliente_generar->fields["codigo_cliente_secundario"]) and $this->ExisteCodigoSecundario($cliente->fields['id_cliente'], $cliente_generar->fields["codigo_cliente_secundario"]))
		{
			return false;
		}

		if($this->GuardarCliente($cliente, $cliente_generar))
		{
			$contrato = new Contrato($this->sesion);
			$contrato->Load($cliente->fields['id_contrato']);
			if ($contrato->Loaded())
			{
				echo "Editando contrato ID " . $contrato->fields['id_contrato'] . " para el cliente ID " . $cliente->fields['id_cliente'] .  "\n";
			}
			else
			{
				unset($contrato_generar->fields['id_contrato']);
				echo "Ingresando contrato para el cliente ID " . $cliente->fields['id_cliente'] .  "\n";
			}

			if(!$this->GuardarContrato($contrato, $contrato_generar, $cliente, $cobros_pendientes, $documentos_legales))
			{
				return false;
			}

			$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);
			if($this->Write($cliente))
			{
				echo "Cliente y contrato guardados\n";
			}
			else
			{
				echo "Error al guardar el cliente y el contrato\n";
				return false;
			}
		}

		if ($agregar_asuntos_defecto)
		{
			if (((method_exists('Conf','GetConf') && Conf::GetConf($this->sesion,'AgregarAsuntosPorDefecto') != '') || (method_exists('Conf','AgregarAsuntosPorDefecto'))))
			{

				if (method_exists('Conf','GetConf'))
				{
					$asuntos = explode(';', Conf::GetConf($this->sesion,'AgregarAsuntosPorDefecto'));
				}
				else
				{
					$asuntos = Conf::AgregarAsuntosPorDefecto();
				}

				foreach ($asuntos as $glosa_asunto)
				{
					$asunto = new Asunto($this->sesion);
					$asunto->Edit('codigo_asunto', $asunto->AsignarCodigoAsunto($cliente->fields['codigo_cliente']));
					$asunto->Edit('codigo_asunto_secundario',$asunto->AsignarCodigoAsuntoSecundario($cliente->fields['codigo_cliente_secundario']));
					$asunto->Edit('glosa_asunto', $glosa_asunto);
					$asunto->Edit('codigo_cliente', $cliente->fields['codigo_cliente']);
					$asunto->Edit('id_contrato', $contrato->fields['id_contrato']);
					$asunto->Edit('id_usuario', $cliente->fields['id_usuario_encargado']);
					$asunto->Edit('contacto', $cliente->fields['nombre_contacto']);
					$asunto->Edit("fono_contacto", $cliente->fields['fono_contacto']);
					$asunto->Edit("email_contacto", $cliente->fields['mail_contacto']);
					$asunto->Edit("direccion_contacto", $cliente->fields['dir_calle'] . " " . $cliente->fields['dir_numero'] . " " . $cliente->fields['dir_comuna']);
					$asunto->Edit("id_encargado", $cliente->fields['id_usuario_encargado']);
					if(!$this->Write($asunto))
					{
						"El error al guardar el asunto\n";
						continue;
					}
				}
			}
		}
	}

	public function GuardarCliente($cliente, $cliente_generar)
	{
		$cliente->Edit("glosa_cliente", $cliente_generar->fields["glosa_cliente"]);
		$cliente->Edit("codigo_cliente", $cliente_generar->fields["codigo_cliente"]);
		$cliente->Edit("codigo_cliente_secundario",
			empty($cliente_generar->fields["codigo_cliente_secundario"]) ? "NULL" : $cliente->fields["codigo_cliente_secundario"]);
		$cliente->Edit("id_moneda", $cliente_generar->fields["id_moneda"]);
		$cliente->Edit("activo", $cliente_generar->fields["activo"] == 1 ? '1' : '0');
		$cliente->Edit("id_usuario_encargado", $cliente_generar->fields["id_usuario_encargado"]);

		if (!$this->ValidarCliente($cliente))
		{
			return false;
		}

		$cliente->Edit("id_grupo_cliente",
			empty($cliente_generar->fields["id_grupo_cliente"]) ? "NULL" : $cliente_generar->fields["id_grupo_cliente"]);

		if(!$this->Write($cliente))
		{
			echo "Error al guardar cliente\n";
			return false;
		}
		
		echo "Cliente ID " . $cliente->fields['id_cliente'] .  " guardado\n";
		return true;
	}
	
	public function ValidarCliente($cliente)
	{
		if (!$this->ValidarCodigo($cliente, "id_moneda", "prm_moneda", true))
		{
			return false;
		}

		if (!$this->ValidarCodigo($cliente, "id_grupo_cliente", "grupo_cliente"))
		{
			return false;
		}

		return $this->ValidarCodigo($cliente, "id_usuario", "usuario", false, "id_usuario_encargado");
	}
	
	public function ValidarContrato($contrato)
	{
		if (!$this->ValidarCodigo($contrato, "id_moneda", "prm_moneda", true))
		{
			return false;
		}

		if (!$this->ValidarCodigo($contrato, "codigo_cliente", "cliente", true, "codigo_cliente", true))
		{
			return false;
		}

		return $this->ValidarCodigo($contrato, "id_usuario", "usuario", false, "id_usuario_responsable");
	}
	
	public function GuardarContrato($contrato, $contrato_generar, $cliente, $cobros_pendientes = array(), $documentos_legales = array())
	{
		$moneda = new Moneda($this->sesion);
		$contrato->guardar_fecha = false;

		if($contrato_generar->fields["forma_cobro"] != 'TASA' && $contrato_generar->fields["monto"] == 0)
		{
			echo  __('Ud. a seleccionado forma de cobro:') . " " . $contrato_generar->fields["forma_cobro"] . " " . __('y no ha ingresado monto') . "\n";
			echo "Error al guardar contrato\n";
			return false;
		}
		else if($contrato_generar->fields["forma_cobro"] == 'TASA')
		{
			$contrato_generar->fields["monto"] = '0';
		}
	
		$contrato->Edit("activo", $contrato_generar->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato->Edit("usa_impuesto_separado", $contrato_generar->fields["impuesto_separado"] ? '1' : '0');
		$contrato->Edit("usa_impuesto_gastos", $contrato_generar->fields["impuesto_gastos"] ? '1' : '0');

		$contrato->Edit("glosa_contrato", $contrato_generar->fields["glosa_contrato"]);
		$contrato->Edit("codigo_cliente",
			empty($contrato_generar->fields["codigo_cliente"]) ? $cliente->fields['codigo_cliente'] : $contrato_generar->fields["codigo_cliente"]);
	
		$contrato->Edit("id_usuario_responsable", $contrato_generar->fields["id_usuario_responsable"]);
		$contrato->Edit("centro_costo", $contrato_generar->fields["centro_costo"]);
	
		$contrato->Edit("observaciones", $contrato_generar->fields["observaciones"]);


		if (method_exists('Conf','GetConf'))
		{
			if (Conf::GetConf($this->sesion,'TituloContacto'))
			{
				$contrato->Edit("titulo_contacto", $contrato_generar->fields["titulo_contacto"]);
				$contrato->Edit("contacto", $contrato_generar->fields["nombre_contacto"]);
				$contrato->Edit("apellido_contacto", $contrato_generar->fields["apellido_contacto"]);
			}
		}
		else if (method_exists('Conf','TituloContacto'))
		{
			if (Conf::TituloContacto())
			{
				$contrato->Edit("titulo_contacto", $contrato_generar->fields["titulo_contacto"]);
				$contrato->Edit("contacto", $contrato_generar->fields["nombre_contacto"]);
				$contrato->Edit("apellido_contacto",$contrato_generar->fields["apellido_contacto"]);
			}
		}

		$contrato->Edit("contacto", $contrato_generar->fields["contacto"]);
		$contrato->Edit("fono_contacto", $contrato_generar->fields["fono_contacto_contrato"]);
		$contrato->Edit("email_contacto",$contrato_generar->fields["email_contacto_contrato"]);
		$contrato->Edit("direccion_contacto", $contrato_generar->fields["direccion_contacto_contrato"]);
		$contrato->Edit("es_periodico", $contrato_generar->fields["es_periodico"]);
		$contrato->Edit("activo", $contrato_generar->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato->Edit("periodo_fecha_inicio", Utiles::fecha2sql($contrato_generar->fields["periodo_fecha_inicio"]));
		$contrato->Edit("periodo_repeticiones", $contrato_generar->fields["periodo_repeticiones"]);
		$contrato->Edit("periodo_intervalo", $contrato_generar->fields["periodo_intervalo"]);
		$contrato->Edit("periodo_unidad", $contrato_generar->fields["codigo_unidad"]);
		$contrato->Edit("monto", $contrato_generar->fields["monto"]);
		$contrato->Edit("id_moneda", $contrato_generar->fields["id_moneda"]);
		$contrato->Edit("forma_cobro", $contrato_generar->fields["forma_cobro"]);
		$contrato->Edit("fecha_inicio_cap", Utiles::fecha2sql($contrato_generar->fields["fecha_inicio_cap"]));
		$contrato->Edit("retainer_horas", $contrato_generar->fields["retainer_horas"]);
		$contrato->Edit("id_usuario_modificador", $this->sesion->usuario->fields['id_usuario']);
		$contrato->Edit("id_carta", $contrato_generar->fields["id_carta"] ? $contrato->fields["id_carta"] : 'NULL');
		$contrato->Edit("id_formato", $contrato_generar->fields["id_formato"]);
		$contrato->Edit("id_tarifa", $contrato_generar->fields["id_tarifa"] ? $contrato->fields["id_tarifa"] : 'NULL');
		$contrato->Edit("id_tramite_tarifa", $contrato_generar->fields["id_tramite_tarifa"] ? $contrato->fields["id_tramite_tarifa"] : 'NULL' );
		$contrato->Edit("id_moneda_tramite", 
			empty($contrato_generar->fields['id_moneda_tramite']) ? '1' : $contrato_generar->fields['id_moneda_tramite']);

		//Facturacion
		$contrato->Edit("rut", $contrato_generar->fields["factura_rut"]);
		$contrato->Edit("factura_razon_social", $contrato_generar->fields["factura_razon_social"]);
		$contrato->Edit("factura_giro", $contrato_generar->fields["factura_giro"]);
		$contrato->Edit("factura_direccion", $contrato_generar->fields["factura_direccion"]);
		$contrato->Edit("factura_telefono", $contrato_generar->fields["factura_telefono"]);
		$contrato->Edit("cod_factura_telefono", $contrato_generar->fields["cod_factura_telefono"]);

	
		#Opc contrato
		$contrato->Edit("opc_ver_modalidad", $contrato_generar->fields["opc_ver_modalidad"]);
		$contrato->Edit("opc_ver_profesional",$contrato_generar->fields["opc_ver_profesional"]);
		$contrato->Edit("opc_ver_gastos", $contrato_generar->fields["opc_ver_gastos"]);
		$contrato->Edit("opc_ver_morosidad", $contrato_generar->fields["opc_ver_morosidad"]);
		$contrato->Edit("opc_ver_descuento", $contrato_generar->fields["opc_ver_descuento"]);
		$contrato->Edit("opc_ver_tipo_cambio", $contrato_generar->fields["opc_ver_tipo_cambio"]);
		$contrato->Edit("opc_ver_numpag", $contrato_generar->fields["opc_ver_numpag"]);
		$contrato->Edit("opc_ver_resumen_cobro", $contrato_generar->fields["opc_ver_resumen_cobro"]);
		$contrato->Edit("opc_ver_carta", $contrato_generar->fields["opc_ver_carta"]);
		$contrato->Edit("opc_papel", $contrato_generar->fields["opc_papel"]);
		$contrato->Edit("opc_moneda_total", $contrato_generar->fields["opc_moneda_total"]);
		$contrato->Edit("opc_moneda_gastos", $contrato_generar->fields["opc_moneda_gastos"]);
		
		$contrato->Edit("opc_ver_solicitante", $contrato_generar->fields["opc_ver_solicitante"]);
	
		$contrato->Edit("opc_ver_asuntos_separados", $contrato_generar->fields["opc_ver_asuntos_separados"]);
		$contrato->Edit("opc_ver_horas_trabajadas", $contrato_generar->fields["opc_ver_horas_trabajadas"]);
		$contrato->Edit("opc_ver_cobrable", $contrato_generar->fields["opc_ver_cobrable"]);
	
		$contrato->Edit("codigo_idioma", $contrato_generar->fields["codigo_idioma"] != '' ? $contrato->fields["codigo_idioma"] : 'es');

		#descto
		$contrato->Edit("tipo_descuento", $contrato_generar->fields["tipo_descuento"]);
		if($contrato_generar->fields["PORCENTAJE"])
		{
			$contrato->Edit("porcentaje_descuento", !empty($contrato_generar->fields["porcentaje_descuento"]) ? $contrato_generar->fields["porcentaje_descuento"] : '0');
			$contrato->Edit("descuento", '0');
		}
		else
		{
			$contrato->Edit("descuento",
				empty($contrato_generar->fields["descuento"]) ? '0' : $contrato_generar->fields["descuento"]);
			$contrato->Edit("porcentaje_descuento",'0');
		}
		$contrato->Edit("id_moneda_monto", $contrato_generar->fields["id_moneda_monto"]);
		$contrato->Edit("alerta_hh", $contrato_generar->fields["alerta_hh"]);
		$contrato->Edit("alerta_monto", $contrato_generar->fields["alerta_monto"]);
		$contrato->Edit("limite_hh", $contrato_generar->fields["limite_hh"]);
		$contrato->Edit("limite_monto", $contrato_generar->fields["limite_monto"]);
		$contrato->Edit("separar_liquidaciones", $contrato_generar->fields["separar_liquidaciones"]);
		
		if (!$this->ValidarContrato($contrato))
		{
			echo "Error al guardar el contrato\n";
			return false;
		}
		
		if (!$this->Write($contrato))
		{
			echo "Error al guardar el contrato\n";
			return false;
		}

		echo "Contrato ID " . $contrato->fields['id_contrato'] . " guardado\n";

		return true;
		
		/*
		//Cobros pendientes
		CobroPendiente::EliminarPorContrato($this->sesion, $contrato->fields['id_contrato']);
		foreach ($cobros_pendientes as $cobro_pendiente)
		{
			$cobro = new CobroPendiente($this->sesion);
			$cobro->Edit("id_contrato", $contrato->fields["id_contrato"]);
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
		$contrato->fields['id_contrato']);
		foreach ($documentos_legales as $documento_legal) {
			$contrato_documento_legal = new ContratoDocumentoLegal($this->sesion);
			$contrato_documento_legal->Edit('id_contrato', $contrato->fields["id_contrato"]);
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

	/* Datos a recibir
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
		#permisos. Array('ADM','PRO',etc). 'ALL' siempre se incluye automÃ¡ticamente.
		#secretario_de. Ids de los usuarios del cual este es secretario. (Por lo tanto, los secretarios se ingresan al final). Array('1'=>'1','2'=>'2').
		#usuarios_revisados. Ids de los usuarios a los cuales este revisa. Array('1'=>'1','2'=>'2')
	*/
	
	public function AgregarUsuario($usuario = null, $permisos = array(),$secretario_de = array(),$usuarios_revisados = array())
	{
		global $tbl_usuario_permiso, $tbl_prm_permiso;
        $tbl_usuario_permiso = 'usuario_permiso';
		$tbl_prm_permiso = 'prm_permisos';

		echo "---Ingresando usuario\n";

		$usuario->Edit('id_area_usuario', empty($usuario->fields['id_area_usuario']) ? 1 : $usuario->fields['id_area_usuario']);

		if (!$this->ValdiarUsuario($usuario))
		{
			echo "Error al ingresar el usuario\n";
			return false;
		}

		$usuario->Edit('id_categoria_usuario',
			empty($usuario->fields['id_categoria_usuario']) ? "NULL" : $usuario->fields['id_categoria_usuario']);
		/*$usuario->Edit('dir_numero',
			empty($usuario->fields['$dir_numero']) ? "NULL" : $usuario->fields['$dir_numero']);*/
		$usuario->Edit('dir_comuna',
			empty($usuario->fields['dir_comuna']) ? "NULL" : $usuario->fields['dir_comuna']);

		#Genero el username si no existe
		if (!empty($usuario->fields['id_usuario']))
		{
			$usuario->Edit('id_usuario', (int)$usuario->fields['id_usuario']);
			$forzar_insert = true;
		}
		
		if(!$usuario->fields['username'])
		{
			$usuario->Edit('username', $nombre.' '.$apellido1.' '.$apellido2);
		}

		#Confirmo que el username no exista
		$query = "SELECT count(*) FROM usuario WHERE username = '" . addslashes($usuario->fields['username']) . "'";
		$resp = mysql_query($query, $this->sesion->dbh);
		if (!$resp)
		{
			echo "Error explicacion: " . mysql_error($this->sesion->dbh). "\n";
			echo "Query:</b> " . $query."\n";
			return false;
		}
	
		list($cantidad) = mysql_fetch_array($resp);
		if($cantidad > 0)
		{
			echo "Error ingreso usuario " . $usuario->fields['id_usuario'] . ": username '" . $usuario->fields['username'] . "' ya existe\t\n";
			return false;
		}

		#Confirmo que venga email
		if($usuario->fields['email'] == "")
		{
			echo "Error ingreso usuario: debe ingresar email\n";
			return false;
		}

		#Visible depende de activo
		$usuario->Edit('visible', $usuario->fields['activo']==1 ? '1' : '0');

		#Calculo el md5 del password provisto, o uno nuevo si no viene.
		if(!$usuario->fields['password'])
		{
			$password = Utiles::NewPassword();
			//$this->logs[] .= 'Usuario: username "'.$usuario->fields['username'].'" password = "'.$password.'"';
		}
		else
		{
			$password = $usuario->fields['password'];
		}
		#Ingreso el password
		
		$usuario->Edit('password', md5( $password ) );

		if ($this->Write($usuario, $forzar_insert))
		{ 
			#Cargar permisos 
			if (is_array($permisos))
			{
				foreach($permisos as $index => $permiso->fields)
				{
					if(!$usuario->EditPermisos($permiso))
					{
						echo "Error en permiso usuario: '".$usuario->fields['username']."': ". $usuario->error . "\n";
					}
				}
			}
			
			$usuario->PermisoAll($usuario->fields['id_usuario']);

			#End Cargar permisos
			$usuario->GuardarSecretario($secretario_de);
			$usuario->GuardarRevisado($usuarios_revisados);

			$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'],$usuario->fields['id_categoria_usuario']);
			echo "Usuario '" . $usuario->fields['username'] . "' ingresado con éxito, su password es: " . $password . "\n";
		}
	}
	
    function Query2ObjetoHora($response)
	{
		while($row = mysql_fetch_assoc($response))
		{
			$trabajo = new Trabajo($this->sesion);
			$trabajo->guardar_fecha = false;

			foreach( $row as $key => $val )
			{
				$trabajo->Edit($key, $val);
			}
			
			$this->AgregarHora($trabajo);
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
			echo "Error ingreso hora: id_moneda " . $hora->fields['id_moneda'] . " no existe\n";
			//return false;
		}
		$hora->Edit('id_moneda', $id_moneda);
		
		/*
		* Registrar información
		*/
		if($this->Write($hora))
		{
			echo "Trabajo " . $hora->fields['id_trabajo'] . " ingresado\n";
		}
		else
		{
			echo "Trabajo NO FUE ingresado\n";
		}
	}

	public function Write($objeto, $forzar_insert = false)
	{
		$objeto->error = "";
	
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
			if($do_update) //Solo en caso de que se haya modificado algÃºn campo
			{
				$resp = mysql_query($query, $this->sesion->dbh);
				if (!$resp)
				{
					echo "Error explicacion: " . mysql_error($this->sesion->dbh). "\n";
					echo "Datos: " .$error_string . "\n";
					echo "Query: " . $query . "\n";
					return false;
				}
				return true;
			}
			else //Retorna true ya que si no quiere hacer update la funciÃ³n corriÃ³ bien
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
				
				echo "Error explicacion: " . mysql_error($this->sesion->dbh) . "\n";
				echo "Datos: " .$error_string . "\n";
				echo "Query: " . $query."\n";
				return false;
			}
			$objeto->fields[$objeto->campo_id] = mysql_insert_id($this->sesion->dbh);
		}
		return true;
	}

	/*
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
	*/

	function GenerarCobroBase($cobro_generar = null, $incluye_gastos = true, $incluye_honorarios = true, $con_gastos = false, $solo_gastos = false)
	{
		$cobro_guardado = true;

		$incluye_gastos = empty($incluye_gastos) ? '0' : '1';
		$incluye_honorarios = empty($incluye_honorarios) ? '0' : '1';

		$cobro = new Cobro($this->sesion);
		$cobro->guardar_fecha = false;
		
		if (!empty($cobro_generar))
		{
			$cobro->Load($cobro_generar->fields['id_cobro']);
		}

		if ($cobro->Loaded())
		{
			echo "--Editando cobro con ID " . $cobro_generar->fields['id_cobro'] . "\n";
		}
		else
		{
			if (empty($cobro_generar->fields['id_cobro']))
			{
				echo "--Generando cobro\n";
			}
			else
			{
				$cobro->Edit('id_cobro', $cobro_generar->fields['id_cobro']);
				echo "--Generando cobro con ID " . $cobro_generar->fields['id_cobro'] . "\n";
				$forzar_insert = true;
			}
		}

		if (!$this->ValidarCobro($cobro_generar))
		{
			if (empty($cobro_generar->fields['id_cobro']))
			{
				echo "Error al generar cobro\n";
			}
			else
			{
				echo "Error al generar cobro con ID " . $cobro_generar->fields['id_cobro'] . "\n";
			}
			return false;
		}

		$contrato = new Contrato($this->sesion, null, null, 'contrato', 'id_contrato');
		$contrato->Load($cobro_generar->fields['id_contrato']);

		$contrato->EliminarBorrador($incluye_gastos, $incluye_honorarios);

		$moneda_base = Utiles::MonedaBase($this->sesion);
		$moneda = new Moneda($this->sesion);
		$moneda->Load($contrato->fields['id_moneda']);

		$cobro->Edit('id_usuario',
			!empty($cobro_generar->fields['id_usuario']) ? $cobro_generar->fields['id_usuario'] : "NULL");
		$cobro->Edit('codigo_cliente',
			empty($cobro_generar->fields['codigo_cliente']) ? $contrato->fields['codigo_cliente'] : $cobro_generar->fields['codigo_cliente']);
		$cobro->Edit('id_contrato', $contrato->fields['id_contrato']);
		$cobro->Edit('id_moneda',
			empty($cobro_generar->fields['id_moneda']) ? $contrato->fields['id_moneda'] : $cobro_generar->fields['id_moneda']);
		$cobro->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
		$cobro->Edit('forma_cobro',
			empty($cobro_generar->fields['forma_cobro']) ? $contrato->fields['forma_cobro'] : $cobro_generar->fields['forma_cobro']);

		//Este es el monto fijo, pero si no se inclyen honorarios no va
		$monto = empty($monto) ? $contrato->fields['monto'] : $monto;
		if(empty($incluye_honorarios)) $monto = '0';
		$cobro->Edit('monto_contrato', $monto);

		$cobro->Edit('retainer_horas',
			empty($cobro_generar->fields['retainer_horas']) ? $contrato->fields['retainer_horas'] : $cobro_generar->fields['retainer_horas']);

		//Opciones
		$cobro->Edit('id_carta',
			empty($cobro_generar->fields['id_carta']) ? $contrato->fields['id_carta'] : $cobro_generar->fields['id_carta']);
		$cobro->Edit("opc_ver_modalidad",
			empty($cobro_generar->fields['opc_ver_modalidad']) ? $contrato->fields['opc_ver_modalidad'] : $cobro_generar->fields['opc_ver_modalidad']);
		$cobro->Edit("opc_ver_profesional",
			empty($cobro_generar->fields['opc_ver_profesional']) ? $contrato->fields['opc_ver_profesional'] : $cobro_generar->fields['opc_ver_profesional']);
		$cobro->Edit("opc_ver_gastos",
			empty($cobro_generar->fields['opc_ver_gastos']) ? $contrato->fields['opc_ver_gastos'] : $cobro_generar->fields['opc_ver_gastos']);
		$cobro->Edit("opc_ver_morosidad",
			empty($cobro_generar->fields['opc_ver_morosidad']) ? $contrato->fields['opc_ver_morosidad'] : $cobro_generar->fields['opc_ver_morosidad']);
		$cobro->Edit("opc_ver_resumen_cobro",
			empty($cobro_generar->fields['opc_ver_resumen_cobro']) ? $contrato->fields['opc_ver_resumen_cobro'] : $cobro_generar->fields['opc_ver_resumen_cobro']);
		$cobro->Edit("opc_ver_descuento",
			empty($cobro_generar->fields['opc_ver_descuento']) ? $contrato->fields['opc_ver_descuento'] : $cobro_generar->fields['opc_ver_descuento']);
		$cobro->Edit("opc_ver_tipo_cambio",
			empty($cobro_generar->fields['opc_ver_tipo_cambio']) ? $contrato->fields['opc_ver_tipo_cambio'] : $cobro_generar->fields['opc_ver_tipo_cambio']);
		$cobro->Edit("opc_ver_solicitante",
			empty($cobro_generar->fields['opc_ver_solicitante']) ? $contrato->fields['opc_ver_solicitante'] : $cobro_generar->fields['opc_ver_solicitante']);
		$cobro->Edit("opc_ver_numpag",
			empty($cobro_generar->fields['opc_ver_numpag']) ? $contrato->fields['opc_ver_numpag'] : $cobro_generar->fields['opc_ver_numpag']);
		$cobro->Edit("opc_ver_carta",
			empty($cobro_generar->fields['opc_ver_carta']) ? $contrato->fields['opc_ver_carta'] : $cobro_generar->fields['opc_ver_carta']);
		$cobro->Edit("opc_papel",
			empty($cobro_generar->fields['opc_papel']) ? $contrato->fields['opc_papel'] : $cobro_generar->fields['opc_papel']);
		$cobro->Edit("opc_restar_retainer",
			empty($cobro_generar->fields['opc_restar_retainer']) ? $contrato->fields['opc_restar_retainer'] : $cobro_generar->fields['opc_restar_retainer']);
		$cobro->Edit("opc_ver_detalle_retainer",
			empty($cobro_generar->fields['opc_ver_detalle_retainer']) ? $contrato->fields['opc_ver_detalle_retainer'] : $cobro_generar->fields['opc_ver_detalle_retainer']);
		$cobro->Edit("opc_ver_valor_hh_flat_fee",
			empty($cobro_generar->fields['opc_ver_valor_hh_flat_fee']) ? $contrato->fields['opc_ver_valor_hh_flat_fee'] : $cobro_generar->fields['opc_ver_valor_hh_flat_fee']);

		//Configuración moneda del cobro
		$moneda_cobro_configurada = empty($cobro_generar->fields['opc_moneda_total']) ? $contrato->fields['opc_moneda_total'] : $cobro_generar->fields['opc_moneda_total'];

		//Si incluye solo gastos, utilizar la moneda configurada para ello
		if ($incluye_gastos && !$incluye_honorarios)
		{
			$moneda_cobro_configurada = empty($cobro_generar->fields['opc_moneda_gastos']) ? $contrato->fields['opc_moneda_gastos'] : $cobro_generar->fields['opc_moneda_gastos'];
		}

		$cobro->Edit("opc_moneda_total", $moneda_cobro_configurada);

		$cobro->Edit("opc_ver_asuntos_separados",
			empty($cobro_generar->fields['opc_ver_asuntos_separados']) ? $contrato->fields['opc_ver_asuntos_separados'] : $cobro_generar->fields['opc_ver_asuntos_separados']);
		$cobro->Edit("opc_ver_horas_trabajadas",
			empty($cobro_generar->fields['opc_ver_horas_trabajadas']) ? $contrato->fields['opc_ver_horas_trabajadas'] : $cobro_generar->fields['opc_ver_horas_trabajadas']);
		$cobro->Edit("opc_ver_cobrable",
			empty($cobro_generar->fields['opc_ver_cobrable']) ? $contrato->fields['opc_ver_cobrable'] : $cobro_generar->fields['opc_ver_cobrable']);

		// Guardamos datos de la moneda base
		$cobro->Edit('id_moneda_base', $moneda_base['id_moneda']);
		$cobro->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']);
		$cobro->Edit('etapa_cobro', '4');
		$cobro->Edit('codigo_idioma', empty($contrato->fields['codigo_idioma']) ? 'es' : $contrato->fields['codigo_idioma']);
		$cobro->Edit('id_proceso', $cobro->GeneraProceso());

		//Descuento
		$cobro->Edit("tipo_descuento",
			empty($cobro_generar->fields['tipo_descuento']) ? $contrato->fields['tipo_descuento'] : $cobro_generar->fields['tipo_descuento']);
		$cobro->Edit("descuento",
			empty($cobro_generar->fields['descuento']) ? $contrato->fields['descuento'] : $cobro_generar->fields['descuento']);
		$cobro->Edit("porcentaje_descuento",
			empty($cobro_generar->fields['porcentaje_descuento']) ? empty($contrato->fields['porcentaje_descuento']) ? '0' : $contrato->fields['porcentaje_descuento'] : $cobro_generar->fields['porcentaje_descuento']);
		$cobro->Edit("id_moneda_monto",
			empty($cobro_generar->fields['id_moneda_monto']) ? $contrato->fields['id_moneda_monto'] : $cobro_generar->fields['id_moneda_monto']);

		$fecha_ini = $cobro_generar->fields['fecha_ini'];
		if (!empty($fecha_ini) and $fecha_ini != '0000-00-00')
		{
			$cobro->Edit('fecha_ini', $fecha_ini);
		}

		$fecha_fin = $cobro_generar->fields['fecha_fin'];
		if (!empty($fecha_fin))
		{
			$cobro->Edit('fecha_fin', $fecha_fin);
		}
		
		if ($solo_gastos)
		{
			$cobro->Edit('solo_gastos', 1);
		}
	
		$cobro->Edit("incluye_honorarios", $incluye_honorarios);
		$cobro->Edit("incluye_gastos", $incluye_gastos);
		
		$cobro->Edit("fecha_creacion", (!empty($cobro_generar->fields['fecha_creacion'])) ? $cobro_generar->fields['fecha_creacion'] : "NULL");
		$cobro->Edit("id_moneda", (!empty($cobro_generar->fields['id_moneda'])) ? $cobro_generar->fields['id_moneda'] : '1');
		$cobro->Edit("monto", (!empty($cobro_generar->fields['monto'])) ? $cobro_generar->fields['monto'] : "NULL");
		$cobro->Edit("impuesto", (!empty($cobro_generar->fields['impuesto'])) ? $cobro_generar->fields['impuesto'] : '0');
		$cobro->Edit("monto_subtotal", (!empty($cobro_generar->fields['monto_subtotal'])) ? $cobro_generar->fields['monto_subtotal'] : '0');
		$cobro->Edit("porcentaje_impuesto", (!empty($cobro_generar->fields['porcentaje_impuesto'])) ? $cobro_generar->fields['porcentaje_impuesto'] : '0');

		if (!$this->Write($cobro, $forzar_insert))
		{
			if (empty($cobro->fields['id_cobro']))
			{
				echo "Error al guardar el cobro\n";
			}
			else
			{
				echo "Error al guardar el cobro ID " . $cobro->fields['id_cobro'] . "\n";
			}
			return false;
		}

		echo "Cobro " . $cobro->fields['id_cobro'] . " generado\n";
		
		if ($cobro->Loaded())
		{
			$this->EliminarCobroAsuntos($cobro->fields['id_cobro']);
			$this->AddCobroAsuntos($cobro->fields['id_cobro']);
		}

		//Moneda cobro
		$cobro_moneda = new CobroMoneda($this->sesion);
		$cobro_moneda->ActualizarTipoCambioCobro($cobro->fields['id_cobro']);

		//Gastos
		if(!empty($incluye_gastos))
		{
			if ($solo_gastos == true)
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
					if (!$this->Write($cta_gastos))
					{
						echo "Error al modificar el gasto " . $trabajo->fields['id_trabajo'] . "\n";
						continue;
					}
					echo "Gasto " . $cta_gastos->fields['id_movimiento'] . " asociado al cobro " . $cobro->fields['id_cobro'] . "\n";
				}
			}
		}

		//Trabajos
		if (!empty($incluye_honorarios) and !$solo_gastos)
		{
			echo "Asociando trabajos al cobro " . $cobro->fields['id_cobro'] . "\n";
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
			echo $lista_trabajos->num . " trabajos encontrados\n";
			for($x=0; $x<$lista_trabajos->num; $x++)
			{
				$trabajo = $lista_trabajos->Get($x);
				$emitir_trabajo = new Objeto($this->sesion, null, null, 'trabajo', 'id_trabajo');
				$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
				$emitir_trabajo->Edit('id_cobro', $cobro->fields['id_cobro']);
				if (!$this->Write($emitir_trabajo))
				{
					echo "Error al modificar trabajo " . $trabajo->fields['id_trabajo'] . "\n";
					continue;
				}
				echo "Trabajo " . $emitir_trabajo->fields['id_trabajo'] . " asociado al cobro " . $cobro->fields['id_cobro'] . "\n";
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

		//Se ingresa la anotación en el historial
		$observacion = new Observacion($this->sesion);
		$observacion->Edit('fecha', date('Y-m-d H:i:s'));
		$observacion->Edit('comentario', __('COBRO CREADO'));
		$observacion->Edit('id_usuario', $cobro->fields['id_usuario']);
		$observacion->Edit('id_cobro', $cobro->fields['id_cobro']);
		$this->Write($observacion);

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
			echo "El trabajo " . $trabajo->fields['id_trabajo'] . " no contiene codigo asunto\n";
			return array("error" => true, "contrato" => null);
		}
		$asunto->LoadByCodigo($trabajo->fields['codigo_asunto']);
		if (!$asunto->Loaded())
		{
			echo "Para el trabajo " . $trabajo->fields['id_trabajo'] . " no exite el asunto con codigo " . $trabajo->fields['codigo_asunto'] . "\n";
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
			echo "Parametros incorrectos\n";
			return false;
		}

		foreach ($datos as $indice => $dato)
		{
			if (empty($dato['id_contrato']) or empty($dato['fecha_ini']) or empty($dato['fecha_fin']))//empty($dato['cobro']) or //
			{
				echo "Si desea generar cobros entre fechas debe ingresar el contrato y las fechas, para la fila " . $indice . "\n";
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
		
		if (!empty($gasto_generar->fields['id_movimiento']) and (!preg_match("/^[[:digit:]]+$/", $gasto_generar->fields['id_movimiento']) or $gasto_generar->fields['id_movimiento'] == 0))
		{
			$gasto_generar->Edit('id_movimiento', '999' . sprintf("%04d", (int)$gasto_generar->fields['id_movimiento']));
			echo "ID cambiado " . $gasto_generar->fields['id_movimiento'] . " al gasto\n";
		}
		
		$gasto->Load($gasto_generar->fields['id_movimiento']);

		if ($gasto->Loaded())
		{
			echo "--Editando gasto ID " . $gasto->fields['id_movimiento'] . "\n";
		}
		else
		{
			if (empty($gasto_generar->fields['id_movimiento']))
			{
				echo "--Generando gasto\n";
			}
			else
			{
				$gasto->Edit("id_movimiento", $gasto_generar->fields['id_movimiento']);
				echo "--Generando gasto ID " . $gasto_generar->fields['id_movimiento'] . "\n";
				$forzar_insert = true;
			}
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
			$this->GenerarPago($gasto);
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

		if (!$this->ValidarGasto($gasto))
		{
			echo "Error al generar el gasto\n";
			return false;
		}

		if (!$this->Write($gasto, $forzar_insert))
		{
			echo "Error al generar el gasto\n";
			return false;
		}
		
		echo "Gasto " . $gasto->fields['id_movimiento'] . " guardado\n";
	}

	public function ValidarGasto($gasto)
	{
		if (!$this->ValidarCodigo($gasto, "codigo_asunto", "asunto", true, "codigo_asunto", true))
		{
			return false;
		}

		return $this->ValidarCodigo($gasto, "id_usuario", "usuario");
	}
	
	public function ValidarCobro($cobro)
	{
		if (!$this->ValidarCodigo($cobro, "id_contrato", "contrato", true, "id_contrato"))
		{
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "id_moneda", "prm_moneda", true, "id_moneda"))
		{
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "id_moneda_monto", "prm_moneda", true, "id_moneda"))
		{
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "opc_moneda_total", "prm_moneda", true, "id_moneda"))
		{
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "codigo_cliente", "cliente", true, "codigo_cliente", true))
		{
			return false;
		}

		return $this->ValidarCodigo($cobro, "id_usuario", "usuario");
	}
	
	public function ValdiarUsuario($usuario)
	{
		if (!$this->ValidarCodigo($usuario, "id_categoria_usuario", "prm_categoria_usuario"))
		{
			return false;
		}
		if (!$this->ValidarCodigo($usuario, "dir_comuna", "prm_comuna", false, "id_comuna"))
		{
			return false;
		}
		if (!$this->ValidarCodigo($usuario, "id_area_usuario", "prm_area_usuario", true, "id"))
		{
			return false;
		}
		return true;
	}
	
	public function ValidarCodigo($objeto, $campo_validar, $tabla, $obligatorio = false, $campo_id = null, $string = false)
	{
		$valido = true;

		$objeto_validar = new Objeto($this->sesion, null, null, $tabla, empty($campo_id) ? $campo_validar : $campo_id);

		if (empty($objeto->fields[$campo_validar]) and $obligatorio)
		{
			$msg .= "El(la) " . strtolower($campo_validar) . " no fue ingresado(a)";
			$valido = false;
		}
		else if (!empty($objeto->fields[$campo_validar]) and $objeto->fields[$campo_validar] != "NULL" and !$this->ExisteCodigo($objeto->fields[$campo_validar], $objeto_validar->tabla, $objeto_validar->campo_id, $string))
		{
			$msg .= "No existe el(la) " . strtolower($campo_validar) . " ID o Codigo " . $objeto->fields[$campo_validar];
			$valido = false;
		}

		if (!empty($cobro->fields[$objeto->campo_id]))
		{
			$msg .= " para el " . $objeto>tabla . " " . $objeto->fields[$objeto->campo_id];
		}

		if (!$valido)
		{
			echo $msg . "\n";
			return false;
		}

		return true;
	}
	
	public function ExisteCodigo($codigo, $tabla, $campo, $string = false)
	{
		$sss = ($string) ? "'" . $codigo . "'" : $codigo;
		$query = "SELECT COUNT(*) FROM " . $tabla . " WHERE " . $campo . " = " . $sss;
		
		if ($campo == "id_area_usuario"){
			echo $query;
		}

		$ttt = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($existe) = mysql_fetch_array($ttt);
		if (empty($existe))
		{
			return false;
		}
		return true;
	}

	public function AddCobroAsuntos($id_cobro)
	{
		#Genero la parte values a través de queries
		$query = "SELECT CONCAT('(', '$id_cobro',',\'',codigo_asunto,'\')') as value FROM asunto WHERE id_contrato = '".$this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh);
		if (!$resp)
		{
			echo "Error explicacion: " . mysql_error($this->sesion->dbh). "\n";
			echo "Query: " . $query . "\n";
			return false;
		}
		while(list($values) = mysql_fetch_array($resp))
		{
			if($values != "")
			{
				$query2 = "INSERT INTO cobro_asunto (id_cobro, codigo_asunto) values $values";
				$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$this->sesion->dbh);
			}
		}
		return true;
	}

	public function EliminarCobroAsuntos($id_cobro)
	{
		$query = "DELETE FROM cobro_asunto WHERE id_cobro = " . $id_cobro;
		$resp = mysql_query($query, $this->sesion->dbh);
		if (!$resp)
		{
			echo "Error explicacion: " . mysql_error($this->sesion->dbh). "\n";
			echo "Query: " . $query . "\n";
			return false;
		}
		return true;
	}

	public function ValidarFactura($factura)
	{
		if (!$this->ValidarCodigo($factura, "id_documento_legal_motivo", "prm_documento_legal_motivo"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($factura, "id_cobro", "cobro"))
		{
			return false;
		}
		
		if (!$this->ValidarCodigo($factura, "id_estado", "prm_estado_factura"))
		{
			return false;
		}
		
		return $this->ValidarCodigo($factura, "id_moneda", "prm_moneda");
	}

	public function GenerarFactura($factura_generar)
	{
		$factura = new Factura($this->sesion);
		$factura->Load($factura_generar->fields['id_factura']);
		if ($factura->Loaded())
		{
			echo "--Editando factura ID " . $factura->fields['id_factura'] . "\n";
		}
		else
		{
			if (empty($factura_generar->fields['id_factura']))
			{
				echo "--Generando factura\n";
			}
			else
			{
				echo "--Generando factura ID " . $factura_generar->fields['id_factura'] . "\n";
				$factura->Edit('id_factura', $factura_generar->fields['id_factura']);
				$forzar_insert = true;
			}
		}
		
		$mensaje_accion = 'guardado';
		$factura->Edit('subtotal', $factura_generar->fields['subtotal']);
		$factura->Edit('porcentaje_impuesto', $factura_generar->fields['porcentaje_impuesto']);
		$factura->Edit('iva', $factura_generar->fields['iva']);
		$factura->Edit('total', '' . ($factura_generar->fields['subtotal'] + $factura_generar->fields['iva']));
		$factura->Edit("id_factura_padre", empty($factura_generar->fields['id_factura_padre']) ? "NULL" : $factura_generar->fields['id_factura_padre']);
		$factura->Edit("fecha", Utiles::fecha2sql($factura_generar->fields['fecha']));
		$factura->Edit("cliente", empty($factura_generar->fields['cliente']) ? "NULL" : $factura_generar->fields['cliente']);
		$factura->Edit("RUT_cliente", empty($factura_generar->fields['RUT_cliente']) ? "NULL" : $factura_generar->fields['RUT_cliente']);
		$factura->Edit("direccion_cliente", empty($factura_generar->fields['direccion_cliente']) ? "NULL" : $factura_generar->fields['direccion_cliente']);
		$factura->Edit("codigo_cliente", empty($factura_generar->fields['codigo_cliente']) ? "NULL" : $factura_generar->fields['codigo_cliente']);
		$factura->Edit("id_cobro", empty($factura_generar->fields['id_cobro']) ? "NULL" : $factura_generar->fields['id_cobro']);
		$factura->Edit("id_documento_legal", empty($factura_generar->fields['id_documento_legal']) ? '1' : $factura_generar->fields['id_documento_legal']);
		$factura->Edit("serie_documento_legal", Conf::GetConf($this->sesion, 'SerieDocumentosLegales'));
		$factura->Edit("numero", empty($factura_generar->fields['numero']) ? '1' : $factura_generar->fields['numero']);
		$factura->Edit("id_estado", empty($factura_generar->fields['id_estado']) ? '1' : $factura_generar->fields['id_estado']);
		$factura->Edit("id_moneda", empty($factura_generar->fields['id_moneda']) ? $factura_generar->fields['id_moneda'] : '1');

		if($factura_generar->fields['id_estado'] == '5')
		{
			$factura->Edit('estado', 'ANULADA');
			$factura->Edit('anulado', 1);
			$mensaje_accion = 'anulado';
		}
		else if(!empty($factura_generar->fields['anulado']))
		{
			$factura->Edit('estado', 'ABIERTA');
			$factura->Edit('anulado', '0');
		}

		if (method_exists("Conf", "GetConf") && (Conf::GetConf($this->sesion, "DesgloseFactura") == "con_desglose"))
		{
			$factura->Edit("descripcion", $factura_generar->fields['descripcion']);
			$factura->Edit("honorarios", empty($factura_generar->fields['honorarios']) ? "NULL" : $factura_generar->fields['honorarios']);
			$factura->Edit("subtotal", empty($factura_generar->fields['subtotal']) ? "NULL" : $factura_generar->fields['subtotal']);
			$factura->Edit("subtotal_sin_descuento", empty($factura_generar->fields['subtotal_sin_descuento']) ? "NULL" : $factura_generar->fields['subtotal_sin_descuento']);
			$factura->Edit("descripcion_subtotal_gastos", empty($factura_generar->fields['descripcion_subtotal_gastos']) ? "NULL" : $factura_generar->fields['descripcion_subtotal_gastos']);
			$factura->Edit("subtotal_gastos", empty($factura_generar->fields['subtotal_gastos']) ? "NULL" : $factura_generar->fields['subtotal_gastos']);
			$factura->Edit("descripcion_subtotal_gastos_sin_impuesto", empty($factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']) ? "NULL" : $factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']);
			$factura->Edit("subtotal_gastos_sin_impuesto", empty($factura_generar->fields['subtotal_gastos_sin_impuesto']) ? "NULL" : $factura_generar->fields['subtotal_gastos_sin_impuesto']);
			$factura->Edit("total", empty($factura_generar->fields['total']) ? "NULL" : $factura_generar->fields['total']);
			$factura->Edit("iva", empty($factura_generar->fields['iva']) ? "NULL" : $factura_generar->fields['iva']);
		}
		else
		{
			$factura->Edit("descripcion", $factura_generar->fields['descripcion']);
		}

		$factura->Edit("letra", $factura_generar->fields['letra']);

		$cobro = new Cobro($this->sesion);
		if($cobro->Load($factura_generar->fields['id_cobro']))
		{
			$factura->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
		}

		$factura->Edit("numero", $factura->ObtenerNumeroDocLegal($factura->fields['id_documento_legal']));
		if (!$factura->Loaded())
		{
			$generar_nuevo_numero = true;
		}

		if (!$this->ValidarFactura($factura))
		{
			echo "Error al ingresar la factura\n";
			return false;
		}

		$query = "SELECT id_documento_legal, glosa, codigo FROM prm_documento_legal WHERE id_documento_legal = '$id_documento_legal'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tipo_documento_legal, $codigo_tipo_doc) = mysql_fetch_array($resp);

		if($this->Escribir($factura, forzar_insert))
		{
			if ($generar_nuevo_numero)
			{
				$factura->GuardarNumeroDocLegal($factura->fields['id_documento_legal'], $factura->fields['numero']);
			}

			$signo = ($codigo_tipo_doc == 'NC') ? 1 : -1; //es 1 o -1 si el tipo de doc suma o resta su monto a la liq
			$neteos = empty($factura->fields['id_factura_padre']) ? null : array(array($factura->fields['id_factura_padre'], $signo * $factura->fields['total']));

			$cta_cte_fact = new CtaCteFact($this->sesion);
			$cta_cte_fact->RegistrarMvto($factura->fields['id_moneda'],
				$signo*($factura->fields['total']-$factura->fields['iva']),
				$signo*$factura->fields['iva'],
				$signo*$factura->fields['total'],
				$factura->fields['fecha'],
				$neteos,
				$factura->fields['id_factura'],
				null,
				$codigo_tipo_doc,
				$ids_monedas_documento,
				$tipo_cambios_documento,
				!empty($factura->fields['anulado']));
			
			echo __('Documento Tributario') . " " . $mensaje_accion . "\n";

			if($factura->fields['id_cobro'])
			{					
				$documento = new Documento($this->sesion);
				$documento->LoadByCobro($factura->fields['id_cobro']);
				
				$valores = array( 
					$factura->fields['id_factura'],
					$factura->fields['id_cobro'],
					$documento->fields['id_documento'],
					$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
					$factura->fields['iva'],
					$documento->fields['id_moneda'],
					$documento->fields['id_moneda']
				);
				
				$query = "DELETE FROM factura_cobro WHERE id_factura = '" . $factura->fields['id_factura'] . "' AND id_cobro = '" . $factura->fields['id_cobro'] . "'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento) VALUES ('" . implode("','", $valores) . "')";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
		else
		{
			echo "Error al guardar la factura";
			return false;
		}

		echo "Factura ID " . $factura->fields['id_factura'] . " guardada";

		$observacion = new Observacion($this->sesion);
		$observacion->Edit('fecha', date('Y-m-d H:i:s'));
		$observacion->Edit('comentario', "MODIFICACIÓN FACTURA");
		$observacion->Edit('id_usuario', "NULL");
		$observacion->Edit('id_factura', $factura->fields['id_factura']);
		$observacion->Write();
	}

	function Escribir($objeto, $forzar_insert = false)
	{
		if(!$this->Write($objeto, $forzar_insert))
		{
			return false;
		}

		$cobro = new Cobro($this->sesion);
		if($cobro->Load($objeto->fields['id_cobro']))
		{
			if( UtilesApp::GetConf($this->sesion,'NuevoModuloFactura'))
			{
				$query = "SELECT
					group_concat(idDocLegal) as listaDocLegal
					FROM (
					SELECT
					 CONCAT(if(f.id_documento_legal != 0, if(f.letra is not null, if(f.letra != '',concat('LETRA ',f.letra), CONCAT(p.codigo,' ',f.numero)), CONCAT(p.codigo,' ',f.numero)), ''),IF(f.anulado=1,' (ANULADO)',''),' ') as idDocLegal
					,f.id_cobro
					FROM factura f, prm_documento_legal p
					WHERE f.id_documento_legal = p.id_documento_legal
					AND id_cobro = '" . $objeto->fields['id_cobro'] . "'
					)zz
					GROUP BY id_cobro";
				$resp = mysql_query($query,$this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				list($lista) = mysql_fetch_array($resp);
				$cobro->Edit('documento', $lista);
			}
			else
			{
				$cobro->Edit('documento', $this->fields['numero']);
			}

			if (!$this->Write($cobro))
			{
				return false;
			}
		}

		return true;
	}

	/*
	public function ExisteCodigo($lista_objetos, $tabla, $campo, $string = true)
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

			echo "Los siguientes " . $campo . " no existen: " . implode(", ", $codigos_asunto) . "\n";
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
			echo "Eliminados de la lista objetos con indice: " . implode(", ", $lista_extraer) . "\n";
		}
		return $lista;
	}
	*/
}

?>