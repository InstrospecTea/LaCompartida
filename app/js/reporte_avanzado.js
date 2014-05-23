function FormFiltersText(data) {
	var filters = {
		'check_clientes': 'clientesF',
		'check_profesionales': 'usuariosF',
		'check_encargados': 'encargados',
		'check_area_prof': 'areas',
		'check_cat_prof': 'categorias',
		'check_area_asunto': 'areas_asunto',
		'check_tipo_asunto': 'tipos_asunto',
		'check_estado_cobro': 'estado_cobro',
		'check_moneda_contrato': 'moneda_contrato',
		'usuarios': '',
		'clientes': '',
		'codigo_contrato': ''
	}
	var form = jQuery('#div_nuevo_reporte_text tbody');
	var textos = [];
	jQuery.each(filters, function(name, value) {
		if (!data[name]) {
			return;
		}
		var obj = {key: __(name)};
		if (obj.key == name) {
			obj.key = TextFromValue(name, data[name]);
		}
		if (value == '') {
			obj.text = TextFromValue(name, data[name]);
		} else {
			if (!data[value]) {
				obj.text = 'Todos.'
			} else {
				obj.text = TextFromValue(value, data[value]);
			}
		}
		textos.push(obj);
	});
	var html = '';
	jQuery.each(textos, function(k, filter) {
		html += '<tr><td align="right">' + filter.key + ': </td><td>' + filter.text + '</td></tr>';
	});
	form.append(html);
}

function TextFromValue(name, value) {
	var elm_name = name;
	if (jQuery.isArray(value)) {
		elm_name += '[]';
	}
	var text = '';
	var nodeName = jQuery('[name="' + elm_name + '"]').get(0).nodeName.toLowerCase();

	switch (nodeName) {
		case 'select':
			var elm = jQuery('select[name="' + elm_name + '"] option:selected');
			var t = [];
			var counter = 0;
			jQuery.each(elm, function(k, item) {
				if (++counter > 5) {
					return false;
				}
				t.push(jQuery(item).text());
			});
			if (counter > 5) {
				t.push('y ' + (elm.length - 5) + ' más.');
			}
			text = t.length == 1 ? t[0] : '<ul class="lista"><li>' + t.join('</li><li>') + '</li></ul>';
			break;
		case 'input':
			var inputType = jQuery('input[name="' + elm_name + '"]').attr('type');
			switch (inputType) {
				case 'radio':
				case 'checkbox':
					var id = jQuery('input[name="' + elm_name + '"][type="' + inputType + '"]:checked').attr('id');
					text = jQuery('label[for="' + id + '"]').text();
					break;
				case 'text':
					text = jQuery('input[name="' + elm_name + '"][type="' + inputType + '"]').val();
					break;
			}
			break;
	}
	return text
}

function dialogoReporte(title, buttons) {
	var data = serializeFormulario();
	data = arrayFilter(data, true);
	FormFiltersText(data);

	jQuery('#div_nuevo_reporte').dialog({
		width: 500,
		height: 400,
		title: title,
		modal: true,
		buttons: buttons
	});
}

function serializeFormulario() {
	var incluir = ['agrupador[0]', 'agrupador[1]', 'agrupador[2]', 'agrupador[3]', 'agrupador[4]', 'agrupador[5]',
		'numero_agrupadores', 'areas', 'areas_asunto', 'campo_fecha', 'categorias', 'tipos_asunto',
		'check_area_asunto', 'check_area_prof', 'check_cat_prof', 'check_clientes', 'check_encargados',
		'check_estado_cobro', 'check_moneda_contrato', 'check_profesionales', 'check_tipo_asunto',
		'clientesF', 'codigo_contrato', 'comparar', 'encargados', 'estado_cobro', 'proporcionalidad',
		'moneda_contrato', 'tipo_dato', 'tipo_dato_check', 'tipo_dato_comparado',
		'tipo_asunto', 'usuariosF', 'usuarios', 'clientes', 'fecha_corta', 'nuevo_reporte_segun', 'id_moneda'];

	var datos = jQuery.parseParams(jQuery('#formulario').serialize());
	var ftt = {};
	jQuery.each(datos, function(key, value) {
		if (jQuery.inArray(key, incluir) > -1) {
			if (jQuery.isArray(value)) {
				value = arrayFilter(value);
			}
			ftt[key] = value;

		}
	});
	return ftt;
}

