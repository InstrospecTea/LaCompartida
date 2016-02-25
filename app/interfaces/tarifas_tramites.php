<?php
require_once dirname(__FILE__).'/../conf.php';

$sesion = new Sesion(array('TAR'));
$pagina = new Pagina($sesion);
$tramite_tarifa = new TramiteTarifa($sesion);

if ($opc == 'eliminar') {
	$tramite_tarifa_eliminar = new TramiteTarifa($sesion);
	$tramite_tarifa_eliminar->loadById($id_tramite_tarifa_eliminar);

	// validate if the last rate can not be removed
	if ($tramite_tarifa_eliminar->countRates() > 1) {
		if ($tramite_tarifa_eliminar->Eliminar()) {
			$pagina->AddInfo(__('La tarifa tramite se ha eliminado satisfactoriamente'));
		} else {
			$pagina->AddError($tramite_tarifa_eliminar->error);
		}
	} else {
		$pagina->AddError(__('Al menos debe quedar una tarifa activa en el sistema'));
	}

	$id_tramite_tarifa_edicion = $tramite_tarifa_eliminar->getFirstIdRate();
}

if (!$tramite_tarifa->Load($id_tramite_tarifa_edicion) && $crear != 1) {
	$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_tramite_tarifa_edicion) = mysql_fetch_array($resp);
	$tramite_tarifa->Load($id_tramite_tarifa_edicion);
}

if ($opc != 'guardar') {
	$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE guardado = 0";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	while (list($id) = mysql_fetch_array($resp)) {
		$tramite_tarifa_eliminar = new TramiteTarifa($sesion);
		$tramite_tarifa_eliminar->loadById($id);
		$tramite_tarifa_eliminar->Eliminar();
	}
}

if ($crear == 1 && !$id_tramite_tarifa_edicion && $opc != 'guardar') {
	$query = "INSERT INTO tramite_tarifa(fecha_creacion) VALUES(NOW())";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	$query = "SELECT id_tramite_tarifa FROM tramite_tarifa ORDER BY id_tramite_tarifa DESC";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_nuevo) = mysql_fetch_array($resp);

	$tramite_tarifa->loadById($id_nuevo);
	$id_tramite_tarifa_edicion = $tramite_tarifa->fields['id_tramite_tarifa'];
} else if($id_tramite_tarifa_previa && !$id_tramite_tarifa_edicion) {
	$query = "SELECT id_tramite_tarifa FROM tramite_tarifa ORDER BY id_tramite_tarifa DESC";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($id_nuevo) = mysql_fetch_array($resp);

	$tramite_tarifa->loadById($id_nuevo);
	$id_tramite_tarifa_edicion = $tramite_tarifa->fields['id_tramite_tarifa'];
}

if ($id_tramite_tarifa_previa && $opc != 'guardar') {
	$query = "SELECT id_tramite_tipo, id_moneda, tarifa FROM tramite_valor WHERE id_tramite_tarifa = {$id_tramite_tarifa_previa}";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	while (list($id_tramite_tipo, $id_moneda, $tarifa) = mysql_fetch_array($resp)) {
		$query2 = "INSERT INTO tramite_valor(id_tramite_tipo, id_moneda, tarifa, id_tramite_tarifa) VALUES({$id_tramite_tipo}, {$id_moneda}, {$tarifa}, {$id_nuevo})";
		$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
	}
}

