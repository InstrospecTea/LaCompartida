<?
	require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/fw/classes/noticia.php';
	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/classes/lista.php';



	$Sesion = new Sesion( );
	$pagina = new Pagina($Sesion);
	$pagina->titulo = "Noticias";
	$pagina->PrintHeaders();

	$lista_noticia = new ListaNoticias($Sesion,'',"SELECT * FROM noticia 	
														WHERE id_noticia_agrupador = '$id_noticia_agrupador'
														ORDER BY fecha_creacion LIMIT 0,10");


    $pagina->PrintTop();

?>
<style type="text/css">
<!--
.Estilo1 {color: #000000}
-->
</style>


<?
    for($x=0;$x<$lista_noticia->num; $x++)
    {
        $noticia = $lista_noticia->Get($x)
?>
<table width="676" border="0" cellpadding="0" cellspacing="0">
  <!--DWLayoutTable-->
  <tr>
    <td width="16" height="3"></td>
    <td width="38" rowspan="2" valign="top"><div align="center"><img src="<?=Conf::ImgDir()?>/noticia.gif" width="35" height="30" border=0></div></td>
    <td width="10"></td>
    <td width="513"></td>
    <td width="99"></td>
  </tr>
  <tr>
    <td height="27"></td>
    <td></td>

    <td valign="top"><b> <h1 align="left" class="Estilo1"><?=$noticia->fields['titulo']; ?>
    </b></h1></td>
  <td></td>
  </tr>
  <tr>
    <td height="16"></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td height="47"></td>
    <td></td>
    <td></td>
    <td valign="top"><h2 align="left" class="Estilo1">     <i> <?=$noticia->fields['resumen']; ?></i>      <!--DWLayoutEmptyCell-->      &nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td height="59"></td>
 <td></td>
    <td></td>
    <td valign="top"><p>&nbsp;<a href="ver_noticia.php?id_noticia=<?=$noticia->fields['id_noticia']?>&id_noticia_agrupador=<?=$noticia->fields['id_noticia_agrupador']?> " class="Estilo2">Leer mas..</a></p>
    <hr></td>
    <td></td>
  </tr>
  <tr>
    <td height="13"></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
</table>
<?
     }
?>

