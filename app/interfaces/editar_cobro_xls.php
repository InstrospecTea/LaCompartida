<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/PrmExcelCobro.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);

	$pagina->titulo = __('Editar glosa planilla de') . " " . __('cobros');
	$pagina->PrintTop();

	if($opc == 'guardar')
	{
		foreach($nuevo_valor_es as $id => $valor_es){
			if($valor_es == "")
				continue;
			$parametro = new PrmExcelCobro($sesion);
			$parametro->Load($id);
			$parametro->Edit('glosa_es', $valor_es);
			$parametro->Write();
		}
		foreach($nuevo_valor_en as $id => $valor_en){
			if($valor_en == "")
				continue;
			$parametro = new PrmExcelCobro($sesion);
			$parametro->Load($id);
			$parametro->Edit('glosa_en', $valor_en);
			$parametro->Write();
		}
	}

	$query = "SELECT id_prm_excel_cobro, glosa_es, glosa_en, grupo
			FROM prm_excel_cobro
			ORDER BY grupo, id_prm_excel_cobro";
	$lista_parametros = new ListaPrmExcelCobro($sesion, '', $query);

?>
	<form method="post" action="">
		<input type="hidden" name="opc" value="guardar">
		<table width='100%' border="1" style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545; border-bottom:none' cellpadding="3" cellspacing="3">
<?
	$grupo_anterior = 'Este valor debe no válido.';
	for($i=0; $i<$lista_parametros->num; $i++)
	{
		$parametro = $lista_parametros->Get($i);
		if($grupo_anterior != $parametro->fields['grupo'])
		{
?>
			<tr bgcolor="#6CA522">
				<td style="font-weight:bold" colspan="4"><?=__('Sección').' '.$parametro->fields['grupo']?></td>
			</tr>
			<tr>
				<td style="font-weight:bold" colspan="2"><?=__('Español')?></td>
				<td style="font-weight:bold" colspan="2"><?=__('English')?></td>
			</tr>
			<tr>
				<td style="font-weight:bold"><?=__('Valor actual')?></td>
				<td style="font-weight:bold"><?=__('Nuevo valor')?></td>
				<td style="font-weight:bold"><?=__('Valor actual')?></td>
				<td style="font-weight:bold"><?=__('Nuevo valor')?></td>
			</tr>
<?
			$grupo_anterior = $parametro->fields['grupo'];
		}
?>
			<tr>
				<td><?=$parametro->fields['glosa_es']?></td>
				<td><input name="nuevo_valor_es[<?=$parametro->fields['id_prm_excel_cobro']?>]" value="" tabindex="<?=$i?>"/></td>
				<td><?=$parametro->fields['glosa_en']?></td>
				<td><input name="nuevo_valor_en[<?=$parametro->fields['id_prm_excel_cobro']?>]" value="" tabindex="<?=$i+2*$lista_parametros->num?>"/></td>
			</tr>
<?
	}
?>
			<tr>
				<td colspan="2" align="right"><input type="submit" value="Actualizar"/></td>
			</tr>
		</table>

	</form>
<?
	$pagina->PrintBottom();
?>