<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/Observacion.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Documento.php';
	require_once Conf::ServerDir().'/../app/classes/Testing.php';
	
	$sesion = new Sesion(array());
	if( method_exists('Conf','GetConf') )
	{
		if( !Conf::GetConf($sesion,'EsAmbientePrueba') )
		{
			header("location: ".Conf::ServerDir()."/../fw/usuarios/index.php");
		}
	}
	else if( method_exists('Conf','EsAmbientePrueba') )
	{
		if( !Conf::EsAmbientePrueba() )
		{
			header("location: ".Conf::ServerDir()."/../fw/usuarios/index.php");
		}
	}
	else
		header("location: ".Conf::ServerDir()."/../fw/usuarios/index.php");
	$pagina = new Pagina($sesion);
	
	$pagina->titulo = __(' Testing automatico');
	$pagina->PrintTop();
	
	set_time_limit(200);
	?>
	
	<script type="text/javascript">
		function Validar( opcion )
		{
			var form = document.getElementById('form_testing');
			var hidden_opc = document.getElementById( 'opc' );
			
			if( opcion == 'generar' )
				{
					hidden_opc.value = 'generar_datos';
					form.submit();	
					return true;
				}
			else if( opcion == 'eliminar' )
				{
					hidden_opc.value = 'eliminar_datos';
					form.submit();
					return true;
				}
			else
				return;
		}
	</script>
	
	<fieldset width="97%">
		<legend>Filtros</legend>
			<form name="form_testing" action="" method="POST">
				<input type="hidden" name="opc" id="opc" value="generar_datos" />
					<table width="100%">
						<tr>
							<td width="50%" align="right">
								<?=__('Cliente')?>
							</td>
							<td width="50%" align="left">
								<?=Html::SelectQuery( $sesion, "SELECT codigo_cliente,glosa_cliente FROM cliente WHERE glosa_cliente LIKE '%TESTING%' ORDER BY glosa_cliente","codigo_cliente", $codigo_cliente, '','',"220"); ?>
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">
								<?=__('Moneda para tarificar trabajos')?>
							</td>
							<td width="50%" align="left">
								<?=Html::SelectQuery( $sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda", $id_moneda, '','',"80"); ?>
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">
								<?=__('Moneda para tarificar tramites')?>
							</td>
							<td width="50%" align="left">
								<?=Html::SelectQuery( $sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda_tramite", $id_moneda_tramite, '','',"80"); ?>
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">
								<?=__('Moneda del contrato')?>
							</td>
							<td width="65%" align="left">
								<?=Html::SelectQuery( $sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda_monto", $id_moneda_monto, '','',"80"); ?>
							</td>
						</tr>
						<tr>
							<td width="50%" align="right">
								<?=__('Moneda total para cobrar a cliente')?>
							</td>
							<td width="50%" align="left">
								<?=Html::SelectQuery( $sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","opc_moneda_total", $opc_moneda_total, '','',"80"); ?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<?=__('Opciones de configuracion')?>
							</td>
							<td align="left">
								<select id="config" name="config">
									<option value="todo" selected>Todos los opciones</option>
									<option value="con_impuesto">Con Impuestos</option>
									<option value="sin_impuesto">Sin Impuestos</option>
									<option value="impuestos_honorarios">Impuestos a honorarios</option>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="2" align="center">
						<? 
							if( $opc == "generar_datos" )
								{	?>
									<input type="submit" value="Eliminar datos testing" onclick="Validar( 'eliminar' );" />
						<?  } 
							else
								{ ?>
									<input type="submit" value="Generar datos testing" onclick="Validar( 'generar' );" /> 
						<?  } ?>
							</td>
						</tr>
					</table>
			</form>
	</fieldset>
	
	<?
	if( $opc == "generar_datos" )
		{
			// Generar los cobros del cliente TESTING 
			// Imprimir una tabla con todas las informaciones 
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'EsAmbientePrueba') ) || ( method_exists('Conf','EsAmbientePrueba') && Conf::EsAmbientePrueba() ) )
			{
					if( $id_moneda )
						CambiarCampoContrato( '', $codigo_cliente, 'id_moneda', $id_moneda );
					if( $opc_moneda_total )
						CambiarCampoContrato( '', $codigo_cliente, 'opc_moneda_total', $opc_moneda_total );
					if( $id_moneda_monto )
						{
							CambiarCampoContrato( '', $codigo_cliente, 'id_moneda_monto', $id_moneda_monto );
							
							$query_contrato = "SELECT id_contrato, id_moneda_monto, monto FROM contrato WHERE codigo_cliente='$codigo_cliente'";
							$resp_contrato = mysql_query($query_contrato,$sesion->dbh) or Utiles::errorSQL($query_contrato,__FILE__,__LINE__,$sesion->dbh);
							while( list($id,$id_moneda_monto_antes,$monto) = mysql_fetch_array($resp_contrato) )
								{
									$moneda_antes = new Moneda($sesion);
									$moneda_antes->Load($id_moneda_monto_antes);
									$moneda_actual = new Moneda($sesion);
									$moneda_actual->Load($id_moneda_monto);
									$monto_actualisado = $monto * $moneda_antes->fields['tipo_cambio'] / $moneda_actual->fields['tipo_cambio'];
									CambiarCampoContrato( $id, '', 'monto', $monto_actualisado);
								}
						}
					if( $id_moneda_tramite )
						CambiarCampoContrato( '', $codigo_cliente, 'id_moneda_tramite', $id_moneda_tramite );
				
					if( method_exists('Conf','GetConf') )
						{
							$valor_usar_impuesto = Conf::GetConf($sesion,'UsarImpuestoSeparado');
							$valor_usar_impuesto_gastos = Conf::GetConf($sesion,'UsarImpuestoPorGastos');
						}
						
				if( $config == 'todo' )
					{
						ModificarConfig( 'UsarImpuestoPorGastos', '1' );
						ModificarConfig( 'UsarImpuestoSeparado', '1' );
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_separado', '1');
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_gastos', '1');
							
							Testing::BorrarDatos($sesion, $codigo_cliente);
						echo '<h3> Cobros con impuestos a honorarios y gastos.</h3>';
							$cobros = GenerarDatos( $codigo_cliente, $opc_moneda_total );
							ImprimirCobros( $cobros );
							ImprimirDocumentos( $cobros );
							Testing::BorrarDatos($sesion, $codigo_cliente);
							
						echo '<h3> Cobros con impuestos a honorarios. </h3>';
							ModificarConfig( 'UsarImpuestoPorGastos', '0' );
							CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_gastos', '0');
							$cobros = GenerarDatos( $codigo_cliente, $opc_moneda_total );
							ImprimirCobros( $cobros );
							ImprimirDocumentos( $cobros );
							Testing::BorrarDatos($sesion, $codigo_cliente);
							
						echo '<h3> Cobros sin impuestos. </h3>';
							ModificarConfig( 'UsarImpuestoSeparado', '0' );
							CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_separado', '0');
							$cobros = GenerarDatos( $codigo_cliente, $opc_moneda_total );
							ImprimirCobros( $cobros );
							ImprimirDocumentos( $cobros );
							
					}
				else if( $config == 'con_impuesto' )
					{
						
						ModificarConfig( 'UsarImpuestoPorGastos', '1' );
						ModificarConfig( 'UsarImpuestoSeparado', '1' );
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_gastos', '1');
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_separado', '1');
						
						Testing::BorrarDatos($sesion, $codigo_cliente);
						echo '<h3> Cobros con impuestos a honorarios y gastos.</h3>';
							$cobros = GenerarDatos( $codigo_cliente, $opc_moneda_total );
							ImprimirCobros( $cobros, true );
							ImprimirDocumentos( $cobros );
							ImprimirOpcionesReportes( $sesion, $codigo_cliente );
					}
				else if( $config == 'sin_impuesto' )
					{
						ModificarConfig( 'UsarImpuestoPorGastos', '0' );
						ModificarConfig( 'UsarImpuestoSeparado', '0' );
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_gastos', '0');
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_separado', '0');
						
						Testing::BorrarDatos($sesion, $codigo_cliente);
						echo '<h3> Cobros sin impuestos. </h3>';
							$cobros = GenerarDatos( $codigo_cliente, $opc_moneda_total );
							ImprimirCobros( $cobros, true );
							ImprimirDocumentos( $cobros );
							ImprimirOpcionesReportes( $sesion, $codigo_cliente );
					}
				else if( $config == 'impuestos_honorarios' )
					{
						ModificarConfig( 'UsarImpuestoPorGastos', '0' );
						ModificarConfig( 'UsarImpuestoSeparado', '1' );
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_gastos', '0');
						CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_separado', '1');
						
						Testing::BorrarDatos($sesion, $codigo_cliente);
						echo '<h3> Cobros con impuestos a honorarios. </h3>';
							$cobros = GenerarDatos( $codigo_cliente, $opc_moneda_total );
							ImprimirCobros( $cobros, true );
							ImprimirDocumentos( $cobros );
							ImprimirOpcionesReportes( $sesion, $codigo_cliente );
					}
					
					ModificarConfig( 'UsarImpuestoPorGastos', $valor_usar_impuesto_gastos );
					ModificarConfig( 'UsarImpuestoSeparado', $valor_usar_impuesto );
					if( method_exists('Conf','GetConf') )
						{
							CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_separado', Conf::GetConf($sesion,'UsarImpuestoSeparado') );
							CambiarCampoContrato( '', $codigo_cliente, 'usa_impuesto_gastos', Conf::GetConf($sesion,'UsarImpuestoPorGastos') );
						}
			}
		}
	
	if( $opc == 'eliminar_datos' )
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'EsAmbientePrueba') ) || ( method_exists('Conf','EsAmbientePrueba') && Conf::EsAmbientePrueba() ) )
			{
				Testing::BorrarDatos( $sesion, $codigo_cliente);
			}
		}
	
	function GenerarDatos( $codigo_cliente, $opc_moneda_total )
	{
		global $sesion;
		$fecha_fin = date("Y-m-d", time());
		$fecha_ini = date("Y-m-d", mktime(0,0,0,date("m",time())-1,1,date("Y",time())));
		$max_dia = 1;
		$max_usuarios = 3;
		$min_gastos = 4;
		$max_gastos = 10;
		$min_tramites = 4;
		$max_tramites = 8;
			
		Testing::BorrarDatos( $sesion, $codigo_cliente );
		Testing::GenerarTrabajos( $sesion, $fecha_ini, $fecha_fin, $max_dia, $max_usuarios, $codigo_cliente );
		Testing::GenerarTramites( $sesion, $fecha_ini, $fecha_fin, $codigo_cliente, $min_tramites, $max_tramites, $max_usuarios);
		Testing::GenerarGastos( $sesion, $fecha_ini, $fecha_fin, $codigo_cliente, $min_gastos, $max_gastos, $opc_moneda_total);
		$cobros = Testing::GenerarCobros( $sesion, $fecha_ini, $fecha_fin, $codigo_cliente );
				
		return $cobros;
	}

	function CambiarCampoContrato( $id_contrato='', $codigo_cliente='', $campo, $valor )
	{
		global $sesion;
		if( $id_contrato != '' ) 
			$query = " UPDATE contrato SET ".$campo."='".$valor."' WHERE id_contrato='$id_contrato' ";
		else if( $codigo_cliente != '' )
			$query = " UPDATE contrato SET ".$campo."='".$valor."' WHERE codigo_cliente='$codigo_cliente' ";
		mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	}
	
	function ModificarConfig( $glosa_opcion, $valor_opcion ) 
	{
		global $sesion;
		$query = " UPDATE configuracion SET valor_opcion='".$valor_opcion."' WHERE glosa_opcion='".$glosa_opcion."' ";
		mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	}
	
	function ImprimirCobros( $cobros, $opciones=false )
	{
		global $sesion;
					$lista_cobros = implode( '\' ,\'', $cobros );
					$query = "SELECT SQL_CALC_FOUND_ROWS id_cobro, 
														forma_cobro, 
														id_moneda, 
														opc_moneda_total, 
														id_moneda_monto, 
														monto_subtotal, 
														monto_trabajos, 
														monto_tramites, 
														descuento, 
														impuesto, 
														monto, 
														subtotal_gastos, 
														impuesto_gastos, 
														monto_gastos 
											FROM cobro 
										 WHERE id_cobro IN ('".$lista_cobros."') ";
					$x_pag = 15;
					$b = new Buscador($sesion,$query,'Cobro',$desde,$x_pag,$orden);
					$b->nombre = "busc_cobro";
					$b->titulo = __('Listado de').' '.__('cobros');
					$b->AgregarEncabezado("id_cobro","ID","align=center");
					$b->AgregarEncabezado("forma_cobro","Forma cobro","align=center");
					$b->AgregarEncabezado("id_moneda",__('ID moneda'),"align=center");
					$b->AgregarEncabezado("opc_moneda_total",__('Opc mon tot'),"align=center");
					$b->AgregarEncabezado("id_moneda_monto",__('Mon Monto'),"align=center");
					$b->AgregarEncabezado("monto_subtotal",__('Subtotal'),"align=center");
					$b->AgregarEncabezado("monto_trabajos",__('mon trab'),"align=center");
					$b->AgregarEncabezado("monto_tramites",__('mon tram'),"align=center");
					$b->AgregarEncabezado("descuento",__('Descuento'),"align=center");
					$b->AgregarEncabezado("impuesto",__('IVA'),"align=center");
					$b->AgregarEncabezado("monto",__('Monto'),"align=center");
					$b->AgregarEncabezado("subtotal_gastos",__('Sub gastos'), "align=center");
					$b->AgregarEncabezado("impuesto_gastos",__('Imp Gastos'), "align=center");
					$b->AgregarEncabezado("monto_gastos",__('Gastos'), "align=center");
					if( $opciones )
						$b->AgregarFuncion("Opc.",'Opciones',"align=center nowrap");
					$b->color_mouse_over = "#bcff5c";
					$b->Imprimir();
	}
	
	function Opciones(& $fila)
	{
		$opc_html = "<a href='javascript:void(0)' onclick=\"nuevaVentana('Generar Cobro',750,660,'cobros5.php?popup=1&id_cobro=".$fila->fields['id_cobro']."');\"'><img src=".Conf::ImgDir()."/editar_on.gif border=0></a>";
		return $opc_html;
	}
	
	function ImprimirDocumentos( $cobros ) 
	{
		global $sesion;
					$lista_cobros = implode( '\' ,\'', $cobros );
					$query_doc = "SELECT SQL_CALC_FOUND_ROWS documento.id_cobro, 
																		cobro.forma_cobro, 
																		documento.id_moneda, 
																		documento.subtotal_honorarios, 
																		documento.monto_trabajos, 
																		documento.monto_tramites, 		
																		documento.subtotal_sin_descuento, 
																		documento.descuento_honorarios, 
																		documento.honorarios, 
																		documento.subtotal_gastos, 
																		documento.subtotal_gastos_sin_impuesto,
																		documento.gastos, 
																		documento.impuesto, 
																		documento.monto, 
																		documento.tipo_doc 
													FROM documento 
													JOIN cobro USING( id_cobro ) 
												 WHERE documento.id_cobro IN ('".$lista_cobros."') ";
					$x_pag = 15;
					$b = new Buscador($sesion,$query_doc,'Documento',$desde,$x_pag,$orden);
					$b->nombre = "busc_documento";
					$b->titulo = __('Listado de').' '.__('documentos');
					$b->AgregarEncabezado("documento.id_cobro","ID","align=center");
					$b->AgregarEncabezado("cobro.forma_cobro","ID","align=center");
					$b->AgregarEncabezado("documento.tipo_doc","tipo doc","align=center");
					$b->AgregarEncabezado("documento.id_moneda",__('ID moneda'),"align=center");
					$b->AgregarEncabezado("documento.subtotal_honorarios",__('Subtotal'),"align=center");
					$b->AgregarEncabezado("documento.monto_trabajos",__('Mon trab'),"align=center");
					$b->AgregarEncabezado("documento.monto_tramites",__('Mon tram'),"align=center");
					$b->AgregarEncabezado("documento.subtotal_sin_descuento",__('con descuento'),"align=center");
					$b->AgregarEncabezado("documento.descuento_honorarios",__('desc hon'),"align=center");
					$b->AgregarEncabezado("documento.honorarios",__('honorarios'),"align=center");
					$b->AgregarEncabezado("documento.subtotal_gastos",__('sub gastos'),"align=center");
					$b->AgregarEncabezado("documento.subtotal_gastos_sin_impuesto",__('G SIN IVA'),"align=center");
					$b->AgregarEncabezado("documento.gastos",__('Gastos'),"align=center");
					$b->AgregarEncabezado("documento.impuesto",__('IVA'),"align=center");
					$b->AgregarEncabezado("documento.monto",__('Monto'),"align=center");
					$b->color_mouse_over = "#bcff5c";
					$b->Imprimir();
	}
	
	function ImprimirOpcionesReportes( $sesion, $codigo_cliente )
	{
		$fecha_fin = date("Y-m-d", time());
		$fecha_ini = date("Y-m-d", mktime(0,0,0,date("m",time())-1,1,date("Y",time())));
		$clientes = array();
		array_push($clientes,$codigo_cliente); 
		echo "<table>
					<tr><td align=center>
					<form action=\"planillas/planilla_resumen_cobranza2.php\" method=\"POST\">
					<input type=hidden name=xls value=1 />
					<input type=hidden name=\"clientes[]\" value=\"".$codigo_cliente."\" />
					<input type=hidden name=fecha_ini value='".$fecha_ini."' />
					<input type=hidden name=fecha_fin value='".$fecha_fin."' />
					".Html::SelectQuery( $sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","moneda", '1', '','',"80")."
					<input type=submit value='Descargar Reporte Cobranza' />
					</form>
					</td></tr>
					<tr><td align=center>
					<form action=\"planillas/planilla_participacion_abogado2.php\" method=\"POST\">
					<input type=hidden name=xls value=1 />
					<input type=hidden name=\"clientes[]\" value=\"".$codigo_cliente."\" />
					<input type=hidden name=fecha_ini value='".$fecha_ini."' />
					<input type=hidden name=fecha_fin value='".$fecha_fin."' />
					".Html::SelectQuery( $sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","moneda", '1', '','',"80")."
					<input type=submit value='Descargar Reporte Participacion Abogado' />
					</form>
					</td></tr>
					<tr><td align=center>
					<form action=\"planillas/planilla_morosidad2.php\" method=\"POST\">
					<input type=hidden name=xls value=1 />
					<input type=hidden name=\"clientes[]\" value=\"".$codigo_cliente."\" />
					<input type=submit value='Descargar Reporte morosidad' />
					</form>
					</td></tr>
					</table>";
	} 
	 
	$pagina->PrintBottom();
	?>
