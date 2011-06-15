<?
	require_once dirname(__FILE__).'/../../../conf.php';

	require_once Conf::ServerDir().'/fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/fw/modulos/noticia/classes/Noticia.php';
    require_once Conf::ServerDir().'/fw/classes/Lista.php';

	$sesion = new Sesion( '');
	$pagina = new Pagina($sesion);
	$pagina->titulo = "Noticias";
	$pagina->PrintHeaders();

	$noticia = new Noticia($sesion);


	$pagina->PrintHeaders();

	if(!$noticia->Load($id_noticia))
		$pagina->FatalError("La noticia es inválida");

	$id_noticia_agrupador = $noticia->fields['id_noticia_agrupador'];

    $lista_noticia_relacionadas = new ListaNoticias($sesion,'',"SELECT * FROM noticia WHERE id_noticia_agrupador = '$id_noticia_agrupador' 
																				AND id_noticia <>'$id_noticia' ORDER BY fecha_creacion LIMIT 0,10 ");
    $pagina->PrintTop();
?>

<table width="90%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table>
				<tr>
					<td rowspan="2">
						<img src=<?=Conf::ImgDir()?>/noticia32.png>
					</td>
					<td>
						<b><span class=titulo><?=$noticia->fields['titulo'];?></span></b>
					</td>
				</tr>
				<tr>
					<td valign="top"><span class=texto_chico><?=utiles::sql2fecha($noticia->fields['fecha_creacion'],'%A, %d de %B de %Y');?></a></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td height="20"></td>
	</tr>
	<tr>
		<td valign="top"><span style="font-style: italic">"<?=$noticia->fields['resumen'] ?>"</span></td>
	</tr>
	<tr>
		<td height="15"></td>
	</tr>
	<tr>
		<td valign="top" bgcolor="#FFFFFF"><?=$noticia->fields['detalle']; ?></td>
	</tr>
</table>

<br><br>

<div style="text-align: left; padding-left: 30px;"><strong>Noticias Relacionadas<strong></div>
<hr size=1 width=90% color="#000000">

<?
    for($x=0;$x<$lista_noticia_relacionadas->num; $x++)
    {
        $noticia = $lista_noticia_relacionadas->Get($x)
?>
<table width="90%" border="0" cellpadding="0" cellspacing="0">
  <!--DWLayoutTable-->
  <tr>
    <td valign="top"><b> <a href="ver_noticia.php?id_noticia=<?=$noticia->fields['id_noticia']?>&id_noticia_agrupador=<?=$noticia->fields['id_noticia_agrupador']?>"><?= $noticia->fields['titulo'] ?></a></b> </td>
  </tr>
</table>
<?
     }

$pagina->PrintBottom();
?>
