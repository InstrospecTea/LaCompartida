<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Contrato.php';
	require_once Conf::ServerDir().'/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	
	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	
	if( Debug::debug_echo($sesion,'LEMONTECH') != 'LEMONTECH' ) exit;
	
	if( $_POST['opc'] == "generar" )
	{
			if( $_POST['clientes_activos'] == "solo_activos" )
				$where = " activo = 1 ";
			else
				$where = " 1 ";
			
			$query = "SELECT codigo_cliente FROM cliente WHERE $where AND LENGTH( codigo_cliente ) = 4";
			$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			
			$contador = 0;
			while( list($codigo_cliente) = mysql_fetch_array($resp) )
			{
				$cliente = new Cliente($sesion);
				
				if( !$cliente->LoadByCodigo($codigo_cliente) )
				{
					echo "Error al cargar el cliente con codigo $codigo_cliente <br>";
					continue;
				}
				
				$contrato_cliente = new Contrato($sesion);
				
				if( !$contrato_cliente->Load($cliente->fields['id_contrato']) )
				{
					echo "Error al crear asunto para cliente ".$cliente->fields['glosa_cliente'].", no se pudo cargar su contrato <br>";
					continue;
				}
				
				$id_contrato = $contrato_cliente->fields['id_contrato'];
				
				if( $_POST['cobro_independiente'] )
				{
					$contra= new Contrato($sesion);
					$contra->guardar_fecha = false;
					
					foreach($contrato_cliente->fields as $key => $val)
					{
						if( $key == 'id_usuario_responsable' )
							$contra->Edit($key, !empty($val) ? $val : "NULL" );
						else if( $key != "id_contrato" )
							$contra->Edit($key, $val);
					}
					
					$contra->Write( false );
					
					$id_contrato = $contra->fields['id_contrato'];
					$id_contrato_indep = $contra->fields['id_contrato'];
				}
				
				$asunto=new Asunto($sesion);
				$asunto->Edit('codigo_asunto',$asunto->AsignarCodigoAsunto(substr($cliente->fields['codigo_cliente'],-4)));
				$asunto->Edit('codigo_asunto_secundario',$asunto->AsignarCodigoAsuntoSecundario(substr($cliente->fields['codigo_cliente_secundario'],-4)));
				$asunto->Edit('glosa_asunto',$glosa_asunto);
				$asunto->Edit('codigo_cliente',substr($cliente->fields['codigo_cliente'],-4));
				$asunto->Edit('id_contrato',$id_contrato);
				if($id_contrato_indep)
					$asunto->Edit('id_contrato_indep',$id_contrato_indep);
				$asunto->Edit('id_usuario', !empty($contrato_cliente->fields['id_usuario_responsable']) ? $contrato_cliente->fields['id_usuario_responsable'] : "NULL" );
				$asunto->Edit('contacto',$cliente->fields['nombre_contacto']);
				$asunto->Edit("fono_contacto",$cliente->fields['fono_contacto']);
				$asunto->Edit("email_contacto",$cliente->fields['mail_contacto']);
				$asunto->Edit("direccion_contacto",$cliente->fields['dir_calle']);
				$asunto->Edit("id_encargado",( !empty($cliente->fields['id_usuario_encargado']) && $cliente->fields['id_usuario_encargado'] != '-1' ) ? $cliente->fields['id_usuario_encargado'] : "NULL");
				
				if( $asunto->Write() )
					$contador++;
			}
			echo "<h3> $contador asuntos agregados! </h3>";
	}
	$pagina->PrintTop(true);
?>
	<form action="#" method="POST">
		<input type="hidden" name="opc" value="generar" />
		<table width="40%">
			<tr>
				<td width="40%" align="right">Glosa asunto</td>
				<td width="60%" align="left">
					<input type="text" name="glosa_asunto" value="" />
				</td>
			</tr>
			<tr>
				<td width="40%" align="right">Se cobrará de forma independiente</td>
				<td width="60%" align="left">
					<input type="checkbox" name="cobro_independiente" value="1" />
				</td>
			</tr>
			<tr>
				<td width="40%" align="right">A que clientes ? </td>
				<td width="60%" align="left">
					<select name="clientes_activos">
						<option value="todos">Todos</option>
						<option value="solo_activos">Solo activos</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="GenerarAsunto" />
				</td>
		</table>
	</form>
<?
$pagina->PrintBottom(true);
?>
