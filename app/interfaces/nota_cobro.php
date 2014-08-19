<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$NotaCobro = new NotaCobro($sesion);

if ($opc == 'guardar') {
	$id_formato = $NotaCobro->GuardarCarta($nota);
	die(json_encode(array('id' => $id_formato)));
} else if ($opc == 'prev') {
	$id_formato = $NotaCobro->PrevisualizarDocumento($nota, $id_cobro);
} else {
	$nota = $NotaCobro->ObtenerCarta($id_formato);
}

$pagina = new Pagina($sesion);
$pagina->titulo = __('Notas de cobro');
$pagina->PrintTop();
$Form = new Form;
$template_parts = array(
	'html_header' => 'Header',
	'html_pie' => 'Pie',
	'cobro_css' => 'CSS',
	'pdf_encabezado_imagen' => 'Imagen Encabezado PDF',
	'pdf_encabezado_texto' => 'Texto Encabezado PDF'
);
?>
<div class="loader-overlay" style="z-index: 1000; opacity:.8; background: #fff; top: 0; left: 0; right: 0; bottom: 0; position: fixed;"></div>
<div class="loader-overlay" style="z-index: 1001; top: 0; left: 0; right: 0; bottom: 0; position: fixed; height: 100%; width: 100%;">
	<img alt="cargando" src="//estaticos.thetimebilling.com/templates/cargando.gif" style="background: #fff; margin:150px; padding: 20px; border: 1px solid #eee;"/>
</div>
<form>
	<?php echo Html::SelectQuery($sesion, "SELECT id_formato, descripcion FROM cobro_rtf", "id_formato", $id_formato, '', ' '); ?>
	<?php echo $Form->submit(__('Editar')); ?>
</form>
<hr/>
<form method="POST" id="form_nota_cobro">
	<input type="hidden" name="opc" value="guardar"/>
	<input type="hidden" name="nota[id_formato]" value="<?php echo $nota['id_formato']; ?>"/>
	<p>
		<label>Descripcion: <input name="nota[descripcion]" value="<?php echo $nota['descripcion']; ?>"/></label><br/>
	</p>
	<h3>Template</h3>
	<div id="tabs-secciones" class="tabs">
		<ul>
			<?php foreach ($nota['secciones'] as $seccion => $html) { ?>
				<li><a href="#tab-<?php echo $seccion; ?>"><?php echo $seccion; ?></a></li>
			<?php } ?>
		</ul>
		<?php foreach ($nota['secciones'] as $seccion => $html) { ?>
			<div id="tab-<?php echo $seccion; ?>">
				<textarea class="ckeditor" id="editor_<?php echo $seccion; ?>" name="nota[secciones][<?php echo $seccion; ?>]"><?php echo htmlentities($html); ?></textarea>
				<?php if (isset($NotaCobro->diccionario[$seccion])) { ?>
					<?php
					$valores = UtilesApp::mergeKeyValue($NotaCobro->diccionario[$seccion]);
					echo $Form->label(__('Insertar Valor:'), "valores_$seccion");
					echo $Form->select(null, $valores, null, array('id' => "valores_$seccion", 'class' => 'valores', 'empty' => false));
					echo $Form->button(__('Insertar'), array('class' => 'agregar_valor'));
					?>
				<?php } ?>
				<?php if (isset($NotaCobro->secciones[$seccion])) { ?>
					<br/>
					<?php
					echo $Form->label(__('Agregar Sección:'), "secciones_$seccion");
					echo $Form->select(null, $NotaCobro->secciones[$seccion], null, array('id' => "secciones_$seccion", 'class' => 'secciones', 'empty' => false));
					echo $Form->button(__('Agregar'), array('class' => 'agregar_seccion'));
					?>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
	<br/>
	<br/>
	<br/>
	<div id="tabs-parts" class="tabs">
		<ul>
			<?php foreach ($template_parts as $campo => $nombre) { ?>
				<li><a href="#tab-<?php echo $campo; ?>"><?php echo $nombre; ?></a></li>
			<?php } ?>
		</ul>
		<?php foreach ($template_parts as $campo => $nombre) { ?>
			<div id="tab-<?php echo $campo; ?>">
				<textarea name="nota[<?php echo $campo; ?>]" class="<?php echo strpos($campo, 'html_') === 0 ? 'ckeditor' : ''; ?>" rows="12" cols="40" style="width: 98%"><?php echo $nota[$campo]; ?></textarea>
			</div>
		<?php } ?>
	</div>
	<div style="padding: 23px">
		<?php echo $Form->button('Guardar', array('id' => 'btn_guardar')); ?>
		<?php echo $Form->button('Guardar como nueva carta', array('id' => 'btn_guardar_nueva')); ?>
		<br/>
		<br/>
		<label>Previsualizar con el cobro N° <input name="id_cobro" value="<?php echo $id_cobro; ?>"/></label>
		<?php echo $Form->button('Previsualizar documento', array('id' => 'btn_previsualizar')); ?>
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
	var diccionario = <?php echo json_encode(UtilesApp::utf8izar($NotaCobro->diccionario)); ?>;
	var secciones = <?php echo json_encode(UtilesApp::utf8izar($NotaCobro->secciones)); ?>;

	jQuery(function() {
		CKEDITOR.config.fontSize_sizes = '7/7pt;8/8pt;9/9pt;10/10pt;11/11pt;12/12pt;14/14pt;16/16pt;18/18pt;20/20pt;22/22pt;24/24pt;26/26pt;28/28pt';
		CKEDITOR.config.allowedContent = true;

		jQuery('.agregar_valor').live('click', AgregarValor);
		jQuery('.agregar_seccion').live('click', AgregarSeccion);
		jQuery('#btn_guardar, #btn_guardar_nueva').click(function() {
			jQuery('[name=opc]').val('guardar');
			if (jQuery(this).attr('id') === 'btn_guardar_nueva') {
				jQuery('[name="nota[id_formato]"]').val('');

			}
			jQuery.each(CKEDITOR.instances, function(i) {
				CKEDITOR.instances[i].updateElement();
			});
			var form = jQuery('#form_nota_cobro');
			jQuery.post(form.attr('action'), form.serialize(), function(carta) {
				if (carta.id) {
					alerta('La nota de cobro se guardó correctamente.');
					if (jQuery('[name="nota[id_formato]"]').val() != carta.id) {
						window.location = '?id_formato=' + carta.id;
					}
				} else if (carta.error) {
					alerta(carta.error);
				}
			}, 'json');
		});
		jQuery('#btn_previsualizar').click(function() {
			jQuery('[name=opc]').val('prev');
			jQuery('#form_nota_cobro').submit();
		});
		jQuery('#btn_valores').click(function() {
			window.open('carta_test_valores.php?tipo=nota&id_cobro=' + jQuery('[name=id_cobro]').val());
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
					'name': 'nota[secciones][' + seccion + ']',
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
					selvals.append(jQuery('<option/>', {value: idx, text: val}));
				});
			}
		}
		return false;
	}

	function PrevisualizarHTML() {
		var css = jQuery('[name="nota[cobro_css]"]').val();
		var body = GenerarHTML('INFORME');
		var html = '<style type="text/css">' + css + '</style>' + body;
		jQuery('#previsualizacion_html')[0].contentWindow.document.body.innerHTML = html;
	}

	function GenerarHTML(seccion) {
		var id = 'editor_' + seccion;

		var html = CKEDITOR.instances[id] ? CKEDITOR.instances[id].getData() : jQuery('#' + id).val();
		if (!html)
			return '';

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
