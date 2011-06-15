<? 
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	
	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	
	$popup = true;
	
	$pagina->PrintTop($popup);
?>
	
	<script type="text/javascript">
		function confirmar(form)
			{
				if(confirm("Esta subiendo la información administrativa,\n ¿Desea continuar?"))
					{
						form.submit();
					}
			}
	</script>
	
	<form name="form_archivo" id="form_archivo" method="post" action="" enctype="multipart/form-data">
	<table width="100%">
		<tr>
			<td align="center">
					<input type="hidden" name="opc" id="opc" value="subir_excel" />
					Subir excel datos implementacion: <input type="file" name="archivo_data" />
					<input type="button" value="<?=__('Cargar Documento')?>" class="btn" onclick="confirmar(this.form);" />
					<br />
			</td>
		</tr>
		<tr>
			<td align="center">
				Numero clientes: <input type="text" name="num_clientes" id="num_clientes" size="3" />
			<td>
		</tr>
		<tr>
			<td align="center">
				Numero asuntos: <input type="text" name="num_asuntos" id="num_asuntos" size="3" />
			<td>
		</tr>
		<tr>
			<td align="center">
				Numero usuarios: <input type="text" name="num_usuarios" id="num_usuarios" size="3" />
			<td>
		</tr>
	</table>
	</form>
	
<?
	if( $opc == 'subir_excel' ) 
		{
		if(!$archivo_data["tmp_name"])
			{
				echo __('Debe seleccionar un archivo a subir.');
				exit;
			}
		require_once Conf::ServerDir().'/classes/ExcelReader.php';
		$excel = new Spreadsheet_Excel_Reader();
		if(!$excel->read($archivo_data["tmp_name"]))
			{
				echo __('Error, el archivo no se puede leer, intente nuevamente.');
			}
			
			foreach($excel->sheets as $index => $hoja)
				{
					if( $index == 0 )
					{
						$col_codigo = 1;
						$col_glosa_cliente = 2;
						$col_rut = 3;
						$col_razon_social = 4;
						$col_direccion = 5;
						$col_giro = 7;
						$col_nombre_contacto = 8;
						$col_telefono_contacto = 9;
						$col_mail_contacto = 10;
						
						$query_cliente = "INSERT INTO cliente ( codigo_cliente, codigo_cliente_secundario, glosa_cliente, rut, rsocial, direccion, giro, nombre_contacto, fono_contacto, mail_contacto ) VALUES "; 
						$query_contrato = "INSERT INTO contrato ( codigo_cliente, contacto, apellido_contacto, fono_contacto, email_contacto, fecha_creacion, fecha_modificacion, rut, factura_razon_social, factura_giro, factura_direccion, factura_telefono, usa_impuesto_separado, usa_impuesto_gastos ) VALUES ";
							$j=2;
							$query_cliente_datos = array();
							$query_contrato_datos = array();
							while( $j < $num_clientes ) 
							{
								list( $nombre, $apellido1, $apellido2, $extra1, $extra2 ) = split( ' ',$hoja['cells'][$j][$col_nombre_contacto]);
								$apellido = $apellido1.' '.$apellido2.' '.$extra1.' '.$extra2;
								$query_cliente_dato = " ( '".$hoja['cells'][$j][$col_codigo]."', '".$hoja['cells'][$j][$col_codigo]."', '".$hoja['cells'][$j][$col_glosa_cliente]."',
																			'".$hoja['cells'][$j][$col_rut]."', '".$hoja['cells'][$j][$col_razon_social]."', '".$hoja['cells'][$j][$col_direccion]."', 
																			'".$hoja['cells'][$j][$col_giro]."', '".$hoja['cells'][$j][$col_nombre_contacto]."', '".$hoja['cells'][$j][$col_telefono_contacto]."',
																			'".$hoja['cells'][$j][$col_mail_contacto]."' ) ";
								$query_contrato_dato = " ( '".$hoja['cells'][$j][$col_codigo]."', '".$nombre."', '".$apellido."', '".$hoja['cells'][$j][$col_telefono_contacto]."',
																			 '".$hoja['cells'][$j][$col_mail_contacto]."', NOW(), NOW(), rut, '".$hoja['cells'][$j][$col_rut]."',
																			 '".$hoja['cells'][$j][$col_factura_razon_social]."', '".$hoja['cells'][$j][$col_giro]."', '".$hoja['cells'][$j][$col_direccion]."', 
																			 '".$hoja['cells'][$j][$col_telefono_contacto]."', 
																			 '".( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'UsarImpuestoSeparado') : ( method_exists('Conf','UsarImpuestoSeparado') ? Conf::UsarImpuestoSeparado() : '' ) )."',
																			 '".( method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'UsarImpuestoPorGastos') : ( method_exists('Conf','UsarImpuestoPorGastos') ? Conf::UsarImpuestoPorGastos() : '' ) )."' ) ";
								array_push( $query_cliente_datos, $query_cliente_dato );
								array_push( $query_contrato_datos, $query_contrato_dato );
							}
							for($z=0;$z<count($query_cliente_datos);$z++)
							{
								$query_cliente .= $query_cliente_datos[$z];
								$query_contrato .= $query_contrato_datos[$z];
								if( $z == count($query_cliente_datos)-1 )
									{
										$query_cliente .= "; ";
										$query_contrato .= "; ";
									}
								else
									{
										$query_cliente = ", ";
										$query_contrato = ", ";
									}
							}
							echo $query_cliente;
							echo '<br><br>';
							echo $query_contrato;
					}
				}
		}
		
	$pagina->PrintBottom($popup);
?>