function arrayFilter(a, isObject) {
	var r = isObject ? {} : [];
	jQuery.each(a, function(key, value) {
		if (value != null && value != '' && value != []) {
			if (isObject) {
				r[key] = value;
			} else {
				r.push(value);
			}
		}
	});
	return r;
}

function SelectValueSet(SelectName, Value) {
	SelectObject = $(SelectName);
	for (index = 0; index < SelectObject.length; ++index) {
		if (SelectObject[index].value == Value) {
			SelectObject.selectedIndex = index;
		}
	}
}

function EliminarReporte() {
	var data = {
		'opc': 'eliminar_reporte',
		'id_reporte': jQuery('#mis_reportes option:selected').val()
	}
	jQuery.ajax({
		url: urlAjaxReporteAvanzado,
		data: data,
		success: eliminarItemReporte,
		dataType: 'json',
		type: 'POST'
	});
}

/*Submitea la form para que genere un reporte segun lo elegido.*/
function GuardarReporte() {
	var patt = /^([0-9]+).*/;
	var data = {
		'opc': 'guardar_reporte',
		'nuevo_reporte': jQuery.param(arrayFilter(serializeFormulario(), true)),
		'nombre_reporte': jQuery('#nombre_reporte').val(),
		'id_reporte_editado': jQuery('#id_reporte_editado').val(),
		'last_num': jQuery('#mis_reportes option:last').text().replace(patt, '$1'),
		'my_num': jQuery('#mis_reportes option:selected').text().replace(patt, '$1')
	};
	jQuery.ajax({
		url: urlAjaxReporteAvanzado,
		data: data,
		success: cargarItemReporte,
		dataType: 'json',
		type: 'POST'
	});
	jQuery(this).dialog('close');
}
function eliminarItemReporte(data) {
	if (data.eliminado) {
		jQuery('#mis_reportes option:selected').remove();
		jQuery('#mis_reportes option[value="0"]').attr('selected', true);
		jQuery('#span_eliminar_reporte, #span_editar_reporte').hide();
	}
}
function cargarItemReporte(data) {
	if (jQuery('#mis_reportes option[value="' + data.id_reporte + '"]').attr('value')) {
		jQuery('#mis_reportes option[value="' + data.id_reporte + '"]')
				.data('reporte', data.reporte)
				.data('glosa', data.glosa)
				.text(data.text);
	} else {
		var opt = jQuery('<option/>')
				.attr('value', data.id_reporte)
				.data('reporte', data.reporte)
				.data('glosa', data.glosa)
				.text(data.text)
				.attr('selected', true);
		jQuery('#mis_reportes').append(opt);
	}
	jQuery('#span_eliminar_reporte, #span_editar_reporte').show();
}

function NuevoReporte() {
	dialogoReporte(jQuery('#label_nuevo_reporte').text(), buttonsReporte);
	jQuery('#nombre_reporte').val('');
	jQuery('#id_reporte_editado').val('0');
	ActualizarNuevoReporte();
}

function EditarReporte() {
	var option = jQuery('#mis_reportes option:selected');
	var id_reporte = option.val();
	var envio_reporte = option.val() == 0 ? 0 : option.data('envio_reporte');

	jQuery('#nombre_reporte').val(option.data('glosa'));
	jQuery('#id_reporte_editado').val(id_reporte);
	dialogoReporte(jQuery('#label_editar_reporte').text(), buttonsReporte);

	ActualizarNuevoReporte();
	if (envio_reporte) {
		var fecha_corta = jQuery('#formulario input[type="radio"][name="fecha_corta"]:checked').val();
		if (fecha_corta == 'semanal') {
			$('select_reporte_envio_semana').selectedIndex = envio_reporte;
		} else {
			$('select_reporte_envio_mes').selectedIndex = envio_reporte;
		}
	}
}

