<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('COB'));
$pagina = new PaginaCobro($sesion);
$id_usuario = $sesion->usuario->fields['id_usuario'];

$cobro = new Cobro($sesion);
$cobro->Load($id_cobro);

if (!$cobro->Load($id_cobro)) {
	$pagina->FatalError(__('Cobro inválido'));
}

$cliente = new Cliente($sesion);
$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
$nombre_cliente = $cliente->fields['glosa_cliente'];
$pagina->titulo = __('Emitir') . ' ' . __('Cobro') . __(' :: Selección de trabajos #') . $id_cobro . ' ' . $nombre_cliente;

if ($cobro->fields['estado'] <> 'CREADO' && $cobro->fields['estado'] <> 'EN REVISION') {
	$pagina->Redirect("cobros6.php?id_cobro={$id_cobro}&popup=1&contitulo=true");
}

$cobro->Edit('etapa_cobro', '2');
$cobro->Write();

if ($opc == "siguiente") {
	$pagina->Redirect("cobros_tramites.php?id_cobro={$id_cobro}&popup=1&contitulo=true");
} else if($opc == "anterior") {
	$pagina->Redirect("cobros2.php?id_cobro={$id_cobro}&popup=1&contitulo=true");
}

$cobro->LoadAsuntos();
$pagina->PrintTop($popup);

if ($popup) {
?>
<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td valign="top" align="left" class="titulo" bgcolor="<?= (method_exists('Conf','GetConf') ? Conf::GetConf($sesion,'ColorTituloPagina') : Conf::ColorTituloPagina()); ?>">
			<?php echo __('Emitir') . ' ' . __('Cobro') . __(' :: Selección de trabajos #') . $id_cobro . ' ' . $nombre_cliente; ?>
		</td>
	</tr>
</table>
<br>
<?php } ?>

<form method="post">
	<input type="hidden" name="opc">
	<input type="hidden" name="id_cobro" value="<?php echo $id_cobro; ?>">
	<?= $pagina->PrintPasos($sesion, 2, '', $id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']); ?>
	<table border="0" width="100%">
		<tr>
			<td align="left"><input type="button" class="btn" value="<?php echo __('<< Anterior'); ?>" onclick="this.form.opc.value = 'anterior'; this.form.submit();"></td>
			<td align="center">&nbsp;</td>
			<td align="right"><input type="button" class="btn" value="<?php echo __('Siguiente >>'); ?>" onclick="this.form.opc.value = 'siguiente'; this.form.submit();"></td>
		</tr>
	</table>
	<table border="0" width="100%">
		<tr>
			<?php
				if (Conf::GetConf($sesion, 'CodigoSecundario')) {
					$codigo_cliente_query_string = "codigo_cliente_secundario={$codigo_cliente_secundario}";
				} else {
					$codigo_cliente_query_string = "codigo_cliente={$codigo_cliente}";
				}
			?>
			<td class="cvs" align="center" colspan="2">
				<iframe name="trabajos" id="asuntos" src="trabajos.php?<?php echo $codigo_cliente_query_string; ?>&id_cobro=<?php echo $id_cobro; ?>&motivo=cobros&opc=buscar&popup=1&from_cobro=1" frameborder="0" width="800px" height="1500px"></iframe>
			</td>
		</tr>
	</table>
</form>
<?= InputId::Javascript($sesion); ?>
<script type="text/javascript" src="guardar_campo_trabajo.js"></script>
<?php $pagina->PrintBottom($popup);
