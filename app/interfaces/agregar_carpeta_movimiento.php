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
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';

	$sesion = new Sesion(array('EDI'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$carpeta = new Carpeta($sesion);
	if($id_carpeta > 0)
	{
		$carpeta->Load($id_carpeta);
		$codigo_asunto = $carpeta->fields['codigo_asunto'];
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($codigo_asunto);
		$codigo_asunto_secundario=$asunto->fields['codigo_asunto_secundario'];
		$codigo_cliente=$asunto->fields['codigo_cliente'];
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		$codigo_cliente_secundario=$cliente->fields['codigo_cliente_secundario'];
	}
	else
	{
		$pagina->AddError(__('Carpeta inválida'));
	}
		
	if($opcion == 'guardar')
	{
		$carpeta->Edit('id_tipo_movimiento_carpeta',$id_tipo_movimiento_carpeta);
		$carpeta->Edit('id_usuario_ultimo_movimiento',$id_usuario_ultimo_movimiento);
		$carpeta->Edit('id_usuario_modificacion',$sesion->usuario->fields['id_usuario']);

		if($carpeta->Write())
		{
			$pagina->AddInfo( __('Movimiento guardado con exito') );
		}
		else
			$pagina->AddError($carpeta->error);
	}

	$txt_pagina = __('Ingreso de Movimiento');

	$pagina->titulo = $txt_pagina;
	$pagina->PrintTop($popup);
?>
<script>

function Validar()
{
	var form = $('form_mover_carpeta');
form.submit();
return true;
}
</script>
<form method="post" action="agregar_carpeta_movimiento.php" name="form_mover_carpeta" id="form_mover_carpeta">
<input type=hidden name=opcion value="guardar" />
<input type=hidden name=popup value="1" />
<input type=hidden name=codigo_carpeta value="<?= $carpeta->fields['codigo_carpeta'] ?>" />
<input type=hidden name=id_carpeta value="<?= $carpeta->fields['id_carpeta'] ?>" />

<br>
<table width='90%'>
	<tr>
		<td align=left><b><?=$txt_pagina ?></b></td>
	</tr>
</table>
<br>

<table style="border: 1px solid black;" width='90%'>
		<? if($carpeta->Loaded()){ ?>
	<tr><td colspan=2 align='center'>
		<?
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
<tr><td colspan="2">&nbsp;<br /><br /></td></tr>
	<tr>
		<td align=right>
			<?=__('Nuevo movimiento:')?>&nbsp;
		</td>
		<td align=left >
			<? if (!empty($carpeta->fields['id_tipo_movimiento_carpeta'])) $where_tipo_carpeta= " AND id_tipo_movimiento_carpeta<> ".$carpeta->fields['id_tipo_movimiento_carpeta'];
				 else if ($id_tipo_movimiento_carpeta) $where_tipo_carpeta= " AND id_tipo_movimiento_carpeta<> ".$id_tipo_movimiento_carpeta;
				 else $where_tipo_carpeta = " AND id_tipo_movimiento_carpeta=1"; ?>
			<?= Html::SelectQuery($sesion, "SELECT * FROM prm_tipo_movimiento_carpeta WHERE 1 $where_tipo_carpeta ORDER BY glosa_tipo_movimiento_carpeta","id_tipo_movimiento_carpeta", $id_tipo_movimiento_carpeta ? $id_tipo_movimiento_carpeta : $carpeta->fields['id_tipo_movimiento_carpeta'],"","","120"); ?>
			<?=__('Por')?>
			<?= Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario WHERE visible=1","id_usuario_ultimo_movimiento", $id_usuario_ultimo_movimiento ? $id_usuario_ultimo_movimiento : $carpeta->fields['id_usuario_ultimo_movimiento'],"","","200"); ?>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;<br /><br /></td></tr>
	<tr>
		<td colspan=2 align="center">
			<input type="button" class=btn value="<?=__('Guardar')?>" onclick="return Validar()" />
		</td>
	</tr>
<?}?>
</table>
	
</form>
<br/><br/>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom($popup);
?>