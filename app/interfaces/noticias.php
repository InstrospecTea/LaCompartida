<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once dirname(__FILE__).'/../../fw/classes/Utiles.php';
    

	$sesion = new Sesion( array() );
	
	$pagina = new Pagina($sesion);

	$pagina->titulo = "Actualizaciones del sistema ".Conf::AppName();

	$pagina->PrintTop();
	
	$query= "SELECT ".(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColumnaNotificacion'):Conf::ColumnaNotificacion())." FROM usuario WHERE id_usuario=".$sesion->usuario->fields['id_usuario'];
	$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($id_not)=mysql_fetch_array($resp);
	

$query= "SELECT id_notificacion, fecha, texto_notificacion FROM notificacion ORDER BY id_notificacion DESC";
$resp=mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

$i=0;
while(list($id,$fecha,$text)=mysql_fetch_array($resp))
{
	if($i==0)
	{
	$query2="UPDATE usuario SET ".(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ColumnaNotificacion'):Conf::ColumnaNotificacion())."=".$id." WHERE id_usuario=".$sesion->usuario->fields['id_usuario'];
	$resp2=mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
	}
			$text=str_replace("\r\n","<br />&nbsp;&nbsp;",$text);
			$text=str_replace("\r","<br />&nbsp;&nbsp;",$text);
			$text=str_replace("\n","<br />&nbsp;&nbsp;",$text);
	if($id > $id_not)
	echo '<table width=100%><tr><td width=10%><img style=cursor:pointer title=izquierda src='.Conf::ImgDir().'/noticia16.png /></td><td align=left width=20%><b>['.Utiles::sql2fecha($fecha,'%d-%m-%Y').']</b></td><td width=70%>'.$text.'</td></tr></table><br />';
	else
	echo '<table width=100%><tr><td width=10%></td><td align=left width=20%><b>['.Utiles::sql2fecha($fecha,'%d-%m-%Y').']</b></td><td width=70%>'.$text.'</td></tr></table><br />';
	$i++;
}
	
	$pagina->PrintBottom();
?>