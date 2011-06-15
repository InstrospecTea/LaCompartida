<?php
	$query_doc_legales = "SELECT id_documento_legal AS id, glosa FROM prm_documento_legal ORDER BY glosa";
	$query_nros_doc_legales = "SELECT COUNT(*) FROM prm_documento_legal";
	$nros_doc_legales = mysql_query($query_nros_doc_legales, $sesion->dbh) or Utiles::errorSQL($query_nros_doc_legales,__FILE__,__LINE__,$sesion->dbh);
	list($nros_doc_legales)=mysql_fetch_array($nros_doc_legales);

	$query_contrato_docs_legales = "SELECT id_tipo_documento_legal, honorarios, gastos_con_impuestos, gastos_sin_impuestos
		FROM contrato_documento_legal 
		WHERE id_contrato " . (empty($contrato->fields['id_contrato']) ? 'IS NULL' : '= ' . $contrato->fields['id_contrato']);

	$contrato_docs_legales = mysql_query($query_contrato_docs_legales, $sesion->dbh) or Utiles::errorSQL($query_contrato_docs_legales,__FILE__,__LINE__,$sesion->dbh);
	$nro_docs_legales = mysql_num_rows($contrato_docs_legales);
?>
<script type="text/javascript">
var fila_doc_legal = <?php echo $nro_docs_legales ?>;
</script>

<center>
	<table id="doc_legales_asociados" width='60%' style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom:1px solid #454545;' cellpadding="3" cellspacing="3" style="border-collapse:collapse;">
		<tr>
			<td bgcolor="#6CA522"><?php echo __('Documentos Legales') ?></td>
			<td bgcolor="#6CA522"><?php echo __('Honorario') ?></td>
			<td bgcolor="#6CA522"><?php echo __('Gasto c/IVA') ?></td>
			<td bgcolor="#6CA522"><?php echo __('Gasto s/IVA') ?></td>
			<td bgcolor="#6CA522" style="width:21px;"></td>
		</tr>
		<?php if ($nro_docs_legales > 0): ?>
			<?php $i = 0; ?>
			<?php while (list($id_tipo_documento_legal, $honorario, $gastos_con_impuestos, $gastos_sin_impuestos) = mysql_fetch_array($contrato_docs_legales)) { ?>
			<tr id="doc_legal_<?php echo $i ?>">
				<td align="center"><?php echo Html::SelectQuery( $sesion, $query_doc_legales, 'docs_legales['. $i . '][documento_legal]', $id_tipo_documento_legal, null, 'Documento Legal' ) ?></td>
				<td align="center"><input id="honorario_<?php echo $i ?>" type="checkbox" class="honorario" name="docs_legales[<?php echo $i ?>][honorario]" <?php echo $honorario == '1' ? 'checked="checked"' : '' ?> /></td>
				<td align="center"><input id="gastos_con_iva_<?php echo $i ?>" type="checkbox" class="gastos_con_iva" name="docs_legales[<?php echo $i ?>][gastos_con_iva]" <?php echo $gastos_con_impuestos == '1' ? 'checked="checked"' : '' ?> /></td>
				<td align="center"><input id="gastos_sin_iva_<?php echo $i ?>" type="checkbox" class="gastos_sin_iva" name="docs_legales[<?php echo $i ?>][gastos_sin_iva]" <?php echo $gastos_sin_impuestos == '1' ? 'checked="checked"' : '' ?> /></td>
				<td><img style="cursor:pointer" <img src="<?php echo Conf::ImgDir() ?>/cruz_roja.gif" onclick="borrar_doc_legal($('doc_legal_<?php echo $i ?>'))" /></td>
			</tr>
			<?php $i++; ?>
			<?php } ?>
			
		<?php else: ?>
		<tr id="doc_legal_0">
			<td align="center"><?php echo Html::SelectQuery( $sesion, $query_doc_legales, 'docs_legales[0][documento_legal]', null, null, 'Documento Legal' ) ?></td>
			<td align="center"><input id="honorario_0" type="checkbox" class="honorario" name="docs_legales[0][honorario]" /></td>
			<td align="center"><input id="gastos_con_iva_0" type="checkbox" class="gastos_con_iva" name="docs_legales[0][gastos_con_iva]" /></td>
			<td align="center"><input id="gastos_sin_iva_0" type="checkbox" class="gastos_sin_iva" name="docs_legales[0][gastos_sin_iva]" /></td>
			<td></td>
		</tr>
		<?php endif; ?>
	</table>
	</center>
<br />
<center><input type="button" class="btn" value="<?php echo __('Asociar nuevo documento legal') ?>" id="asociar_doc_legal" /></center>

