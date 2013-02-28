<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$sesion = new Sesion(array('ADM'));

$pagina = new Pagina($sesion);
$pagina->titulo = __("Carga masiva de $clase");
$pagina->PrintTop();

$CargaMasiva = new CargaMasiva($sesion);

if (isset($data) && isset($campos)) {
	$errores = $CargaMasiva->CargarData($data, $clase, $campos);
}

$listados = $CargaMasiva->ObtenerListados($clase);

$campos_clase = $CargaMasiva->ObtenerCampos($clase);
$titulos_campos = array_map(function($campo) {
		return $campo['titulo'];
	}, $campos_clase);

if (isset($raw_data)) {
	$data = $CargaMasiva->ParsearData($raw_data);
	$campos = array_keys($campos_clase);
}

if (empty($data)) {
	$data[] = array_fill(0, count($campos), '');
} else if (count($campos) > count($data[0])) {
	$campos = array_slice($campos, 0, count($data[0]));
} else {
	while (count($campos) < count($data[0])) {
		$campos[] = '';
	}
}
?>
<style type="text/css">
	#data thead tr{
		height: 40px;
		background-color: #42A62B;
	}
	#data tbody tr:nth-child(odd){
		background-color: #eee;
	}
	#data select{
		max-width: 100px;
	}
	.ok{
		background-color: #cfc !important;
	}
	.warning{
		background-color: #ffc !important;
	}
	.error{
		background-color: #fcc !important;
	}
