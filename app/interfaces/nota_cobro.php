<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/CartaCobro.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';

$sesion = new Sesion(array('ADM'));
$NotaCobro = new NotaCobro($sesion);

if ($opc == 'guardar') {
	$id_formato = $NotaCobro->GuardarCarta($nota);
}
else if ($opc == 'prev') {
	$id_formato = $NotaCobro->PrevisualizarDocumento($nota, $id_cobro);
}
else{
	$nota = $NotaCobro->ObtenerCarta($id_formato);
}

$pagina = new Pagina($sesion);
$pagina->titulo = __('Notas de cobro');
$pagina->PrintTop();
?>

<form>
	<?php echo Html::SelectQuery($sesion, "SELECT id_formato, descripcion FROM cobro_rtf", "id_formato", $id_formato, '', ' '); ?>
	<button type="submit">Editar</button>
</form>
<hr/>
<form method="POST">
	<input type="hidden" name="opc" value="guardar"/>
	<input type="hidden" name="nota[id_formato]" value="<?php echo $nota['id_formato']; ?>"/>
	<p>
		<label>Descripcion: <input name="nota[descripcion]" value="<?php echo $nota['descripcion']; ?>"/></label><br/>
	</p>
	<fieldset id="secciones">
		<legend>Template</legend>
		<?php foreach($nota['secciones'] as $seccion => $html){ ?>
		<div>
			<h3><?php echo $seccion; ?>:</h3>
			<textarea class="ckeditor" id="editor_<?php echo $seccion; ?>" name="nota[secciones][<?php echo $seccion; ?>]"><?php echo htmlentities($html); ?></textarea>
			<label>
				Insertar Valor:
				<select class="valores">
					<?php foreach($NotaCobro->diccionario[$seccion] as $tag => $desc) echo "<option value='$tag'>$tag - $desc</option>"; ?>
				</select>
				<button class="agregar_valor" type="button">Insertar</button>
			</label>
			<?php if(isset($NotaCobro->secciones[$seccion])){ ?>
			<label>
				Agregar Sección:
				<select class="secciones">
					<?php foreach($NotaCobro->secciones[$seccion] as $tag => $desc) echo "<option value='$tag'>%$tag% - $desc</option>"; ?>
				</select>
				<button class="agregar_seccion" type="button">Agregar</button>
			</label>
			<?php } ?>
		</div>
		<?php } ?>
	</fieldset>
	<br/>
	<br/>
	<br/>
	<?php foreach(array(
			'html_header' => 'Header',
			'html_pie' => 'Pie',
			'cobro_css' => 'CSS',
			'pdf_encabezado_imagen' => 'Imagen Encabezado PDF',
			'pdf_encabezado_texto' => 'Texto Encabezado PDF') as $campo => $nombre){ ?>
		<h4><?php echo $nombre; ?></h4>
		<textarea name="nota[<?php echo $campo; ?>]" class="<?php echo strpos($campo, 'html_') === 0 ? 'ckeditor' : ''; ?>" rows="10" cols="40"><?php echo $nota[$campo]; ?></textarea>
	<?php } ?>
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

<script type="text/javascript" src="../classes/CKEditor/ckeditor.js"></script>
<script type="text/javascript">
	var diccionario = <?php echo json_encode(UtilesApp::utf8izar($NotaCobro->diccionario)); ?>;
	var secciones = <?php echo json_encode(UtilesApp::utf8izar($NotaCobro->secciones)); ?>;

	jQuery(function(){
		jQuery('.agregar_valor').live('click', AgregarValor);
		jQuery('.agregar_seccion').live('click', AgregarSeccion);
		jQuery('#btn_guardar').click(function(){
			jQuery('[name=opc]').val('guardar');
		});
		jQuery('#btn_guardar_nueva').click(function(){
			jQuery('[name=opc]').val('guardar');
			jQuery('[name="nota[id_formato]"]').val('');
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
					'name': 'nota[secciones][' + seccion + ']',
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