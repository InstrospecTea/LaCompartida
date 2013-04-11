<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion(array('ADM'));
$pagina = new Pagina($Sesion);

$pagina->titulo = __('Configuraci�n');
$pagina->PrintTop();

if ($opc == 'guardar') {
	$Sesion->pdodbh->exec("update configuracion set id_configuracion_categoria=10 where glosa_opcion='BeaconTimer'");
	foreach ($opcion as $id => $valor) {
		if (isset($opcion_hidden[$id])) {
			$opcion_hidden[$id] = 1;
		}
		if ($id == 'radio') {
			$radios = $valor;
			foreach ($radios as $name => $id) {
				$query = "UPDATE configuracion SET valor_opcion='0' WHERE NOT id='$id' AND valores_posibles='radio;$name' AND valor_opcion='1'";
				mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
				$query = "UPDATE configuracion SET valor_opcion='1' WHERE id='$id' AND valor_opcion='0'";
				mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
			}
		} else {
			$valor = trim(str_replace("\n", '', utf8_decode($valor)));
			$query = "UPDATE configuracion SET valor_opcion='$valor' WHERE id='$id'";
			mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		}
	}
	foreach ($opcion_hidden as $id => $valor)
		if ($valor == 0) {
			$query = "UPDATE configuracion SET valor_opcion='" . utf8_decode($valor) . "' WHERE id='$id'";
			mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		}

	ContratoDocumentoLegal::EliminarDocumentosLegales($Sesion, null, true);
	if (is_array($docs_legales)) {
		foreach ($docs_legales as $doc_legal) {
			if (empty($doc_legal['documento_legal']) or ( empty($doc_legal['honorario']) and empty($doc_legal['gastos_con_iva']) and empty($doc_legal['gastos_sin_iva']) )) {
				continue;
			}
			$contrato_doc_legal = new ContratoDocumentoLegal($Sesion);
			$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
			if (!empty($doc_legal['honorario'])) {
				$contrato_doc_legal->Edit('honorarios', 1);
			}
			if (!empty($doc_legal['gastos_con_iva'])) {
				$contrato_doc_legal->Edit('gastos_con_impuestos', 1);
			}
			if (!empty($doc_legal['gastos_sin_iva'])) {
				$contrato_doc_legal->Edit('gastos_sin_impuestos', 1);
			}
			$contrato_doc_legal->Edit('id_tipo_documento_legal', $doc_legal['documento_legal']);
			$contrato_doc_legal->Write();
		}
	}
	// piso los valores cacheados del conf
	$query = "SELECT glosa_opcion, valor_opcion FROM configuracion";
	$bd_configs = $Sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_GROUP);
	foreach ($bd_configs as $glosa => $valor) {
		$Sesion->arrayconf[$glosa] = $valor[0][0];
	}
	global $memcache;
	$existememcache = isset($memcache) && is_object($memcache);
	// 4.2) Si existe memcache, fijo la llave usando lo obtenido en 4.1
	if ($existememcache) {
		$memcache->set(DBNAME . '_config', json_encode($Sesion->arrayconf), false, 120);
		error_log("MEMCACHE CACHE SET $conf = {$Sesion->arrayconf[$conf]} (" . count($Sesion->arrayconf) . " registros)");
	}
}
?>

<script type="text/javascript">

	function Ocultar(version, trID, valor_hidden, inputID) {
		var trArea = document.getElementById(trID);
		var valorhidden = document.getElementById(valor_hidden);
		var string_asuntos = valorhidden.value;

		trArea.style['display'] = "none";

		if (version == 'nuevo') {
			var str = ';' + document.getElementById(inputID).value;
		} else {
			var str = ';' + trID;
		}
		string_asuntos = string_asuntos.replace(str, "");
		valorhidden.value = string_asuntos;
	}

	function MuestraOculta(divID) {
		var divArea = $(divID);
		var divAreaImg = $(divID + "_img");
		var divAreaVisible = divArea.style['display'] != "none";

		if (divAreaVisible) {
			divArea.style['display'] = "none";
			divAreaImg.innerHTML = "<img src='../templates/default/img/mas.gif' border='0' title='Desplegar'>";
		} else {
			divArea.style['display'] = "inline";
			divAreaImg.innerHTML = "<img src='../templates/default/img/menos.gif' border='0' title='Ocultar'>";
		}
	}


	function AgregarAsunto(numero, valor_hidden) {
		var tr_elemento = document.getElementById('hidden_' + numero);
		var input_elemento = document.getElementById('text_' + numero);
		var div_elemento = document.getElementById('texto_' + numero);
		var img_elemento = document.getElementById('img_' + numero);
		var agregar_elemento = document.getElementById('agregar_' + numero);
		var valorhidden = document.getElementById(valor_hidden);
		var string_asuntos = valorhidden.value;

		var array_asuntos = string_asuntos.split(';');
		if (jQuery('#asuntos_por_separado').is(':checked')) {
			string_asuntos = 'true';
		} else {
			string_asuntos = 'false';
		}
		for (var i = 1; i < array_asuntos.length; i++) {
			if (input_elemento.value == array_asuntos[i]) {
				alert('El asunto indicado ya existe.');
				input_elemento.focus();
				return false;
			}
			string_asuntos += ';' + array_asuntos[i];
		}
		string_asuntos += ';' + input_elemento.value;
		valorhidden.value = string_asuntos;
		div_elemento.innerHTML = input_elemento.value;
		div_elemento.style.display = 'inline';
		input_elemento.style.display = 'none';

		agregar_elemento.style.display = 'none';
		img_elemento.style.display = 'block';

		numero_nuevo = (numero - 0) + 1;
		var tr_elemento_nuevo = document.getElementById('hidden_' + numero_nuevo);
		tr_elemento_nuevo.style.display = 'table-row';
	}