</style>
<form method="POST" action="datos_carga_masiva.php">
	<table id="data">
		<thead>
			<tr>
				<?php foreach ($campos as $c => $campo) { ?>
					<th><?php echo Html::SelectArrayDecente($titulos_campos, "campos[$c]", $campo, '', '(ignorar)'); ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $idx => $fila) { ?>
				<tr <?php echo isset($errores[$idx]) ? 'class="error" title="' . $errores[$idx] . '"' : ''; ?>>
					<?php foreach ($fila as $c => $col) { ?>
						<td>
							<input id="<?php echo "data_{$idx}_{$c}"; ?>" name="<?php echo "data[$idx][$c]"; ?>" value="<?php echo $col; ?>" class="col_<?php echo $c; ?>"/>
							<div class="extra"/>
						</td>
					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>

	<input type="hidden" name="clase" value="<?php echo $clase; ?>"/>
	<input type="submit" value="Enviar"/>
</form>

<script type="text/javascript">
	var clase = '<?php echo $clase; ?>';
	var llave = '<?php echo $CargaMasiva->LlaveUnica($clase); ?>';

	/**
	 * listados[nombre][id] = glosa
	 * @type json
	 */
	var listados = <?php echo json_encode(UtilesApp::utf8izar($listados)); ?>;
	/**
	 * campos_clase[campo] = {titulo, tipo, unico, relacion, creable}
	 * @type json
	 */
	var campos_clase = <?php echo json_encode(UtilesApp::utf8izar($campos_clase)); ?>;

	/**
	 * multi_unicos[nombre llave unica multiple] = [lista ordenada de campos que la componen]
	 * @type json
	 */
	var multi_unicos = {};
	jQuery.each(campos_clase, function(campo, info) {
		if (typeof(info.unico) === 'string') {
			if (!multi_unicos[info.unico]) {
				multi_unicos[info.unico] = [];
			}
			multi_unicos[info.unico].push(campo);
		}
	});

	/**
	 * listados_inversos[nombre][glosa postprocesada] = id
	 * @type json
	 */
	var listados_inversos = {};
	jQuery.each(listados, function(nombre, listado) {
		listados_inversos[nombre] = {};
		jQuery.each(listado, function(id, valor) {
			if (typeof(valor) !== 'string') {
				var v = [];
				jQuery.each(valor, function(campo, valor_campo) {
					v.push(valor_campo);
				});
				valor = v.join(' / ');
			}
			listados_inversos[nombre][limpiar(valor)] = id;
		});
	});

	/** 
	 * idx_campos[campo] = numero de columna (th.index())
	 * @type json
	 */
	var idx_campos = {};

	/** 
	 * campos_idx[numero de columna (th.index())] = campo
	 * @type json
	 */
	var campos_idx = {};

	/**
	 * valores_unicos[llave unica / campo][valor postprocesado] = [numeros de filas]
	 * @type type
	 */
	var valores_unicos = {};
	jQuery.each(campos_clase, function(campo, info) {
		if (info.relacion || info.unico === true) {
			valores_unicos[campo] = {
				unico: info.unico
			};
		}
		else if (typeof(info.unico) === 'string') {
			valores_unicos[info.unico] = {
				unico: info.unico
			};
		}
	});

	/**
	 * columna_validada[indice] dice si se ejecuto alguna vez la validacion de los campos de esa columna
	 * @type json
	 */
	var columna_validada = {};
	jQuery('[name^=campos]').each(function(idx) {
		columna_validada[idx] = false;
	});

	/**
	 * agrega un valor a la lista de valores unicos, y elimina el anterior
	 * @param {string} campo
	 * @param {string} nuevo
	 * @param {string} viejo
	 * @param {int} idx_fila
	 */
	function agregarValorUnico(campo, nuevo, viejo, idx_fila) {
		nuevo = limpiar(nuevo);
		viejo = limpiar(viejo);

		var titulo = campos_clase[campo] ? campos_clase[campo].titulo : campo;

		//si ya existe en la BD
		if (valores_unicos[campo].unico && idx_campos[campo] !== undefined) {
			if (listados_inversos[campo][nuevo]) {
				var id = listados_inversos[campo][nuevo];
				var val = listados[campo][id];
				var ids = '';
				if (typeof(val) === 'string') {
					ids = '#data_' + idx_fila + '_' + idx_campos[campo];
					jQuery(ids).val(val);
				}
				else {
					//marcar todos los campos del multi-unico
					ids = [];
					var vals = [];
					jQuery.each(multi_unicos[campo], function(i, c) {
						var id = '#data_' + idx_fila + '_' + idx_campos[c];
						jQuery(id).val(val[c]);
						ids.push(id);
						vals.push(val[c]);
					});
					ids = ids.join(',');
					val = vals.join(' / ');
				}

				var msg = titulo + ' debe ser único, pero ya existe el valor ' + val + ' entre los datos actuales';
				if (campo === llave) {
					var tr = jQuery(ids).addClass('warning')
							.attr('title', msg + '. Se editará el ' + clase + ' existente')
							.closest('tr').addClass('warning').attr('data-id', id);

					if (!tr.hasClass('error')) {
						tr.attr('title', msg + '. Se editará el ' + clase + ' existente');
					}
				}
				else if (jQuery(ids).closest('tr').attr('data-id') != id) {
					jQuery(ids).addClass('error').attr('title', msg);
				}
			}
			else if (listados_inversos[campo][viejo] && multi_unicos[campo]) {
				jQuery.each(multi_unicos[campo], function(i, c) {
					jQuery('#data_' + idx_fila + '_' + idx_campos[c]).removeClass('error').removeAttr('title');
				});
			}
			else if (campo === llave) {
				var tr = jQuery('#data_' + idx_fila + '_' + idx_campos[campo]).closest('tr')
						.removeClass('warning').removeAttr('data-id');
				if (!tr.hasClass('error')) {
					tr.removeAttr('title');
				}
			}
		}

		if (nuevo === viejo) {
			return;
		}
		if (nuevo) {
			if (!valores_unicos[campo][nuevo]) {
				valores_unicos[campo][nuevo] = [];
			}
			var i = valores_unicos[campo][nuevo].indexOf(idx_fila);
			if (i < 0) {
				valores_unicos[campo][nuevo].push(idx_fila);
				if (valores_unicos[campo].unico && idx_campos[campo] !== undefined) {
					if (valores_unicos[campo][nuevo].length > 1) {
						inputsValoresUnicos(campo, nuevo)
								.addClass('error').attr('title', 'El campo ' + titulo + ' debe ser único, pero está repetido en esta carga');
					}
				}
			}
		}

		if (viejo && valores_unicos[campo][viejo]) {
			var i = valores_unicos[campo][viejo].indexOf(idx_fila);
			if (i >= 0) {
				valores_unicos[campo][viejo].splice(i, 1);

				switch (valores_unicos[campo][viejo].length) {
					case 0:
						delete valores_unicos[campo][viejo];
						break;
					case 1: //antes eran >1 y ahora queda 1 solo, ya no hay problema de unicidad
						if (valores_unicos[campo].unico) {
							var idx_viejo = valores_unicos[campo][viejo][0];
							if (multi_unicos[campo]) {
								jQuery.each(multi_unicos[campo], function(i, c) {
									jQuery('#data_' + idx_viejo + '_' + idx_campos[c])
											.removeClass('error').attr('title', '');
								});
							}
							else {
								jQuery('#data_' + idx_viejo + '_' + idx_campos[campo])
										.removeClass('error').attr('title', '');
							}
						}
						break;
				}
			}

		}
	}

	/**
	 * obtiene los inputs que tienen este valor
	 * @param {int} campo
	 * @param {string} val
	 * @returns {jQuery} inputs
	 */
	function inputsValoresUnicos(campo, val) {
		val = limpiar(val);
		if (!valores_unicos[campo][val]) {
			return null;
		}
		var ids_tr = valores_unicos[campo][val];
		var ids_input = [];
		var idxs = idx_campos[campo];
		if (typeof(idxs) === 'number') {
			idxs = [idxs];
		}

		jQuery.each(idxs, function(i, idx_col) {
			ids_input.push(jQuery.map(ids_tr, function(id_tr) {
				return '#data_' + id_tr + '_' + idx_col;
			}).join(','));
		});
		return jQuery(ids_input.join(','));
	}

	/** 
	 * actualizar el valor del input al cambiar un selector de relacion
	 */
	function cambioRelacion() {
		var input = jQuery(this).closest('td').find('[name^=data]');
		var val_input = limpiar(input.val());
		var idx = jQuery(this).closest('td').index();
		if (val_input && (input.hasClass('error') || input.hasClass('warning'))) {
			var iguales = inputsValoresUnicos(campos_idx[idx], val_input);
			if (iguales && iguales.length > 1 && confirm('Cambiar todos los datos de esta columna que tienen el valor ' + input.val() + '?')) {
				input = iguales;
			}
		}
		input.val(jQuery(this).find(':selected').text()).change();
	}

	/**
	 * actualizar el input al (des)checkear un checkbox
	 */
	function cambioCheck() {
		jQuery(this).closest('td').find('[name^=data]')
				.val(jQuery(this).is(':checked') ? 'SI' : 'NO');
	}

	/**
	 * eliminar espacios, minusculas y acentos para comparar
	 * @param {string} s
	 * @returns {string}
	 */
	function limpiar(s) {
		if (typeof(s) !== 'string') {
			return '';
		}
		s = s.replace(/\s/g, '').toUpperCase();
		var acentos = {
			'Á|À|Ä': 'A',
			'É|È|Ë': 'E',
			'Í|Ì|Ï': 'I',
			'Ó|Ò|Ö': 'O',
			'Ú|Ù|Ü': 'U',
			'Ñ': 'N'
		};
		jQuery.each(acentos, function(acento, limpio) {
			s = s.replace(new RegExp(acento, 'g'), limpio);
		});
		return s;
	}

	/**
	 * valida que el valor ingresado este dentro de las opciones existentes
	 * @param {jQuery} td
	 * @param {jQuery} input
	 * @param {string} val
	 * @param {string} relacion
	 */
	function validarRelacion(td, input, val, relacion) {
		if (!val) {
			return;
		}
		val = limpiar(val);

		var id = listados_inversos[relacion][val];
		if (id) {
			td.find('.extra select').val(id);
			input.val(listados[relacion][id]);
		}
		else {
			//fail: no existe el dato, si es creable solo es warning, si no es un error
			var idx = td.index();
			var campo = campos_idx[idx];
			var creable = campos_clase[campo].creable;
			input.addClass(creable ? 'warning' : 'error')
					.attr('title', 'No existe este valor en el listado actual' +
					(creable ? '. Se creará al cargar los datos' : ''));
			td.find('.extra select').val('');

			if (creable) {
				//homogeneizar espacios/mayusculas con otras filas existentes
				var iguales = inputsValoresUnicos(campo, val);
				if (iguales) {
					iguales.val(input.val());
				}
			}
		}
	}

	/**
	 * actualiza el checkeado de un checkbox cuando se cambia el input
	 * @param {jQuery} input
	 * @param {bool} defval
	 */
	function actualizarCheckbox(input, defval) {
		var val = limpiar(input.val());
		var checked = defval;
		if (val !== '') {
			checked = val.charAt(0) !== 'N' && val !== '0';
		}

		var check = input.closest('td').find(':checkbox');
		if (checked) {
			check.attr('checked', 'checked');
		}
		else {
			check.removeAttr('checked');
		}
	}

	/**
	 * valida que el email sea un email (o vacio)
	 * @param {jQuery} input
	 */
	function validarEmail(input) {
		if (input.val() && !input.val().match(/^\s*[\w\.-]+@[\w\.-]+\.\w+\s*$/)) {
			input.addClass('error').attr('title', 'Ingrese un mail válido');
		}
	}

	function validacionInput(input, campo, info) {
		if (input[0].className) {
			input.removeClass('error').removeClass('warning').removeAttr('title');
		}
		//elimina espacios repetidos
		input.val(input.val().replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, ''));

		if (!info) {
			return;
		}
		var val = input.val();
		var tr_idx = input.closest('tr').index();

		if (valores_unicos[campo]) {
			agregarValorUnico(campo, val, input.attr('data-viejo'), tr_idx);
		}

		if (info.requerido && val === '') {
			input.addClass('error').attr('title', 'Este campo es obligatorio');
		}
		else {
			if (typeof(info.unico) === 'string') {
				//llave multiple
				var nuevo = [];
				var viejo = [];
				jQuery.each(multi_unicos[info.unico], function(i, c) {
					var inp = jQuery('#data_' + tr_idx + '_' + idx_campos[c]);
					nuevo.push(inp.val());
					viejo.push(inp.attr('data-viejo'));
				});
				agregarValorUnico(info.unico, nuevo.join(' / '), viejo.join(' / '), tr_idx);
			}

			if (info.relacion) {
				validarRelacion(input.closest('td'), input, val, info.relacion);
			}

			if (info.tipo === 'bool') {
				actualizarCheckbox(input, info.defval);
			}
			else if (info.tipo === 'email') {
				validarEmail(input);
			}
		}
		input.attr('data-viejo', val);
	}

	function calcularCamposIdx() {
		idx_campos = {};
		campos_idx = {};
		jQuery('[name^=campos]').each(function(idx) {
			idx_campos[jQuery(this).val()] = idx;
			campos_idx[idx] = jQuery(this).val();
		});
		jQuery.each(multi_unicos, function(nombre, campos) {
			var idxs = jQuery.map(campos, function(c) {
				return idx_campos[c];
			});
			idx_campos[nombre] = idxs;
		});
	}

	console.log(new Date() + ' fin js');
	jQuery(function() {
		console.log(new Date() + ' onload');

		jQuery('[name^=data]').change(function() {
			var input = jQuery(this);
			var idx = input.closest('td').index();
			var campo = campos_idx[idx];
			var info = campos_clase[campo];

			validacionInput(input, campo, info);
		}).focus(function() {
			jQuery('.extra').html('');
			var td = jQuery(this).closest('td');
			var idx = td.index();
			var campo = campos_idx[idx];
			var info = campos_clase[campo];


			if (!columna_validada[idx]) {
				jQuery('[name="campos[' + idx + ']"]').change();
			}

			if (info) {
				//mostrar listado para relaciones o checkbox para bools
				if (info.relacion) {
					var sel = jQuery('<select/>', {html: '<option value=""/>'});
					jQuery.each(listados[info.relacion], function(id, valor) {
						sel.append(jQuery('<option/>', {value: id, text: valor}));
					});
					sel.change(cambioRelacion);
					td.find('.extra').append(sel);
				}
				else if (info.tipo === 'bool') {
					//agregar checkbox
					var check = jQuery('<input/>', {type: 'checkbox'}).change(cambioCheck);
					td.find('.extra').append(check);
				}
				jQuery(this).change();
			}

		});

		jQuery('[name^=campos]').change(function() {
			calcularCamposIdx();

			var campo = jQuery(this).val();
			console.log(new Date() + ' campo: ' + campo);

			var idx = jQuery(this).closest('th').index();
			var info = campos_clase[campo];
			jQuery('.col_' + idx).each(function() {
				validacionInput(jQuery(this), campo, info);
			});
			columna_validada[idx] = true;
		});

		calcularCamposIdx();

		if (jQuery('input').length < 1000) {
			jQuery('[name^=campos]').change();
		}

		jQuery('form').submit(function() {
			var msg = '';
			var repetidos = [];
			var faltan = [];
			var campos = jQuery('[name^=campos]');
			jQuery.each(campos_clase, function(campo, info) {
				var num = campos.filter(function() {
					return jQuery(this).val() === campo;
				}).length;
				if (num > 1) {
					repetidos.push(info.titulo);
				}
				else if (!num && info.requerido) {
					faltan.push(info.titulo);
				}
			});
			if (repetidos.length) {
				msg += '\nLas siguientes columnas están repetidas: ' + repetidos.join(', ');
			}
			if (faltan.length) {
				msg += '\nLas siguientes columnas no están pero son obligatorias: ' + faltan.join(', ');
			}

			jQuery.each(columna_validada, function(idx, validada) {
				if (!validada) {
					jQuery('[name="campos[' + idx + ']"]').change();
				}
			});

			var errores = jQuery('.error');
			if (errores.length) {
				msg += '\nHay errores en los datos!';
				errores.focus();
			}

			if (msg) {
				alert(msg);
				return false;
			}

			var warnings = jQuery('.warning');
			if (warnings.length && !confirm('Hay advertencias! Desea enviar los datos de todas formas?')) {
				warnings.focus();
				return false;
			}
			return true;
		});

		jQuery('tr.error :input').one('change', function() {
			jQuery(this).closest('tr').removeClass('error').removeAttr('title');
		});
	});

//TODO: poder agregar/eliminar filas/columnas
</script>