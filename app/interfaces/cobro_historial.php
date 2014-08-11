<?php  
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../fw/classes/Html.php';
require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/classes/InputId.php';
require_once Conf::ServerDir().'/classes/CobroHistorial.php';
require_once Conf::ServerDir().'/classes/Cliente.php';
require_once Conf::ServerDir().'/classes/Autocompletador.php';

$sesion = new Sesion(array('REV','ADM','COB'));
$pagina = new Pagina($sesion);

$pagina->titulo = __('Historial de cobros');
$pagina->PrintTop();

if($buscar == 1)
{
	if(( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) )
		{
			$cliente = new Cliente($sesion);
			if( $codigo_cliente_secundario )
				$codigo_cliente = $cliente->CodigoSecundarioACodigo( $codigo_cliente_secundario );
		}
	
	$where = " 1 ";
	if( $id_cobro != '' ) 
		$where .= " AND ch.id_cobro = ".$id_cobro." ";
	if( $codigo_cliente ) 
		$where .= " AND cl.codigo_cliente = '$codigo_cliente' ";
	if( $fecha_desde )
		$where .= " AND ch.fecha > '".Utiles::fecha2sql($fecha_desde)."' ";
	if( $fecha_hasta ) 
		$where .= " AND ch.fecha < '".Utiles::fecha2sql($fecha_hasta)." 23:59:59' ";
	if( $comentario != '' )
		$where .= " AND ch.comentario = '$comentario' ";
		
	$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS *,
									 ch.id_cobro as id_cobro, 
									 ch.fecha as fecha_modificacion,  
									 ch.id_usuario as id_modificador, 
									 CONCAT_WS(' ',u.nombre,u.apellido1,u.apellido2) as modificador, 
									 ch.comentario as comentario, 
									 cl.glosa_cliente as glosa_cliente, 
									 ch.es_modificable 
						FROM cobro_observacion AS ch
						JOIN cobro AS c ON c.id_cobro = ch.id_cobro 
						JOIN contrato AS co ON c.id_contrato=co.id_contrato 
						JOIN usuario AS u ON u.id_usuario=ch.id_usuario 
						JOIN cliente AS cl ON cl.codigo_cliente = co.codigo_cliente  
						WHERE $where";
	
	if( $orden == "" )
		$orden = " ch.fecha DESC ";
		
	$x_pag = 15;
	$b = new Buscador($sesion, $query, "CobroHistorial", $desde, $x_pag, $orden);
	$b->mensaje_error_fecha = "N/A";
	$b->nombre = "busc_cobro_historial";
	$b->titulo = __('Listado de').' '.__('cobros modificados');
	$b->AgregarEncabezado("id_cobro","ID","align=center");
	$b->AgregarEncabezado("glosa_cliente",__('Cliente'),"align=left");
	$b->AgregarEncabezado("fecha_modificacion",__('Fecha modificacion'),"align=center");
	$b->AgregarEncabezado("modificador",__('Modificado por'),"align=left");
	$b->AgregarEncabezado("comentario",__('Comentario'),"align=left");
	$b->color_mouse_over = "#bcff5c";
}

echo(Autocompletador::CSS());
?>
<form action="#">
	<input type="hidden" name="buscar" id="buscar" value="1" />
	<table width="90%" style="border: 1px solid #BDBDBD;">
		<tr>
			<td align="right" width="20%">
				<?php echo __('ID') . " " . __('cobro')?>
			</td>
			<td align="left" width="80%" colspan="3">
				<input type="text" size="3" name="id_cobro" name="id_cobro" value="<?php echo $id_cobro?>" />
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Cliente')?>
			</td>
			<td nowrap align="left" colspan="3">
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
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto);
		else
			echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
	}
?>
				</td>
			</tr>
			<tr>
				<td align="right" width="20%">
			<?php echo __('Fecha desde')?>
		</td>
		<td align="left" width="20%">
			<input type="text" size="10" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde?>" />
			<img src="<?php echo Conf::ImgDir()?>/calendar.gif" id="img_fecha_desde" style="cursor:pointer" />
		</td>
		<td align="right" width="20%">
			<?php echo __('Fecha hasta')?>
		</td>
		<td align="left" width="40%"> 
			<input type="text" size="10" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta?>" /> 
			<img src="<?php echo Conf::ImgDir()?>/calendar.gif" id="img_fecha_hasta" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align="right" width="20%">
			<?php echo __('Acción')?>
		</td>
		<td align="left" colspan="3">
			<select name="comentario" id="comentario" width="150">
				<option value=''></option>
				<option value='COBRO CREADO' <?php echo $comentario=='COBRO CREADO'?'selected':''?>><?php echo __('COBRO CREADO') ?></option>
				<option value='COBRO EMITIDO' <?php echo $comentario=='COBRO EMITIDO'?'selected':''?>><?php echo __('COBRO EMITIDO') ?></option>
				<option value='COBRO FACTURADO' <?php echo $comentario=='COBRO FACTURADO'?'selected':''?>><?php echo __('COBRO FACTURADO') ?></option>
				<option value='COBRO ANULADO' <?php echo $comentario=='COBRO ANULADO'?'selected':''?>><?php echo __('COBRO ANULADO') ?></option>
				<option value='COBRO PAGO PARCIAL' <?php echo $comentario=='COBRO PAGO PARCIAL'?'selected':''?>><?php echo __('COBRO PAGO PARCIAL') ?></option>
				<option value='COBRO PAGADO' <?php echo $comentario=='COBRO PAGADO'?'selected':''?>><?php echo __('COBRO PAGADO') ?></option>
				<option value='COBRO EN REVISION' <?php echo $comentario=='COBRO EN REVISION'?'selected':''?>><?php echo __('COBRO EN REVISION') ?></option>
			</select>
	<tr>
		<td align="center" colspan="4">
			<input type="submit" value="<?php echo __('Buscar')?>" />
		</td>
	</tr>
</table>
</form>
<?php 
if($buscar==1)
{
	echo "<center>";
	$b->Imprimir();
	echo "</center>";
}
if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
{
	echo(Autocompletador::Javascript($sesion));
}
echo(InputId::Javascript($sesion));
$pagina->PrintBottom();
?>


<script language="javascript" type="text/javascript">
//datepicker Fecha
Calendar.setup(
	{
		inputField	: "fecha_desde",				// ID of the input field
		ifFormat	: "%d-%m-%Y",			// the date format
		button			: "img_fecha_desde"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField : "fecha_hasta",    // input of the input field
		ifFormat   : "%d-%m-%Y", // the date format
		button     : "img_fecha_hasta"  // ID of the button
	}
);
</script>
			
