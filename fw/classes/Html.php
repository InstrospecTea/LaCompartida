<?php

//ini_set('display_errors', 'Off');
date_default_timezone_set('America/Santiago');
require_once dirname(__FILE__) . '/../../app/conf.php';

class Html {

	public static function PrintCheckbox($sesion, $lista, $nombre, $glosa, $param_input) {
		$html = "";
		for ($i = 0; $i < $lista->num; $i++) {
			$obj = $lista->Get($i);
			$html_nombre = $obj->fields[$nombre];
			$html_checked = $obj->fields[$param_input] ? "checked" : "";
			$html_glosa = $obj->fields[$glosa];
			$html.=<<<HTML
  <tr>
        <td valign="top" align="left">
            <input type="checkbox" class="checkbox" $html_checked id="$html_nombre$i" name="$html_nombre" value="1" />
        </td>
        <td valign="top" align="left">
            <label for="$html_nombre$i">$html_glosa</label>
        </td>
  </tr>
HTML;
		}
		return $html;
	}

	public static function PrintCheckboxList($sesion, $lista, $function_rows = "PrintCheckRow") {
		$html = "";
		for ($i = 0; $i < $lista->num; $i++) {
			$obj = $lista->Get($i);
			$html .= $function_rows($obj);
		}
		return $html;
	}

	/**
	 * Construye un select a partir de un arreglo
	 * @param type $array
	 * @param type $name
	 * @param type $selected
	 * @param type $opciones
	 * @param type $titulo
	 * @param type $width
	 * @return string
	 */
	public static function SelectArrayDecente($array, $name, $selected = '', $opciones = '', $titulo = '', $width = "150px") {
		$select = "<select name='$name' id='$name' $opciones style='width: $width;'>";
		if ($titulo == 'Vacio') {
			$select .= "<option value='-1'>&nbsp;</option>\n";
		} else if ($titulo != '') {
			$select .= "<option value=''>" . $titulo . "</option>\n";
		}

		foreach ($array as $value => $key) {
			if ($value == $selected)
				$select .= "<option value='$value' selected>$key</option>\n";
			else
				$select .= "<option value='$value'>$key</option>\n";
		}

		$select .= "</select>";

		return $select;
	}

	public static function SelectArray($array, $name, $selected = '', $opciones = '', $titulo = '', $width = "150px") {
		//sort($array);
		$is_assoc = is_array($array[0]);

		$select = "<select name='$name' $opciones style='width: $width;'>";
		if ($titulo != '')
			$select .= "<option value=''>" . $titulo . "</option>\n";

		for ($i = 0; $i < count($array); $i++) {
			if ($is_assoc) {
				//sort($array[$i]);
				if (array_key_exists(1, $array[$i])) {
					$key = $array[$i][1];
					$value = $array[$i][0];
				} else {
					$key = array_pop(array_slice($array[$i], 1, 1));
					$value = array_pop(array_slice($array[$i], 0, 1));
				}
			} else {
				$key = $array[$i];
				$value = $array[$i];
			}

			if ($value == $selected)
				$select .= "<option value='$value' selected>$key</option>\n";
			else
				$select .= "<option value='$value'>$key</option>\n";
		}

		$select .= "</select>";

		return $select;
	}

	public static function CheckboxList($sesion, $query, $campoid, $campoclass, $campoglosa, $name) {
		$checkboxquery = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$html = "";

		while ($fila = mysql_fetch_assoc($checkboxquery)):
			if (!isset($fila[$campoclass]))
				$fila[$campoclass] = $campoclass;

			$html.='<label class="' . $campoclass . $fila[$campoclass] . '" for="' . $campoid . $fila[$campoid] . '"><input type="checkbox" value="' . $fila[$campoid] . '" name="' . $name . '[' . $fila[$campoid] . ']"  id="' . $campoid . $fila[$campoid] . '" class="' . $campoclass . $fila[$campoclass] . '"/>';
			$html.= ucwords($fila[$campoglosa]) . '</label>';
		endwhile;
		return $html;
	}

