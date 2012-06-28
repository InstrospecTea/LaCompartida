<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Gasto.php';


	$sesion = new Sesion(array('OFI'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$ingreso = new Gasto($sesion);  //Este es un problema del nombre de la clase no más en verdad gasto debiese de llamarse cta corriente

	if($opcion == "guardar")
	{
		$ingreso->Edit("ingreso",$monto);
		$ingreso->Edit("fecha",$fecha);
		$ingreso->Edit("id_usuario",$id_usuario);
		$ingreso->Edit("descripcion",$descripcion);
		$ingreso->Edit("id_moneda",$id_moneda);
		$ingreso->Edit("codigo_cliente",$codigo_cliente);
		if($ingreso->Write())
		{
			$pagina->addInfo(__('Provisión ingresada con éxito'));
			$pagina->Redirect("cuenta_corriente.php?codigo_cliente=$codigo_cliente");
		}
		else
			$pagina->AddError($ingreso->error);
		$ingreso = new Ingreso($sesion);
	}

	if($ingreso->fields[id_usuario_orden] == "")
		$ingreso->fields[id_usuario_orden] = $sesion->usuario->fields[id_usuario];
	

	$pagina->titulo = __('Ingreso de provisión');
	$pagina->PrintTop();
?>

<form method=post action="<?= $SERVER[PHP_SELF] ?>" onsubmit="return Validar(this);">
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=id_ingreso value="<?= $ingreso->fields['id_ingreso'] ?>" />

<table style="border: 1px solid black;">
	<tr>
		<td align=right>
				<?=__('Fecha')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha", "$fecha"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Cliente')?>
		</td>
		<td align=left>
			<?= InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos');") ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Monto')?>
		</td>
		<td align=left>
			<input name=monto size=6 value="<?= $ingreso->fields[monto] ?>" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Moneda')?>
		</td>
		<td align=left>
			<?= Funciones::PrintRadioMonedas($sesion,"id_moneda",$ingreso->fields[id_moneda]) ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Descripción')?>
		</td>
		<td align=left>
			<textarea name=descripcion><?= $ingreso->fields['descripcion']?></textarea>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=submit value=<?=__('Guardar')?> />
		</td>
	</tr>

</table>
	
</form>

<script type="text/javascript">
function Validar(form)
{
	monto = parseFloat(form.monto.value);

	if(monto <= 0 || isNaN(monto))
	{
		alert("<?=__('Debe ingresar un monto para el ingreso')?>");
		form.monto.focus();
		return false;
	}
	if(form.descripcion.value == "")
	{
		alert("<?=__('Debe ingresar una descripción')?>");
		form.descripcion.focus();
		return false;
	}
	for (counter = 0; counter < form.id_moneda.length; counter++)
	{
		if (form.id_moneda[counter].checked)
			radio_choice = true; 
	}
	if (!radio_choice)
	{
		alert("<?=__('Debe ingresar la moneda')?>");
		return false;
	}
	return true;
}
</script>
<?
	echo(InputId::Javascript($sesion));

	$pagina->PrintBottom();
?>
