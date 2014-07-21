<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$CartaCobro = new CartaCobro($sesion);

if ($opc == 'guardar') {
	$id_carta = $CartaCobro->GuardarCarta($_POST['carta']);
	die(json_encode(array('id' => $id_carta)));
} else if ($opc == 'prev') {
	$id_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro);
} else {
	$carta = $CartaCobro->ObtenerCarta($id_carta);
}

$pagina = new Pagina($sesion);
$pagina->titulo = __('Cartas de cobro');
$pagina->PrintTop();
$Form = new Form;
?>
<div class="loader-overlay" style="z-index: 1000; opacity:.8; background: #fff; top: 0; left: 0; right: 0; bottom: 0; position: fixed;"></div>
<div class="loader-overlay" style="z-index: 1001; top: 0; left: 0; right: 0; bottom: 0; position: fixed; height: 100%; width: 100%;">
	<img alt="cargando" src="//estaticos.thetimebilling.com/templates/cargando.gif" style="background: #fff; margin:150px; padding: 20px; border: 1px solid #eee;"/>
</div>

<form>
	<?php echo Html::SelectQuery($sesion, 'SELECT id_carta, descripcion FROM carta', 'id_carta', $id_carta, '', ' '); ?>
	<?php echo $Form->button('Editar', array('id' => 'btn_editar')) ?>