<script type="text/javascript">
	var checks = new Array('honorario', 'gastos_con_iva', 'gastos_sin_iva');

	function agregar_observador_click(elemento) {
		$(elemento + '_' + fila_doc_legal).observe('click', function(event) {
			var check = $(Event.element(event));
			if (check.checked) {
				$$('.' + elemento).each(function(item){ if (!$(item).checked) { $(item).disabled = true; } });
			} else {
				$$('.' + elemento).each(function(item){ $(item).disabled = false; });
			}
			verificar_opciones_ingresadas();
		});
		return true;
	}

	function agregar_todos_observador_click(elemento) {
		$$('.' + elemento).invoke('observe', 'click', function(event) {
			var check = $(Event.element(event));
			if (check.checked) {
				$$('.' + elemento).each(function(item){ if (!$(item).checked) { $(item).disabled = true; } });
			} else {
				$$('.' + elemento).each(function(item){ $(item).disabled = false; });
			}
			verificar_opciones_ingresadas();
		});
		return true;
	}

	function varificar_check_anteriores(elemento) {
		var marcado = false;
		$$('.' + elemento).each(function(item){ if ($(item).checked) marcado = true; return true; });
		if (marcado) {
			$$('.' + elemento).each(function(item){ if (!$(item).checked) { $(item).disabled = true; } });
		} else {
			$$('.' + elemento).each(function(item){ $(item).disabled = false; });
		}
		return true;
	}

	function borrar_doc_legal(elementoBorrar) {
		if ($$('#doc_legales_asociados tr').length - 1 == 1) {
			var elementos_id = elementoBorrar.id.split('_');
			var elemento_id = elementos_id[elementos_id.length-1];
			$('docs_legales[' + elemento_id + '][documento_legal]').value = "";
			$('honorario_' + elemento_id).checked = false;
			$('gastos_con_iva_' + elemento_id).checked = false;
			$('gastos_sin_iva_' + elemento_id).checked = false;
			elementoBorrar.select('td')[4].innerHTML = "";
		} else {
			elementoBorrar.remove();
			checks.each(function(check) {
				varificar_check_anteriores(check);
			});
		}
		verificar_opciones_ingresadas();
	}

	function agregar_observador_select() {
		$$('#doc_legales_asociados select').invoke('observe', 'change', function(event) {
			var select = $(Event.element(event));
			
			var trs = $$('#doc_legales_asociados tr');
			if (trs.length - 1 == 1) {
				if (select.getValue() != "") {
					tr = trs[1];
					tr.childElements()[4].innerHTML = '<img style="cursor:pointer" src="<?php echo Conf::ImgDir() ?>/cruz_roja.gif" onclick="borrar_doc_legal($(\'' + tr.id + '\'))" />';
				} else {
					trs[1].childElements()[4].innerHTML = "";
				}
			}
			
			return true;
		});
	}
	
	function validar_doc_legales(desdeSubmit) {
		var valido_tipo_doc = true;
		var valido_checks = true;
		var fila = 0;
		$$('#doc_legales_asociados tr').each(function(tr, index) {
			if (index == 0) return;
			fila = index;

			var checkeado = false;
			tr.select('input').each(function(input) { if (input.checked) { checkeado = true; throw $break; } });
			var valor_select = tr.select('select')[0].getValue();

			if (checkeado && valor_select == "") {
				valido_tipo_doc = false;
				throw $break;
			}

			if (valor_select != "" && !checkeado) {
				valido_checks = false;
				throw $break;
			}
			
			if (valor_select == "" && !checkeado) {
				valido_tipo_doc = false;
				valido_checks = false;
				throw $break;
			}

		});

		if (desdeSubmit) {
			if (!valido_tipo_doc && valido_checks) {
				alert("En los documentos legales por defecto, en la fila " + fila + " debe ingresar el tipo de documento");
				return false;
			}
		} else if (!valido_tipo_doc || !valido_checks) {
			alert("El documento legal especificado en la fila "+fila+" no incluye el tipo de documento o el tipo de información que contendrá");
			return false;
		}
		
		return true;
	}
	
	function verificar_opciones_ingresadas() {
		honorario = false; gastos_sin = false; gastos_con = false;
		$$('.honorario').each(function(check) {
			if (check.checked) { honorario = true; throw $break; }
		});
		$$('.gastos_con_iva').each(function(check) {
			if (check.checked) { gastos_con = true; throw $break; }
		});
		$$('.gastos_sin_iva').each(function(check) {
			if (check.checked) { gastos_sin = true; throw $break; }
		});
		if (honorario && gastos_sin && gastos_con) {
			$('asociar_doc_legal').disabled = true;
			return true;
		}
		$('asociar_doc_legal').disabled = false;
		return false;
	}

	document.observe("dom:loaded", function() {
		var template_docs_legales = new Template($('template_docs_legales').innerHTML);

		$('asociar_doc_legal').observe('click', function() {
			if ($$('#doc_legales_asociados tr').length-1 < 3 && validar_doc_legales()) {
				fila_doc_legal += 1;
				$('doc_legales_asociados').insert(template_docs_legales.evaluate({fila: fila_doc_legal}), { 'position' : 'bottom' });
				checks.each(function(check) {
					agregar_observador_click(check)
					varificar_check_anteriores(check);
				});
				agregar_observador_select();
				verificar_opciones_ingresadas();
			}
		});

		<?php if ($nro_docs_legales > 0): ?>
		checks.each(function(check) {
			agregar_todos_observador_click(check);
			varificar_check_anteriores(check);
		});
		agregar_observador_select();
		<?php else: ?>
		checks.each(function(check) { agregar_observador_click(check); });
		agregar_observador_select();
		<?php endif; ?>
		verificar_opciones_ingresadas();
	});
</script>

<table style="display:none;">
	<tbody id="template_docs_legales">
		<tr id="doc_legal_#{fila}">
			<td align="center"><?php echo Html::SelectQuery( $sesion, $query_doc_legales, 'docs_legales[#{fila}][documento_legal]', null, null, 'Documento Legal' ) ?></td>
			<td align="center"><input id="honorario_#{fila}" type="checkbox" class="honorario" name="docs_legales[#{fila}][honorario]" /></td>
			<td align="center"><input id="gastos_con_iva_#{fila}" type="checkbox" class="gastos_con_iva" name="docs_legales[#{fila}][gastos_con_iva]" /></td>
			<td align="center"><input id="gastos_sin_iva_#{fila}" type="checkbox" class="gastos_sin_iva" name="docs_legales[#{fila}][gastos_sin_iva]" /></td>
			<td><img style="cursor:pointer" <img src="<?php echo Conf::ImgDir() ?>/cruz_roja.gif" onclick="borrar_doc_legal($('doc_legal_#{fila}'))" /></td>
		</tr>
	</tbody>
</table>