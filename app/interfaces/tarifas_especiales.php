<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';


	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	$tip_tarifa_especial = __('Al ingresar una nueva tarifa, esta se actualizará automáticamente.');

	if($opcion == "eliminar")
	{
		$query = "DELETE FROM usuario_tarifa_cliente WHERE id_usuario_tarifa_cliente=$id_tarifa";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	}

	function TTip($texto)
	{
		return "onmouseover=\"ddrivetip('$texto');\" onmouseout=\"hideddrivetip('$texto');\"";
	}

	$cliente = new Cliente($sesion);
	if($id_cliente > 0)
		if(!$cliente->Load($id_cliente))
			$pagina->FatalError(__('Código inválido'));


	$pagina->titulo = __('Tarifas especiales').' '.$cliente->fields[glosa_cliente];
	$pagina->PrintTop();
?>
<script type="text/javascript">
function MostrarTarifa(id_usuario,radio_moneda)
{
	for(var i = 0; i < radio_moneda.length; i++) 
	{
		if(radio_moneda[i].checked)
			id_moneda = radio_moneda[i].value;
	}
	codigo_cliente = document.formulario.codigo_cliente.value;
	var http = getXMLHTTP();
	http.open('get', 'ajax.php?accion=get_tarifa_cliente&id_usuario=' + id_usuario + "&id_moneda=" + id_moneda + "&codigo_cliente=" + codigo_cliente);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			if(response > 0)
				document.formulario.tarifa_especial.value = response;
			else
				document.formulario.tarifa_especial.value = "";
		}
	}
	http.send(null);
}
function GuardarTarifa(id_usuario,radio_moneda,tarifa)
{
	tarifa = tarifa.replace(",",".");
	if(tarifa <= 0)
	{
		alert("<?=__('La tarifa debe ser mayor a cero')?>");
		return false;
	}

	var id_moneda = 0;
	for(var i = 0; i < radio_moneda.length; i++) 
	{
		if(radio_moneda[i].checked)
			id_moneda = radio_moneda[i].value;
	}
	codigo_cliente = document.formulario.codigo_cliente.value;
	var http = getXMLHTTP();
	http.open('get', 'ajax.php?accion=set_tarifa_cliente&id_usuario=' + id_usuario + "&id_moneda=" + id_moneda + "&codigo_cliente=" + codigo_cliente + "&tarifa=" + tarifa);
	http.onreadystatechange = function()
	{
		if(http.readyState == 4)
		{
			var response = http.responseText;
			var update = new Array();
            if(response.indexOf('OK') != -1)
				alert("<?=__('Tarifa guardada con éxito')?>");
			else
				alert(response);
		}
	}
	http.send(null);
}
</script>
<form name=formulario id=formulario method=post onKeyUp="highlight(event)" onClick="highlight(event)" action=<?= $SERVER[PHP_SELF] ?>>
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=codigo_cliente value="<?= $cliente->fields['codigo_cliente'] ?>" />

	<fieldset style="width: 95%">
		<legend><?=__('Tarifas especiales')?></legend>
			<table>
				<tr>
					<td>
						<?= Html::SelectQuery($sesion, "SELECT id_usuario,CONCAT_WS(', ',apellido1, nombre) 
										FROM usuario WHERE visible=1 ORDER BY apellido1","id_usuario_tarifa", "",
							"onchange=\"MostrarTarifa(this.value,this.form.id_moneda_tarifa)\""); ?>
					</td>
					<td>
						<?= Funciones::PrintRadioMonedas($sesion,"id_moneda_tarifa", 1,
							"onchange=\"MostrarTarifa(this.form.id_usuario_tarifa.value,this.form.id_moneda_tarifa)\""); ?>
					</td>
					<td nowrap>
						<input <?= TTip($tip_tarifa_especial) ?> name=tarifa_especial size=4 >
						<input type=button class=btn value="<?=__('Guardar tarifa')?>" onclick="GuardarTarifa(this.form.id_usuario_tarifa.value,this.form.id_moneda_tarifa,this.form.tarifa_especial.value);">
					</td>
				</tr>
			</table>
	</fieldset>
	<br>
	<input type=button class=btn_rojo value="<?=__('Cancelar')?>" onclick="history.back(-1)">
</form>
<br /><br />
<?= InputId::Javascript($sesion) ?>
<?
	$codigo_cliente = $cliente->fields[codigo_cliente];
	if($orden == "")
		$orden = "nombre, glosa_moneda";

	$query = "SELECT SQL_CALC_FOUND_ROWS *, CONCAT_WS(', ', apellido1, nombre) as nombre
				FROM 
				usuario_tarifa_cliente LEFT JOIN usuario USING (id_usuario) 
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda=usuario_tarifa_cliente.id_moneda
				WHERE codigo_cliente='$codigo_cliente'";

	$x_pag = 25;
	$b = new Buscador($sesion, $query, "Objeto", $desde, $x_pag, $orden);
	$b->nombre = "busc_gastos";
	$b->titulo = __('Listado de tarifas especiales para'). $cliente->fields[codigo_cliente];
	$b->AgregarEncabezado("nombre",__('Nombre'));
	$b->AgregarEncabezado("glosa_moneda",__('Moneda'));
	$b->AgregarEncabezado("tarifa",__('Tarifa'),"align=right");
	$b->AgregarFuncion("Eliminar","Eliminar", "align=center");
	$b->color_mouse_over = "#DF9862";
	$b->Imprimir();


	$pagina->PrintBottom();


	function Eliminar($elem)
	{
		$id_usuario_tarifa_cliente = $elem->fields[id_usuario_tarifa_cliente];
		$img_dir = Conf::ImgDir();
		global $id_cliente;
		return "<a href=?opcion=eliminar&id_tarifa=$id_usuario_tarifa_cliente&id_cliente=$id_cliente><img border=0 src=$img_dir/cruz_roja.gif></A>";
	}

?>
