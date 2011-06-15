<?
	$lista_menu_permiso = Html::ListaMenuPermiso($sesion);
	$home_html="<!-- Home Section--> \n \n    <tr>\n";
	$query = "SELECT * from menu WHERE tipo=1 and codigo in ('$lista_menu_permiso') ORDER BY orden";//Tipo=1 significa menu principal
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	for($i=0; $row = mysql_fetch_assoc($resp);$i++)
	{
		$glosa_menu=$row['glosa'];
		$img_dir='';
		if($row['foto_url'] != '')
			$img_dir = "<img src=".Conf::ImgDir()."/".$row['foto_url']." alt=''/>";

		$descripcion ='';
		if($row['descripcion'] != '')
			$descripcion=$row['descripcion']."<br/><br/>";

		$home_html.=<<<HTML
<td>
	<table class="tb_base" width=100% height="200" border=0 >
	<tr>
		<td width=25 align=right>
			$img_dir
		</td>
		<td valign="top" align="left" width=240>
		<span style="font-size:14px;"><strong>$glosa_menu</strong></span><br/><hr size=1 style="color: #BDBDBD;"/><table width=400 class="table_blanco"><tr><td><span style="font-size:10px;">$descripcion</span>
HTML;
//Ahora imprimo los sub-menu
		$query = "SELECT * from menu WHERE tipo=0 and codigo in ('$lista_menu_permiso') and codigo_padre='${row['codigo']}' ORDER BY orden";//Tipo=0 significa menu secundario
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$root = Conf::RootDir();
		for($j=0; $row = mysql_fetch_assoc($resp2);$j++)
		{
			$home_html.=<<<HTML
	   <a id="${row['codigo']}" href="$root${row['url']}" style="color: #000; text-decoration: none;">- ${row['glosa']}</a><br/>
HTML;
		}
			$home_html.=<<<HTML
		 </td></tr></table>
		</td>
	</tr>
	</table>
</td>
HTML;
		$ind =$i+1;
		if($ind%2==0 && $i != '0')
			$home_html .="</tr><tr><td colspan=2>&nbsp;</td></tr><tr>";
	}
	$home_html.="</tr><!-- End Menu Section--> \n";
?>

<table width="100%" border=0>
    <tr>
        <td align="left" nowrap>
			&nbsp;&nbsp;&nbsp;&nbsp; <strong><?=__('Usuario')?>:</strong>
			<?=$sesion->usuario->fields['nombre']?> <?=$sesion->usuario->fields['apellido1']?> <?=$sesion->usuario->fields['apellido2']?><br/>
			&nbsp;&nbsp;&nbsp;&nbsp; <strong><?=__('Ultimo ingreso')?>:</strong>
			<?=Utiles::sql2fecha($sesion->ultimo_ingreso,'%A %d de %B de %Y')?><br/><br/>
		</td>
	</tr>

	<?= $home_html ?>

	<tr>
		<td><img src="<?=Conf::ImgDir()?>/pix.gif" border="0" width="1" height="7" alt="" /></td>
	</tr>
</table>

