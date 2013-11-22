<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../app/classes/Tarifa.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';

	$sesion = new Sesion(array('PRO','REV','ADM','COB'));

	$pagina = new Pagina($sesion);

	$tarifa = new Tarifa($sesion);

	$tarifa->loadById($id_tarifa_edicion);


	if($opc != 'guardar')
	{
		$query="SELECT id_tarifa FROM tarifa WHERE guardado=0";
		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		while(list($id)=mysql_fetch_array($resp))
		{
			$tarifa_eliminar = new Tarifa($sesion);
			$tarifa_eliminar->loadById($id);
			$tarifa_eliminar->Eliminar();
		}
	}

	if($id_tarifa_previa && !$id_tarifa_edicion && $opc != 'guardar')
	{
		$query="INSERT INTO tarifa(fecha_creacion) VALUES(NOW())";
		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		$query="SELECT id_tarifa FROM tarifa ORDER BY id_tarifa DESC";
		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($id_nuevo)=mysql_fetch_array($resp);

		$tarifa->loadById($id_nuevo);
		$id_tarifa_edicion=$tarifa->fields['id_tarifa'];
	}
	else if($id_tarifa_previa && !$id_tarifa_edicion)
	{
		$query="SELECT id_tarifa FROM tarifa ORDER BY id_tarifa DESC";
		$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($id_nuevo)=mysql_fetch_array($resp);

		$tarifa->loadById($id_nuevo);
		$id_tarifa_edicion=$tarifa->fields['id_tarifa'];
	}

	// Copia los datos al nuevo tarifa.
	if($id_nuevo && $opc != 'guardar')
	{
		$query="SELECT id_usuario, id_moneda, tarifa FROM usuario_tarifa WHERE id_tarifa=".$id_tarifa_previa;
		$resp=mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		$UsuarioTarifa = new UsuarioTarifa($sesion);
		while (list($id_usuario, $id_moneda, $tarifa) = mysql_fetch_array($resp)) {
			$UsuarioTarifa->GuardarTarifa($id_nuevo, $id_usuario, $id_moneda, $tarifa);
		}

		$query="SELECT id_categoria_usuario, id_moneda, tarifa FROM categoria_tarifa WHERE id_tarifa=".$id_tarifa_previa;
		$resp=mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		$CategoriaTarifa = new CategoriaTarifa($sesion);
		while (list($id_categoria_usuario, $id_moneda, $tarifa) = mysql_fetch_array($resp)) {
			$CategoriaTarifa->GuardarTarifa($id_nuevo, $id_categoria_usuario, $id_moneda, $tarifa);
		}
	}

	if($opc == 'guardar')
	{
		$tarifa->Edit('glosa_tarifa',$glosa_tarifa);
		if($tarifa_defecto)
		{
			$tarifa->TarifaDefecto($tarifa->fields['id_tarifa']);
			$tarifa->Edit('tarifa_defecto','1');
		}
		else
			$tarifa->Edit('tarifa_defecto','0');

		if($tarifa->Write())
		{
			$usuario_tarifa = new UsuarioTarifa($sesion);
			foreach($tarifa_moneda as $id_usuario => $arr_moneda)
			{
				foreach ($arr_moneda as $id_moneda => $tarifa_monto)
				{
					$usuario_tarifa->GuardarTarifa($tarifa->fields['id_tarifa'], $id_usuario, $id_moneda, $tarifa_monto);
				}
			}
			$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
			$tarifa->Edit('guardado','1');
		}


		if($tarifa->Write())
		{
			$categoria_tarifa = new CategoriaTarifa($sesion);
			foreach($tarifa_categoria_moneda as $id_categoria_usuario => $arr_categoria_moneda)
			{
				foreach ($arr_categoria_moneda as $id_moneda => $tarifa_categoria_monto)
				{
					$categoria_tarifa->GuardarTarifaCategoria($tarifa->fields['id_tarifa'], $id_categoria_usuario, $id_moneda, $tarifa_categoria_monto);
				}
			}
			$id_tarifa_edicion = $tarifa->fields['id_tarifa'];
			$pagina->AddInfo(__('La tarifa se ha modificado satisfactoriamente'));
		}
	}


	$pagina->titulo = __('Ingreso de Tarifas');

	$pagina->PrintTop($popup);

	$active = ' onFocus="foco(this);" onBlur="no_foco(this);" ';