	public static function SelectResultado($sesion, $resp, $name, $selected = '', $opciones = '', $titulo = '', $width = '150') {
		if (is_array($selected)) {
			$cont_selected = 0;
			$seleccionado = $selected[0];
		}
		else
			$seleccionado = $selected;


		$select = "<select name='$name' id='$name' $opciones style='width: " . $width . "px;'>";
		if ($titulo != '') {
			if ($titulo == 'Vacio')
				$select .= "<option value='-1'> &nbsp; </option>\n";
			else
				$select .= "<option value=''>" . $titulo . "</option>\n";
		}

		while ($arreglo = mysql_fetch_array($resp)) {


			try {
				$id = $arreglo[0];
			} catch (Exception $e) {
				echo "jsdlkjflsjfkjl";
			}
			try {
				$glosa = $arreglo[1];
			} catch (Exception $e) {
				echo "jsdlkjflsjfkjl";
			}
			try {
				$class = $arreglo[2];
			} catch (Exception $e) {
				echo "jsdlkjflsjfkjl";
			}
			// clase opcional
			 	try {
				$rel = $arreglo[3];
			} catch (Exception $e) {
				echo "jsdlkjflsjfkjl";
			}
			// clase opcional
			$clase = ($class != "") ? " class='$class'" : "";

			if ($glosa == "")
				$glosa = $id;
			if (strcmp($id, $seleccionado) == 0) {
				$select .= "<option value='$id'$clase rel='$rel' selected='selected'>$glosa</option>\n";
				if (is_array($selected))
					$seleccionado = $selected[++$cont_selected];
			} else {
				$select .= "<option value='$id'$clase rel='$rel'>$glosa</option>\n";
			}
			//$count++;
		}

		$select .= "</select>";

		return $select;
	}

	/**
	 * Construye un select a partir de un query
	 * @param type $sesion
	 * @param type $query
	 * @param type $name
	 * @param type $selected
	 * @param type $opciones
	 * @param type $titulo
	 * @param type $width
	 * @return type
	 */
	public static function SelectQuery($sesion, $query, $name, $selected = '', $opciones = '', $titulo = '', $width = '150') {
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		return self::SelectResultado($sesion, $resp, $name, $selected, $opciones, $titulo, $width);
	}

	function PrintListRows($sesion, $lista, $function_row) {
		for ($i = 0; $i < $lista->num; $i++) {
			$obj = $lista->Get($i);
			$html .= $function_row($obj);
		}
		return $html;
	}

	function PrintListPages(& $lista, & $desde, & $x_pag, $func_js) {
		$html = <<<HTML
  <tr>
      <td colspan=25 align=center>
HTML;
		$num_pages = (int) ( $lista->mysql_total_rows / $x_pag + ( ($lista->mysql_total_rows % $x_pag) ? 1 : 0) );
		$actual_page = (int) ($desde / $x_pag) + 1;
		for ($i = 0; $i < $num_pages; $i++) {
			$page = $i + 1;
			if ($page == $actual_page)
				$html.="<a href=\"javascript:$func_js('$page');\"><b>$page</b></a> ";
			else
				$html.="<a href=\"javascript:$func_js('$page');\">$page</a> ";
		}

		$html.=<<<HTML
      </td>
  </tr>
HTML;
		return $html;
	}

	//Funci�n que imprime un calendario
	public static function PrintCalendar($input_name, $value, $requerido = true) {
		if ($value == "0000-00-00")
			echo("<script type='text/javascript'>DateInput('$input_name', false, 'YYYY-MM-DD' )</script>");#No muestra los selects, no manda nada al form
		else if ($value == "")
			echo("<script type='text/javascript'>DateInput('$input_name', $requerido, 'YYYY-MM-DD' )</script>");#Muestra por defecto la fecha de hoy
		else
			echo("<script type='text/javascript'>DateInput('$input_name', $requerido, 'YYYY-MM-DD', '$value')</script>");#Muestra el valor pasado
	}

	function PrintCalendar2($input_name, $value, $requerido = true) {
		echo '<input type="text" name="' . $input_name . '" class="fechadiff" value="' . $value . '" />';
	}

