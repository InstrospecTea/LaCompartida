<?
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
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';


	$sesion = new Sesion(array('DAT','LEE'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	$params_array['codigo_permiso'] = 'EDI';
	$permisos = $sesion->usuario->permisos->Find('FindPermiso',$params_array); #tiene permiso de edicion de carpetas
	if($permisos->fields['permitido'])
		$p_edicion = true;
	else
		$p_edicion = false;
		
	$carpeta = new Carpeta($sesion);
	if($id_carpeta > 0)
		$carpeta->Load($id_carpeta);
	
	if($opcion == 'eliminar')
	{
		if(!$carpeta->Eliminar())
			$pagina->AddError($carpeta->error);
		else
			$pagina->AddInfo(__('Archivo').' '.__('eliminado con éxito'));
	}
	
	if($codigo_cliente_secundario)
	{
		$cliente=new Cliente($sesion);
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente=$cliente->fields['codigo_cliente'];
	}
	if($codigo_asunto_secundario)
	{
		$asunto=new Asunto($sesion);
		$asunto->LoadByCodigoSecundario($codigo_asunto_secundario);
		$codigo_asunto=$asunto->fields['codigo_asunto'];
	}
	$where = base64_decode($where);
	if($where=='')
	$where = '1';
	if($glosa_carpeta != '')
	{
		$contenido = strtr($glosa_carpeta, ' ', '%' );
	$where .= " AND carpeta.glosa_carpeta LIKE '%$contenido%'";
	}
	if($nombre_carpeta != '')
	{
		$nombre = strtr($nombre_carpeta, ' ', '%' );
		$where .= " AND carpeta.nombre_carpeta LIKE '%$nombre%'";
	}
	if( $codigo_carpeta != '')
		$where .= " AND carpeta.codigo_carpeta = '".$codigo_carpeta."'";
	if ( $codigo_asunto > 0 )
		$where .= " AND carpeta.codigo_asunto = '".$codigo_asunto."'";
	if ( $codigo_cliente > 0 )
		$where .= " AND cliente.codigo_cliente = '".$codigo_cliente."'";
	if ( $id_bodega > 0 )
		$where .= " AND bodega.id_bodega = ".$id_bodega."";
	if ( $id_tipo_carpeta > 0 )
		$where .= " AND prm_tipo_carpeta.id_tipo_carpeta = ".$id_tipo_carpeta."";
	if ( $id_tipo_movimiento_carpeta > 0 )
		$where .= " AND prm_tipo_movimiento_carpeta.id_tipo_movimiento_carpeta = ".$id_tipo_movimiento_carpeta."";
	if ( $id_usuario_ultimo_movimiento > 0 )
		$where .= " AND usuario.id_usuario = ".$id_usuario_ultimo_movimiento."";
	$query = "SELECT SQL_CALC_FOUND_ROWS *
						FROM carpeta
						LEFT JOIN asunto USING (codigo_asunto)
						LEFT JOIN cliente USING (codigo_cliente) 
						LEFT JOIN bodega ON carpeta.id_bodega = bodega.id_bodega
						LEFT JOIN prm_tipo_carpeta ON carpeta.id_tipo_carpeta = prm_tipo_carpeta.id_tipo_carpeta
						LEFT JOIN prm_tipo_movimiento_carpeta ON prm_tipo_movimiento_carpeta.id_tipo_movimiento_carpeta=carpeta.id_tipo_movimiento_carpeta
						LEFT JOIN usuario ON usuario.id_usuario=carpeta.id_usuario_ultimo_movimiento
						WHERE $where
						";

	if($orden == "")
		$orden = "glosa_carpeta";
	if($buscar)
    {
		$x_pag = 20;
					
		$b = new Buscador($sesion, $query, "Carpeta", $desde, $x_pag, $orden);
		$b->AgregarEncabezado("codigo_carpeta",__('Nº Archivo'),"align=center");
		if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
			$b->AgregarEncabezado("codigo_asunto_secundario",__('Código'),"align=left");
		else
			$b->AgregarEncabezado("glosa_cliente",_('Cliente'),"align=left");
		$b->AgregarEncabezado("glosa_asunto",__('Asunto'),"align=left");
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'SistemaCarpetasEspecial') ) || ( method_exists('Conf','SistemaCarpetasEspecial') && Conf::SistemaCarpetasEspecial() ) )
			$b->AgregarEncabezado("nombre_carpeta",__('Nombre'),"align=left");
		$b->AgregarEncabezado("glosa_carpeta",__('Contenido'),"align=left");
		$b->AgregarEncabezado("glosa_tipo_carpeta",__('Tipo'),"align=center");
		$b->AgregarEncabezado("glosa_bodega",__('Ubicación'),"align=center");
		$b->AgregarEncabezado("glosa_tipo_movimiento_carpeta",__('Estado'),"align=center");
		$b->AgregarEncabezado("username",__('Persona'),"align=center");
		if ($p_edicion)
  			$b->AgregarFuncion("","Opciones","align=center nowrap");
		$b->color_mouse_over = "#bcff5c";
	}	
  function Opciones(& $fila)
  {
  		global $sesion;
			global $desde;
			$id_carpeta = $fila->fields['id_carpeta'];
			$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuevaVentana('Mover_Carpeta',730,300,'agregar_carpeta_movimiento.php?id_carpeta=$id_carpeta&popup=1','top=100, left=155');\"><img src='".Conf::ImgDir()."/encuesta_xresp16.gif' border=0 title='".__('Mover carpeta')."' alt='' /></a>&nbsp;";
			$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Carpeta',730,300,'agregar_carpeta.php?id_carpeta=$id_carpeta&popup=1','top=100, left=155');\"><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title='".__('Editar carpeta')."' alt='' /></a>&nbsp;";
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
				$html_opcion .= "<a target='_top' onclick=\"return confirm('¿".__('Está seguro de eliminar la')." ".__('carpeta')."?');\" href=?id_carpeta=$id_carpeta&opcion=eliminar&desde=$desde><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 alt='Eliminar' /></a>";
    	else
    		$html_opcion .= "<a target='_top' onclick=\"return confirm('¿".__('Está seguro de eliminar la')." ".__('carpeta')."?');\" href=?id_carpeta=$id_carpeta&opcion=eliminar&desde=$desde><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 alt='Eliminar' /></a>";
    	return $html_opcion;
  }
	if($excel)
	{
		$b1 = new Buscador($sesion, $query, "Carpeta", 0, 6500, $orden);	
		$lista = $b1->lista;
		require_once Conf::ServerDir().'/interfaces/carpetas.xls.php';
		exit;
	}
		
	$pagina->titulo = __('Archivo');
	$pagina->PrintTop();
	if ($p_edicion)
		$NuevoArchivo = "<tr>
        <td align=right colspan=2><a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Carpeta',730,300,'agregar_carpeta.php?id_carpeta=$id_carpeta&popup=1','top=100, left=155');\"><img src='".Conf::ImgDir()."/agregar.gif' border=0 title='".__('Agregar carpeta')."' alt='' />".__('Agregar carpeta')."</a></td></tr>";
  else
  	$NuevoArchivo = "";
    