?>
<script>
function foco(elemento)
{
	elemento.style.border = "2px solid #000000";
}

function no_foco(elemento)
{
	elemento.style.border = "1px solid #CCCCCC";
}

function cambia_tarifa(valor)
{
	var popup = $('popup').value;
	if( confirm('<?=__('Confirma cambio de tarifa?')?>') )
		self.location.href = 'agregar_tarifa.php?id_tarifa_edicion=' +valor+ '&popup=' +popup;
}

function ActualizarTarifaUsuario(glosa_categoria,valor,glosa_moneda,vacio)
{
	var clase='.'+glosa_categoria+''+glosa_moneda;

	//alert(clase);

	if(!vacio || confirm('<?=__('Confirma cambio de tarifa para todos los usuarios de esta categoria?')?>')==true )
	{
	$$(clase).each(								// Para el cambio del tarifa de la categoria cambia todos los tarifas de usuarios
			function(item)						// que pertenecen a este categoria.
			{
				item.value=valor;
			}
		);
	}
}

function CrearTarifa( from, id )
{
	if(document.getElementById('usar_tarifa_previa').checked)
		{
			self.location.href='agregar_tarifa.php?popup=<?=$popup?>&id_tarifa_previa=' + id;
		}
	else {
		self.location.href='agregar_tarifa.php?popup=<?=$popup?>';
		}
}

</script>



<style>
#tbl_tarifa
{
	font-size: 10px;
	padding: 1px;
	margin: 0px;
	vertical-align: middle;
	border:1px solid #CCCCCC;
}
.text_box
{
	font-size: 10px;
	text-align:right;
}
</style>
<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
		echo "<table width=\"90%\" class=\"tb_base\"><tr><td align=\"center\">"; ?>
<form name=formulario id=formulario method=post action='' autocomplete="off">
	<input type="hidden" name='id_tarifa_edicion' value='<?=$tarifa->fields['id_tarifa']?>' />
	<input type="hidden" name='opc' value='guardar' />
	<input type="hidden" name='popup' id='popup' value='<?=$popup ?>' />
	<input type="hidden" name="tarifa_defecto" value="<?php echo ($tarifa->fields['tarifa_defecto']) ? $tarifa->fields['tarifa_defecto'] : 0; ?>" />
	<input type="hidden" name="glosa_tarifa" value="<?php echo $tarifa->fields['glosa_tarifa']; ?>" />
	<table width='95%' border="0" cellpadding="0" cellspacing="0">
		<tr>
<?
	$colspan=3;

	if($tarifa->fields['id_tarifa'])
	{
		$colspan=2;
?>

<?
	}
?>
			<td width="200"> <?=__('Nombre')?>: <?php echo $tarifa->fields['glosa_tarifa']; ?> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right" colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td align="right"> <input type=submit value='<?=__('Guardar') ?>' class=btn > </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align=right colspan="2">&nbsp;</td>
		</tr>
	</table>
	<br>



<?
/* self.location.href= */
	######## MONEDAS #########
	$lista_monedas = new ListaObjetos($sesion,'',"SELECT * from prm_moneda Order by id_moneda ASC");
	$td_moneda = '';
	for($x=0;$x<$lista_monedas->num;$x++)
	{
		$moneda = $lista_monedas->Get($x);
		$td_moneda .= "<td align=center class=\"border_plomo\"><b>".$moneda->fields['glosa_moneda']."</b></td>";
	}






