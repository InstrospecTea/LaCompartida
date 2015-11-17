<?php 
	require_once dirname(__FILE__).'/../classes/Utiles.php';
	require_once dirname(__FILE__).'/../classes/Html.php';
	require_once dirname(__FILE__).'/../classes/Lista.php';
	require_once dirname(__FILE__).'/../classes/Buscador.php';
	require_once dirname(__FILE__).'/../../conf.php';

class InputMagico
{
	// Sesion PHP
	var $sesion = null;
	
	// String con el último error
	var $error = '';

	var $desde = null;
	var $x_pag = null;

	//Titulo del buscador, si está se imprime
	var $titulo = null;

	var $url = null;

    //Obliga a ingresar un valor desde la tabla selecccionada. Muestra un buscador y hay que elegir dentro de los resultados desplegados
	function InputMagico( $sesion, $tabla, $nombre_input, $seleccionado, $url_popup)
	{
		$this->sesion = $sesion;
		$this->tabla = $tabla;
		$this->nombre_input = $nombre_input;
		$this->seleccionado = $seleccionado;
		$this->url_popup = $url_popup;

		$this->random_string = Utiles::RandomString();

		$this->ImprimirInput();
	}

	function ImprimirInput($funcion = "")
	{
		if($this->seleccionado > 0)
			$valor = Utiles::Glosa($this->sesion, $this->seleccionado, $this->tabla->campo_glosa, $this->tabla->nombre, $this->tabla->campo_id);
		$nombre_func = "mostrar_".$this->random_string;
		$id_oculto = "oculto_".$this->random_string;
		$id_campo = "campo_".$this->random_string;
		echo("<input value='$valor' readonly onclick=\"$nombre_func();\" id=\"$id_campo\">");
		echo("<input type=button value='Elegir' onclick=\"$nombre_func();\">");
		echo("<input id=\"$id_oculto\" type=hidden name=".$this->nombre_input." value=".$this->seleccionado.">");
		$this->Javascript();
	}

	function ImprimirBuscador()
	{
		$id_div = "div_".$this->random_string;
		echo("<div style=\"display: none;\" id=\"$id_div\">");
		InputMagico::EncabezadoTabla();
		echo("</div>");
	}

	function EncabezadoTabla()
	{
		$random = $this->random_string;
		
		if(!strstr($this->url_popup, "?"))
			$url_popup = $this->url_popup . "?random=$random";
		else
			$url_popup = $this->url_popup . "&random=$random";

		if($this->url_popup != "")
			$link = "<a href=# onclick=\"window.open('$url_popup', '', 'width=400,height=400,resizable=no,dependent=yes');\" onmouseover=\"ddrivetip('Haga clic sobre este ícono para <b>agregar</b> un nuevo valor');\" 
						onmouseout=\"hideddrivetip();\"><img border=0 src=".Conf::ImgDir()."/agregar.gif></a>";

		echo <<<HTML
		<table style="border: 1px solid black" id="tabla_$random">
			<tr class=buscador>
				<td>#</td>
				<td>Glosa</td>
				<td align=right>$link</td>
			</tr>
			<tr class=buscador>
				<td></td>
				<td>
					<input onmouseover="ddrivetip('Ingrese aquí el un criterio de búsqueda.<br> Luego presione el botón buscar.');" onmouseout="hideddrivetip();"
						id=input_$random style="font-size: 9px;">
				</td>
				<td><input type=button value="Buscar" style="font-size: 9px;" onclick="buscar_ajax_$random();"></td>
			</tr>

		</table>
HTML;
	}

	function Javascript()
	{
		$pref = Conf::RootDir()."/fw/ajax";
		$tabla = $this->tabla->nombre;
		$campo_id = $this->tabla->campo_id;
		$campo_glosa = $this->tabla->campo_glosa;
		$random = $this->random_string;

		$func_mostrar = "mostrar_".$random;
		$func_esconder = "esconder_".$random;
		$func_ajax = "buscar_ajax_".$random;
		$id_div = "div_".$random;

		echo<<<HTML
			<script>
				function $func_mostrar(valor)
				{
					var $id_div = document.getElementById("$id_div");	
					$id_div.style.display = "inline";
				}
				function $func_esconder(valor)
				{
					var $id_div = document.getElementById("$id_div");	
					$id_div.style.display = "none";
				}
				function $func_ajax()
				{
					var http = getXMLHTTP();

					var input = document.getElementById("input_$random");
					var valor = input.value;

					url = '$pref/ajax_input_magico.php?accion=buscar&tabla=$tabla&campo_id=$campo_id&campo_glosa=$campo_glosa&busqueda=' + valor; 

					http.open('get', url);

					loading( 'Obteniendo información del servidor.' );

					http.onreadystatechange = function()
					{
						if(http.readyState == 4)
						{
							offLoading();

							var response = http.responseText;
							var update = new Array();
							var tabla = document.getElementById("tabla_$random");

							response = response.split('\\n');
							response = response[0];
							if(response.indexOf('|') != -1) 
							{
								detenidos = response.split('~');
								BorrarTabla(tabla);
							}
							else if(response == "")
							{
								NoHayRespuesta(tabla);
								return;
							}
							else
								alert(response);

							for(i = 0; i < detenidos.length; i++)
							{
								valores = detenidos[i].split('|');
								AgregarFila(tabla,valores,"$random");
							}
							return;

							if(response.indexOf('|') != -1) 
								update = response.split('|');
						}
					};
					http.send(null);
				}
		</script>
HTML;
	}

	function JavascriptGlobal()
	{
		echo<<<HTML
		<script>
		function setValores(random, id, glosa)
		{
			//Esta funcion debe ser llamada por el pop-up que agregar un nuevo registro en la base de datos
			var campo = document.getElementById("campo_" + random);
			var campo_oculto = document.getElementById("oculto_" + random);
			campo.value = glosa;
			campo_oculto.value = id;
			eval("esconder_" + random + "();");
		}
		function AgregarFila(tabla,valores,random)
		{
			var newRow = tabla.insertRow(tabla.rows.length);
			var newCell = newRow.insertCell(0);
			newCell.innerHTML = valores[0];
			newCell = newRow.insertCell(1);
			newCell.innerHTML = valores[1];
			newCell = newRow.insertCell(2);
			newCell.innerHTML = '<a href="javascript:void(0);" onclick="setValores(\'' + random + '\',' + valores[0] + ',\'' + valores[1] + '\');" title="Seleccionar"><img border=0 src=' + img_dir + '/check.gif></a>';
		}
		function NoHayRespuesta(tabla)
		{
			BorrarTabla(tabla);
			var newRow = tabla.insertRow(tabla.rows.length);
			var newCell = newRow.insertCell(0);
			newCell.setAttribute('colspan','3');
			newCell.setAttribute('align','center');
			newCell.innerHTML = "No se encontraron resultados";
		}
		function BorrarTabla(tbl)
		{
			while (tbl.rows.length > 2) 
				tbl.deleteRow(tbl.rows.length - 1);
		}
		</script>
HTML;
	}
}

class Tabla
{
	var $nombre;
	var $campo_id;
	var $campo_glosa;

	function Tabla($nombre, $campo_id, $campo_glosa)
	{
		$this->nombre = $nombre;
		$this->campo_id = $campo_id;
		$this->campo_glosa = $campo_glosa;
	}
}

