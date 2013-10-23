<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Actividad.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

	$sesion = new Sesion(array('DAT'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$actividad = new Actividad($sesion);
	if($id_actividad > 0)
		$actividad->Load($id_actividad);
	else
		$codigo_actividad=$actividad->AsignarCodigoActividad();
	if($opcion == 'guardar')
	{
		if($codigo_asunto == '')
			$codigo_asunto = 'NULL';
		$actividad->Edit('codigo_actividad', $codigo_actividad);
		$actividad->Edit('glosa_actividad', $glosa_actividad);
		$actividad->Edit('codigo_asunto', $codigo_asunto);

		if($actividad->Write())
			$pagina->AddInfo( __('Actividad guardada con exito') );
		else
			$pagina->AddError($actividad->error);
	} else if($opcion == 'eliminar') {
		if(!$actividad->Eliminar())
			$pagina->AddError($actividad->error);
	} else if ($opcion == 'buscar') {
		$buscar = 1;
	}
	
	$tooltip = Html::Tooltip(str_replace('%s', __('asunto'), __('Para agregar una actividad genérica deja el cliente y el %s en blanco.<br>Si quieres asignarla a un %s en particular, selecciónalo de la lista.')));

	$pagina->titulo = __('Ingreso de actividad');
	$pagina->PrintTop();
?>
<script type="text/javascript">

	jQuery(document).ready(function() {
        jQuery("#agregar_actividad").click(function() {
			nuovaFinestra('Agregar_Asunto',670,300,'agregar_actividades.php?popup=1&opcion=agregar'); 
        });

    });

	function EliminarActividad( id , desde)
	{
		if( parseInt(id)>0 && confirm('¿Desea eliminar la actividad seleccionada') == true )
		{
			var url = '?id_actividad='+id+'&opcion=eliminar&desde='+desde;
			self.location.href = url;
		}
	}

	function go() {
		var form = document.getElementById('form_actividades');
		var cod_act = document.getElementById('codigo_actividad');

		form.action = "actividades.php?buscar=1&cod_act="+cod_act.value;
		form.submit();
	}

	function Refrescar() {
		self.location.reload(); 
	}
</script>


<? echo Autocompletador::CSS(); ?>
<form method="post" action="actividades.php" name="form_actividades" id="form_actividades">
<input type="hidden" name="opcion" value="buscar" />
<input type="hidden" name="codigo_actividad" value="<?= $actividad->fields['codigo_actividad'] ?>" />
<input type="hidden" name="id_actividad" value="<?= $actividad->fields['id_actividad'] ?>" />

<table style="border: 0px solid black" width='90%'>
	<tr>
		<td></td>
		<td colspan="3" align="right">
			<a href="#" class="btn botonizame" icon="agregar" id="agregar_actividad" title="<?php echo __('Agregar') ?>" onclick=""><?php echo __('Agregar') . ' ' . __('Actividad') ?></a>
		</td>
	</tr>
</table>
<table style="border: 1px solid #BDBDBD;" class="tb_base" width="90%">
	<tr>
		<td align=right>
			<?=__('Código')?>
		</td>
		<td align=left>
			<input name="codigo_actividad" size="5" maxlength="5" value="<?=$actividad->fields['codigo_actividad']?>" id="codigo_actividad" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Título')?>
		</td>
		<td align=left>
			<input <?= $tooltip ?> name='glosa_actividad' id='glosa_actividad' size='35' value="<?= $actividad->fields['glosa_actividad'] ?>" />
			<span style="color:#FF0000; font-size:10px">*</span>
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
			echo Autocompletador::ImprimirSelector($sesion,'',$codigo_cliente_secundario);
		else
			echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
	}
	else
	{
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,""           ,"CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320,$codigo_asunto_secundario);
		}
		else
		{
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320,$codigo_asunto);
		}
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
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto_secundario","glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario,"","CargaIdioma(this.value);CargarSelectCliente(this.value);", 320,$codigo_cliente_secundario);
					}
					else
					{
						echo InputId::Imprimir($sesion,"asunto","codigo_asunto","glosa_asunto", "codigo_asunto", $tramite->fields['codigo_asunto'] ? $tramite->fields['codigo_asunto'] : $codigo_asunto ,"","CargaIdioma(this.value); CargarSelectCliente(this.value);", 320,$codigo_cliente);
					}

				?>
            <br />
            <font style="font-size: 0.8em">
            	<?=__('Para ingresar una actividad genérica, deje el campo asunto en blanco y estará disponible para todos los asuntos')?> 
            </font>
		</td>
	</tr>
	<tr>
		<td colspan=2 align="center">
			<a href="#" class="btn botonizame" icon="find" id="buscar_actividad" title="<?php echo __('Buscar') ?>" onclick="go()"><?php echo __('Buscar') ?></a>
		</td>
	</tr>

