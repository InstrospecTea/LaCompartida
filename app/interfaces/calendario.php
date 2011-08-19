<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';

  
	$sesion = new Sesion(array('ADM','PRO'));
	$pagina = new Pagina($sesion);

	$pagina->titulo = __('Calendario');
	$pagina->PrintTop($popup);
	
	$params = array();
	
	if(!$opcion)
		$usuarios = array($sesion->usuario->fields['id_usuario']);
	
	if(is_array($usuarios))
		$params[] = 'usuarios='.join(',',$usuarios);
		
	if(is_array($grupo_cliente))
		$params[] = 'grupo='.join(',',$grupo_cliente);
		
	if( $codigo_cliente_secundario != '' && $codigo_cliente == '' )
	{
		$cliente = new Cliente($sesion);
		$codigo_cliente = $cliente->CodigoSecundarioACodigo( $codigo_cliente_secundario );
	} 
	if($codigo_cliente)
		$params[] = 'cliente='.$codigo_cliente;
	
	$argumentos = '';
	if(!empty($params))
		$argumentos = '?'.join('&',$params);
	
	require Conf::ServerDir().'/templates/'.Conf::Templates().'/headers_calendario.php';
?>

<script type="text/javascript">
	function Actualizar_Calendario( form )
	{
	form.submit();
	/*scheduler.load("<?=Conf::RootDir()?>/app/interfaces/eventos.php");*/
	}
	
function Refrescar()
{
	var url = "calendario.php";
	self.location.href= url;
}

</script>
<? echo Autocompletador::CSS(); ?>

<?
if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
		$width = "90%";
	else 
		$width = "100%";
?>

<form id="filtros_calendario" action="" method="POST">
	<input type="hidden" name="opcion" id="opcion" value="reload">
<table width=<?=$width ?>><tr><td>
<fieldset class="tb_base" width="100%" style="border: 1px solid #BDBDBD; z-index:100;">
	<legend style="cursor:pointer">Filtros Calendario</legend>
		<table width="90%">
			<tr>
				<td align=right><b><?=__('Usuario')?></b>:</td>
				<td align=left colspan=3>
					<? if(!$usuario) $usuario = $sesion->usuario->fields['id_usuario']; ?>
					<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"class=\"selectMultiple\" multiple size=4 ","","200"); ?>	
				</td>
			</tr>
			<tr>
				<td align=right width='30%'><b><?=__('Cliente')?></b></td>
				<td colspan=3 align=left>
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
						  if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
								echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","", 320);
							else
								echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","", 320);
						}?>
				</td>
			</tr>
			<tr>
				<td align=right><b><?=__('Grupo Cliente')?></b>:</td>
				<td align=left colspan=3>
					<?=Html::SelectQuery($sesion,"SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente ORDER BY glosa_grupo_cliente ASC","grupo_cliente[]",$grupo_cliente,"class=\"selectMultiple\" multiple size=4 ","","200");?>	
				</td>
			</tr>
			<tr>
				<td align="center" colspan="4">
					<input type="button" value="<?=__('Cargar Calendario')?>" onclick="Actualizar_Calendario( this.form );">
				</td>
			</tr>
		</table>
</fieldset>
</td></tr></table>

</form> 

<br>

	<div id="scheduler_here" class="dhx_cal_container" style='width:<?=width ?>; height:820px; overflow: visible;'> 
		<div class="dhx_cal_navline" >
     
			<div class="dhx_cal_prev_button">&nbsp;</div>
			<div class="dhx_cal_next_button">&nbsp;</div>
			<div class="dhx_cal_today_button"></div>
			<div class="dhx_cal_date"></div>
			<div class="dhx_cal_tab" name="day_tab" style="right:302px;"></div>
			<div class="dhx_cal_tab" name="week_tab" style="right:238px;"></div>
			<div class="dhx_cal_tab" name="month_tab" style="right:174px;"></div>
			<div class="dhx_cal_tab" name="year_tab" style="right:110px;"></div>
			<div class="dhx_cal_tab" name="agenda_tab" style="right:46px;"></div>

		</div>
		<div class="dhx_cal_header">
		</div>
		<div class="dhx_cal_data">
		</div>		
	</div>

<script type="text/javascript">
Event.observe(window, "load", function(e)
{
init();
});
</script>
<?
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion,false));
	}
$pagina->PrintBottom($popup);
?>
