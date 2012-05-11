<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
  require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Carpeta.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	
	require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion(array('EDI'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$carpeta = new Carpeta($sesion);
	if($id_carpeta > 0)
	{
		$carpeta->Load($id_carpeta);
		if(!$codigo_asunto_secundario)
		{
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigo($carpeta->fields['codigo_asunto']);
			$codigo_asunto_secundario=$asunto->fields['codigo_asunto_secundario'];
		}
		else
		{
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
			$codigo_asunto = $asunto->fields['codigo_asunto'];
		}
		if(!$codigo_cliente && !$codigo_cliente_secundario)
		{
			$codigo_cliente=$asunto->fields['codigo_cliente'];
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($codigo_cliente);
			$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
		}
	}
	else
	{
		$codigo_carpeta=$carpeta->AsignarCodigoCarpeta();
		if($codigo_asunto_secundario)
		{
			$asunto = new Asunto($sesion);
			$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
			$codigo_asunto=$asunto->fields['codigo_asunto'];
		}
		if($codigo_cliente_secundario)
		{
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
			$codigo_cliente=$cliente->fields['codigo_cliente'];
		}
	}
		
	if($opcion == 'guardar')
	{
		
		$carpeta->Edit('codigo_carpeta', $codigo_carpeta);
		$carpeta->Edit('glosa_carpeta', $glosa_carpeta);
		$carpeta->Edit('nombre_carpeta',$nombre_carpeta);
		$carpeta->Edit('codigo_asunto', $codigo_asunto);
		$carpeta->Edit('id_tipo_carpeta',$id_tipo_carpeta);
		$carpeta->Edit('id_bodega',$id_bodega);
		if ( UtilesApp::GetConf($sesion, 'MostrarLinkCarpeta') ) {
			$carpeta->Edit('link_carpeta',$link_carpeta);
		}
		

		if($carpeta->Write())
		{
			$pagina->AddInfo( __('Archivo guardado con exito') );
		}
		else
			$pagina->AddError($carpeta->error);
	}

	$txt_pagina = $id_carpeta ? __('Edición de Carpeta') : __('Ingreso de Carpeta');

	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>
<script>

function Validar()
{
	var form = $('form_agregar_carpeta');

	if(!form.glosa_carpeta.value)
	{
		alert("<?php echo __('Ud. debe ingresar el título del archivo')?>");
		form.glosa_carpeta.focus();
        return false;
	}
<?php
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
				echo "if(!form.codigo_asunto_secundario.value){";
			}
			else
			{
				echo "if(!form.codigo_asunto.value){";
			}
?>
			alert("<?php echo __('Debe seleccionar un').' '.__('asunto')?>");
<?php
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
				echo "form.codigo_asunto_secundario.focus();";
			}
			else
			{
				echo "form.codigo_asunto.focus();";
			}
?>
			return false;
    }
<?php
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
				echo "if(!form.codigo_cliente_secundario.value){";
			}
			else
			{
				echo "if(!form.codigo_cliente.value){";
			}
?>
			alert("<?php echo __('Debe seleccionar un').' '.__('cliente')?>");
<?php
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
			{
				echo "form.codigo_cliente_secundario.focus();";
			}
			else
			{
				echo "form.codigo_cliente.focus();";
			}
?>
			return false;
    }
form.submit();
return true;
}
</script>
<?php echo Autocompletador::CSS(); ?>
<form method="post" action="agregar_carpeta.php" name="form_agregar_carpeta" id="form_agregar_carpeta">
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=popup value="1" />
<input type=hidden name=codigo_carpeta value="<?php echo  $carpeta->fields['codigo_carpeta'] ?>" />
<input type=hidden name=id_carpeta value="<?php echo  $carpeta->fields['id_carpeta'] ?>" />

<br>
<table width='90%'>
	<tr>
		<td align=left><b><?php echo $txt_pagina ?></b></td>
	</tr>
</table>
<br>

<table style="border: 1px solid black;" width='90%'>
	<tr>
		<td align=right>
			<?php echo __('Nº Archivo')?>
		</td>
		<td align=left>
			<input name="codigo_carpeta" size="5" maxlength="5" readonly value="<?php echo $carpeta->fields['codigo_carpeta']?>" id="codigo_carpeta" />
	<?php if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'SistemaCarpetasEspecial') ) || ( method_exists('Conf','SistemaCarpetasEspecial') && Conf::SistemaCarpetasEspecial() ) ){?>
			<?php echo __('Nombre')?>
			<input name='nombre_carpeta' size='35' value="<?php echo  $carpeta->fields['nombre_carpeta'] ?>" />
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Contenido')?>
		</td>
		<td align=left>
	<?php } else {?>
			<?php echo __('Contenido')?>
	<?php } ?>
			<input name='glosa_carpeta' size='60' value="<?php echo  $carpeta->fields['glosa_carpeta'] ?>" />
			<span style="color:#FF0000; font-size:10px">*</span>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Cliente')?>
		</td>
		<td align=left nowrap>
			<?php
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
							echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario);
						else 
							echo Autocompletador::ImprimirSelector($sesion,$codigo_cliente);
					}
				else
					{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						{
							echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto_secundario);
						}
						else
						{
							echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
						}
					}
