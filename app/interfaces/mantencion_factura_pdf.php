<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/FacturaPdfDatos.php';
	
	$sesion = new Sesion(array('ADM','COB'));
	$pagina = new Pagina($sesion);
    
        if( empty($id_documento_legal) ) {
            $query = "SELECT id_documento_legal FROM prm_documento_legal LIMIT 1";
            $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            list($id_documento_legal) = mysql_fetch_array($resp);
        }
        
        if( empty($id_factura_pdf_datos_categoria) ) {
            $query = "SELECT id_factura_pdf_datos_categoria FROM factura_pdf_datos_categoria LIMIT 1";
            $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
            list($id_factura_pdf_datos_categoria) = mysql_fetch_array($resp);
        }
	
	if( $opc == 'guardar' || $opc == 'imprimir_factura' ) {
		foreach($_POST as $key => $value) {
			list($indicador, $campo, $id) = explode("_",$key);
                        if( $id == 'documento' ) {
                            list($e1,$e2,$e3,$e4,$id) = explode("_",$key);
                            $campo = 'id_documento_legal';
                        }
			
			if( $indicador != 'fac' ) continue;
			
			$factura_pdf_datos = new FacturaPdfDatos($sesion);
			$factura_pdf_datos->Load($id);
			$factura_pdf_datos->Edit($campo, $value);
			if( empty($_POST['fac_activo_'.$id]) ) {
				$factura_pdf_datos->Edit('activo','0');
			}
			$factura_pdf_datos->Write();
		}
        $query = "SELECT id_factura FROM factura WHERE id_documento_legal = '$id_documento_legal' ORDER BY id_factura DESC LIMIT 1";
        $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
        list($id_factura) = mysql_fetch_array($resp);

		$factura_pdf_datos = new FacturaPdfDatos($sesion);
		$factura_pdf_datos->generarFacturaPDF( $id_factura, true );
	}
	
	if( $opc == 'imprimir_factura' ) {
		$factura_pdf_datos->generarFacturaPDF( $id_factura );
	}
	
	$pagina->titulo = __('Mantención factura PDF');
	$pagina->PrintTop();
 ?>

        <form id="cambio_tipo_doc" action="#" method="POST">
        <table width="80%">
            <tr>
                <td align="right" width="20%">
                    <? echo __('Tipo documento legal:') ?>
                    &nbsp;
                </td>
                <td width="80%" align="left">
                    <? echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", "id_documento_legal", $id_documento_legal, " onchange=\"this.form.submit();\" "); ?>
                </td>
            </tr>
            <tr>
                <td align="right" width="20%">
                    <? echo __('Tipo dato:') ?>
                    &nbsp;
                </td>
                <td width="80%" align="left">
                    <? echo Html::SelectQuery($sesion, "SELECT id_factura_pdf_datos_categoria, glosa FROM factura_pdf_datos_categoria", "id_factura_pdf_datos_categoria", $id_factura_pdf_datos_categoria, " onchange=\"this.form.submit();\" "); ?>
                </td>
            </tr>
        </table>
        </form>