/*Carga lo elegido en el deglose del nuevo reporte*/
function ActualizarNuevoReporte() {
	var s = __(jQuery('#tipo_dato').val());
	if ($('comparar').checked == true) {
		s += ' vs. ' + __($('tipo_dato_comparado').value);
	}

	jQuery('#tipos_datos_nuevo_reporte').html(s);

	s = '';
	var numero_agrupadores = parseInt(jQuery('#numero_agrupadores').val());
	for (i = 0; i < numero_agrupadores; ++i) {
		if (i != 0 && i != 3) {
			s += ' - ';
		}
		s += __(jQuery('#agrupador_' + i).val());
		if (i == 2) {
			s += '<br />';
		}
	}
	jQuery('#agrupadores_nuevo_reporte').html(s);

	s = "<i>Puede seleccionar 'Semana pasada',<br /> 'Mes pasado' o 'Año en curso'.</i>";
	var fecha_corta = jQuery('#formulario input[name="fecha_corta"]:checked').val();

	if (fecha_corta == 'semanal') {
		s = __('Semana pasada');
	} else if (fecha_corta == 'mensual') {
		s = __('Mes pasado');
	} else if (fecha_corta == 'anual') {
		s = __('Año en curso');
	}
	jQuery('#periodo_nuevo_reporte').html(s);

	var campo_fecha = jQuery('#formulario input[name="campo_fecha"]:checked').val();
	if (campo_fecha == 'trabajo') {
		s = __("Trabajo");
	} else if (campo_fecha == 'corte') {
		s = __("Corte");
	} else if (campo_fecha == 'envio') {
		s = __("Facturacion");
	} else if (campo_fecha == 'facturacion') {
		s = __("Envío");
	} else {
		s = __("Emisión");
	}

	jQuery('#segun_nuevo_reporte').html(s);
}

jQuery(function() {
	jQuery("select option").attr("title", "");
	jQuery("select option").each(function(i) {
		this.title = this.text;
	})
});