########## CATEGORIA TARIFA ###########
	$td_categoria_tarifas = '';
	$cont = 0;
	$where = '1';
	if($id_tarifa_edicion)
	{
		$where .= " AND categoria_tarifa.id_tarifa = '$id_tarifa_edicion'";
	}
	else if($id_nuevo)
	{
		$where .= " AND categoria_tarifa.id_tarifa = '$id_nuevo'";
	}
	else if($tarifa->fields['id_tarifa'])
	{
		$where .= " AND categoria_tarifa.id_tarifa = '".$tarifa->fields['id_tarifa']."'";
	}
	else
	{
		$where = 'categoria_tarifa.id_tarifa IS NULL';
	}

	#Revisar coordinacion de usuarios con usuario_tarifa
	$query_tarifas_categoria = "SELECT categoria_tarifa.id_categoria_usuario,
																categoria_tarifa.id_tarifa,
																IF(categoria_tarifa.tarifa >= 0,categoria_tarifa.tarifa,'') AS tarifa,
																categoria_tarifa.id_moneda
																FROM categoria_tarifa
																JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario=categoria_tarifa.id_categoria_usuario
																WHERE $where
																ORDER BY prm_categoria_usuario.glosa_categoria,prm_categoria_usuario.id_categoria_usuario, categoria_tarifa.id_moneda ASC";
	$resp_categoria = mysql_query($query_tarifas_categoria, $sesion->dbh) or Utiles::errorSQL($query_tarifas_categoria,__FILE__,__LINE__,$sesion->dbh);
	list($id_categoria_usuario_tarifa,$id_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp_categoria);

	########## CATEGORIA TARIFA #########
	$query_categoria = "SELECT id_categoria_usuario, REPLACE(glosa_categoria,' ','_') AS glosa_categoria
												FROM prm_categoria_usuario
												ORDER BY glosa_categoria,id_categoria_usuario";
	$resp_categoria2 = mysql_query($query_categoria, $sesion->dbh) or Utiles::errorSQL($query_categoria,__FILE__,__LINE__,$sesion->dbh);
	$result_categoria = mysql_query("SELECT FOUND_ROWS()");
	$row_categoria = mysql_fetch_row($result_categoria);
	$total_categoria = $row_categoria[0];
	while(list($id_categoria_usuario,$glosa_categoria) = mysql_fetch_array($resp_categoria2))
	{
		$cont++;
		$glosa_categoria_2=preg_replace("/_/"," ",$glosa_categoria);
		$td_categoria_tarifas .= '<tr><td align=left class="border_plomo">'.$glosa_categoria_2.'</td>';
		$tab = $cont;
		for($j=0;$j<$lista_monedas->num;$j++)
		{
			$tab += ($total_categoria * ($j+1)) + $j;
			$money = $lista_monedas->Get($j);
			$glosa_moneda=preg_replace("/ó/","o",$money->fields['glosa_moneda']);

			#echo "comienzo<br>".$id_moneda."<br>".$money->fields['id_moneda']."<br>".$id_categoria_usuario_tarifa."<br>".$id_categoria_usuario."<br>";

			if( $id_moneda == $money->fields['id_moneda'] && $id_categoria_usuario_tarifa == $id_categoria_usuario)
			{
				$td_categoria_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='text_box' name='tarifa_categoria_moneda[$id_categoria_usuario][".$money->fields['id_moneda']."]' value='".$tarifa."' $active tabindex=$tab onChange=\"ActualizarTarifaUsuario('$glosa_categoria',this.value,'$glosa_moneda','$tarifa');\"></td> \n";
				list($id_categoria_usuario_tarifa,$id_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp_categoria);
			}
			else
				$td_categoria_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='text_box' name='tarifa_categoria_moneda[$id_categoria_usuario][".$money->fields['id_moneda']."]' value='' $active tabindex=$tab onChange=\"ActualizarTarifaUsuario('$glosa_categoria',this.value,'$glosa_moneda');\"></td> \n";
		}
		$td_categoria_tarifas .= '</tr>';
	}
	$cont=$tab;    // deja tabindex equal al maximo de la tabla de categorias para que no se interfere con la tabla de usuarios







	########## USUARIO TARIFA ###########
	$td_tarifas = '';
	//$cont = 0;
	$where = '1';
	if($id_tarifa_edicion)
	{
		$where .= " AND usuario_tarifa.id_tarifa = '$id_tarifa_edicion'";
	}
	else if($id_nuevo)
	{
		$where .= " AND usuario_tarifa.id_tarifa = '$id_nuevo'";
	}
	else if($tarifa->fields['id_tarifa'])
	{
		$where .= " AND usuario_tarifa.id_tarifa = '".$tarifa->fields['id_tarifa']."'";
	}
	else
	{
		$where = 'usuario_tarifa.id_tarifa IS NULL';
	}

	#Revisar coordinacion de usuarios con usuario_tarifa
	$query_tarifas = "SELECT	usuario_tarifa.id_usuario,
														usuario_tarifa.id_tarifa,
														IF(usuario_tarifa.tarifa >= 0,usuario_tarifa.tarifa,'') AS tarifa,
														usuario_tarifa.id_moneda
														FROM usuario_tarifa
														JOIN usuario ON usuario_tarifa.id_usuario = usuario.id_usuario
														JOIN usuario_permiso ON usuario_permiso.id_usuario=usuario_tarifa.id_usuario
														WHERE $where
														AND usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO'
														ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario, usuario_tarifa.id_moneda ASC";
	$resp = mysql_query($query_tarifas, $sesion->dbh) or Utiles::errorSQL($query_tarifas,__FILE__,__LINE__,$sesion->dbh);
	list($id_usuario_tarifa,$id_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp);

	########## USUARIO TARIFA #########
	$query = "SELECT usuario.id_usuario, CONCAT(usuario.apellido1,' ',usuario.apellido2,' ',usuario.nombre) AS nombre_usuario,
									REPLACE(prm_categoria_usuario.glosa_categoria,' ','_') as glosa_categoria
									FROM usuario
									JOIN usuario_permiso USING(id_usuario)
									LEFT JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario=usuario.id_categoria_usuario
									WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY usuario.apellido1, usuario.apellido2, usuario.nombre, usuario.id_usuario";
	$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$result = mysql_query("SELECT FOUND_ROWS()");
	$row = mysql_fetch_row($result);
	$total = $row[0];
	while(list($id_usuario,$nombre_usuario,$glosa_categoria) = mysql_fetch_array($resp2))
	{
		$cont++;
		$td_tarifas .= '<tr><td align=left class="border_plomo">'.$nombre_usuario.'</td>';
		$tab = $cont;
		for($j=0;$j<$lista_monedas->num;$j++)
		{
			$tab += ($total * ($j+1)) + $j;
			$money = $lista_monedas->Get($j);
			$glosa_moneda=$money->fields['glosa_moneda'];
			$nombre_clase=preg_replace("/ó/","o",$glosa_categoria.$glosa_moneda);

			if($id_moneda == $money->fields['id_moneda'] && $id_usuario_tarifa == $id_usuario)
			{
				$td_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='$nombre_clase' id='' name='tarifa_moneda[$id_usuario][".$money->fields['id_moneda']."]' value='".$tarifa."' $active tabindex=$tab></td> \n";
				list($id_usuario_tarifa,$id_tarifa,$tarifa,$id_moneda) = mysql_fetch_array($resp);
			}
			else
				$td_tarifas .= "<td align=right class=\"border_plomo\"><input type=text size=6 class='$nombre_clase' name='tarifa_moneda[$id_usuario][".$money->fields['id_moneda']."]' value='' $active tabindex=$tab></td> \n";
		}
		$td_tarifas .= '</tr>';
	}