?>
<script type="text/javascript">
	function Listar( form, from )
	{
		if(from == 'buscar')
			form.action = 'carpeta.php?buscar=1';
		else
			return false;
		
		form.submit();
		return true;
	}
	function EliminarCarpeta( id , desde)
	{
		if( parseInt(id)>0 && confirm('¿Desea eliminar el archivo seleccionado') == true )
		{
			var url = '?id_carpeta='+id+'&opcion=eliminar&desde='+desde;
			self.location.href = url;
		}
	}
	
	function Validar()
	{
		var form = document.getElementById('form_carpetas');
		form.submit();
		return true;
	}
	
	function descargarPlanilla(form)
	{
		alert('hola')
		var xopcion = form.opcion.value
		var xcodigo_carpeta = form.codigo_carpeta.value
		var xid_carpeta = form.id_carpeta.value
		//var xnombre_carpeta = form.nombre_carpeta.value
		//var xcodigo_cliente_secundario = form.codigo_cliente_secundario.value
		var xglosa_carpeta = form.glosa_carpeta.value
		//var xcodigo_asunto_secundario = form.codigo_asunto_secundario.value
		var xid_bodega = form.id_bodega.value
		var xid_tipo_carpeta = form.id_tipo_carpeta.value
		var xid_tipo_movimiento_carpeta = form.id_tipo_movimiento_carpeta.value
		var xid_usuario_ultimo_movimiento = form.id_usuario_ultimo_movimiento.value
		window.open('carpeta.php?excel=1&xopcion=opcion&codigo_carpeta='+xcodigo_carpeta
														+'&id_carpeta='+xid_carpeta
														//+'&nombre_carpeta='+xnombre_carpeta
														//+'&codigo_cliente_secundario='+xcodigo_cliente_secundario
														+'&glosa_carpeta='+xglosa_carpeta
														//+'&codigo_asunto_secundario='+xcodigo_asunto_secundario
														+'&id_bodega='+xid_bodega
														+'&id_tipo_carpeta='+xid_tipo_carpeta
														+'&id_tipo_movimiento_carpeta='+xid_tipo_movimiento_carpeta
														+'&id_usuario_ultimo_movimiento='+xid_usuario_ultimo_movimiento);
	}
	