<?	
	echo "<form action=\"#\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"opc\" id=\"opc\" value=\"guardar\" />";
    echo "<input type=\"hidden\" name=\"id_documento_legal\" id=\"id_documento_legal\" value=\"$id_documento_legal\" />";
	echo "<input type=\"hidden\" name=\"id_factura_pdf_datos_categoria\" id=\"id_factura_pdf_datos_categoria\" value=\"$id_factura_pdf_datos_categoria\" />";
    echo "<table align=\"center\" width=\"80%\" cellpadding=\"0\" cellspacing=\"0\">";
	echo "<tr>";
	echo "<td class=\"encabezado\">Tipo Dato</td>";
	echo "<td class=\"encabezado\">Activo</td>";
	echo "<td class=\"encabezado\">Coord. X</td>";
	echo "<td class=\"encabezado\">Coord. Y</td>";
    echo "<td class=\"encabezado\">Alto celda</td>";
    echo "<td class=\"encabezado\">Ancho celda</td>";
	echo "<td class=\"encabezado\">Font Family</td>";
	echo "<td class=\"encabezado\">Font Style</td>";
	echo "<td class=\"encabezado\">Mayúscula</td>";
	echo "<td class=\"encabezado\">tamaño</td>";
	echo "</tr>";
	
	$query = " SELECT * FROM factura_pdf_datos 
                            JOIN factura_pdf_tipo_datos USING( id_tipo_dato ) 
                            JOIN factura_pdf_datos_categoria USING( id_factura_pdf_datos_categoria )
                            WHERE factura_pdf_datos.id_documento_legal = '$id_documento_legal' 
                              AND factura_pdf_datos_categoria.id_factura_pdf_datos_categoria = '$id_factura_pdf_datos_categoria' 
                            ORDER BY factura_pdf_datos_categoria.id_factura_pdf_datos_categoria ASC";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	while( $row = mysql_fetch_assoc($resp) ) 
	{
		echo "<tr>";
		echo "<td>".__($row['glosa_tipo_dato'])."</td>";
		echo "<td><input type=\"checkbox\" name=\"fac_activo_".$row['id_dato']."\" id=\"fac_activo_".$row['id_dato']."\" onchange=\"this.form.submit();\" value=\"1\" ".( $row['activo'] == 1 ? 'checked' : '' )." /></td>";
		echo "<input type=\"hidden\" name=\"fac_id_documento_legal_".$row['id_dato']."\" id=\"fac_id_documento_legal_".$row['id_dato']."\" onblur=\"this.form.submit();\" value=\"".$id_documento_legal."\" />";
                echo "<td><input type=\"text\" size=\"4\" name=\"fac_coordinateX_".$row['id_dato']."\" id=\"fac_coordinateX_".$row['id_dato']."\" onblur=\"this.form.submit();\" value=\"".$row['coordinateX']."\" /></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_coordinateY_".$row['id_dato']."\" id=\"fac_coordinateY_".$row['id_dato']."\" onblur=\"this.form.submit();\" value=\"".$row['coordinateY']."\" /></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_cellW_".$row['id_dato']."\" id=\"fac_cellW_".$row['id_dato']."\" onblur=\"this.form.submit();\" value=\"".$row['cellW']."\" /></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_cellH_".$row['id_dato']."\" id=\"fac_cellH_".$row['id_dato']."\" onblur=\"this.form.submit();\" value=\"".$row['cellH']."\" /></td>";
		echo "<td><select name=\"fac_font_".$row['id_dato']."\" id=\"fac_font_".$row['id_dato']."\" onchange=\"this.form.submit();\">
								<option value='Times' ".($row['font'] == 'Times' ? 'selected' : '').">Times New Roman</option>
								<option value='Arial' ".($row['font'] == 'Arial' ? 'selected' : '').">Arial</option>
								<option value='Courier' ".($row['font'] == 'Courier' ? 'selected' : '').">Courier</option>
								<option value='Symbol' ".($row['font'] == 'Symbol' ? 'selected' : '').">Symbolic</option>
							</select></td>";
		echo "<td><select name=\"fac_style_".$row['id_dato']."\" id=\"fac_style_".$row['id_dato']."\" onchange=\"this.form.submit();\">
								<option value='' ".($row['style'] == '' ? 'selected' : '').">Normal</option>
								<option value='B' ".($row['style'] == 'B' ? 'selected' : '').">Bold</option>
								<option value='I' ".($row['style'] == 'I' ? 'selected' : '').">Italic</option>
								<option value='U' ".($row['style'] == 'U' ? 'selected' : '').">Underline</option>
							</select></td>";
		echo "<td><select name=\"fac_mayuscula_".$row['id_dato']."\" id=\"fac_mayuscula_".$row['id_dato']."\" onchange=\"this.form.submit();\">
								<option value='' ".($row['mayuscula'] == '' ? 'selected' : '').">Normal</option>
								<option value='may' ".($row['mayuscula'] == 'may' ? 'selected' : '').">Mayúscula</option>
								<option value='min' ".($row['mayuscula'] == 'min' ? 'selected' : '').">Minúscula</option>
							</select></td>";
		echo "<td><input type=\"text\" size=\"4\" name=\"fac_tamano_".$row['id_dato']."\" id=\"fac_tamano_".$row['id_dato']."\" onblur=\"this.form.submit();\" value=\"".$row['tamano']."\" /></td>";
		echo "</tr>";
	}
	
	echo "</table>";
	echo "<br/>";
	echo "<input type=\"button\" onclick=\"GuardarDatos(this.form);\" value=\"Guardar\">";
	echo "<br/>";
	echo "<input type=\"button\" onclick=\"ImprimirFacturaPrueba(this.form);\" value=\"Imprimir Factura\">";
	echo "</form>";
	$url = "http://docs.google.com/gview?url=".Conf::Server().Conf::RootDir()."/pdf/factura_".time().".pdf&embedded=true";
?>

<iframe src=<?=$url?> style="width:800px; height:1100px;" frameborder="0"></iframe>

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
