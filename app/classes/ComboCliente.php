<?php

class ComboCliente extends InputId //Es cuando uno quiere unir un codigo con un selectbox
{
	function __construct($sesion,  $campo_id,  $name, $selected="", $opciones="", $onchange="")
	{
		$this->sesion = $sesion;
		$this->tabla = 'cliente';
		$this->campo_id = $campo_id;
		$this->campo_glosa = 'glosa_cliente';
		$this->name = $campo_id;
		$this->selected = $selected;
		$this->opciones = $opciones;
		$this->onchange = $onchange;
	}

	public static function PrintCombo($sesion,  $campo_id,   $selected="", $onchange="",$width=320, $otro_filtro = "")
	{
		 
		$usa_inactivo=false;
		$desde = "";
		$filtro_banco = "";
		$join = '';
		$tabla='cliente';
		$campo_glosa='glosa_cliente';
		$name=$campo_id;
		$otro_filtro=$codigo_asunto;
		$opciones="";
		if($tabla == "asunto")
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $otro_filtro != '')
				{
					$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario='$otro_filtro'";
					$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
					list($otro_filtro) = mysql_fetch_array($resp);
				}
			$join .= "JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";
			if(!$usa_inactivo)
				$where = " WHERE asunto.activo=1 AND cliente.activo = 1 ";
			if($otro_filtro != "")
				$where .= "  AND asunto.codigo_cliente = '$otro_filtro' ";
			else
				$where .= " AND 1=0";
		}

		if($tabla == "cliente")
			if(!$usa_inactivo)
				$where = " WHERE (activo=1 or cliente.codigo_cliente='$selected' )";

		
			$oncambio=$onchange;
			
		
			$querycombo="SELECT $campo_id,$campo_glosa	FROM $tabla 	$join	$where ORDER BY $campo_glosa,	$name";
 
		$output .= "<input maxlength=10 id=\"campo_".$name."\" size=10 value=\"".$selected."\" onchange=\"this.value=this.value.toUpperCase();SetSelectInputId('campo_".$name."','".$name."');$oncambio\" $opciones />";
		$output .= Html::SelectQuery($sesion, $querycombo			,$name,
						$selected,
						"onchange=\"SetCampoInputId('".$name."','campo_".$name."'); $onchange\" class='combox' ",
						__("Cualquiera"),$width);
		return $output;
	}

	 
}
?>