</script>
<? echo Autocompletador::CSS(); ?>
<form method="post" action="<?= $_SERVER[PHP_SELF] ?>" name="form_carpetas" id="form_carpetas">
<input type=hidden name=opcion value="buscar" />
<!--input type=hidden name=codigo_carpeta value="<?= $carpeta->fields['codigo_carpeta'] ?>" />-->
<input type=hidden name=id_carpeta value="<?= $carpeta->fields['id_carpeta'] ?>" />

<table class="border_plomo tb_base" width="90%">
	<tr>
		<td align=right>
			<?=__('Nº Carpeta')?>
		</td>
		<td align=left>
			<input name="codigo_carpeta" size="5" maxlength="5" value="<?=$codigo_carpeta ?>" id="codigo_carpeta" />
			<? if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'SistemaCarpetasEspecial') ) || ( method_exists('Conf','SistemaCarpetasEspecial') && Conf::SistemaCarpetasEspecial() ) ){?>
			<?=__('Nombre')?>
			<input name='nombre_carpeta' size='35' value="<?=$nombre_carpeta ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Contenido')?>
		</td>
		<td align=left>
	<? } else {?>
			<?=__('Contenido')?>
	<? } ?>
			<input name='glosa_carpeta' size='60' value="<?=$glosa_carpeta ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left nowrap>
			<?
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario);
					else
						echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
				}
			else	
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos');", 320,"",true);
					else
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');", 320,"",true);
				}
			?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Asunto')?>
		</td>
		<td align=left >
			<?
					if (( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ))
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargarSelectCliente(this.value);", 320,  $codigo_cliente_secundario,true);
					else
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $codigo_asunto,"", "CargarSelectCliente(this.value);", 320,  $codigo_cliente,true);
			?>
		<br />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Ubicación')?>
		</td>
		<td align=left >
			<?= Html::SelectQuery($sesion, "SELECT * FROM bodega ORDER BY glosa_bodega","id_bodega", $id_bodega ? $id_bodega : $carpeta->fields['id_bodega'],"onchange='cambia_bodega(this.value)'","Cualquiera","120"); ?>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<?=__('Tipo')?>
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_carpeta ORDER BY glosa_tipo_carpeta","id_tipo_carpeta", $id_tipo_carpeta ? $id_tipo_carpeta : $carpeta->fields['id_tipo_carpeta'],"onchange='cambia_tipo_carpeta(this.value)'","Cualquiera","120"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Estado')?>
		</td>
		<td align=left >
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_movimiento_carpeta ORDER BY glosa_tipo_movimiento_carpeta","id_tipo_movimiento_carpeta", $id_tipo_movimiento_carpeta ? $id_tipo_movimiento_carpeta : $carpeta->fields['id_tipo_movimiento_carpeta'],"","Cualquiera","120"); ?>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<?=__('Persona')?>
			<?= Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario WHERE visible=1","id_usuario_ultimo_movimiento", $id_usuario_ultimo_movimiento ? $id_usuario_ultimo_movimiento : $carpeta->fields['id_usuario_ultimo_movimiento'],"","Cualquiera","200"); ?>
		</td>
	</tr>
	<tr>
		<td align=center colspan=2>
    	<input type=button class=btn name=buscar value=<?=__('Buscar')?> onclick="Listar(this.form, 'buscar')">
    	<input type=button class=btn value="<?=__('Descargar listado a Excel')?>" onclick="descargarPlanilla(this.form)">
		</td>
	</tr>
	<?= $NuevoArchivo ?>
 </table>
</form>

<?
if($buscar)
	$b->Imprimir();
echo(InputId::Javascript($sesion));
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
{
	echo(Autocompletador::Javascript($sesion));
}
	
	$pagina->PrintBottom();
?>
