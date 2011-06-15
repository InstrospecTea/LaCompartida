<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class TemplateParser
{
	var $tags = array();

	function TemplateParser( $templateData )
	{
		if( is_string($templateData) )
		{
			$data = explode("\n",$templateData);

			foreach( $data as $linea )
			{
				$linea = chop($linea);

				if( substr($linea,0,3) === '###' and substr($linea,strlen($linea)-3,3) === '###' )
					$tag = substr($linea,3,strlen($linea)-6);
				else if( $tag != '' )
				{
					if( !isset($this->tags[$tag]) ) $this->tags[$tag] = '';
					$this->tags[$tag] .= "$linea\r\n";
				}
			}
		}
	}
}
?>
