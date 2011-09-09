<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/FacturaPdfDatos.php';
	
	$sesion = new Sesion(array('ADM','COB'));
	$pagina = new Pagina($sesion);
	
	if( $opc == 'guardar' || $opc == 'imprimir_factura' ) {
		foreach($_POST as $key => $value) {
			list($indicador, $campo, $id) = split("_",$key);
			
			if( $indicador != 'fac' ) continue;
			
			$factura_pdf_datos = new FacturaPdfDatos($sesion);
			$factura_pdf_datos->Load($id);
			$factura_pdf_datos->Edit($campo, $value);
			if( empty($_POST['fac_activo_'.$id]) ) {
				$factura_pdf_datos->Edit('activo','0');
			}
			$factura_pdf_datos->Write();
		}
	}
	
	if( $opc == 'imprimir_factura' ) {
		$query = "SELECT id_factura FROM factura ORDER BY id_factura DESC LIMIT 1";
		$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($id_factura) = mysql_fetch_array($resp);
		
		$factura_pdf_datos = new FacturaPdfDatos($sesion);
		$factura_pdf_datos->generarFacturaPDF( '11365' );
	}
	
	$pagina->titulo = __('Mantención factura PDF');
	$pagina->PrintTop();
	
	echo "<form action=\"#\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"opc\" id=\"opc\" value=\"guardar\" />";
	echo "<table align=\"center\" width=\"80%\" cellpadding=\"0\" cellspacing=\"0\">";
	echo "<tr>";
	echo "<td class=\"encabezado\">Tipo Dato</td>";
	echo "<td class=\"encabezado\">Activo</td>";
	echo "<td class=\"encabezado\">Coord. X</td>";
	echo "<td class=\"encabezado\">Coord. Y</td>";
	echo "<td class=\"encabezado\">Font Family</td>";
	echo "<td class=\"encabezado\">Font Style</td>";
	echo "<td class=\"encabezado\">Mayúscula</td>";
	echo "<td class=\"encabezado\">tamaño</td>";
	echo "</tr>";
	
	$query = " SELECT * FROM factura_pdf_datos ";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	while( $row = mysql_fetch_assoc($resp) ) 
	{
		echo "<tr>";
		echo "<td>".__($row['glosa_dato'])."</td>";
		echo "<td><input type=\"checkbox\" name=\"fac_activo_".$row['id_tipo_dato']."\" id=\"fac_activo_".$row['id_tipo_dato']."\" value=\"1\" ".( $row['activo'] == 1 ? 'checked' : '' )." /></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_coordinateX_".$row['id_tipo_dato']."\" id=\"fac_coordinateX_".$row['id_tipo_dato']."\" value=\"".$row['coordinateX']."\" /></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_coordinateY_".$row['id_tipo_dato']."\" id=\"fac_coordinateY_".$row['id_tipo_dato']."\" value=\"".$row['coordinateY']."\" /></td>";
		echo "<td><select name=\"fac_font_".$row['id_tipo_dato']."\" id=\"fac_font_".$row['id_tipo_dato']."\">
								<option value='Times' ".($row['font'] == 'Times' ? 'selected' : '').">Times New Roman</option>
								<option value='Arial' ".($row['font'] == 'Arial' ? 'selected' : '').">Arial</option>
								<option value='Courier' ".($row['font'] == 'Courier' ? 'selected' : '').">Courier</option>
								<option value='Symbol' ".($row['font'] == 'Symbol' ? 'selected' : '').">Symbolic</option>
							</select></td>";
		echo "<td><select name=\"fac_style_".$row['id_tipo_dato']."\" id=\"fac_style_".$row['id_tipo_dato']."\">
								<option value='' ".($row['style'] == '' ? 'selected' : '').">Normal</option>
								<option value='B' ".($row['style'] == 'B' ? 'selected' : '').">Bold</option>
								<option value='I' ".($row['style'] == 'I' ? 'selected' : '').">Italic</option>
								<option value='U' ".($row['style'] == 'U' ? 'selected' : '').">Underline</option>
							</select></td>";
		echo "<td><select name=\"fac_mayuscula_".$row['id_tipo_dato']."\" id=\"fac_mayuscula_".$row['id_tipo_dato']."\">
								<option value='' ".($row['mayuscula'] == '' ? 'selected' : '').">Normal</option>
								<option value='may' ".($row['mayuscula'] == 'may' ? 'selected' : '').">Mayúscula</option>
								<option value='min' ".($row['mayuscula'] == 'min' ? 'selected' : '').">Minúscula</option>
							</select></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_tamano_".$row['id_tipo_dato']."\" id=\"fac_tamano_".$row['id_tipo_dato']."\" value=\"".$row['tamano']."\" /></td>";
		echo "</tr>";
	}
	
	echo "</table>";
	echo "<br/>";
	echo "<input type=\"button\" onclick=\"GuardarDatos(this.form);\" value=\"Guardar\">";
	echo "<br/>";
	echo "<input type=\"button\" onclick=\"ImprimirFacturaPrueba(this.form);\" value=\"Imprimir Factura\">";
	echo "</form>";
?>

<script type="text/javascript">
	function ImprimirFacturaPrueba( form )
	{
		$('opc').value = 'imprimir_factura';
		form.submit();
	}
	function GuardarDatos( form )
	{
		$('opc').value = 'guardar';
		form.submit();
	}
</script>

<?php
	$pagina->PrintBottom();
?>
