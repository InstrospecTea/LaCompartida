<?
//Clase Debug, para escribir Debugs que solo sean visible para usuario Admin Lemontech
require_once dirname(__FILE__).'/../conf.php';

class Debug 
{
	function debug_echo( &$sesion, $str )
		{
			if( $sesion->usuario->TienePermiso('SADM') )
				return $str;
			else	
				return '';
		}
		
	function debug_print_r( &$sesion, $arreglo )
		{
			if( $sesion->usuario->TienePermiso('SADM') )
				{
					echo '<pre>';
					print_r($arreglo);
					echo '</pre>';
					return true;
				}
			else
				return;
		}
		
	function h1( &$sesion, $str )
		{
			if( $sesion->usuario->TienePermiso('SADM') )
				{
					echo '<h1>';
					echo $str;
					echo '</h1>';
					return true;
				}
			else
				return;
		}
}
?>