</form>
<hr/>
<form method="post" id="form_carta">
	<input type="hidden" name="opc" value="guardar"/>
	<input type="hidden" name="carta[id_carta]" value="<?php echo $carta['id_carta']; ?>"/>
	<p>
		<label>Descripcion: <input name="carta[descripcion]" value="<?php echo $carta['descripcion']; ?>"/></label><br/>
		<label>Margen Superior: <input name="carta[margen_superior]" value="<?php echo $carta['margen_superior']; ?>"/></label><br/>
		<label>Margen Inferior: <input name="carta[margen_inferior]" value="<?php echo $carta['margen_inferior']; ?>"/></label><br/>
		<label>Margen Izquierdo: <input name="carta[margen_izquierdo]" value="<?php echo $carta['margen_izquierdo']; ?>"/></label><br/>
		<label>Margen Derecho: <input name="carta[margen_derecho]" value="<?php echo $carta['margen_derecho']; ?>"/></label><br/>
		<label>Margen Encabezado: <input name="carta[margen_encabezado]" value="<?php echo $carta['margen_encabezado']; ?>"/></label><br/>
		<label>Margen Pie de página: <input name="carta[margen_pie_de_pagina]" value="<?php echo $carta['margen_pie_de_pagina']; ?>"/></label><br/>
	</p>

	<div id="tabs-secciones" class="tabs">
		<ul>
			<?php foreach ($carta['secciones'] as $seccion => $html) { ?>
				<li><a href="#tab-<?php echo $seccion; ?>"><?php echo $seccion; ?></a></li>
			<?php } ?>
		</ul>
		<?php
		foreach ($carta['secciones'] as $seccion => $html) {
			$valores = UtilesApp::mergeKeyValue($NotaCobro->diccionario[$seccion]);
			?>
			<div id="tab-<?php echo $seccion; ?>">
				<textarea class="ckeditor" id="editor_<?php echo $seccion; ?>" name="carta[secciones][<?php echo $seccion; ?>]"><?php echo htmlentities($html); ?></textarea>
				<?php
				$valores = UtilesApp::mergeKeyValue($CartaCobro->diccionario[$seccion]);
				echo $Form->label(__('Insertar Valor:'), "valores_$seccion");
				echo $Form->select(null, $valores, null, array('id' => "valores_$seccion", 'class' => 'valores', 'empty' => false));
				echo $Form->button(__('Insertar'), array('class' => 'agregar_valor'));
				?>
				<?php if (isset($CartaCobro->secciones[$seccion])) { ?>
					<br/>
					<?php
					$valores = UtilesApp::mergeKeyValue($CartaCobro->secciones[$seccion]);
					echo $Form->label(__('Agregar Sección:'), "secciones_$seccion");
					echo $Form->select(null, $valores, null, array('id' => "secciones_$seccion", 'class' => 'secciones', 'empty' => false));
					echo $Form->button(__('Agregar'), array('class' => 'agregar_seccion'));
					?>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
	<h4>CSS</h4>
	<textarea name="carta[formato_css]" rows="12" cols="40" style="width: 98%"><?php echo $carta['formato_css']; ?></textarea>
	<div style="padding: 23px">
		<?php echo $Form->button('Guardar', array('id' => 'btn_guardar')); ?>
		<?php echo $Form->button('Guardar como nueva carta', array('id' => 'btn_guardar_nueva')); ?>
		<br/>
		<br/>
		<label>Previsualizar con el cobro N° <input name="id_cobro" value="<?php echo $id_cobro; ?>"/></label>
		<?php echo $Form->button('Previsualizar carta', array('id' => 'btn_previsualizar')); ?>
		<?php echo $Form->button('Ver valores de tags', array('id' => 'btn_valores')); ?>
	</div>
	<hr/>
	<p style="text-align: center">
		<strong>Previsualización HTML</strong>
		<?php echo $Form->button('Regenerar', array('id' => 'btn_previsualizar_html')); ?>
	</p>

	<iframe id="previsualizacion_html" style="width:674px;height:730px"></iframe>
</form>

<?php echo $Form->script(); ?>
<script type="text/javascript" src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<script type="text/javascript" src="//static.thetimebilling.com/js/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
	var diccionario = <?php echo json_encode(UtilesApp::utf8izar($CartaCobro->diccionario)); ?>;
	var secciones = <?php echo json_encode(UtilesApp::utf8izar($CartaCobro->secciones)); ?>;

	jQuery(function() {
		CKEDITOR.config.fontSize_sizes = '7/7pt;8/8pt;9/9pt;10/10pt;11/11pt;12/12pt;14/14pt;16/16pt;18/18pt;20/20pt;22/22pt;24/24pt;26/26pt;28/28pt';
		CKEDITOR.config.allowedContent = true;

		jQuery('.agregar_valor').live('click', AgregarValor);
		jQuery('.agregar_seccion').live('click', AgregarSeccion);
		jQuery('#btn_editar').click(function() {
			window.location = '?id_carta=' + jQuery('[name=id_carta]').val();
		});
		jQuery('#btn_guardar, #btn_guardar_nueva').click(function() {
			jQuery('[name=opc]').val('guardar');
			if (jQuery(this).attr('id') === 'btn_guardar_nueva') {
				jQuery('[name="carta[id_carta]"]').val('');

			}
			jQuery.each(CKEDITOR.instances, function(i) {
				CKEDITOR.instances[i].updateElement();
			});
			var form = jQuery('#form_carta');
			jQuery.post(form.attr('action'), form.serialize(), function(carta) {
				if (carta.id) {
					alerta('La carta se guardó correctamente.');
					if (jQuery('[name="carta[id_carta]"]').val() != carta.id) {
						window.location = '?id_carta=' + carta.id;
					}
				} else if (carta.error) {
					alerta(carta.error);
				}
			}, 'json');
		});
		jQuery('#btn_previsualizar').click(function() {
			jQuery('[name=opc]').val('prev');
			jQuery('#form_carta').submit();
		});
		jQuery('#btn_valores').click(function() {
			window.open('carta_test_valores.php?id_cobro=' + jQuery('[name=id_cobro]').val());
		});
		jQuery('#btn_previsualizar_html').click(function() {
			PrevisualizarHTML();
		});

		PrevisualizarHTML();

		jQuery('.tabs').tabs();

		CKEDITOR.on('instanceReady', function() {
			jQuery('.loader-overlay').fadeOut(300, function() {
				jQuery(this).remove();
			});
		});
	});

	function alerta(msg, type) {
		var div = jQuery('<div/>', {style: 'margin-top: .5em;'})
			.addClass('ui-corner-all')
			.addClass(type == 'error' ? 'ui-state-error' : 'ui-state-highlight')
			.html(msg);
		div.insertAfter('#btn_guardar_nueva')
			.hide()
			.fadeIn()
			.delay(5000)
			.fadeOut();
	}
	function AgregarValor() {
		var div = jQuery(this).closest('div');
		AgregarHTML(div, div.find('.valores').val());
		return false;
	}

	function AgregarHTML(div, valor) {
		var editor = CKEDITOR.instances[div.find('.ckeditor').attr('id')];

		// Check the active editing mode.
		if (editor.mode == 'wysiwyg') {
			editor.insertHtml(valor);
		}
		else {
			var ta = editor.textarea.$;
			ta.value = ta.value.substr(0, ta.selectionStart) + valor + ta.value.substr(ta.selectionEnd);
		}
		return true;
	}

	function AgregarSeccion() {
		var div = jQuery(this).closest('div');
		var seccion = div.find('.secciones').val();
		if (AgregarHTML(div, '%' + seccion + '%') && !document.getElementById('editor_' + seccion)) {
			var tabs = jQuery('#tabs-secciones');
			var li = jQuery('<li/>')
				.append(jQuery('<a/>', {text: seccion, href: '#tab-' + seccion}));

			var div = jQuery('<div/>', {id: 'tab-' + seccion})
				.append(jQuery('<textarea/>', {
					'class': 'ckeditor',
					'name': 'carta[secciones][' + seccion + ']',
					'id': 'editor_' + seccion
				}));

			tabs.find('.ui-tabs-nav').append(li);
			tabs.append(div);
			tabs.tabs('refresh');

			CKEDITOR.replace('editor_' + seccion);

			if (diccionario[seccion]) {
				div.append(jQuery('<label/>', {text: 'Insertar Valor: '})
					.append(jQuery('<select/>', {'class': 'valores'}))
					.append(jQuery('<button/>', {
						'class': 'agregar_valor',
						type: 'button',
						text: 'Insertar'
					}))
					)
				var selvals = div.find('.valores');
				jQuery.each(diccionario[seccion], function(idx, val) {
					selvals.append(jQuery('<option/>', {value: idx, text: idx + ' - ' + val}));
				});
			}

			if (secciones[seccion]) {
				div.append(jQuery('<br/>'));
				div.append(jQuery('<label/>', {text: 'Insertar Seccion: '})
					.append(jQuery('<select/>', {'class': 'secciones'}))
					.append(jQuery('<button/>', {
						'class': 'agregar_seccion',
						type: 'button',
						text: 'Agregar'
					}))
				);
				var selvals = div.find('.secciones');
				jQuery.each(secciones[seccion], function(idx, val) {
					selvals.append(jQuery('<option/>', {value: idx, text: '%' + idx + '% - ' + val}));
				});
			}
		}
		return false;
	}

	function PrevisualizarHTML() {
		var css = jQuery('[name="carta[formato_css]"]').val();
		var body = GenerarHTML('CARTA');
		var html = '<style type="text/css">' + css + '</style>' + body;
		jQuery('#previsualizacion_html')[0].contentWindow.document.body.innerHTML = html;
	}

	function GenerarHTML(seccion) {
		var id = 'editor_' + seccion;

		var html = CKEDITOR.instances[id] ? CKEDITOR.instances[id].getData() : jQuery('#' + id).val();
		if (!html) {
			return '';
		}

		jQuery.each(secciones[seccion] || [], function(s) {
			var tag = '%' + s + '%';
			if (html.indexOf(tag) >= 0) {
				html = html.replace(tag, GenerarHTML(s));
			}
		});
		return html;
	}

</script>

<?php
$pagina->PrintBottom($popup);