?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Asunto')?>
		</td>
		<td align=left >
			<?php
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,  $codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $carpeta->fields['codigo_asunto'],"", "CargarSelectCliente(this.value);", 320,  $codigo_cliente);
					}
?>
			<br />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Ubicación')?>
		</td>
		<td align=left >
			<?php echo  Html::SelectQuery($sesion, "SELECT * FROM bodega ORDER BY glosa_bodega","id_bodega", $id_bodega ? $id_bodega : $carpeta->fields['id_bodega'],"onchange='cambia_bodega(this.value)'","","120"); ?>
			<?php echo __('Tipo')?>
			<?php echo  Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_carpeta ORDER BY glosa_tipo_carpeta","id_tipo_carpeta", $id_tipo_carpeta ? $id_tipo_carpeta : $carpeta->fields['id_tipo_carpeta'],"onchange='cambia_bodega(this.value)'","","120"); ?>
		</td>
	</tr>
	<?php if ( UtilesApp::GetConf($sesion, 'MostrarLinkCarpeta') ) { 
				if( strlen($carpeta->fields['link_carpeta']) > 0 ) {
					if(substr($carpeta->fields['link_carpeta'], 0, 7) == 'http://' || substr($carpeta->fields['link_carpeta'], 0, 6) == 'ftp://') {
						$_enlace_tmp = $carpeta->fields['link_carpeta'];
					} else {
						$_enlace_tmp = "http://" . $carpeta->fields['link_carpeta'];
					}
				} else {
					$_enlace_tmp = "javascript:;";
				}
			
		?>
	<tr>
		<td align=right>
			<?php echo __('Link')?>
		</td>
		<td align=left >
			<input name='link_carpeta' size='75' value="<?php echo  $carpeta->fields['link_carpeta'] ?>" onkeyup="actualiza_link(this.value);" /> <a href="<?php echo $_enlace_tmp; ?>" id="enlace_carpeta" target="_blank" title="Abrir link en una ventana nueva"><img  alt="" src="<?php echo Conf::ImgDir()?>/ver_16.gif" /></a>
		</td>
	</tr>
	<?php } ?>
	<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td colspan=2 align="center">
			<input type="button" class=btn value="<?php echo __('Guardar')?>" onclick="return Validar()" />
		</td>
	</tr>
	<?php if($id_carpeta){ ?>
<tr>
        <td align=right colspan=2><img src='<?php echo Conf::ImgDir()?>/agregar.gif' border=0> 
        <a href='agregar_carpeta.php?popup=1'>Agregar nuevo archivo</a></td></tr>
        	<?php } ?>
        			<?php if($carpeta->Loaded()){ ?>
	<tr><td colspan=2 align='center'>
		<?php
			if($carpeta->fields['id_tipo_movimiento_carpeta'] > 0)
			{
				$query = "SELECT CONCAT_WS(' ',usuario.nombre,usuario.apellido1,usuario.apellido2) as nombre_abogado,
										CONCAT_WS(' ',usuario_modificacion.nombre,usuario_modificacion.apellido1,usuario_modificacion.apellido2) as nombre_modificador,
										carpeta.fecha_modificacion, prm_tipo_movimiento_carpeta.glosa_tipo_movimiento_carpeta
										FROM carpeta
										LEFT JOIN prm_tipo_movimiento_carpeta ON prm_tipo_movimiento_carpeta.id_tipo_movimiento_carpeta=carpeta.id_tipo_movimiento_carpeta
										LEFT JOIN usuario ON usuario.id_usuario=carpeta.id_usuario_ultimo_movimiento
										LEFT JOIN usuario AS usuario_modificacion ON usuario_modificacion.id_usuario=carpeta.id_usuario_modificacion
										WHERE id_carpeta=".$carpeta->fields['id_carpeta'];
				$resp = mysql_query($query, $sesion->dbh);
				$row = mysql_fetch_array($resp);
				echo "<span style='font-size:10pt;font-style:italic'>".$row['glosa_tipo_movimiento_carpeta']." por ".$row['nombre_abogado']." con fecha ".Utiles::sql2date($row['fecha_modificacion'])." (".$row['nombre_modificador'].").</span>";
			}
			else
			{
				echo "<span style='font-size:10pt;font-style:italic'>No existe movimiento.</span>";
			}
		?>
	</td>
</tr>
<?php } ?>
</table>
	
</form>
<br/><br/>
<?php
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion));
	}
	echo(InputId::Javascript($sesion));
	
	if ( UtilesApp::GetConf($sesion, 'MostrarLinkCarpeta') ) {
?>
<script type="text/javascript">
function actualiza_link(enlace){
	if( enlace.length >= 5 ) {
		if( enlace.substring(0,7) == 'http://' || enlace.substring(0,6) == 'ftp://' ) {
			jQuery('#enlace_carpeta').attr('href',enlace);
		} else {
			jQuery('#enlace_carpeta').attr('href','http://'+enlace);			
		}
	} else {
		jQuery('#enlace_carpeta').attr('href','javascript:;');
	}
}
</script>
<?php
	}
	$pagina->PrintBottom($popup);
?>