	//Imprime un reloj
	public static function PrintTime($input_name, $value, $opciones = "", $editable = true) {
		static $i = 0;
		echo '<script type="text/javascript" src="//static.thetimebilling.com/js/fs_pat.js"></script>';

		$imgs = Conf::ImgDir();
		if ($editable)
			$html .= <<<HTML
				  <table border="0" cellpadding="1" cellspacing="0">
					<tr>
					  <td>
						 <script type="text/javaScript">patCreate();</script>
						<input id="$input_name" name="$input_name" type="text" size="6" value="$value" readonly="true" $opciones onclick="javascript:patShow('$input_name','timeOneTrigger$i',':');"/>
					  </td>
					  <td height="24">
						<a href="javascript:patShow('$input_name','timeOneTrigger$i',':');">
						  <img id="timeOneTrigger$i" src="$imgs/clock.gif" border="0" alt="TimePicker" />
						</a>
					  </td>
					</tr>
				  </table>
HTML;
		else
			$html .= <<<HTML
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td>
							<input id="$input_name" name="$input_name" type="text" size="6" value="$value" readonly="true" $opciones />
						</td>
						<td height="24">
							<img id="timeOneTrigger$i" src="$imgs/clock.gif" border="0" alt="TimePicker" />
						</td>
					</tr>
				</table>
HTML;
		$i++;
		return $html;
	}

	public static function ListaMenuPermiso($sesion) {
		$usuario = $sesion->usuario;

		$tbl_usuario_permiso = 'usuario_permiso';
		$tbl_menu_permiso = 'menu_permiso';

		if (method_exists('Conf', 'TablaJuicios') && Conf::TablaJuicios()) {
			$tbl_usuario_permiso = 'j_usuario_permiso';
			$tbl_menu_permiso = 'j_menu_permiso';
		}

		//Creamos el menu
		$query = "SELECT codigo_permiso from " . $tbl_usuario_permiso . " WHERE id_usuario='" . $usuario->fields['id_usuario'] . "' or id_usuario=-1"; //rut=-1 es para incluir el permiso all que debe estar disponible para todos los usuarios. Es necesario que en la tabla usuario_permiso que est� la tupla -1,ALL
		$query = "SELECT codigo_menu from " . $tbl_menu_permiso . "  WHERE codigo_permiso in ($query)";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		for ($i = 0; list($codigo_menu_permiso[$i]) = mysql_fetch_array($resp); $i++)
			;
		$lista_menu_permiso = implode("','", $codigo_menu_permiso);

		if ($lista_menu_permiso == "")
			$lista_menu_permiso = "NADA";

		return $lista_menu_permiso;
	}

	public static function PrintMenu($sesion) {
		$lista_menu_permiso = Html::ListaMenuPermiso($sesion);

		$menu_html = "<!-- Menu Section--> \n";
		$menu_html.= <<<HTML
                <div class="wireframemenu">
                <ul>
HTML;
		$query = "SELECT * from menu WHERE tipo=1 and codigo in ('$lista_menu_permiso') ORDER BY orden"; //Tipo=1 significa menu principal
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($i = 0; $row = mysql_fetch_assoc($resp); $i++) {
			$glosa_menu = $row['glosa'];
			$menu_html.=<<<HTML
                    <li class=titulo_menu>$glosa_menu</li>
HTML;
			//Ahora imprimo los sub-menu
			$query = "SELECT * from menu WHERE tipo=0 and codigo in ('$lista_menu_permiso') and codigo_padre='${row['codigo']}' ORDER BY orden";
			//Tipo=0 significa menu secundario
			$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$root_dir = Conf::RootDir();
			for ($j = 0; $row = mysql_fetch_assoc($resp2); $j++) {

				$menu_html.=<<<HTML
                    <li><a href="$root_dir${row['url']}">${row['glosa']}</a></li>
HTML;
			}
		}
		$menu_html.=<<<HTML
                </ul>
            </div>
HTML;
		$menu_html.="<!-- End Menu Section--> \n";
		return $menu_html;
	}