/*Carga los datos del reporte elegido en los selectores*/
function CargarReporte() {
	var reporte = jQuery('#mis_reportes option:selected');
	resetForm();
	jQuery(reporte).attr('selected', true);
	if (reporte.val() == "0") {
		jQuery('#span_eliminar_reporte, #span_editar_reporte').hide();
		return 0;
	}
	jQuery('#span_eliminar_reporte, #span_editar_reporte').show();

	if (reporte.data('segun')) {
		/*Se añade 'envio'*/

		var segun = reporte.data('segun');
		var reporte = reporte.data('reporte');

		var elementos = reporte.split('.');
		var datos = elementos[0].split(',');
		var agrupadores = elementos[1].split(',');
		SelectValueSet('tipo_dato', datos[0]);
		if (datos.size() == 2) {
			SelectValueSet('tipo_dato_comparado', datos[1]);
			jQuery('#comparar').attr('checked', true);
		} else {
			jQuery('#comparar').attr('checked', false);
		}
		Comparar();

		Agrupadores(agrupadores.size() - parseInt(jQuery('#numero_agrupadores').val()));

		for (i = 0; i < agrupadores.size(); ++i) {
			SelectValueSet('agrupador_' + i, agrupadores[i]);
			CambiarAgrupador(i);
		}

		if (segun == 'trabajo') {
			jQuery('#campo_fecha_trabajo').click();
		} else if (segun == 'cobro') {
			jQuery('#campo_fecha_cobro').click();
		} else if (segun == 'cobro') {
			jQuery('#campo_fecha_envio').click();
		} else {
			jQuery('#campo_fecha_emision').click();
		}

		if (elementos.size() == 3) {
			var periodo = elementos[2];
			if (periodo == 'semanal') {
				jQuery('#fecha_corta_semana').click();
			} else if (periodo == 'mensual') {
				jQuery('#fecha_corta_mes').click();
			}
			else if (periodo == 'anual') {
				jQuery('#fecha_corta_anual').click();
			}
		}
	} else {
		var filtros_avanzados = ['check_area_asunto', 'check_area_prof', 'check_cat_prof', 'check_clientes', 'check_encargados', 'check_estado_cobro', 'check_moneda_contrato', 'check_profesionales', 'check_tipo_asunto'];
		var showFullFiltros = false;
		var json_reporte = reporte.data('reporte').replace(/'/g, '"')
		var datos = jQuery.parseJSON(json_reporte);

		setFieldsValues(datos);
		Comparar();

		jQuery.each(datos, function(k) {
			if (jQuery.inArray(k, filtros_avanzados) != -1) {
				showFullFiltros = true;
			}
		});

		if ((showFullFiltros && jQuery('#filtrosimple').is(':visible')) || (!showFullFiltros && jQuery('#full_filtros').is(':visible'))) {
			jQuery('#fullfiltrostoggle').click();
		}

		Agrupadores(0);
		TipoDato();
		jQuery('input[name="fecha_corta"]:checked').click();
	}
}

function Agrupadores(num) {
	var numero_agrupadores = parseInt(jQuery('#numero_agrupadores').val());
	numero_agrupadores += num;
	if (numero_agrupadores < 1) {
		numero_agrupadores = 1;
	}
	if (numero_agrupadores > 6) {
		numero_agrupadores = 6;
	}
	jQuery('#numero_agrupadores').val(numero_agrupadores);
	for (var i = 0; i < 6; ++i) {
		var selector = jQuery('#span_agrupador_' + i);
		if (i < numero_agrupadores) {
			selector.show();
		} else {
			selector.hide();
		}
	}
	RevisarTabla();
	ActualizarNuevoReporte();
}

function setFieldsValues(data) {
	jQuery.each(data, function(k, v) {
		var elm_name = k;
		if (jQuery.isArray(v)) {
			elm_name += '[]';

			// el nombre incluye el indice del array
			if (jQuery('[name="' + elm_name + '"]').length == 0) {
				var tmp_data = {};
				jQuery.each(v, function(index, value) {
					tmp_data[k + '[' + index + ']'] = value;
				});

				setFieldsValues(tmp_data);
				return;
			}
		}
		var nodeName = jQuery('[name="' + elm_name + '"]').get(0).nodeName.toLowerCase();
		switch (nodeName) {
			case 'select':
				var input_combobox = jQuery('input[id="input_' + elm_name + '"]');
				if (input_combobox.length > 0) {
					v = jQuery.isArray(v) ? v[0] : v;
					var v_text = jQuery('select[name="' + elm_name + '"] option[value="' + v + '"]').text();
					input_combobox.val(v_text);
				}

				var values = jQuery.isArray(v) ? v : [v];
				jQuery.each(values, function(k, value) {
					jQuery('select[name="' + elm_name + '"] option[value="' + value + '"]').attr('selected', true);
					jQuery('select[name="' + elm_name + '"]').change();
				});

				break;
			case 'input':
				var inputType = jQuery('input[name="' + elm_name + '"]').attr('type');
				switch (inputType) {
					case 'radio':
					case 'checkbox':
						jQuery('input[name="' + elm_name + '"][type="' + inputType + '"][value="' + v + '"]')
								.attr('checked', true)
								.change();
						break;
					case 'text':
					case 'hidden':
						jQuery('input[name="' + elm_name + '"][type="' + inputType + '"]').val(v);
						break;
				}
				break;
		}
	});

}

function resetForm() {
	// al asignar el valor "checked" el reset no funciona así que se fuerza a deschequear el campo
	jQuery('#comparar').attr('checked', false);
	jQuery('#formulario').get(0).reset();
}

/*Al activar la Comparación, debo hacer cosas Visibles y cambiar valores*/
function Comparar() {
	var comparar = jQuery('#comparar');
	var tipo_de_dato = jQuery('#tipo_dato').get(0);
	var tipo_dato_comparado = jQuery('#tipo_dato_comparado');
	var valor_tipo_dato_comparado = tipo_dato_comparado.val();
	tipo_dato_comparado = tipo_dato_comparado.get(0)

	//Si el valor comparado es igual al principal, debo cambiarlo:
	if (tipo_dato_comparado.selectedIndex == tipo_de_dato.selectedIndex) {
		if (tipo_dato_comparado.selectedIndex == 0) {
			tipo_dato_comparado.selectedIndex = 1;
		} else {
			tipo_dato_comparado.selectedIndex = 0;
		}
	}
	if (comparar.is(':checked')) {
		jQuery('#dispersion ,#tipo_dato_comparado, #td_dato_comparado, #vs, #tipo_tinta').show();
		jQuery('#td_dato').removeClass('borde_blanco').addClass('borde_rojo');
		jQuery('#td_dato_comparado').removeClass('borde_blanco').addClass('borde_azul');
	} else {
		jQuery('#dispersion, #tipo_dato_comparado, #vs, #tipo_tinta').hide();
		jQuery('#td_dato').removeClass('borde_rojo').addClass('borde_blanco');
		jQuery('#td_dato_comparado').removeClass('borde_azul').addClass('borde_blanco');
	}

	TipoDato(valor_tipo_dato_comparado, true);
	RevisarTabla();
	RevisarMoneda();
	RevisarCircular();
	ActualizarNuevoReporte();
}

function TipoDato(valor, noSet) {
	var comparar = jQuery('#comparar').is(':checked');
	var tinta = jQuery('[name="tinta"]:checked').val();

	if (valor && !noSet) {
		if (jQuery('#' + valor).hasClass('boton_disabled')) {
			return;
		}
		if (tinta === 'azul') {
			jQuery('#tipo_dato_comparado option[value="' + valor + '"]').attr('selected', true);
		} else if (tinta === 'rojo') {
			jQuery('#tipo_dato option[value="' + valor + '"]').attr('selected', true);
		}
	}

	jQuery('#full_tipo_dato .boton_tipo_dato')
			.removeClass('boton_presionado')
			.removeClass('boton_comparar')
			.addClass('boton_normal');

	valor = jQuery('#tipo_dato').val();
	jQuery('#' + valor)
			.addClass('boton_presionado')
			.removeClass('boton_normal');

	if (comparar) {
		valor = jQuery('#tipo_dato_comparado').val();
		jQuery('#' + valor)
				.addClass('boton_comparar')
				.removeClass('boton_normal');
	}
	RevisarMoneda();
	RevisarCircular();
	ActualizarNuevoReporte();
}

function RevisarTabla() {
	var comparar = jQuery('#comparar');
	var tabla = jQuery('#tabla');

	if (!comparar.is(':checked') && jQuery('#numero_agrupadores').val() == 2) {
		tabla.show();
	} else {
		tabla.hide();
	}
}

//Hace visible o invisible el input de Moneda.
function Monedas(visible) {
	var div_moneda = jQuery('#moneda');
	var div_anti_moneda = jQuery('#anti_moneda');
	var div_moneda_select = jQuery('#moneda_select');
	var div_anti_moneda_select = jQuery('#anti_moneda_select');

	if (visible) {
		div_moneda.show();
		div_moneda_select.show();
		div_anti_moneda.hide();
		div_anti_moneda_select.hide();
	} else {
		div_moneda.hide();
		div_moneda_select.hide();
		div_anti_moneda.show();
		div_anti_moneda_select.show();
	}
}

//Hace visible o invisible por id [Para Inputs con + y -]
function MostrarOculto(id) {
	var table_full = jQuery('#full_' + id);
	var table_mini = jQuery('#mini_' + id);
	var check = jQuery('#' + id + '_check');
	var img = jQuery('#' + id + '_img');

	if (table_full.is(':visible')) {
		table_full.hide();
		table_mini.show();
		check.attr('checked', false);
		img.html('<img src="../templates/default/img/mas.gif" border="0" title="Desplegar">');
	} else {
		table_full.show();
		table_mini.hide();
		check.attr('checked', true);
		img.html('<img src="../templates/default/img/menos.gif" border="0" title="Ocultar">');
	}
}

function MostrarLimite(visible) {
	var limite_check = jQuery('limite_check');
	var limite_checkbox = jQuery('limite_checkbox');
	if (visible) {
		limite_check.show();
	} else {
		limite_checkbox.attr('checked', false);
		limite_check.hide();
	}
}

function ActualizarPeriodo(fi, ff) {
	fi = fi.split('-');
	ff = ff.split('-');
	jQuery('#fecha_ini').datepicker('setDate', new Date(fi[2], fi[1] - 1, fi[0]));
	jQuery('#fecha_fin').datepicker('setDate', new Date(ff[2], ff[1] - 1, ff[0]));
}

function SeleccionarSelector() {
	var month = jQuery('#fecha_mes').val();
	var year = jQuery('#fecha_anio').val();
	jQuery('#fecha_ini').datepicker('setDate', new Date(year, month - 1, 1));
	jQuery('#fecha_fin').datepicker('setDate', new Date(year, month, 0));

//	jQuery('#reporte_envio_selector').show();
//	jQuery('#reporte_envio_semana').hide();
//	jQuery('#reporte_envio_mes').hide();
	ActualizarNuevoReporte();
}

//Revisa visibilidad de la Moneda
function RevisarMoneda() {
	var tipo_de_dato = jQuery('#tipo_dato');
	var tipo_de_dato_comparado = jQuery('#tipo_dato_comparado');
	var comparar = jQuery('#comparar');
	var tipos = {'costo': '', 'costo_hh': '', 'valor_pagado': '', 'valor_cobrado': '', 'valor_por_cobrar': '', 'valor_por_pagar': '', 'valor_incobrable': '', 'valor_hora': '', 'diferencia_valor_estandar': '', 'valor_trabajado_estandar': ''};
	if (tipo_de_dato.val() in tipos || (comparar.is(':checked') && tipo_de_dato_comparado.val() in tipos)) {
		Monedas(true);
	} else {
		Monedas(false);
	}
}

//Revisa la visibilidad del botón de gráfico circular.
function RevisarCircular() {
	var tipo_de_dato = jQuery('#tipo_dato');
	var comparar = jQuery('#comparar');
	var circular = jQuery('#circular');
	var tipos = {'rentabilidad': '', 'valor_hora': '', 'diferencia_valor_estandar': '', 'horas_castigadas': '', 'rentabilidad_base': ''};
	if (!comparar.is(':checked')) {
		if (tipo_de_dato.val() in tipos) {
			circular.hide();
		} else {
			circular.show();
		}
	} else {
		circular.hide();
	}
}

//Muestra Categoría y Area
function Categorias(obj, form) {
	var td_show = jQuery('#area_categoria');
	if (jQuery(obj).is(':checked')) {
		td_show.show();
	} else {
		td_show.hide();
	}
}

//Sincroniza los selectores de Campo de Fecha Visibles e Invisibles
function SincronizarCampoFecha() {
	ActualizarNuevoReporte();
}

function Generar(form, valor) {
	form = jQuery(form);
	var form_id = form.attr('id');
	var action = '';
	var ajax = false;
	jQuery('#' + form_id + ' [name="opc"]').val(valor);

	var value = jQuery('#agrupador_0').val();

	var numero_agrupadores = jQuery('#numero_agrupadores').val();

	for (i = 1; i < numero_agrupadores; ++i) {
		value += '-' + jQuery('#agrupador_' + i).val();
	}
	jQuery('#vista').val(value);
	switch (valor) {
		case 'pdf':
			action = 'html_to_pdf.php?frequire=reporte_avanzado.php&popup=1';
			break;
		case 'excel':
			action = 'planillas/planilla_reporte_avanzado.php';
			break;
		case 'tabla':
			action = 'planillas/planilla_reporte_avanzado_tabla.php';
			break;

		case 'circular':
			action = 'reporte_avanzado_grafico.php?tipo_grafico=circular&ajax=1';
			ajax = true;
			break;
		case 'barra':
			action = 'reporte_avanzado_grafico.php?tipo_grafico=barras&ajax=1';
			ajax = true;
			break;
		case 'dispersion':
			action = 'reporte_avanzado_grafico.php?tipo_grafico=dispersion&ajax=1';
			ajax = true;
			break;
		default:
			return;
			break;
	}

	if (ajax) {
		jQuery.ajax({
			url: action,
			data: jQuery('#formulario').serialize(),
			type: 'POST'
		}).done(function(data) {
			jQuery('#iframereporte').html(data);
		});
	} else {
		form.attr('action', action);
		form.submit();
	}
}

//Al cambiar un agrupador, en los agrupadores siguientes, el valor previo se hace disponible y el valor nuevo se indispone.
function CambiarAgrupador(num) {
	var opcion_actual = jQuery('#agrupador_' + num + ' option:selected');
	var valor_actual = opcion_actual.val();
	var texto_actual = opcion_actual.text();
	var selector_previo = jQuery('#agrupador_valor_previo_' + num);
	var valor = selector_previo.val();
	var txt = selector_previo.data('text');
	//Los selectores siguientes
	for (var i = num + 1; i < 6; ++i) {
		var opcion_siguiente = jQuery('#agrupador_' + i + ' option[value="' + valor_actual + '"]');
		//se indispone lo nuevo
		if (opcion_siguiente.length > 0) {
			opcion_siguiente.remove();
			CambiarAgrupador(i);
		}

		//y se dispone lo viejo, SOLO si no resultó elegido en uno anterior
		var elegido_en_anterior = false;
		for (var k = i; k >= 0; --k) {
			var anterior = jQuery('#agrupador_' + k + ' option:selected').val();
			if (anterior == valor) {
				elegido_en_anterior = true;
				break;
			}
		}
		if (!elegido_en_anterior) {
			var opc = jQuery('<option/>').val(valor).text(txt);
			jQuery('#agrupador_' + i).append(opc);
		}
	}
	//ahora selector_previo debe guardar el dato nuevo, para el proximo cambio
	selector_previo.data('text', texto_actual).val(valor_actual);

	ActualizarNuevoReporte();
}

function iframelista() {
	jQuery('.divloading').remove();
	jQuery('#planilla').show();
}

function ResizeIframe(width, height) {
	jQuery('#planilla').css({
		height: height + 'px',
		width: width + 'px'
	});
}


function SeleccionarSemana() {
	var periodo = selector_periodos.semana_pasada;
	ActualizarPeriodo(periodo[0], periodo[1]);
//	$('reporte_envio_semana').show();
//	$('reporte_envio_mes').hide();
//	$('reporte_envio_selector').hide();
	ActualizarNuevoReporte();
}

function SeleccionarMes() {
	var periodo = selector_periodos.mes_pasado;
	ActualizarPeriodo(periodo[0], periodo[1]);
//	$('reporte_envio_mes').show();
//	$('reporte_envio_semana').hide();
//	$('reporte_envio_selector').hide();
	ActualizarNuevoReporte();
}

function SeleccionarAnual() {
	var periodo = selector_periodos.actual;
	ActualizarPeriodo(periodo[0], periodo[1]);
//	$('reporte_envio_mes').show();
//	$('reporte_envio_semana').hide();
//	$('reporte_envio_selector').hide();
	ActualizarNuevoReporte();
}

jQuery(document).ready(function() {
	if (jQuery('#comparar').is(':checked')) {
		jQuery('#tabla, #dispersion').css('display', 'inline-block').show();
	} else {
		jQuery('#tabla, #dispersion').css('display', 'none').hide();
	}

	jQuery('#mis_reportes').change(function() {
		CargarReporte();
	});

	jQuery('#fullfiltrostoggle').click(function() {
		jQuery('#filtrosimple, #full_filtros').toggle();
		jQuery('#filtros_check').prop('checked', jQuery('#full_filtros').is(':visible'));
	});

	jQuery('#runreporte').on('click', function() {
		if (jQuery('#comparar').is(':checked')) {
			jQuery('#tipo_dato_comparado').removeAttr('disabled');

		} else {
			jQuery('#tipo_dato_comparado').attr('disabled', 'disabled');
		}
		jQuery('#vista').val("");
		var vista = [];
		jQuery('#agrupadores select:visible').each(function(i) {
			vista[i] = jQuery(this).val();
		});
		jQuery('#iframereporte').html('<div class="divloading">&nbsp;</div>');

		jQuery('#vista').val(vista.join('-'));
		jQuery.ajax({
			url: "reporte_avanzado_planilla.php?ajax=1&vista=" + jQuery('#vista').val(),
			data: jQuery('#formulario').serialize(),
			type: 'POST'
		}).done(function(data) {
			jQuery('#iframereporte').html(data);
		});
	});

	jQuery('#comparar').change(function() {
		if (jQuery('#comparar').is(':checked')) {
			jQuery('#tabla, #dispersion').show();
		} else {
			jQuery('#tabla, #dispersion').hide();
		}
		Comparar();
	});

	jQuery('#orden_barras_max2min').change(function() {
		MostrarLimite(jQuery(this).attr('checked'));
	});

	jQuery('#tipo_dato, #tipo_dato_comparado').change(function() {
		var elm = jQuery(this);
		setFieldsValues({'tinta': elm.data('color')})
		TipoDato(elm.val(), true);
	});

	jQuery('input[name="fecha_corta"]').click(function() {
		switch (jQuery(this).attr('id')) {
			case 'fecha_corta_semana':
				SeleccionarSemana();
				break;
			case 'fecha_corta_mes':
				SeleccionarMes();
				break;
			case 'fecha_corta_anual':
				SeleccionarAnual();
				break;
			case 'fecha_corta_selector':
			case 'fecha_periodo':
				SeleccionarSelector();
				break;
		}
		return;

	});
	CargarReporte();
	RevisarMoneda();
	RevisarCircular();
	RevisarTabla();
	Comparar();

	jQuery('.boton_tipo_dato').click(function() {
		TipoDato(jQuery(this).attr('id'));
	});
	
	jQuery('.agrupador').change(function() {
		var name = jQuery(this).attr('id').split('_');
		var num = parseInt(name[1]);
		CambiarAgrupador(num);
	});

	jQuery('#fecha_mes, #fecha_anio').change(SeleccionarSelector);
});