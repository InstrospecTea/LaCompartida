<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class TemplateCleaner
{
	function LimpiarParser( $html ) 
	{
		$pedazos = array();
		$charset = str_split($html);
		
		$letra_anterior = '';
		$limitadores = 1;
		foreach($charset as $index => $letra)
		{
			if( $letra == '%' && $letra_anterior == '%' )
			{
				if( $limitadores%2 == 1 )
				{
					$str_ini = $index+1;
					$limitadores++;
				}
				else
				{
					$str_fin = $index-2;
					$limitadores++;
					$codigo_pedazo = substr( $html, $str_ini, $str_fin - $str_ini + 1);
					array_push($pedazos,$codigo_pedazo);
				}
			}
			$letra_anterior = $letra;
		}
		return $pedazos;
	}
}
?>