	public static function PrintMenuTheme($sesion) {
		$lista_menu_permiso = Html::ListaMenuPermiso($sesion);

		$menu_html = "<!-- Menu Section--> \n";
		$menu_html.= <<<HTML
                <div class="wireframemenu">
                <ul>
HTML;
		$query = "SELECT * from menu WHERE tipo=1 and codigo in ('$lista_menu_permiso') ORDER BY orden"; //Tipo=1 significa menu principal
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($i = 0; $row = mysql_fetch_assoc($resp); $i++) {
			$glosa_menu = $row['glosa'];
			$menu_html.=<<<HTML
                    <li class="ui-widget-header titulo_menu">$glosa_menu</li>
HTML;
			//Ahora imprimo los sub-menu
			$query = "SELECT * from menu WHERE tipo=0 and codigo in ('$lista_menu_permiso') and codigo_padre='${row['codigo']}' ORDER BY orden";
			//Tipo=0 significa menu secundario
			$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$root_dir = Conf::RootDir();
			for ($j = 0; $row = mysql_fetch_assoc($resp2); $j++) {

				$menu_html.=<<<HTML
                    <li class="ui-state-default"><a href="$root_dir${row['url']}">${row['glosa']}</a></li>
HTML;
			}
		}
		$menu_html.=<<<HTML
                </ul>
            </div>
HTML;
		$menu_html.="<!-- End Menu Section--> \n";
		return $menu_html;
	}

