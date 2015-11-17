<?php 
	require_once dirname(__FILE__).'/../classes/Utiles.php';
	require_once dirname(__FILE__).'/../classes/Html.php';
	require_once dirname(__FILE__).'/../classes/Lista.php';

class TablaOrdenada
{
	// Sesion PHP
	var $sesion = null;
	
	// String con el último error
	var $error = '';

	var $clase = null;

	//Arreglo con los nombres de los encabezados
	var $encabezados = null;

	//Modificaciones de cada td
	var $opciones_td = null;
	var $dentro_td = null;

	//Variable que imprime la tabla como una tabla de HTML normal si está en true
	var $debug = false;

	//Formato de fecha para los campos que tienen el string "fecha" en su nombre
	var $formato_fecha = "%d/%m/%Y";

	function TablaOrdenada( $sesion, $id, $ancho, $alto, $query = "")
	{
		$this->id = $id;
		$this->sesion = $sesion;
		$this->ancho = $ancho;
		$this->alto = $alto;
		$this->query = $query;
		
		if($query != "")
			$this->lista = new Lista($this->sesion, "Objeto", $params, $query); 
	}

	function AgregarEncabezado($tipo, $key, $valor, $opciones_td = "", $dentro_td = "", $funcion="")
	{
		$this->tipo[$key] = $tipo;
		$this->sql[$key] = true;
		$this->encabezados[$key] = $valor;
		$this->opciones_td[$key] = $opciones_td;
		$this->dentro_td[$key] = $dentro_td;
		$this->funcion[$key] = $funcion;
	}

	function Imprimir($funcion = "")
	{
		$root = Conf::Rootdir();
		$template = Conf::Templates();
		$id = $this->id;
		$ancho = $this->ancho;
		$alto = $this->alto;

		global $HTTP_SERVER_VARS;
echo <<<HTML
<script type=text/javascript src="$root/fw/js/tabla_ordenada.js"></script>
<link rel="stylesheet" type="text/css" href="$root/app/templates/$template/css/tabla_ordenada.css" />
<div class="widget_tableDiv">
<table id="$id">
	<thead>
		<tr>
HTML;
	$this->ImprimirEncabezado();
echo <<<HTML
		</tr>
	</thead>
	<tbody class="scrollingContent">
HTML;
	$this->ImprimirLista();
	$array_param = $this->ArrayParamOrden();
echo <<<HTML
	</tbody>
</table>
</div>
HTML;

		if(!$this->debug)
		{
			echo("
			<script type=\"text/javascript\">
			initTableWidget('$id',$ancho,$alto,Array($array_param));
			</script>
				");
		}
	}

	function ArrayParamOrden()
	{
		foreach($this->tipo as $key => $value)
		{
			if($value != "false")
				$this->tipo[$key] = "'$value'";
		}
		return join(",",$this->tipo);
	}

	function ImprimirEncabezado()
	{
		foreach($this->encabezados as $key => $value)
		{
			echo("<td>".$value."</td>\n");
		}
	}

	function ImprimirLista()
	{
		for($i = 0; $i < $this->lista->num; $i++)
		{
			echo("<tr>");
			$elem = $this->lista->Get($i);

			foreach($elem->fields as $key => $value)
			{
				if($this->sql[$key])
					echo("<td>".$elem->fields[$key]."</td>\n");
			}
			echo("</tr>\n");
		}
	}

	function Javascript()
	{
		echo<<<HTML
HTML;
	}

}

