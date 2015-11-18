<?php
	#@todo falta que checkee el largo de los varchar y que solo deje ingresar numeros en los int
	require_once "../../app/conf.php";
	require_once "./funciones_mantencion_tablas.php";
	require_once "../classes/Sesion.php";
	require_once "../classes/Html.php";
	require_once "../classes/Utiles.php";
	require_once "../classes/Pagina.php";

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	$pagina->titulo = "Creación de interfaces genéricas";
	$pagina->PrintTop();

	import_request_variables("gP");

	if($clase == "");
		$clase = ucfirst($tabla);

	$archivo = "../../app/interfaces/$tabla.php";
	if(file_exists($archivo))
		$archivo = $archivo."__.php";

	$handle = fopen($archivo,"w");

	$fkeys  = GetForeignKeys($sesion, $tabla);

	fwrite($handle, '<?php '."\n");
	fwrite($handle,'
	require_once "../conf.php";
    require_once "../../fw/classes/Sesion.php";
    require_once "../../fw/classes/Html.php";
    require_once "../../fw/classes/Utiles.php";
    require_once "../../fw/classes/Pagina.php";
    require_once "../../app/classes/'.$clase.'.php";

    $sesion = new Sesion();
    $pagina = new Pagina($sesion);

	$obj = new '.$clase.'($sesion);

	if($id_'.$tabla.' > 0)
		$obj->Load($id_'.$tabla.');

	if($opcion == "guardar")
	{
');
	$query = "DESC $tabla";
	$resp = mysql_query($query) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh);
	for($i = 0; $arreglo = mysql_fetch_assoc($resp); $i++)
	{
		$nombre_campo = $arreglo['Field'];
		fwrite($handle,"\t\t".'$obj->Edit("'.$nombre_campo.'",$'.$nombre_campo.');'."\n");
	}

	fwrite($handle,'
		$obj->Write();
	}
	$pagina->titulo = "'.$clase.'";
    $pagina->PrintTop();
	?>

	<script type=text/javascript>
      <!--
      function isNumberKey(evt)
      {
         var charCode = (evt.which) ? evt.which : event.keyCode
		 if(charCode == 46) // punto
			return true;
         if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;

         return true;

      }
		function Validar()
		{
			form = document.forms[0];
		');
	$query = "DESC $tabla";
	$resp = mysql_query($query) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh);
	for($i = 0; $arreglo = mysql_fetch_assoc($resp); $i++)
	{
		$nombre_campo = $arreglo['Field'];
		if(stristr($arreglo['Type'],"tinyint") === FALSE || stristr($arreglo['Type'],"date") === FALSE ) #tipos que no se checkean
			fwrite($handle,"
			if(form.$nombre_campo.value == '')
			{
				alert(\"Debe ingresar el campo $nombre_campo\");
				form.$nombre_campo.focus();
				return false;
			}
			");
	}

		fwrite($handle,'
			return true;
		}
      //-->
   </SCRIPT>
');
	fwrite($handle,'
			<form method=post onsubmit="return Validar();">
			<input type=hidden name=opcion value=guardar />
			<input type=hidden name=\"id_'.$tabla.'\" value=\"<?php echo  $id_'.$tabla.' ?>\" />
			<table>');

	$query = "DESC $tabla";
	$resp = mysql_query($query) or Utiles::errorSQL($query,__FILE__,__LINE__,$dbh);
	for($i = 0; $arreglo = mysql_fetch_assoc($resp); $i++)
	{
		$input = $largo = $numero = $enum = "";

		if($arreglo['Key'] != "PRI")
		{
			$nombre_campo = $arreglo['Field'];

			$obj = "$"."obj->fields[";

			if(stristr($arreglo['Type'],"double") || stristr($arreglo['Type'],"int") || stristr($arreglo['Type'],"float"))
			{
			  $numero = "onkeypress=\"return isNumberKey(event)\" size=\"6\"";
			}
			if(stristr($arreglo['Type'],"tinyint"))
			{
				$input = "<input type=checkbox name=$nombre_campo id=$nombre_campo value=\"1\" <?php echo  ".$obj.$nombre_campo."] == 1 ? \"checked\" : \"\" ?> />";
			}
			else if($largo = stristr($arreglo['Type'],"varchar"))
			{
				$largo = str_replace("varchar","",$largo);
				$largo = str_replace("(","",$largo);
				$largo = str_replace(")","",$largo);
				$largo = "maxlength=$largo";
			}
			else if($lista = stristr($arreglo['Type'],"enum"))
			{
				$lista = str_replace("enum","",$lista);
				$lista = str_replace("(","",$lista);
				$lista = str_replace(")","",$lista);
				$arreglo = explode(',',$lista);
				foreach($arreglo as $key => $value)
				{
					$value = str_replace("'","",$value);
					$enum .= "<option value=\"$value\" <?php echo  ".$obj.$nombre_campo."] == \"$value\" ? \"selected\" : \"\" ?>>$value</option>\n";
				}
				$input = "<select id=\"$nombre_campo\" name=\"$nombre_campo\">$enum</select>";
			}

			if($input == "")
				$input = "<input id=\"$nombre_campo\" $largo $numero name=$nombre_campo value=\"<?php echo  $obj$nombre_campo] ?>\" />";

			if(stristr($arreglo['Type'],"date"))
				$input = '<?php echo  Html::PrintCalendar ("'.$nombre_campo.'", '.$obj.$nombre_campo.']) ?>';

			foreach($fkeys as $key => $value)
			{
				if($nombre_campo == $key)
				{
					$input = '<?php echo  Html::SelectQuery($sesion, "SELECT * FROM '.$fkeys[$nombre_campo]['table'].'", "'.$nombre_campo.'"); ?>'."\n";
				}
			}
			$nombre_campo2 = ucfirst(str_replace("_"," ",$nombre_campo));

			fwrite($handle,"
	<tr>
		<td align=\"right\">
			<label for=\"$nombre_campo\"> $nombre_campo2</label>
		</td>
		<td>
			$input
		</td>
	</tr>
");
		}
	}
	fwrite($handle,"</table>
					<input type=submit value=Guardar />
					</form>
					<?php ");
    fwrite($handle,"\n".'$pagina->PrintBottom();');

	echo("<a href=$archivo>$archivo</a>");
	$pagina->PrintBottom();
?>