</table>
	
</form>
<br/><br/>

<?

	if ($buscar) {
		$query = "SELECT SQL_CALC_FOUND_ROWS *
					FROM actividad
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN cliente USING (codigo_cliente)
					";

		if ($cod_act != '') {
		 		$where .= ' codigo_actividad = "'.$cod_act.'"';
		 		$whereOn = 1;
		}
		if ($glosa_actividad != '') {
			if (!$whereOn) {
				$where .= ' glosa_actividad = "'.$glosa_actividad.'"';
				$whereOn = 1;
			} else {
				$where .= ' AND glosa_actividad = "'.$glosa_actividad.'"';
			}
		}
		if ($codigo_cliente != '') {
			if (!$whereOn) {
				$where .= ' codigo_cliente_secundario = "'.$codigo_cliente.'"';
				$whereOn = 1;
			} else {
				$where .= ' AND codigo_cliente_secundario = "'.$codigo_cliente.'"';
			}
		}
		if ($codigo_asunto != '') {
			if (!$whereOn) {
				$where .= ' codigo_asunto = "'.$codigo_asunto.'"';
				$whereOn = 1;
			} else {
				$where .= ' AND codigo_asunto = "'.$codigo_asunto.'"';
			}
		}
		if ($whereOn) {
			$where = ' WHERE ' . $where . '';
			$query .= $where;
		}

		if(!$desde)
			$desde = 0;	
		
		$x_pag = 10;					
		$b = new Buscador($sesion, $query, 'Actividad', $desde, $x_pag, $orden);
		$b->AgregarEncabezado("glosa_actividad", __('Nombre Actividad'), "align=left");
		$b->AgregarEncabezado("glosa_asunto",__('Asunto'), "align=left");
		$b->AgregarEncabezado("glosa_cliente", __('Cliente'), "align=left");
		$b->AgregarEncabezado("codigo_actividad", __('Código'), "align=left");
	    $b->AgregarFuncion("",'Opciones', "align=center");
		$b->color_mouse_over = "#bcff5c";
		$b->Imprimir();
	}

    function Opciones(& $fila) {
    	global $sesion;
		global $desde;
		$id_act = $fila->fields['id_actividad'];
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) {
			return "<a href=\"javascript:void(0)\" onclick=\"nuovaFinestra('Editar_Actividad', 670, 300,'agregar_actividades.php?id_actividad=" . $id_act . "&popup=1')\"><img src=\"".Conf::ImgDir()."/editar_on.gif\" border=0 title=\"".__('Editar actividad')."\" /></a>"
	        . "<a href='javascript:void(0)' onclick='EliminarActividad($id_act,$desde)'><img src='".Conf::ImgDir()."/cruz_roja_nuevo.gif' border=0 title='".__('Eliminar actividad')."' alt='".__('Eliminar')."'/></a>";
      	} else {
      		return "<a href=?id_actividad=$id_act><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title='".__('Editar actividad')."' alt='' /></a>"
        	. "<a href='javascript:void(0)' onclick='EliminarActividad($id_act,$desde)'><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 title='".__('Eliminar actividad')."' alt='".__('Eliminar')."'/></a>";
      	}
    }

  	echo(InputId::Javascript($sesion)); 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) ) {
		echo(Autocompletador::Javascript($sesion));
	}
	$pagina->PrintBottom();
?>