	public static function PrintMenuJuiciosjQuery($sesion, $url_actual, $argv) {
		$argv = explode('&', $argv[0]);
		switch ($url_actual) {
			case '/app/interfaces/lista_causas.php':
				if (in_array("from=mis_causas", $argv))
					$url_actual = '/app/interfaces/lista_causas.php?from=mis_causas';
				else
					$url_actual = '/app/interfaces/lista_causas.php?from=buscar_causas';
				break;

			case '/app/interfaces/lista_mis_movimientos.php':

				if (in_array("solo_novedades=1", $argv))
					$url_actual = '/app/interfaces/lista_mis_movimientos.php?mis_causas=0&solo_novedades=1&periodo=365&orden=fecha';
				else
					$url_actual = '/app/interfaces/lista_mis_movimientos.php?mis_movimientos=1';
				break;

			case '/app/usuarios/usuario_paso2.php': $url_actual = '/app/usuarios/usuario_paso1.php';
				break;

			case '/app/interfaces/agregar_cliente.php': $url_actual = '/app/interfaces/clientes.php';
				break;
		}
		$lista_menu_permiso = Html::ListaMenuPermiso($sesion);
		/* Ya que ya existe /clientes.php como hijo de ADM, debo buscar además que sea JADM - PERSONAL */
		$query = "SELECT codigo_padre FROM menu WHERE url='$url_actual' AND (LEFT(menu.codigo,1) = 'J' OR LEFT(menu.codigo,1) = 'P')  ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		$menu_html = "<!-- Menu Section--> \n";
		$menu_html .= <<<HTML
    		<div id="droplinetabs1" class="droplinetabs"><ul>
HTML;
		$query = "SELECT * from menu WHERE tipo=1 and codigo in ('$lista_menu_permiso') ORDER BY orden"; //Tipo=1 significa menu principal
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($i = 0; $row = mysql_fetch_assoc($resp); $i++) {
			$glosa_menu = $row['glosa'];
			if ($codigo == $row['codigo']) {
				$active = 'active=true';
				$estilo_con_margin = 'style="margin:0 4px 0 10px; font-size:12px;"';
				$estilo = 'style="color:#FFFFFF; align:center; font-size:12px; "';
			} else {
				$estilo_con_margin = 'style="margin: 0 4px 0 10px; font-size:12px;"';
				$active = 'active=false';
				$estilo = 'style="align:center; font-size:12px;"';
			}
			//Ahora imprimo los sub-menu
			$query = "SELECT * from menu WHERE tipo=0 and codigo in ('$lista_menu_permiso') and codigo_padre='${row['codigo']}' ORDER BY orden";
			//Tipo=0 significa menu secundario
			$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$root_dir = Conf::RootDir();
			for ($j = 0; $row2 = mysql_fetch_assoc($resp2); $j++) {
				if ($j == 0 && $i == 0) {
					$menu_html .= <<<HTML
											<li $active><div id="top_tap_$i"><a href="$root_dir${row2['url']}" class="a_color_activo" $estilo_con_margin>
																				<!--[if IE]><b class="spiffy">
																				<b class="spiffy1"><b class="color_activo" style="width:97px;"></b></b>
																				<b class="spiffy2"><b class="color_activo" style="width:101px;"></b></b>
																				<b class="spiffy3 color_activo" style="width:102px;"></b>
																				<b class="spiffy4 color_activo" style="width:103px;"></b>
																				<b class="spiffy5 color_activo" style="width:103px;"></b></b>
																				<div class="spiffyfg"><![endif]--><span>${row['glosa']}</span><!--[if IE]>
																				</div></b><![endif]--></a></div>
										  <ul $active style="display:none" class="top">
HTML;
				} else if ($j == 0) {
					$menu_html .= <<<HTML
											<li $active><div id="top_tap_$i"><a href="$root_dir${row2['url']}" class="a_color_activo" $estilo>
																				<!--[if IE]><b class="spiffy">
																				<b class="spiffy1"><b class="color_activo" style="width:92px;"></b></b>
																				<b class="spiffy2"><b class="color_activo" style="width:96px;"></b></b>
																				<b class="spiffy3 color_activo" style="width:97px;"></b>
																				<b class="spiffy4 color_activo" style="width:98px;"></b>
																				<b class="spiffy5 color_activo" style="width:98px;"></b></b>
																				<div class="spiffyfg"><![endif]--><span>${row['glosa']}</span><!--[if IE]>
																				</div></b><![endif]--></a></div>
										  <ul $active style="display:none" class="top">
HTML;
				}
				$menu_html .= <<<HTML
            			<li><a class="corner_round" href="$root_dir${row2['url']}" $estilo>${row2['glosa']}</a></li>
HTML;
			}
			$menu_html .= <<<HTML
         				</ul></li>
HTML;
		}
		$menu_html .= <<<HTML
    		</ul></div><div id="fd_menu_grey" class="barra_fija"><ul active=true>
HTML;
		$query = "SELECT * FROM menu WHERE codigo_padre='$codigo' AND tipo=0 AND codigo in ('$lista_menu_permiso') ORDER BY orden";
		$resp3 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		for ($j = 0; $row3 = mysql_fetch_assoc($resp3); $j++) {
			if ($url_actual == $row3['url']) {
				$activo_adentro_ie = 'style="text-decoration: underline;"';
				$activo_adentro_otros = 'style="background: #119011;-webkit-border-radius: 5px; -ms-border-radius: 5px;-moz-border-radius: 5px;-khtml-border-radius: 5px;border-radius: 5px;"';
			} else {
				$activo_adentro_ie = '';
				$activo_adentro_otros = '';
			}
			$menu_html .= <<<HTML
            			<!--[if IE]><li><a href="$root_dir${row3['url']}" $activo_adentro_ie><span>${row3['glosa']}</span></a></li><![endif]-->
            			<!--[if !IE]><!--><li><a href="$root_dir${row3['url']}" $activo_adentro_otros><span>${row3['glosa']}</span></a></li><!--<![endif]-->
HTML;
		}
		$menu_html .= <<<HTML
      </ul></div>
HTML;
		$menu_html.="<!-- End Menu Section--> \n";
		return $menu_html;
	}

	public static function SelectAnos(&$sesion, $name, $selected = '', $opciones = '') {
		$select = "<select name='$name' id='$name' $opciones>";
		$ano_actual = date('Y');
		$hasta = $ano_actual - 101;
		for ($x = $ano_actual; $x > $hasta; $x--) {
			if ($x == $selected)
				$select .= "<option value='$x' selected>$x</option>\n";
			else
				$select .= "<option value='$x'>$x</option>\n";
		}

		$select .= "</select>";

		return $select;
	}

	//genera codigo de tooltip listo para poner dentro de un tag
	public static function Tooltip($html) {
		$html = str_replace(array("\r\n", "\r", "\n"), "<br>", $html);
		$html = str_replace("'", "\'", $html);
		$html = htmlspecialchars($html, ENT_QUOTES);
		return "onmouseover=\"ddrivetip('$html')\" onmouseout=\"hideddrivetip();\"";
	}

}
