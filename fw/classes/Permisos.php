<?php 
	require_once dirname(__FILE__).'/../classes/Utiles.php';
	require_once dirname(__FILE__).'/../classes/Lista.php';

class Permiso
{
	// Sesion PHP
	var $sesion = null;
	
	// Arreglo con los valores de los campos
	var $fields = null;
	
	function Permiso($sesion, $fields=array())
	{
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function FindPermiso($params_array)
	{
		if(isset($params_array['lista_permisos']))
		
			foreach($params_array['lista_permisos'] as $permiso)
			{
				if($this->fields['codigo_permiso'] == $permiso)
					return true;
			}
		//en caso de que se envíe uno solo en vez de un arreglo
		if($this->fields['codigo_permiso'] == $params_array['codigo_permiso'])
			return true;
		return false;
	}
}
