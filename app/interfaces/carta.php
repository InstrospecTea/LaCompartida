<?php

require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('ADM'));
$CartaCobro = new CartaCobro($sesion);

if ($opc == 'guardar') {
	$id_carta = $CartaCobro->GuardarCarta($carta);
}
else if ($opc == 'prev') {
	$id_carta = $CartaCobro->PrevisualizarDocumento($carta, $id_cobro);
}
else{
	$carta = $CartaCobro->ObtenerCarta($id_carta);
}

$pagina = new Pagina($sesion);
$pagina->titulo = __('Cartas de cobro');
$pagina->PrintTop();
?>

<form>
	<?php echo Html::SelectQuery($sesion, "SELECT id_carta, descripcion FROM carta", "id_carta", $id_carta, '', ' '); ?>
	<button type="submit">Editar</button>
</form>
<hr/>
<form method="POST">
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
	<fieldset id="secciones">
		<legend>Template</legend>
		<?php foreach($carta['secciones'] as $seccion => $html){ ?>
		<div>
			<h3><?php echo $seccion; ?>:</h3>
			<textarea class="ckeditor" id="editor_<?php echo $seccion; ?>" name="carta[secciones][<?php echo $seccion; ?>]"><?php echo htmlentities($html); ?></textarea>
			<label>
				Insertar Valor:
				<select class="valores">
					<?php if (isset($CartaCobro->diccionario[$seccion])) foreach($CartaCobro->diccionario[$seccion] as $tag => $desc) echo "<option value='$tag'>$tag - $desc</option>"; ?>
				</select>
				<button class="agregar_valor" type="button">Insertar</button>
			</label>
			<?php if(isset($CartaCobro->secciones[$seccion])){ ?>
			<label>
				Agregar Sección:
				<select class="secciones">
					<?php foreach($CartaCobro->secciones[$seccion] as $tag => $desc) echo "<option value='$tag'>%$tag% - $desc</option>"; ?>
				</select>
				<button class="agregar_seccion" type="button">Agregar</button>
			</label>
			<?php } ?>
		</div>
		<?php } ?>
	</fieldset>
	<h4>CSS</h4>
	<textarea name="carta[formato_css]" rows="10" cols="40"><?php echo $carta['formato_css']; ?></textarea>
	<div style="padding: 23px">
		<input type="submit" value="Guardar" id="btn_guardar"/>
		<input type="submit" value="Guardar como nueva carta" id="btn_guardar_nueva"/>
		<br/>
		<br/>
		<label>Previsualizar con el cobro N° <input name="id_cobro" value="<?php echo $id_cobro; ?>"/></label>
		<input type="submit" value="Previsualizar carta" id="btn_previsualizar"/>
		<input type="button" value="Ver valores de tags" id="btn_valores"/>
	</div>
</form>

<script type="text/javascript" src="//static.thetimebilling.com/js/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
	var diccionario = <?php echo json_encode(UtilesApp::utf8izar($CartaCobro->diccionario)); ?>;
	var secciones = <?php echo json_encode(UtilesApp::utf8izar($CartaCobro->secciones)); ?>;

	jQuery(function(){
		jQuery('.agregar_valor').live('click', AgregarValor);
		jQuery('.agregar_seccion').live('click', AgregarSeccion);
		jQuery('#btn_guardar').click(function(){
			jQuery('[name=opc]').val('guardar');
		});
		jQuery('#btn_guardar_nueva').click(function(){
			jQuery('[name=opc]').val('guardar');
			jQuery('[name="carta[id_carta]"]').val('');
		});
		jQuery('#btn_previsualizar').click(function(){
			jQuery('[name=opc]').val('prev');
		});
		jQuery('#btn_valores').click(function(){
			window.open('carta_test_valores.php?id_cobro=' + jQuery('[name=id_cobro]').val());
		});
	});

	function AgregarValor(){
		var div = jQuery(this).closest('div');
		AgregarHTML(div, div.find('.valores').val());
		return false;
	}

	function AgregarHTML(div, valor){
		var editor = CKEDITOR.instances[div.find('.ckeditor').attr('id')];

		// Check the active editing mode.
		if ( editor.mode == 'wysiwyg' ) {
			editor.insertHtml(valor);
		}
		else {
			var ta = editor.textarea.$;
			ta.value = ta.value.substr(0, ta.selectionStart) + valor + ta.value.substr(ta.selectionEnd);
		}
		return true;
	}

	function AgregarSeccion(){
		var div = jQuery(this).closest('div');
		var seccion = div.find('.secciones').val();
		if(AgregarHTML(div, '%' + seccion + '%') && !document.getElementById('editor_' + seccion)){
			var div = jQuery('<div/>')
				.append(jQuery('<h3/>', {text: seccion}))
				.append(jQuery('<textarea/>', {
					'class': 'ckeditor',
					'name': 'carta[secciones][' + seccion + ']',
					'id': 'editor_' + seccion
				}));
			jQuery('#secciones').append(div);

			CKEDITOR.replace('editor_' + seccion);

			if(diccionario[seccion]){
				div.append(jQuery('<label/>', {text: 'Insertar Valor: '})
					.append(jQuery('<select/>', {'class': 'valores'}))
					.append(jQuery('<button/>', {
						'class': 'agregar_valor',
						type: 'button',
						text: 'Insertar'
					}))
				)
				var selvals = div.find('.valores');
				jQuery.each(diccionario[seccion], function(idx, val){
					selvals.append(jQuery('<option/>', {value: idx, text: idx + ' - ' + val}));
				});
			}

			if(secciones[seccion]){
				div.append(jQuery('<label/>', {text: 'Insertar Seccion: '})
					.append(jQuery('<select/>', {'class': 'secciones'}))
					.append(jQuery('<button/>', {
						'class': 'agregar_seccion',
						type: 'button',
						text: 'Agregar'
					}))
				)
				var selvals = div.find('.secciones');
				jQuery.each(secciones[seccion], function(idx, val){
					selvals.append(jQuery('<option/>', {value: idx, text: '%' + idx + '% - ' + val}));
				});
			}
		}
		return false;
	}

</script>

<?php $pagina->PrintBottom($popup);