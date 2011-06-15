<?
	require_once dirname(__FILE__).'/../conf.php';
	
	require_once Conf::ServerDir().'/interfaces/excel/components/reader.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/classes/UsuarioExt.php';
	
	require_once Conf::ServerDir().'/classes/Excel.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';

	$sesion = new Sesion(array('ADM'));
	$pagina = new Pagina($sesion);

	$pagina->titulo = "Parseador de Excel.";
	$pagina->PrintTop($pop);

	if($opc=="guardar")
	{
		$nombre_archivo = $xls['tmp_name'];

		//$e = new Excel($sesion,$nombre_archivo,1);
		$insertar = 0;
		if($in == 1)
		{
			echo "ingresando..";
			$insertar = 1;
		}
		$e = new Excel($sesion,$nombre_archivo,$insertar,$id_usuario);
		$e->LeerTodo();

		exit();
	}

?>


<form name='formulario' id='formulario' method='post' action='' autocomplete='off' enctype="multipart/form-data">
<input type=hidden name=opc value=guardar>

<input type="file" name="xls"/>
<br>
Id usuario:
<br>
<input name="id_usuario" value="0" />

<input type=hidden name=in value='<?=$in?>'/>
<input type="submit" />

</form>
