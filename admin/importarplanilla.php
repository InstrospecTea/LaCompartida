<?php
require_once dirname(__FILE__).'/../app/conf.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);
 
	
	if($archivo=$_FILES['planilla']['tmp_name']) {
	     $pagina->titulo = __('Importacion: Archivo Cargado');
	     require_once Conf::ServerDir().'/classes/Excel/reader.php';
	    
	     $pagina->PrintTop();


		// ExcelFile($filename, $encoding);
		$data = new Spreadsheet_Excel_Reader();
		    // Set output Encoding.
		    $data->setOutputEncoding('CP1251');
		    $notas=array();

		    error_reporting(E_ALL ^ E_NOTICE);
		    $data->read($archivo);
    $celdas=array();
    $filas=array();
echo $data->sheets[0]['numRows'];
		    #Clientes
		    for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {

			for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
			    $celdas[$j]=$data->sheets[0]['cells'][$i][$j];
			      }
			    if($celdas[3]!='') $filas[]=implode("','",$celdas);  
		    
		}
	   foreach ($filas as $fila):
	      // $query="insert into clientes (glosa_cliente, rut, rsocial, dir_calle, "
	   endforeach;
	    
	} else {
	    $pagina->titulo = __('Importacion: Seleccione un archivo Excel');
	$pagina->PrintTop();
	    ?>
	    <form id="adjuntaplanilla" method="POST"      enctype="multipart/form-data">
<input type="file" name="planilla"><br>

<input type="submit">
</form>

<?php
	}
	?>
<script language="javascript" type="text/javascript">
jQuery(document).ready(function() {
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