if ($opc == 'guardar') {
	$tramite_tarifa->Edit('glosa_tramite_tarifa', $glosa_tramite_tarifa);

	// if is it the only record, assign as default rate
	if ($tramite_tarifa->countRates() == 1) {
		$tarifa_defecto = true;
	}

	if ($tarifa_defecto) {
		$tramite_tarifa->TarifaDefecto($tramite_tarifa->fields['id_tramite_tarifa']);
		$tramite_tarifa->Edit('tarifa_defecto', '1');
	} else {
		$tramite_tarifa->Edit('tarifa_defecto', '0');
	}

	if ($tramite_tarifa->Write()) {
		$tramite_valor = new TramiteValor($sesion);
		foreach($tarifa_moneda as $id_tramite_tipo => $arr_moneda) {
			foreach ($arr_moneda as $id_moneda => $tarifa_monto) {
				$tramite_valor->GuardarTarifa( $id_tramite_tarifa, $id_tramite_tipo, $id_moneda, $tarifa_monto);
			}
		}

		$id_tramite_tarifa_edicion = $tramite_tarifa->fields['id_tramite_tarifa'];
		$query = "UPDATE tramite_tarifa SET guardado = 1 WHERE id_tramite_tarifa = {$id_tramite_tarifa_edicion}";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	}

	$pagina->AddInfo(__('La tarifa se ha modificado satisfactoriamente'));
}

$pagina->titulo = __('Ingreso de Tarifas de Tr�mites');

$id_tr_tar_ed = $id_tramite_tarifa_edicion;
$pagina->PrintTop($popup);
$id_tramite_tarifa_edicion = $id_tr_tar_ed;

$active = ' onFocus="foco(this);" onBlur="no_foco(this);" ';
?>

