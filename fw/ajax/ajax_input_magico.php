<?php 
    require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/Html.php';


    $sesion = new Sesion('');
    $pagina = new Pagina ($sesion);


    if($accion == "buscar")
    {
	   $query = "SELECT $campo_id,$campo_glosa
				   FROM $tabla
				   WHERE $campo_glosa LIKE '%$busqueda%'";

	   $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

	   for($i = 0; $fila = mysql_fetch_assoc($resp); $i++)
	   {
			if($i > 0)
				echo("~");
		   echo(utf8_encode(join("|",$fila)));
	   }
	}
?>
