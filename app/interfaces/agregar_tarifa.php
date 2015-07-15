<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('TAR'));
$Pagina = new Pagina($Sesion);
$tarifa = new Tarifa($Sesion);

if ($opc == 'eliminar') {
	$tarifa_eliminar = new Tarifa($Sesion);
	$tarifa_eliminar->loadById($id_tarifa_eliminar);

	if ($tarifa_eliminar->fields['tarifa_defecto'] == '1') {
		$Pagina->AddError(__('La tarifa base no puede ser eliminada solo puede ser editada'));
	} else {
		if ($tarifa_eliminar->Eliminar()) {
			$id_tarifa_edicion = $tarifa_defecto;
			$Pagina->AddInfo(__('La tarifa se ha eliminado satisfactoriamente'));
		} else {
			$Pagina->AddError($tarifa_eliminar->error);
		}
	}
}

if (!isset($id_tarifa_edicion) && !isset($nueva)) {
	$tarifa->LoadDefault();
	$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
}

if (!empty($id_tarifa_edicion) && !$tarifa->Load($id_tarifa_edicion)) {
	$Pagina->AddError(__('Esta tarifa no existe'));
}

if ($id_tarifa_previa && !$id_tarifa_edicion && $opc != 'guardar') {
	$query = "INSERT INTO tarifa(fecha_creacion) VALUES(NOW())";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

	$query = "SELECT id_tarifa FROM tarifa ORDER BY id_tarifa DESC";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	list($id_nuevo) = mysql_fetch_array($resp);

	$tarifa->loadById($id_nuevo);
	$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
} else if ($id_tarifa_previa && !$id_tarifa_edicion) {
	$query = "SELECT id_tarifa FROM tarifa ORDER BY id_tarifa DESC";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	list($id_nuevo) = mysql_fetch_array($resp);

	$tarifa->loadById($id_nuevo);
	$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
}

// Copia los datos al nuevo tarifa.
if ($id_nuevo && $opc != 'guardar') {
	$query = "SELECT id_usuario, id_moneda, tarifa FROM usuario_tarifa WHERE id_tarifa=" . $id_tarifa_previa;
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

	$UsuarioTarifa = new UsuarioTarifa($Sesion);
	while (list($id_usuario, $id_moneda, $tarifa) = mysql_fetch_array($resp)) {
		$UsuarioTarifa->GuardarTarifa($id_nuevo, $id_usuario, $id_moneda, $tarifa);
	}

	$query = "SELECT id_categoria_usuario, id_moneda, tarifa FROM categoria_tarifa WHERE id_tarifa=" . $id_tarifa_previa;
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

	$CategoriaTarifa = new CategoriaTarifa($Sesion);
	while (list($id_categoria_usuario, $id_moneda, $tarifa) = mysql_fetch_array($resp)) {
		$CategoriaTarifa->GuardarTarifa($id_nuevo, $id_categoria_usuario, $id_moneda, $tarifa);
	}
}

if ($opc == 'guardar') {
	if (empty($glosa_tarifa)) {
		$Pagina->AddError(__('Debe definir un nombre para la tarifa.'));
	} else {

		$tarifa->Edit('glosa_tarifa', $glosa_tarifa);

		if ($tarifa_defecto) {
			$tarifa->TarifaDefecto($tarifa->fields['id_tarifa']);
			$tarifa->Edit('tarifa_defecto', '1');
		}

		if (empty($glosa_tarifa)) {
			$Pagina->AddError(__('Debe el nombre de la tarifa.'));
		}

		if ($tarifa->Write()) {
			$usuario_tarifa = new UsuarioTarifa($Sesion);
			foreach ($tarifa_moneda as $id_usuario => $arr_moneda) {
				foreach ($arr_moneda as $id_moneda => $tarifa_monto) {
					$usuario_tarifa->GuardarTarifa($tarifa->fields['id_tarifa'], $id_usuario, $id_moneda, $tarifa_monto);
				}
			}
			$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
			$tarifa->Edit('guardado', '1');
		}

		if ($tarifa->Write()) {
			$categoria_tarifa = new CategoriaTarifa($Sesion);
			foreach ($tarifa_categoria_moneda as $id_categoria_usuario => $arr_categoria_moneda) {
				foreach ($arr_categoria_moneda as $id_moneda => $tarifa_categoria_monto) {
					$categoria_tarifa->GuardarTarifa($tarifa->fields['id_tarifa'], $id_categoria_usuario, $id_moneda, $tarifa_categoria_monto);
				}
			}
			$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
			$Pagina->AddInfo(__('La tarifa se ha modificado satisfactoriamente'));
		}
	}
}