</script>
<style>
	#flechaverde {background:url('https://static.thetimebilling.com/images/arrowleft.png') 0 -5px no-repeat;display:block;width:41px;height:20px;display:none;position:absolute;}
	.filasx td label {width:45%;display:inline-block;float:left;}
</style>
<div id="flechaverde" ></div>


<?php
if ($Sesion->usuario->fields['rut'] != '99511620') {
	$where_orden = " WHERE orden > -1 ";
} else {
	$where_orden = "WHERE glosa_configuracion_categoria!='Plugins - Hooks'";
}
$query = "SELECT  glosa_opcion,id, valor_opcion, comentario, valores_posibles, configuracion.id_configuracion_categoria, glosa_configuracion_categoria, orden
					FROM configuracion
					JOIN configuracion_categoria ON configuracion.id_configuracion_categoria=configuracion_categoria.id_configuracion_categoria
					$where_orden
					ORDER BY configuracion.id_configuracion_categoria, orden, glosa_opcion ASC";
$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
$arrayopciones = array();
$ConfRS = $Sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC);
//echo '<pre>';print_r($ConfRS);echo '</pre><hr>';
?>
<div style="padding:5px;" id="buscacampos">Buscar un campo en particular:&nbsp;&nbsp;&nbsp;</div>

<div id="configuracion" class="tabs"	>

	<ul id="tabs">

	</ul>
	<div id="tabs-1">
		<form name="formulario" id="formulario" method="post" action='' autocomplete="off" onsubmit="return validar_doc_legales(true)">
			<input type=hidden name='opc' value='guardar'>
			<?php
			$arraycategorias = array();
			foreach ($ConfRS as $arrayvalores) {
				//print_r($arrayvalores);
				$llave = $arrayvalores['id_configuracion_categoria'] . '|' . $arrayvalores['glosa_configuracion_categoria'];
				$arraycategoria[$llave][] = $arrayvalores;

				$arrayopciones[$arrayvalores['glosa_opcion']] = array($arrayvalores['id'], $arrayvalores['glosa_opcion'], $arrayvalores['id_configuracion_categoria']);
			}



			foreach ($arraycategoria as $id_categoria => $accionescategoria) {
				$datoscategoria = explode('|', $id_categoria);

				echo "<div class=\"grupoconf\" id='caja" . $datoscategoria[0] . "' rel='" . $datoscategoria[1] . "' >
					<table width='80%'  >";

				foreach ($accionescategoria as $valoropcion) {

					list($glosa_opcion, $id, $valor_opcion, $comentario, $valores_posibles, $id_categoria, $glosa_categoria, $orden) = array_values($valoropcion);


					$tooltip = $comentario ? Html::Tooltip($comentario) : '';
					echo "<tr class='filasx' $tooltip id='fila_$id'><td align=left width=99%><label for='opcion_$id'>" . __($glosa_opcion) . "</label>";

					$valores = explode(';', $valores_posibles);

					switch ($valores[0]) {
						case 'boolean':
							// generar checkbox, se pone un input hidden para poder saber si se desmarca un checkbox
							echo "<input type='hidden' value='0' name='opcion_hidden[$id]' />";
							echo "<input type='checkbox' value='1' name='opcion[$id]' id='opcion_$id' " . ($valor_opcion ? "checked='checked'" : "") . " />";
							break;
						case 'radio':
							// generar radiobutton para eligir entre opciones
							echo "<input type='radio' " . ($valor_opcion ? "checked='checked'" : "") . " name='opcion[radio][{$valores[1]}]' id='opcion_$id' value='$id' />";
							break;
						case 'array':
							echo "<input type='hidden' id='opcion[$id]' name='opcion[$id]' value='" . $valor_opcion . "' />";
							$valores_array = explode(';', $valor_opcion);
							echo "<input type='checkbox'  id='usa_asuntos_por_defecto' " . ($valores_array[0] == "true" ? "checked='checked'" : "") . " rel='opcion[$id]' />";
							echo "<table id='tabla_asuntos' " . ((!$valor_opcion) ? "style='display: none;'" : "") . ">";
							for ($i = 1; $i < count($valores_array); ++$i)
								echo "<tr id='$valores_array[$i]'><td>" . $valores_array[$i] . "</td><td><img style=\"filter:alpha(opacity=100);\" src='" . Conf::ImgDir() . "/cruz_roja_13.gif' border='0' class='mano_on' alt='Ocultar' onclick=\"Ocultar('viejo','" . $valores_array[$i] . "', 'opcion[" . $id . "]');\"/></td></tr>";
							echo "<tr id='hidden_1'><td><input type='text' id='text_1' size='12' value='' /><div id='texto_1' style=\"display:none;\"/></td><td><img id='img_1' style=\"display:none; filter:alpha(opacity=100);\" src='" . Conf::ImgDir() . "/cruz_roja_13.gif' border='0' class='mano_on' alt='Ocultar' onclick=\"Ocultar('nuevo','hidden_1', 'opcion[" . $id . "]', 'text_1');\"/><input type='button' id='agregar_1' name='agregar_asunto' value=\"Agregar Asunto\" onclick=\"AgregarAsunto('1','opcion[" . $id . "]');\" /></td></tr>";
							for ($j = 2; $j < 21; ++$j)
								echo "<tr id='hidden_" . $j . "' style=\"display: none;\"><td><input type='text' id='text_" . $j . "' size='12' value='' /><div id='texto_" . $j . "' style=\"display:none;\"/></td><td><img id='img_" . $j . "' style=\"display:none; filter:alpha(opacity=100);\" src='" . Conf::ImgDir() . "/cruz_roja_13.gif' border='0' class='mano_on' alt='Ocultar' onclick=\"Ocultar('nuevo','hidden_" . $j . "', 'opcion[" . $id . "]','text_" . $j . "');\"/><input type='button' id='agregar_" . $j . "' name='agregar_asunto' value=\"Agregar Asunto\" onclick=\"AgregarAsunto('" . $j . "','opcion[" . $id . "]');\"/></td></tr>";
							echo "</table>";

							break;
						case 'numero':
						case 'string':
							// generar input de texto
							echo "<input type='text' size='64' class='text_box' name='opcion[$id]' value='$valor_opcion' id='opcion_$id' />";
							break;
						case 'text':
							// generar input de texto largo
							echo "<textarea name='opcion[$id]' id='opcion_$id' cols=45 rows=2>" . $valor_opcion . "</textarea>";
							break;
						case 'select':
							// generar select usando los siguientes valores
							echo "<select name='opcion[$id]' id='opcion_$id'>";
							for ($i = 1; $i < count($valores); ++$i)
								echo "<option value='$valores[$i]'" . ($valores[$i] == $valor_opcion ? " selected='selected'" : "") . ">" . ($valores[$i] ? __($valores[$i]) : '0') . "</option>";
							echo '</select>';
							break;
						case 'fecha':
							// generar DatePicker
							echo "<input type=\"text\"  name='fecha' value='$valor_opcion' id='fecha' class='fechadiff' size=11 maxlength=10 />";

							break;
					}
					echo "</td></tr>\n";
				}

				echo "</table></div>";
			}


			echo '<select id="buscacampo" class="comboplus" width="300px;" ><option value="0-0"></option>';
			foreach ($arrayopciones as $opcion) {
				echo '<option  value="' . $opcion[0] . '-' . $opcion[2] . '">' . $opcion[1] . '</option>';
			}
			echo '</select>';
			?>

			</div>
			<br><br>
			<div class="grupoconf" id="caja20" rel="Documentos Legales" >

				<p><center>Ingrese los documentos legales que desea generar en el proceso de facturaci�n<br>(solamente aplica al nuevo m�dulo de facturaci�n)</center></p>
				<?php include dirname(__FILE__) . '/agregar_doc_legales.php'; ?>

			</div>
			<div class="grupoconf" id="cajalang" rel="Lang" >
				<p><center>Active y Ordene los archivos de lang a cargar. La primera fila se carga en primer lugar, y puede ser sobreescrita por las siguientes (y as� sucesivamente)</center></p>

				<div id="formulariolang"></div>

				<br /><br /><a href="#" class="botonizame" icon="ui-icon-save" id="guardalangs"  setwidth="200">Guardar</a>

			</div>
			<div class="grupoconf" id="cajaplugin" rel="Plugins" >

				<p>En esta pantalla se activan o desactivan los plugins. No toque nada si no sabe para qu� sirve</p>
				<div id="formularioplugins"></div>

				<br /><br /><a href="#" class="botonizame" icon="ui-icon-save" id="guardaplugins"  setwidth="200">Guardar</a> </div>


			<table>
				<tr><td>&nbsp;</td>
					<td><a href="javascript:void(0)" class="btn botonizame"icon="ui-icon-save"  id="enviarconf" ><?php echo __('Guardar') ?></a></td>
				</tr>
				<tr><td colspan="2" id="mensaje">&nbsp;</td>

				</tr>
			</table>
		</form>
	</div>



	<script language="javascript" type="text/javascript">
	jQuery(document).ready(function() {


		jQuery('#buscacampos').append(jQuery('#buscacampo'));

		jQuery('#buscacampo').change(function() {
			var clave = jQuery('#buscacampo').val().split('-')
			jQuery('#configuracion').tabs("select", clave[1] - 1);
			jQuery('#fila_' + clave[0]).css('background-color', '#FF9');
			var pos = jQuery('#fila_' + clave[0]).offset();

			jQuery('#flechaverde').show().offset({top: pos.top, left: pos.left});
		});
		jQuery('.ui-corner-all').live('click', function() {
			var clave = jQuery('#buscacampo').val().split('-')
			jQuery('#configuracion').tabs("select", clave[1] - 1);
			jQuery('.filasx').css('background-color', '#FFF');
			jQuery('#fila_' + clave[0]).css('background-color', '#FF9');
			var pos = jQuery('#fila_' + clave[0]).offset();
			jQuery(window).scrollTop(pos.top - 100);
			jQuery('#flechaverde').show().css('z-index', 500).offset({top: pos.top, left: pos.left - 60});
		});

		jQuery('#enviarconf').click(function() {
			jQuery.post('configuracion.php', jQuery('#formulario').serialize(), function(data) {
				jQuery('#mensaje').delay(1000).html('');
			});
			jQuery('#mensaje').append('Enviando configuracion...');
		});
		jQuery('#usa_asuntos_por_defecto').click(function() {
			hiddenid = jQuery(this).attr('rel');
			var string_asuntos = document.getElementById(hiddenid).value;
			var array_asuntos = string_asuntos.split(';');
			if (jQuery(this).is(':checked')) {
				string_asuntos = 'true';
			} else {
				string_asuntos = 'false';
			}

			for (var i = 1; i < array_asuntos.length; i++)
			{
				string_asuntos += ';' + array_asuntos[i];
			}

			document.getElementById(hiddenid).value = string_asuntos;

		});
		jQuery('.grupoconf').each(function() {
			var LaID = jQuery(this).attr('id');
			var Glosa = jQuery(this).attr('rel');

			jQuery('#tabs').append('<li><a href="#' + LaID + '">' + Glosa + '</a></li>');
		});

		jQuery('#guardalangs').click(function() {
			jQuery.post('../ajax.php', jQuery('#formlangs').serialize(), function(guardar) {
				jQuery.get('../../admin/archivos_lang.php', function(data) {
					jQuery('#formulariolang').append(data);
					jQuery(".buttonset").buttonset();
					jQuery('.sortable').sortable();
				});
				jQuery('#formulariolang').html('');
			});
		});

		jQuery('#guardaplugins').click(function() {
			jQuery.post('../ajax.php', jQuery('#formplugins').serialize(), function(guardar) {
				jQuery.get('../../admin/archivos_plugins.php', function(data) {
					jQuery('#formularioplugins').append(data);
					jQuery(".buttonset").buttonset();
					jQuery('.sortable').sortable();

				});
				jQuery('#formularioplugins').html('');
			});
		});

		jQuery('#configuracion').bind("tabsselect", function(event, ui) {

			if (ui.tab.textContent == 'Lang') {
				jQuery('#enviarconf').hide();
				jQuery.get('../../admin/archivos_lang.php', function(data) {
					jQuery('#formulariolang').append(data);
					jQuery(".buttonset").buttonset();
					jQuery('.sortable').sortable();
				});
				jQuery('#formulariolang').html('');
			} else if (ui.tab.textContent == 'Plugins') {
				jQuery('#enviarconf').hide();
				jQuery.get('../../admin/archivos_plugins.php', function(data) {
					jQuery('#formularioplugins').append(data);
					jQuery(".buttonset").buttonset();
					jQuery('.sortable').sortable();

				});
				jQuery('#formularioplugins').html('');
			} else {
				jQuery('#enviarconf').show();
			}


		});


		jQuery('#input_buscacampo').focus();
	});


	</script>
	<!-- <script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>-->


	<?php
	$pagina->PrintBottom($popup);


