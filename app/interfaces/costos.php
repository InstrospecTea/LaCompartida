<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);

	if(!$fecha_a || $fecha_a<1)
		$fecha_a = date("Y");
	if(!$fecha_m || $fecha_m<1 || $fecha_m>12)
		$fecha_m = date("m");
	$tip_anterior = Html::Tooltip("<b>".__('Semestre anterior').":</b><br />".($fecha_m>6?__("Primer semestre de ").$fecha_a : __("Segundo semestre de ") . ($fecha_a-1)));
	$tip_siguiente = Html::Tooltip("<b>".__('Semestre siguiente').":</b><br />".($fecha_m>6?__("Primer semestre de ") . ($fecha_a+1) : __("Segundo semestre de ") . $fecha_a));
	$meses = array(__("Enero"), __("Febrero"), __("Marzo"), __("Abril"), __("Mayo"), __("Junio"),__("Julio"),__("Agosto"),__("Septiembre"),__("Octubre"),__("Noviembre"),__("Diciembre"));

	$query = 'SELECT simbolo, cifras_decimales FROM prm_moneda WHERE moneda_base = 1';
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp);

	if($opc == 'guardar')
	{
		foreach($costo_mes as $id_usuario => $arr_costo)
		{
			foreach($arr_costo as $mes => $costo)
			{
				if(!$costo)
					continue;
				$query = "REPLACE INTO usuario_costo(id_usuario, fecha, costo) VALUES('".$id_usuario."', '".sprintf("%04d-%02d-01", $fecha_a, $mes)."', '".$costo."')";
				mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			}
		}
	}

	$pagina->titulo = __('Costo por profesional');
	$pagina->PrintTop();
?>
<script type="text/javascript">
function CambiaFecha(fecha_nueva_a, fecha_nueva_m){
	self.location.href = "costos.php?fecha_a=" + fecha_nueva_a + "&fecha_m=" + fecha_nueva_m;
}
</script>
<style>
#tbl_tarifa
{
	font-size: 10px;
	padding: 1px;
	margin: 0px;
	vertical-align: middle;
	border:1px solid #BDBDBD;
	<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) {?>
	background-color: #FFFFFF;
<? } ?>
}
.text_box
{
	font-size: 10px;
	text-align:right;
}
</style>
<form name=formulario id=formulario method=post action='' autocomplete="off">
	<input type=hidden name='opc' value='guardar'>
	<input type=hidden name='popup' id='popup' value='<?=$popup ?>'>