if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) ) { ?>
<table width='95%' border="1px solid #BDBDBD" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
	<tr bgcolor=#A3D55C>
		<td align=left class="border_plomo"><b><?=__("Categoría")?></b></td>
		<?=$td_moneda?>
	</tr>
	<?=$td_categoria_tarifas ?>
</table>
<br>

<table width='95%' border="1px solid #BDBDBD" style='border-top: 1px solid #BDBDBD; border-right: 1px solid #BDBDBD; border-left:1px solid #BDBDBD;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
	<tr bgcolor=#A3D55C>
		<td align=left class="border_plomo"><b><?=__("Profesional")?></b></td>
		<?=$td_moneda?>
	</tr>
	<?=$td_tarifas ?>
</table>
</form>
<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
			echo "</td></tr></table>"; ?>
<br>
<? }
	else
	 { ?>
<table width='100%' border="1" style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
	<tr bgcolor=#6CA522>
		<td align=left><b><?=__("Categoría")?></b></td>
		<?=$td_moneda?>
	</tr>
	<?=$td_categoria_tarifas ?>
</table>
<br>

<table width='100%' border="1" style='border-top: 1px solid #454545; border-right: 1px solid #454545; border-left:1px solid #454545;	border-bottom:none' cellpadding="3" cellspacing="3" id='tbl_tarifa'>
	<tr bgcolor=#6CA522>
		<td align=left><b><?=__("Profesional")?></b></td>
		<?=$td_moneda?>
	</tr>
	<?=$td_tarifas ?>
</table>
</form>
<? if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaDisenoNuevo') ) || ( method_exists('Conf','UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ) ) )
			echo "</td></tr></table>"; ?>
<br>
<? }
	$pagina->PrintBottom($popup);
?>