<script type="text/javascript">
	function foco(elemento) {
		elemento.style.border = '2px solid #000000';
	}

	function cambia_tarifa(valor) {
		var popup = $('popup').value;
		if (confirm('<?php echo __('Confirma cambio de tarifa?'); ?>')) {
			self.location.href = 'tarifas_tramites.php?id_tramite_tarifa_edicion=' + valor + '&popup=' + popup;
		}
	}

	function no_foco(elemento) {
		elemento.style.border = '1px solid #CCCCCC';
	}

	function Eliminar() {
		var http = getXMLHTTP();
		http.open('get', 'ajax.php?accion=obtener_tramite_tarifa_defecto&id_tarifa=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
		http.send(null);
		tarifa_defecto_en_bd = http.responseText;

		if (tarifa_defecto_en_bd != <?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>){
			var http = getXMLHTTP();
			http.open('get', 'ajax.php?accion=contratos_con_esta_tramite_tarifa&id_tarifa=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
			http.send(null);
			num_contratos = http.responseText;

			if (num_contratos > 0) {
				respuesta_num_pagos = confirm('<?php echo  __('La tarifa posee'); ?> ' + num_contratos + ' <?php echo __('contratos asociados. \nSi continua se le asignar� la tarifa est�ndar a los contratos afectados.\n�Est� seguro de continuar?.'); ?>');
				if( respuesta_num_pagos ) {
					http.open('get', 'ajax.php?accion=cambiar_a_tramite_tarifa_por_defecto&id_tarifa=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>', false);  //debe ser syncrono para que devuelva el valor antes de continuar
					http.send(null);
					num_contratos = http.responseText;

					location.href="tarifas_tramites.php?popup=<?php echo $popup?>&id_tramite_tarifa_eliminar=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>&opc=eliminar";
				} else {
					return false;
				}
			} else {
				if (confirm('�<?php echo __('Est� seguro de eliminar la')." ".__('tarifa')?>?')) {
					location.href = "tarifas_tramites.php?popup=<?php echo $popup?>&id_tramite_tarifa_eliminar=<?php echo $id_tramite_tarifa_edicion ? $id_tramite_tarifa_edicion : $id_tramite_tarifa_previa ?>&opc=eliminar";
				}
			}
		} else {
			alert( 'No puede eliminar la tarifa est�ndar (por defecto)' );
			return false;
		}
	}

	function CrearTarifa(from, id) {
		if (document.getElementById('usar_tarifa_previa').checked) {
				self.location.href='tarifas_tramites.php?popup=<?php echo $popup?>&crear=1&id_tramite_tarifa_previa=' + id;
		} else {
			self.location.href='tarifas_tramites.php?popup=<?php echo $popup?>&crear=1';
		}
	}

</script>

<style>
	#tbl_tarifa {
		font-size: 10px;
		padding: 1px;
		margin: 0px;
		vertical-align: middle;
		border:1px solid #CCCCCC;
	}
	.text_box {
		font-size: 10px;
		text-align:right;
	}
</style>

<?php
if (Conf::GetConf($sesion,'UsaDisenoNuevo')) {
	echo '<table width="90%" class="tb_base"><tr><td align="center">';
}
?>

<form name="formulario" id="formulario" method="post" action="" autocomplete="off">
	<input type="hidden" name="id_tramite_tarifa_edicion" value="<?php echo $tramite_tarifa->fields['id_tramite_tarifa']?>">
	<input type="hidden" name="opc" value="guardar">
	<input type="hidden" name="popup" id="popup" value="<?php echo $popup ?>">

	<div style="width:95%; text-align:right; margin-bottom:5px;">
		<input type="button" onclick="CrearTarifa(this.form , <?php echo $id_tramite_tarifa_edicion ;?>);" value='<?php echo __('Crear nueva tarifa'); ?>' class="btn">
		<label><input type="checkbox" id="usar_tarifa_previa" value="1" <?php echo $usar_tarifa_previa ? 'checked' : '' ?> /><?php echo __('Copiar Datos'); ?></label>
	</div>
	<table width='95%' border="0" style="border: 1px solid #BDBDBD">
		<tr valign="middle">
			<?php
				if ($tramite_tarifa->fields['id_tramite_tarifa']) {
					if (!isset($crear) || $opc == 'guardar') {
						?>
						<td align="right"><?php echo __('Tarifa'); ?>:</td>
						<td align="left">
							<?php echo Html::SelectQuery($sesion, "SELECT * FROM tramite_tarifa ORDER BY glosa_tramite_tarifa", 'id_tramite_tarifa', $tramite_tarifa->fields['id_tramite_tarifa'],"onchange='cambia_tarifa(this.value)'", '', '120'); ?>
						</td>
						<td>
							<?php echo __('Nombre')?>:
							<input type="text" name="glosa_tramite_tarifa" value="<?php echo $tramite_tarifa->fields['glosa_tramite_tarifa']; ?>" <?php echo $active; ?>>
							<?php if ($tramite_tarifa->fields['tarifa_defecto']) { ?>
								<input type="hidden" name="tarifa_defecto" value="1"> <b><?php echo __('Tarifa por Defecto')?></b>
							<?php } else { ?>
								<label><input type="checkbox" name="tarifa_defecto" value="1" <?php echo $tramite_tarifa->fields['tarifa_defecto'] ? 'checked' : ''; ?>> <?php echo __('Defecto')?></label>
							<?php } ?>
						</td>
						<?php
					} else {
						?>
						<td align="right"><?php echo __('Nueva ' . __('Tarifa')); ?></td>
						<td align="left">
							<input type="text" name="glosa_tramite_tarifa" value="<?php echo $tramite_tarifa->fields['glosa_tramite_tarifa']; ?>" <?php echo $active; ?> placeholder="<?php echo __('Nombre') ?>">
							<label><input type="checkbox" name="tarifa_defecto" value="1" <?php echo $tramite_tarifa->fields['tarifa_defecto'] ? 'checked' : ''; ?>> <?php echo __('Defecto')?></label>
						</td>
						<?php
					}
				}
			?>
			<td align="right">
				<input type="submit" value='<?php echo __('Guardar'); ?>' class="btn">
				<input type="button" onclick="Eliminar();" value='<?php echo __('Eliminar Tarifa'); ?>' class="btn_rojo" >
			</td>
		</tr>
	</table>

	<br>
	<?php
	######## MONEDAS #########
	$lista_monedas = new ListaObjetos($sesion, '', "SELECT * from prm_moneda Order by id_moneda ASC");
	$td_moneda = '';
	for($x = 0; $x < $lista_monedas->num; $x++) {
		$moneda = $lista_monedas->Get($x);
		$td_moneda .= '<td align="center" class="border_plomo"><b>' . $moneda->fields['glosa_moneda'] . '</b></td>';
	}

	########## USUARIO TARIFA ###########
	$td_tarifas = '';
	$cont = 0;
	$where = '1';
	if ($id_tramite_tarifa_edicion) {
		$where .= " AND tramite_valor.id_tramite_tarifa = '$id_tramite_tarifa_edicion'";
	} else if($id_nuevo) {
		$where .= " AND tramite_valor.id_tramite_tarifa = '$id_nuevo'";
	} else if($tramite_tarifa->fields['id_tramite_tarifa']) {
		$where .= " AND tramite_valor.id_tramite_tarifa = '".$tramite_tarifa->fields['id_tramite_tarifa']."'";
	} else {
		$where = 'tramite_valor.id_tramite_tarifa IS NULL';
	}

	#Revisar coordinacion de usuarios con usuario_tarifa
	$query_tarifas = "SELECT	tramite_valor.id_tramite_tipo,
		tramite_valor.id_tramite_tarifa,
		IF(tramite_valor.tarifa >= 0,tramite_valor.tarifa,'') AS tarifa,
		tramite_valor.id_moneda
		FROM tramite_valor
		JOIN tramite_tipo ON tramite_valor.id_tramite_tipo = tramite_tipo.id_tramite_tipo
		WHERE {$where}
		ORDER BY tramite_tipo.glosa_tramite, tramite_tipo.id_tramite_tipo ASC";
	$resp = mysql_query($query_tarifas, $sesion->dbh) or Utiles::errorSQL($query_tarifas,__FILE__,__LINE__,$sesion->dbh);
	list($id_tramite_valor,$id_tramite_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp);

	########## TARIFA TRAMITE #########
	$query = "SELECT DISTINCT id_tramite_tipo, glosa_tramite
		FROM tramite_tipo
		ORDER BY glosa_tramite, id_tramite_tipo ASC";
	$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$result = mysql_query("SELECT FOUND_ROWS()");
	$row = mysql_fetch_row($result);
	$total = $row[0];

	while(list($id_tramite_tipo,$glosa_tramite) = mysql_fetch_array($resp2)) {
		$cont++;
		$td_tarifas .= '<tr><td align=left class="border_plomo">' . $glosa_tramite . '</td>';
		$tab = $cont;
		for ($j = 0; $j < $lista_monedas->num; $j++) {
			$tab += ($total * ($j+1)) + $j;
			$money = $lista_monedas->Get($j);
			$glosa_moneda=$money->fields['glosa_moneda'];

			if ($id_moneda == $money->fields['id_moneda'] && $id_tramite_valor == $id_tramite_tipo) {
				$td_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 id='' name='tarifa_moneda[$id_tramite_tipo][".$money->fields['id_moneda']."]' value='".$tarifa."' $active tabindex=$tab></td> \n";
				list($id_tramite_valor, $id_tramite_tarifa, $tarifa, $id_moneda) = mysql_fetch_array($resp);
			} else {
				$td_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 name='tarifa_moneda[$id_tramite_tipo][".$money->fields['id_moneda']."]' value='' $active tabindex=$tab></td> \n";
			}
		}
		$td_tarifas .= '</tr>';
	}

	if (Conf::GetConf($sesion,'UsaDisenoNuevo')) {
		?>
		<table width='95%' border="1" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom: none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
			<tr bgcolor="#A3D55C">
				<td align="left" class="border_plomo"><b><?php echo __("Tramite")?></b></td>
				<?php echo $td_moneda?>
			</tr>
			<?php echo $td_tarifas ?>
		</table>
		<?php
	} else {
		?>
		<table width='100%' border="1" style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom: none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
			<tr bgcolor=#6CA522>
				<td align=left><b><?php echo __("Tramite")?></b></td>
				<?php echo $td_moneda?>
			</tr>
			<?php echo $td_tarifas ?>
		</table>
		<?php
	} ?>
</form>

<br>

<?php
if (Conf::GetConf($sesion,'UsaDisenoNuevo')) {
	echo '</td></tr></table>';
}

$pagina->PrintBottom($popup);
