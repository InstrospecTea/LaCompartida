<?php
	require_once dirname(__FILE__).'/../app/conf.php';
	
	function autocargafw($class_name) {    require  Conf::ServerDir().'/../fw/classes/'.$class_name . '.php';	}
	function autocargaapp($class_name) {    require  Conf::ServerDir().'/classes/'.$class_name . '.php';	}
	spl_autoload_register('autocargafw'); 
	spl_autoload_register('autocargaapp'); 

	$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
		$pagina->titulo = __('Importacion de Usuarios');
		$pagina->PrintTop(); 
	
	if($_POST['accion']=='cargausuarios') {
	     $texto= $_POST['usuarios'];
	     $arraytexto=explode("\n",$texto);
	     $filas=array();
	     $aux=array();
	   foreach ($arraytexto as $fila):
	     if($fila!='') $filas[]=explode("\t",$fila);
	   endforeach;
	       $i=0;
	       $querinsert=array();
	       $arreglomaestro=array();
	       foreach ($filas as $fila) {
		if($fila[0]!='' AND $_POST['paso']=='inserta')    {
		    $querinsert[]= "insert into usuario (rut, dv_rut, nombre,apellido1,apellido2,telefono1, email,id_categoria_usuario,id_area_usuario,username,password)  		
		    values (TRIM('".$fila[0]."'),TRIM('".$fila[1]."'),TRIM('".$fila[2]."'),TRIM('".$fila[3]."'),TRIM('".$fila[4]."'),TRIM('".$fila[5]."'),TRIM('".$fila[6]."'),".intval($fila[7]).",".intval($fila[8]).",TRIM('".$fila[9]."'),md5('12345'));";
		   
		    $arreglomaestro[]=$fila;
		    
		    }
		if($fila[0]!='' AND $_POST['paso']!='inserta') $cadena.="<tr class='user_row' id='fila_".++$i."'><td>".implode("</td><td>",$fila)."</td><td>12345</td></tr>";
		 
	       }
	      
	    if ($_POST['paso']=='inserta'):
		   $exito=0;
		   foreach ($arreglomaestro as $insercion):
		       //echo $insercion.'<br>';
		      // if(mysql_query($insercion, $sesion->dbh)) {
			print_r($insercion);
			$exito++;
		       //}
		   endforeach;
	           echo "Se insertaron ".$exito." usuarios.";
	    else:
	    
		
		
		   echo '<form  method="POST"><div id="tablausuarios" style="padding:10px;margin:auto;">Se insertarán los siguientes datos. Puede <input type="submit" value="Confirmar"/>&nbsp;o&nbsp;<button id="volver">Volver</button></div>';
		   echo '<table><tr><th>DNI</th><th>DV</th><th>Nombre</th><th>Apellido P</th><th>Apellido M</th><th>Fono</th><th>Mail</th><th>Categoria</th><th>Area</th><th>Username</th><th>Pass</td></tr>';
		   echo $cadena;
		   echo '</table><input type="hidden" name="accion" value="cargausuarios"/><input type="hidden" name="paso" value="inserta"/><textarea id="usuarios" style="display:none;" name="usuarios" rows="18" cols="100">'.$texto.'</textarea></form>';
	    endif;
	} else {
   
	    ?>
	    <form id="adjuntaclipboard" method="POST"     >
		<textarea id="usuarios" name="usuarios" rows="18" cols="100"></textarea><br /><br />
<input type="hidden" name="accion" value="cargausuarios"/>
<input type="submit" value="enviar">
</form>

<?php
	}
	?>
<script language="javascript" type="text/javascript">
jQuery(document).ready(function() {
    
    jQuery('#confirmar').click(function() {
	
	    jQuery('#paso2').submit();
	
    });
    jQuery('#volver').click(function() {
	document.location.href='usuarios_clipboard.php';
	
    });
    
    jQuery('#usa_asuntos_por_defecto').click(function() {
	   hiddenid=jQuery(this).attr('rel');
       var string_asuntos = document.getElementById(hiddenid).value;
       var array_asuntos = string_asuntos.split(';');
	   if(jQuery(this).is(':checked')) {
            string_asuntos='true';
       } else {
            string_asuntos='false';  
       } 
             
	   for( var i = 1; i < array_asuntos.length; i++)
		{
			string_asuntos += ';'+array_asuntos[i];
        }
		
	    document.getElementById(hiddenid).value = string_asuntos;
       
    });
    jQuery('.grupoconf').each(function() {
	var LaID=jQuery(this).attr('id');
	var Glosa=LaID.replace('divx','');
	jQuery('#tabs').append('<li><a href="#'+LaID+'">'+Glosa+'</a></li>');
    });
});
Calendar.setup(
	{
		inputField	: "fecha",				// ID of the input field
		ifFormat	: "%d-%m-%Y",			// the date format
		button			: "img_fecha"		// ID of the button
	}

);
 
</script>
<?php
	$pagina->PrintBottom($popup);
?>