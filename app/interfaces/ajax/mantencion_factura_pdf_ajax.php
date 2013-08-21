<?php
	
require_once dirname(__FILE__).'/../../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/classes/FacturaPdfDatos.php';

$sesion = new Sesion(array('ADM','COB'));

header("Content-Type: text/html; charset=ISO-8859-1");

if($_GET['id_documento_legal']) $id_documento_legal=$_GET['id_documento_legal'];
        
if( empty($id_documento_legal) ) {
    $query = "SELECT id_documento_legal FROM prm_documento_legal LIMIT 1";
    $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    list($id_documento_legal) = mysql_fetch_array($resp);
}
	
if( $opc == 'guardar' || $opc == 'imprimir_factura' ) {
	
	foreach($_POST as $key => $value) {
		list($indicador, $campo, $id) = explode("_",$key);
    
        if( $id == 'documento' ) {
            list($e1,$e2,$e3,$e4,$id) = explode("_",$key);
            $campo = 'id_documento_legal';
        }
	
		if( $indicador != 'fac' ) {
			continue;
		}
		
		$factura_pdf_datos = new FacturaPdfDatos($sesion);
		$factura_pdf_datos->Load($id);
	
		if(strtolower($campo)=='ejemplo') {
			$value=utf8_decode($value);
		}
		
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
                
    if ($opc == 'guardar') {
    	echo '<div style="color:green;">Plantilla Guardada</div>';
    }
        
	if( $opc == 'imprimir_factura' ) {
		$factura_pdf_datos->generarFacturaPDF( $id_factura );
	}
		
}
	
if( $opc == 'dibuja_tabla' ) {
		
    $query = "SELECT id_factura FROM factura WHERE id_documento_legal = '$id_documento_legal' ORDER BY id_factura DESC LIMIT 1";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($id_factura) = mysql_fetch_array($resp);
    
	$factura_pdf_datos = new FacturaPdfDatos($sesion);
	
	$query = " SELECT * 
				FROM factura_pdf_datos 
                JOIN factura_pdf_tipo_datos USING( id_tipo_dato ) 
                JOIN factura_pdf_datos_categoria USING( id_factura_pdf_datos_categoria )
                WHERE factura_pdf_datos.id_documento_legal = '$id_documento_legal' 
                ORDER BY factura_pdf_datos_categoria.id_factura_pdf_datos_categoria, factura_pdf_tipo_datos.glosa_tipo_dato ASC";
							
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$celda=0;
    $fila=array();
    $fila=$factura_pdf_datos->CargarFilaDato( $id_factura );
	
	$arraypapeles=array();
        $arraypapeles['216x280']='Carta Portrait';
        $arraypapeles['280x216']='Carta Landscape';
        $arraypapeles['216x356']='Legal Portrait';
        $arraypapeles['356x216']='Legal Landscape';
        $arraypapeles['297x420']='A3 Portrait';
        $arraypapeles['420x297']='A3 Landscape';
        $arraypapeles['210x297']='A4 Portrait';
        $arraypapeles['297x210']='A4 Landscape';
        
    while( $row = mysql_fetch_assoc($resp) ) {
    
        $celda++;
        
        if ($row['codigo_tipo_dato']=='tipo_papel'):
                echo "<ul class='cat_".  $row['id_factura_pdf_datos_categoria']  ."' rel='".$celda."'  id=\"fila_".$row['id_dato']."\" >";
		
		echo "<li  class='st1cellx' id='glosa_".$row['id_dato']."' rel=''>".__($row['glosa_tipo_dato'])."</li>";
		echo "<li class='nd2cellx'><input rel='".  $row['id_factura_pdf_datos_categoria']  ."' type=\"checkbox\" class=\"fac_activo\" name=\"fac_activo_".$row['id_dato']."\" id=\"fac_activo_".$row['id_dato']."\"  value=\"0\" disabled='disabled' />";
			echo "<input type=\"hidden\" name=\"fac_id_documento_legal_".$row['id_dato']."\" id=\"fac_id_documento_legal_".$row['id_dato']."\"  value=\"".$id_documento_legal."\" /></li>";
        echo "<li class='dimcells'><input type=\"text\" size=\"4\"    name=\"fac_coordinateX_".$row['id_dato']."\" id=\"margenX\"  value=\"".$row['coordinateX']."\" />";
			echo "<input type=\"text\" size=\"4\"    name=\"fac_coordinateY_".$row['id_dato']."\" id=\"margenY\"  value=\"".$row['coordinateY']."\" />";
		
		$llave=$row['cellW'].'x'.$row['cellH'];

        if (array_key_exists($llave,$arraypapeles)):
            echo "<input type=\"text\"  style='display:none;' size=\"4\"   name=\"fac_cellW_".$row['id_dato']."\" id=\"ancho\"  value=\"".$row['cellW']."\" />";
			echo "<input type=\"text\"  style='display:none;' size=\"4\"   name=\"fac_cellH_".$row['id_dato']."\"  id=\"alto\"   value=\"".$row['cellH']."\" />";

        echo '<select class="papercell" id="papersize">';
            foreach($arraypapeles as $key=>$value):
                echo '<option value="'.$key.'" '.(($key==$llave)? 'selected="selected"':'').' >'.$value.'</option>';
            endforeach;
                echo '<option value="0x0" >Perzonalizado</option>';
        echo '</select>';
              

        else:
            echo "<input type=\"text\" size=\"4\"   name=\"fac_cellW_".$row['id_dato']."\" id=\"ancho\"  value=\"".$row['cellW']."\" />";
			echo "<input type=\"text\" size=\"4\"   name=\"fac_cellH_".$row['id_dato']."\"  id=\"alto\"   value=\"".$row['cellH']."\" />";
		endif;

        echo '</li>';
        echo "<li class='fatcell' id='fatcell'><input type=\"text\"  name=\"fac_font_".$row['id_dato']."\" id=\"fondo\"   value=\"". $row['font'] ."\" /></li>";
		echo "</ul>";
        
        else:
		
		$ejemplo=nl2br(($row['Ejemplo']!='')? $row['Ejemplo']:$fila[$row['codigo_tipo_dato']] );
        
        echo "<ul  class='cat_".  $row['id_factura_pdf_datos_categoria']  ."' rel='".$celda."' id=\"fila_".$row['id_dato']."\">";
			
			echo "<li   class='st1cellx'  id='glosa_".$row['id_dato']."' rel='". $ejemplo ."'>".__($row['glosa_tipo_dato'])."</li>";
			
			echo "<li  class='nd2cellx' ><input rel='".  $row['id_factura_pdf_datos_categoria']  ."' type=\"checkbox\" class=\"fac_activo\" name=\"fac_activo_".$row['id_dato']."\" id=\"fac_activo_".$row['id_dato']."\"  value=\"1\" ".( $row['activo'] == 1 ? 'checked' : '' )." />";
				echo "<input type=\"hidden\" name=\"fac_id_documento_legal_".$row['id_dato']."\" id=\"fac_id_documento_legal_".$row['id_dato']."\" value=\"".$id_documento_legal."\" />";
				echo "<input type=\"hidden\" name=\"fac_ejemplo_".$row['id_dato']."\" id=\"fac_ejemplo_".$row['id_dato']."\" value=\"".$ejemplo."\" />";
			echo "</li>";
        	
        	echo "<li class='dimcells'><input type=\"text\" size=\"4\" class=\"facpos\" rel=\"".$row['id_dato']."\" name=\"fac_coordinateX_".$row['id_dato']."\" id=\"fac_coordinateX_".$row['id_dato']."\"  value=\"".$row['coordinateX']."\" />";
				echo "<input type=\"text\" size=\"4\" class=\"facpos\" rel=\"".$row['id_dato']."\" name=\"fac_coordinateY_".$row['id_dato']."\" id=\"fac_coordinateY_".$row['id_dato']."\"  value=\"".$row['coordinateY']."\" />";
				echo "<input type=\"text\" size=\"4\" class=\"facsize\" rel=\"".$row['id_dato']."\" name=\"fac_cellW_".$row['id_dato']."\" id=\"fac_cellW_".$row['id_dato']."\"  value=\"".$row['cellW']."\" />";
				echo "<input type=\"text\" size=\"4\" class=\"facsize\" rel=\"".$row['id_dato']."\" name=\"fac_cellH_".$row['id_dato']."\" id=\"fac_cellH_".$row['id_dato']."\"  value=\"".$row['cellH']."\" /></li>";
			
			echo "<li class='th7cellx'>
					<select name=\"fac_font_".$row['id_dato']."\" id=\"fac_font_".$row['id_dato']."\" class=\"facfont\" rel=\"".$row['id_dato']."\">
						<option value='Times' ".($row['font'] == 'Times' ? 'selected' : '').">Times New Roman</option>
						<option value='Arial' ".($row['font'] == 'Arial' ? 'selected' : '').">Arial</option>
						<option value='Courier' ".($row['font'] == 'Courier' ? 'selected' : '').">Courier</option>
						<option value='Symbol' ".($row['font'] == 'Symbol' ? 'selected' : '').">Symbolic</option>
					</select></li>";
		
			echo "<li class='th8cellx'>
					<select name=\"fac_style_".$row['id_dato']."\" id=\"fac_style_".$row['id_dato']."\" class=\"facstyle\" rel=\"".$row['id_dato']."\">
						<option value='' ".($row['style'] == '' ? 'selected' : '').">Normal</option>
						<option value='B' ".($row['style'] == 'B' ? 'selected' : '').">Bold</option>
						<option value='I' ".($row['style'] == 'I' ? 'selected' : '').">Italic</option>
						<option value='U' ".($row['style'] == 'U' ? 'selected' : '').">Underline</option>
					</select></li>";
									
			echo "<li class='th9cellx'>
					<select name=\"fac_mayuscula_".$row['id_dato']."\" id=\"fac_mayuscula_".$row['id_dato']."\" class=\"facmayus\" rel=\"".$row['id_dato']."\">
						<option value='' ".($row['mayuscula'] == '' ? 'selected' : '').">Normal</option>
						<option value='may' ".($row['mayuscula'] == 'may' ? 'selected' : '').">Mayúscula</option>
						<option value='min' ".($row['mayuscula'] == 'min' ? 'selected' : '').">Minúscula</option>
					</select></li>";

			echo "<li class='th10cellx'>
					<select name=\"fac_align_".$row['id_dato']."\" id=\"fac_align_".$row['id_dato']."\" class=\"facalign\" rel=\"".$row['id_dato']."\">
						<option value='L' ".($row['align'] == 'L' ? 'selected' : '').">Izquierda</option>
						<option value='R' ".($row['align'] == 'R' ? 'selected' : '').">Derecha</option>
						<option value='C' ".($row['align'] == 'C' ? 'selected' : '').">Centro</option>
						<option value='J' ".($row['align'] == 'J' ? 'selected' : '').">Justificado</option>
					</select></li>";

			echo "<li class='th11cellx'>
					<input type=\"text\" size=\"3\" name=\"fac_tamano_".$row['id_dato']."\" id=\"fac_tamano_".$row['id_dato']."\" class=\"fontsize\" rel=\"".$row['id_dato']."\" value=\"".$row['tamano']."\" /></li>";
			echo "</ul>";
            endif;
	}
}
?>