$Pagina->titulo = __('Ingreso de Tarifas');
$Pagina->PrintTop($popup);
$active = ' onFocus="foco(this);" onBlur="no_foco(this);" ';
?>

<script type="text/javascript">

	jQuery('document').ready(function() {
		jQuery('#fix_tarifas').click(function() {
			jQuery.ajax({
				type: "POST",
				url: 'ajax/completar_tarifas.php',
				data: {accion: "completartarifas"},
				success: function(msg) {
					jQuery('.info td').first().html(msg);
				}
			});

		});
	});

	function foco(elemento)
	{
		elemento.style.border = "2px solid #000000";
	}

	function no_foco(elemento)
	{
		elemento.style.border = "1px solid #CCCCCC";
	}

	function cambia_tarifa(valor)
	{
		var popup = $('popup').value;
		if (confirm('<?php echo __('Confirma cambio de tarifa?') ?>'))
			self.location.href = 'agregar_tarifa.php?id_tarifa_edicion=' + valor + '&popup=' + popup;
	}

	function Eliminar()
	{
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=obtener_tarifa_defecto&id_tarifa=<?php echo $id_tarifa_edicion ? $id_tarifa_edicion : $id_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
		http.send(null);
		tarifa_defecto_en_bd = http.responseText;

		if (tarifa_defecto_en_bd != <?php echo $id_tarifa_edicion ? $id_tarifa_edicion : ( $id_tarifa_previa ? $id_tarifa_previa : '0' ) ?>) {
			var http = getXMLHTTP();
			http.open('get', 'ajax.php?accion=contratos_con_esta_tarifa&id_tarifa=<?php echo $id_tarifa_edicion ? $id_tarifa_edicion : $id_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
			http.send(null);
			num_contratos = http.responseText;

			if (num_contratos > 0) {
				respuesta_num_pagos = confirm('<?php echo __('La tarifa posee'); ?> ' + num_contratos + ' <?php echo __('contratos asociados. \nSi continua se le asignar� la tarifa est�ndar a los contratos afectados.\n�Est� seguro de continuar?.'); ?>');
				if (respuesta_num_pagos) {
					http.open('get', 'ajax.php?accion=cambiar_a_tarifa_por_defecto&id_tarifa=<?php echo $id_tarifa_edicion ? $id_tarifa_edicion : $id_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
					http.send(null);
					num_contratos = http.responseText;

					location.href = "agregar_tarifa.php?popup=<?php echo $popup ?>&id_tarifa_eliminar=<?php echo $id_tarifa_edicion ? $id_tarifa_edicion : $id_tarifa_previa ?>&opc=eliminar";
				} else {
					return false;
				}
			} else {
				if (confirm('�<?php echo __('Est� seguro de eliminar la') . " " . __('tarifa') ?>?')) {
					location.href = "agregar_tarifa.php?popup=<?php echo $popup ?>&id_tarifa_eliminar=<?php echo $id_tarifa_edicion ? $id_tarifa_edicion : $id_tarifa_previa ?>&opc=eliminar";
				}
			}
		} else {
			alert('No puede eliminar la tarifa est�ndar (por defecto)');
			return false;
		}
	}

	function ActualizarTarifaUsuario(glosa_categoria, valor, glosa_moneda, vacio) {
		var glosa_moneda_tarifa = glosa_moneda.replace(" ", "");
		var clase = '.' + glosa_categoria + '' + glosa_moneda_tarifa;

		// la expresi�n regular busca los parentesis y espacios, le agrega un "\"" para escaparlos
		// (?=) busca lo que esta alrededor http://www.regular-expressions.info/lookaround.html
		// ".Socio (1)"" => ".Socio\ \(1\)"
		clase = clase.replace(/(?=[() ])/g, '\\');

		if (!vacio || confirm('<?php echo __('Confirma cambio de tarifa para todos los usuarios de esta categoria?') ?>')) {
			jQuery(clase).each(function (index, value) {
				jQuery(value).val(valor);
			});
		}
	}

	function CrearTarifa(from, id)
	{
		element = document.getElementById('usar_tarifa_previa');
		if (element && element.checked)
		{
			self.location.href = 'agregar_tarifa.php?nueva=true&popup=<?php echo $popup ?>&id_tarifa_previa=' + id;
		}
		else {
			self.location.href = 'agregar_tarifa.php?nueva=true&popup=<?php echo $popup ?>';
		}
	}
</script>

<style>
	#tbl_tarifa
	{
		font-size: 10px;
		padding: 1px;
		margin: 0px;
		vertical-align: middle;
		border:1px solid #CCCCCC;
	}
	.text_box
	{
		font-size: 10px;
		text-align:right;
	}
</style>

<table width="90%" class="tb_base"><tr><td align="center">
<form name="formulario" id="formulario" method="post" action="" autocomplete="off">
	<input type=hidden name='id_tarifa_edicion' value='<?php echo $tarifa->fields['id_tarifa'] ?>'>
	<input type=hidden name='opc' value='guardar'>
	<input type=hidden name='popup' id='popup' value='<?php echo $popup ?>'>

	<table width='95%' border="0" cellpadding="0" cellspacing="0">
		<tr>
			<?php
			$colspan = 3;

			if ($tarifa->fields['id_tarifa']) {
				$colspan = 5;
				?>
				<td style="text-align:left;vertical-align: middle;"><?php echo __('Tarifa') ?>:&nbsp;</td>
				<td style="text-align:left;vertical-align: middle;"><?php echo Html::SelectQuery($Sesion, "SELECT * FROM tarifa WHERE tarifa_flat IS NULL ORDER BY glosa_tarifa", "id_tarifa", $tarifa->fields['id_tarifa'], "onchange='cambia_tarifa(this.value)'", "", "120"); ?></td>
				<?php
			}
			?>
			<td style="text-align:left;vertical-align: middle;" colspan="2"> <?php echo __('Nombre') ?>: <input style="width:200px;" type=text name="glosa_tarifa" value='<?php echo $tarifa->fields['glosa_tarifa'] ?>' <?php echo $active ?>> </td>

			<td style="text-align:right;vertical-align: middle;"> <?php echo __('Defecto') ?>: <input type=checkbox name=tarifa_defecto value='1' <?php echo $tarifa->fields['tarifa_defecto'] ? 'checked readonly onclick="return false"' : '' ?>></td>
		</tr>
		<tr>
			<td colspan="<?php echo $colspan ?>" align=right>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="<?php echo $colspan - 1 ?>" align="right" style="text-align:right;" >
				<input type=submit value='<?php echo __('Guardar') ?>' class=btn > &nbsp;


				<input type="button" id="fix_tarifas" value='<?php echo __('Completar Tarifas') ?>' class='btn' title="Esta funci�n completar� las tarifas faltantes de los profesionales bas�ndose en su categor�a, para todas las tarifas" />

				<input type="button" onclick="self.location.href = 'tarifas_xls.php?id_tarifa_edicion=<?php echo $id_tarifa_edicion ?>&glosa=<?php echo $tarifa->fields['glosa_tarifa'] ?>'" value='<?php echo __('Imprimir tarifas') ?>' class='btn' >
				<input type="button" onclick="self.location.href = 'tarifas_clientes.php'" value='<?php echo __('Imprimir Todas') ?>' class='btn' title="Exporta todas las tarifas a un excel. Incluye qu� contratos estan afectos a cada una" >
			</td><td  style="text-align:left;vertical-align: middle;width:202px;" >
				<input type="button" onclick="CrearTarifa(this.form, '<?php echo $id_tarifa_edicion ?>');" value='<?php echo __('Crear nueva tarifa') ?>' class=btn title="Crea una nueva tarifa. Active el checkbox inferior para basarse en los datos de la actual">
				<input type="button" onclick="Eliminar();" value='<?php echo __('Eliminar Tarifa') ?>' class="btn_rojo" >
			</td>
		</tr>
		<?php if ($opc != "eliminar") { ?>
		<tr>
			<?php
			$colspan = 3;

			if ($tarifa->fields['id_tarifa']) {
				$colspan = 4;
			}
			?>
			<td colspan="<?php echo $colspan ?>"></td><td  align=left>
				<input type=checkbox id=usar_tarifa_previa value='1' <?php $usar_tarifa_previa ? 'checked' : '' ?> /> copiando la actual
			</td>
		</tr>
		<?php } ?>
	</table>
	<br>

	<?php
	######## MONEDAS #########
	$lista_monedas = new ListaObjetos($Sesion, '', "SELECT * from prm_moneda Order by id_moneda ASC");
	$td_moneda = '';
	for ($x = 0; $x < $lista_monedas->num; $x++) {
		$moneda = $lista_monedas->Get($x);
		$td_moneda .= "<td align=center class=\"border_plomo\"><b>" . $moneda->fields['glosa_moneda'] . "</b></td>";
	}

	########## CATEGORIA TARIFA ###########
	$td_categoria_tarifas = '';
	$cont = 0;
	$where = '1';
	if ($id_tarifa_edicion) {
		$where .= " AND categoria_tarifa.id_tarifa = '$id_tarifa_edicion'";
	} else if ($id_nuevo) {
		$where .= " AND categoria_tarifa.id_tarifa = '$id_nuevo'";
	} else if ($tarifa->fields['id_tarifa']) {
		$where .= " AND categoria_tarifa.id_tarifa = '" . $tarifa->fields['id_tarifa'] . "'";
	} else {
		$where = 'categoria_tarifa.id_tarifa IS NULL';
	}

	#Revisar coordinacion de usuarios con usuario_tarifa
	$query_tarifas_categoria = "SELECT categoria_tarifa.id_categoria_usuario,
									categoria_tarifa.id_tarifa,
									IF(categoria_tarifa.tarifa >= 0,categoria_tarifa.tarifa,'') AS tarifa,
									categoria_tarifa.id_moneda
								FROM categoria_tarifa
								JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario=categoria_tarifa.id_categoria_usuario
								WHERE $where
								ORDER BY prm_categoria_usuario.glosa_categoria,prm_categoria_usuario.id_categoria_usuario, categoria_tarifa.id_moneda ASC";
	$resp_categoria = mysql_query($query_tarifas_categoria, $Sesion->dbh) or Utiles::errorSQL($query_tarifas_categoria, __FILE__, __LINE__, $Sesion->dbh);
	list($id_categoria_usuario_tarifa, $id_tarifa, $valor, $id_moneda) = mysql_fetch_array($resp_categoria);

	########## CATEGORIA TARIFA #########
	$query_categoria = "SELECT id_categoria_usuario, REPLACE(glosa_categoria,' ','_') AS glosa_categoria_corregido
												FROM prm_categoria_usuario
												ORDER BY glosa_categoria,id_categoria_usuario";
	$resp_categoria2 = mysql_query($query_categoria, $Sesion->dbh) or Utiles::errorSQL($query_categoria, __FILE__, __LINE__, $Sesion->dbh);
	$result_categoria = mysql_query("SELECT FOUND_ROWS()");
	$row_categoria = mysql_fetch_row($result_categoria);
	$total_categoria = $row_categoria[0];
	while (list($id_categoria_usuario, $glosa_categoria) = mysql_fetch_array($resp_categoria2)) {
		$cont++;
		$glosa_categoria_2 = preg_replace("/_/", " ", $glosa_categoria);
		$glosa_categoria = str_replace('/', '', $glosa_categoria);
		$td_categoria_tarifas .= '<tr><td align=left class="border_plomo">' . $glosa_categoria_2 .
			UtilesApp::LogDialog($Sesion, 'categoria_tarifa', 1000000 * $id_tarifa + $id_categoria_usuario) . '</td>';
		$tab = $cont;
		for ($j = 0; $j < $lista_monedas->num; $j++) {
			$tab += ($total_categoria * ($j + 1)) + $j;
			$money = $lista_monedas->Get($j);
			$glosa_moneda = preg_replace("/�/", "o", $money->fields['glosa_moneda']);

			if ($id_moneda == $money->fields['id_moneda'] && $id_categoria_usuario_tarifa == $id_categoria_usuario) {
				$td_categoria_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='text_box' name='tarifa_categoria_moneda[$id_categoria_usuario][" . $money->fields['id_moneda'] . "]' value='" . $valor . "' $active tabindex=$tab onChange=\"ActualizarTarifaUsuario('$glosa_categoria',this.value,'$glosa_moneda','$valor');\"></td> \n";
				list($id_categoria_usuario_tarifa, $id_tarifa, $valor, $id_moneda) = mysql_fetch_array($resp_categoria);
			} else {
				$td_categoria_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='text_box' name='tarifa_categoria_moneda[$id_categoria_usuario][" . $money->fields['id_moneda'] . "]' value='' $active tabindex=$tab onChange=\"ActualizarTarifaUsuario('$glosa_categoria',this.value,'$glosa_moneda');\"></td> \n";
			}
		}
		$td_categoria_tarifas .= '</tr>';
	}
	$cont = $tab; // deja tabindex equal al maximo de la tabla de categorias para que no se interfere con la tabla de usuarios
	########## USUARIO TARIFA ###########
	$td_tarifas = '';
	$where = '1';
	if ($id_tarifa_edicion) {
		$where .= " AND usuario_tarifa.id_tarifa = '$id_tarifa_edicion'";
	} else if ($id_nuevo) {
		$where .= " AND usuario_tarifa.id_tarifa = '$id_nuevo'";
	} else if ($tarifa->fields['id_tarifa']) {
		$where .= " AND usuario_tarifa.id_tarifa = '" . $tarifa->fields['id_tarifa'] . "'";
	} else {
		$where = 'usuario_tarifa.id_tarifa IS NULL';
	}

	#Revisar coordinacion de usuarios con usuario_tarifa
	$query_tarifas = "SELECT
						usuario_tarifa.id_usuario,
						usuario_tarifa.id_tarifa,
						IF(usuario_tarifa.tarifa >= 0, usuario_tarifa.tarifa, '') AS tarifa,
						usuario_tarifa.id_moneda
					FROM usuario_tarifa
					JOIN usuario ON usuario_tarifa.id_usuario = usuario.id_usuario
					JOIN usuario_permiso ON usuario_permiso.id_usuario=usuario_tarifa.id_usuario
					WHERE $where
					AND usuario.visible = 1 AND usuario_permiso.codigo_permiso = 'PRO'
					ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario, usuario_tarifa.id_moneda ASC";
	$resp = mysql_query($query_tarifas, $Sesion->dbh) or Utiles::errorSQL($query_tarifas, __FILE__, __LINE__, $Sesion->dbh);
	list($id_usuario_tarifa, $id_tarifa, $valor, $id_moneda) = mysql_fetch_array($resp);

	########## USUARIO TARIFA #########
	$query = "SELECT
				usuario.id_usuario,
				CONCAT(usuario.apellido1,' ',usuario.apellido2,' ',usuario.nombre) AS nombre_usuario,
				REPLACE(prm_categoria_usuario.glosa_categoria,' ','_') as glosa_categoria
			FROM usuario
			JOIN usuario_permiso USING(id_usuario)
			LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario.id_categoria_usuario
			WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso = 'PRO' ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario";
	$resp2 = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	$result = mysql_query("SELECT FOUND_ROWS()");
	$row = mysql_fetch_row($result);
	$total = $row[0];
	while (list($id_usuario, $nombre_usuario, $glosa_categoria) = mysql_fetch_array($resp2)) {
		$cont++;
		$glosa_categoria = str_replace('/', '', $glosa_categoria);
		$td_tarifas .= '<tr><td align=left class="border_plomo">' . $nombre_usuario .
			UtilesApp::LogDialog($Sesion, 'usuario_tarifa', 1000000 * $id_tarifa + $id_usuario) . '</td>';
		$tab = $cont;
		for ($j = 0; $j < $lista_monedas->num; $j++) {
			$tab += ($total * ($j + 1)) + $j;
			$money = $lista_monedas->Get($j);
			$glosa_moneda = str_replace(' ', '', $money->fields['glosa_moneda']);
			$nombre_clase = preg_replace("/�/", "o", $glosa_categoria . $glosa_moneda);

			if ($id_moneda == $money->fields['id_moneda'] && $id_usuario_tarifa == $id_usuario) {
				$td_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='$nombre_clase' id='' name='tarifa_moneda[$id_usuario][" . $money->fields['id_moneda'] . "]' value='" . $valor . "' $active tabindex=$tab></td> \n";
				list($id_usuario_tarifa, $id_tarifa, $valor, $id_moneda) = mysql_fetch_array($resp);
			}
			else
				$td_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='$nombre_clase' name='tarifa_moneda[$id_usuario][" . $money->fields['id_moneda'] . "]' value='' $active tabindex=$tab></td> \n";
		}
		$td_tarifas .= '</tr>';
	}
	?>
	<table width='95%' border="1px solid #BDBDBD" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
		<tr bgcolor=#A3D55C>
			<td align=left class="border_plomo"><b><?php echo __("Categor�a") ?></b></td>
			<?php echo $td_moneda ?>
		</tr>
		<?php echo $td_categoria_tarifas ?>
	</table>
	<br>

	<table width='95%' border="1px solid #BDBDBD" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
		<tr bgcolor=#A3D55C>
			<td align=left class="border_plomo"><b><?php echo __("Profesional") ?></b></td>
			<?php echo $td_moneda ?>
		</tr>
		<?php echo $td_tarifas ?>
	</table>
	</form>
	</td></tr></table>
	<br>
<?php
$Pagina->PrintBottom($popup);