<?
	$query = "SELECT 
							usuario.id_usuario, 
							CONCAT(usuario.apellido1,' ',usuario.apellido2,' ',usuario.nombre) AS nombre_usuario, 
							usuario.username
					FROM usuario
						JOIN usuario_permiso USING(id_usuario)
					WHERE usuario.visible = 1
						AND usuario_permiso.codigo_permiso='PRO'
					ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$result = mysql_query("SELECT FOUND_ROWS()");
	$row = mysql_fetch_row($result);

	$where_fecha = " EXTRACT(YEAR FROM usuario_costo.fecha) = '" . $fecha_a . "' AND ";
	if($fecha_m>6)
		$where_fecha .= " EXTRACT(MONTH FROM usuario_costo.fecha) > 6 ";
	else
		$where_fecha .= " EXTRACT(MONTH FROM usuario_costo.fecha) <= 6 ";

	$query_costos = "SELECT usuario_costo.id_usuario,
											EXTRACT(YEAR FROM usuario_costo.fecha) as fecha_costo_a,
											EXTRACT(MONTH FROM usuario_costo.fecha) as fecha_costo_m,
											usuario_costo.costo
										FROM usuario_costo
											JOIN usuario ON usuario_costo.id_usuario = usuario.id_usuario
											LEFT JOIN usuario_permiso ON usuario_permiso.id_usuario = usuario.id_usuario
										WHERE usuario.visible = 1 AND " . $where_fecha . "
											AND usuario_permiso.codigo_permiso='PRO'
										ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario, fecha";
	$resp2 = mysql_query($query_costos, $sesion->dbh) or Utiles::errorSQL($query_costos,__FILE__,__LINE__,$sesion->dbh);
	list($id_usuario_costo, $fecha_costo_a, $fecha_costo_m, $costo) = mysql_fetch_array($resp2);

	$total = $row[0];
	$tab = 0;	// Sirve para que al apretar tab el foco pase hacia abajo y no hacia el lado.
	$td_contenido = '';
	while(list($id_usuario,$nombre_usuario,$username) = mysql_fetch_array($resp))
	{
		++$tab;
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') )
			$td_contenido .= '<tr><td align=left>'.$username.'</td>';
		else
			$td_contenido .= '<tr><td align=left>'.$nombre_usuario.'</td>';
		for($j=($fecha_m>6?6:0); $j<($fecha_m>6?12:6); ++$j) // Mostrar costo de cada mes
		{
			$tab += 1000;

			if($fecha_costo_m == $j+1 && $id_usuario == $id_usuario_costo)
			{
				$td_contenido .= "<td align=right>$simbolo_moneda&nbsp;<input type=text size=6 class='text_box' name='costo_mes[$id_usuario][".($j+1)."]' value='".sprintf("%0.".$cifras_decimales."d", $costo)."' $active tabindex='$tab'></td> \n";
				list($id_usuario_costo, $fecha_costo_a, $fecha_costo_m, $costo) = mysql_fetch_array($resp2);
			}
			else
				$td_contenido .= "<td align=right>$simbolo_moneda&nbsp;<input type=text size=6 class='text_box' name='costo_mes[$id_usuario][".($j+1)."]' value='' $active tabindex='$tab'></td> \n";
		}
		$tab -= 6000;
		$td_contenido .= '</tr>';
	}
	
	if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
		{
			$width_tabla = 'width="90%"';
		}
	else
		{
			$width_tabla = 'width="100%"';
		}
?>
<table <?=$width_tabla?> border="1" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
	<tr bgcolor=#A3D55C>
		<td colspan="6" align="center">
			<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) { ?>
			<img src='<?=Conf::ImgDir()."/izquierda_nuevo.gif"?>' <?=$tip_anterior?> class='mano_on' onclick="CambiaFecha('<?=$fecha_m>6?$fecha_a:$fecha_a-1?>', '<?=$fecha_m>6?$fecha_m-6:$fecha_m+6?>')">
		<? } else { ?>
			<img src='<?=Conf::ImgDir()."/izquierda.gif"?>' <?=$tip_anterior?> class='mano_on' onclick="CambiaFecha('<?=$fecha_m>6?$fecha_a:$fecha_a-1?>', '<?=$fecha_m>6?$fecha_m-6:$fecha_m+6?>')">
		<? } ?>
			<b><?=$fecha_a . " - " . __(($fecha_m>6?"Segundo":"Primer") . " semestre")?></b>
			<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) { ?>
			<img src='<?=Conf::ImgDir()."/derecha_nuevo.gif"?>' <?=$tip_siguiente?> class='mano_on' onclick="CambiaFecha('<?=$fecha_m>6?$fecha_a+1:$fecha_a?>', '<?=($fecha_m+6)%12?>')">
		<? } else { ?>
			<img src='<?=Conf::ImgDir()."/derecha.gif"?>' <?=$tip_siguiente?> class='mano_on' onclick="CambiaFecha('<?=$fecha_m>6?$fecha_a+1:$fecha_a?>', '<?=($fecha_m+6)%12?>')">
		<? } ?>
		</td>
		<td>
			<input type=submit value='<?=__('Guardar') ?>' class=btn >
		</td>
		</tr>
	<tr bgcolor=#A3D55C>
		<td align=left><b><?=__("Profesional")?></b></td>
<?
	for($j=($fecha_m>6?6:0); $j<($fecha_m>6?12:6); ++$j)
		echo '		<td align="center"><b>' . $meses[$j] . "</b></td>\n";
?>
	</tr>
	<?=$td_contenido?>
</table>
</form>
<?
	$pagina->PrintBottom($popup);
?>