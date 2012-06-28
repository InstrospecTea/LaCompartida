<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	$sesion = new Sesion( array() );
$i=1;
header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
echo "<data>";

if( $to && $from )
	$having = " HAVING fecha_inicial >= '".mysql_real_escape_string($from)."' AND fecha_inicial <= '".mysql_real_escape_string($to)."' ";


$query = "SELECT id_datos_calendario, tabla_datos, glosa_datos, glosa_campo_id, glosa_descripcion, glosa_usuario, glosa_datos_cliente, glosa_fecha_ini, url_icon, fecha_con_hora, 
								 glosa_duracion, glosa_fecha_fin   
						FROM datos_calendario 
					 WHERE monstrar_datos=1";
$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);


function arreglar_xml($campo)
{
	$campo = str_replace('&','&amp;',$campo);
	$campo = str_replace('<','&lt;',$campo);
	$campo = str_replace('>','&gt;',$campo);	
	$campo = str_replace('>','&gt;',$campo);	
	$campo = str_replace("'",'&quot;',$campo);
	$campo = str_replace('"','&quot;',$campo);
	$campo = urlencode($campo);
	$campo = str_replace('%0D%0A','&lt;br&gt;',$campo);
	$campo = urldecode($campo);
	return $campo;
}
 
while( list($id_datos_calendario, $tabla_datos, $glosa_datos, $glosa_campo_id, $glosa_descripcion, $glosa_usuario, $glosa_datos_cliente, $glosa_fecha_ini,$url_icon , $fecha_con_hora, $glosa_duracion, $glosa_fecha_fin) = mysql_fetch_array($resp) )
	{
		$select = $glosa_campo_id.", ".$glosa_fecha_ini." AS fecha_inicial ,".$glosa_fecha_ini;
		
		$join = "";
		$where = "1";
		$glosa_descripcion=split('//',$glosa_descripcion);
		$j=0;
		while( $glosa_descripcion[$j] ) {
			if( $glosa_descripcion[$j]==$glosa_usuario ) {
				$join_usuario = " JOIN usuario ON usuario.id_usuario=".$tabla_datos.".".$glosa_descripcion[$j];
				$select .= ", usuario.nombre AS nombre_encargado, apellido1, apellido2 ";
			}
			else{
			if( $glosa_descripcion[$j]=='codigo_cliente' ) {
				$join_cliente = " JOIN cliente ON cliente.codigo_cliente=".$tabla_datos.".codigo_cliente";
				$select .= ", cliente.glosa_cliente";
				$glosa_descripcion[$j]='glosa_cliente';
			}
			else if( $glosa_descripcion[$j]=='codigo_asunto' ) {
				$join_asunto = " JOIN asunto ON asunto.codigo_asunto=".$tabla_datos.".codigo_asunto";
				$select .= ", asunto.glosa_asunto";
				$glosa_descripcion[$j]='glosa_asunto';
			}
			else if( $glosa_descripcion[$j]=='nombre' ) {
				$select .= ", ".$tabla_datos.".".$glosa_descripcion[$j]." AS nombre_".$tabla_datos;
				$glosa_descripcion[$j]='nombre_'.$tabla_datos;
			}
			else
				$select .= ", ".$glosa_descripcion[$j];
			}
			$j++;
		}
		if( $fecha_con_hora==1 ) {
			if( $glosa_fecha_fin )
				$select .= ", ".$glosa_fecha_fin;
			else if( $glosa_duracion )
				$select .= ", ".$glosa_duracion;
			}
			
		
			if($usuarios)
			{
				$in_usuarios = explode(',',$usuarios);
				foreach($in_usuarios as $i => $u)
					$in_usuarios[$i] = mysql_real_escape_string($u);
					
				$where .= " AND ".$tabla_datos.".".$glosa_usuario." IN ('".join("','",$in_usuarios)."') ";
			}
			
			$otro_join_asunto  = '';
			if($cliente)
			{
				if( $glosa_datos_cliente == 'codigo_asunto')
				{
					$otro_join_asunto = " JOIN asunto AS a ON a.codigo_asunto=".$tabla_datos.".codigo_asunto";
					$where .= " AND a.codigo_cliente = '".mysql_real_escape_string($cliente)."' ";
				}
				else
					$where .= "	AND ".$tabla_datos.".codigo_cliente = '".$cliente."' ";
			}
			
			if($grupo)
			{
				$in_grupo = explode(',',$grupo);
				foreach($in_grupo as $i => $g)
					$in_grupo[$i] = mysql_real_escape_string($g);
				
				$otro_join_asunto = " JOIN asunto AS a ON a.codigo_asunto=".$tabla_datos.".codigo_asunto";
				$otro_join_cliente = " JOIN cliente AS c ON c.codigo_cliente= a.codigo_cliente";
				$where .= " AND c.id_grupo_cliente IN ('".join("','",$in_grupo)."') ";
			}
			
		
		$query2 = "SELECT ".$select." FROM ".$tabla_datos." ".$join_usuario." ".$join_cliente." ".$join_asunto." ".$otro_join_asunto." ".$otro_join_cliente." ".$join." WHERE ".$where." ".$having;
		$resp2 = mysql_query($query2,$sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
		
			while( $row = mysql_fetch_array($resp2) )
				{
					if( $row[$glosa_duracion] )
						{
							$fecha_ini = split(' ',$row[$glosa_fecha_ini]);
							list($h,$m,$s) = split(":",$row[$glosa_duracion]);
							if( $fecha_ini[1] && $fecha_ini[1] != '00:00:00' )
								{
									list($hf,$mf,$sf)=split(':',$fecha_ini[1]);
									$hora_ini = $fecha_ini[1];
									$hora_fin = ($h+$hf).':'.($m+$mf).':'.($s+$sf);
								}
							else
								{
									$hora_ini = '10:00:00';
									$hora_fin = (10+$h).':'.$m.':'.$s;
								}
							
							
						$fecha_inicial = $fecha_ini[0].' '.$hora_ini;
						$fecha_final = $fecha_ini[0].' '.$hora_fin;
						}
					else if( $row[$glosa_fecha_fin] )
						{
							$fecha_ini = split(' ',$row[$glosa_fecha_ini]);
							if( $fecha_ini[1] && $fecha_ini[1] != '00:00:00' )
								$hora_ini = $fecha_ini[1];
							else
								$hora_ini = '09:00:00';
									
							$fecha_fin = split(' ',$row[$glosa_fecha_fin]);
							if( $fecha_fin[1] && $fecha_fin[1] != '00:00:00' )
								$hora_fin = $fecha_fin[1];
							else	
								$hora_fin = '13:00:00';
								
							$fecha_inicial = $row[$glosa_fecha_ini];
							$fecha_final = $row[$glosa_fecha_fin];
						}
					else
						{
							$fecha_ini = split(' ',$row[$glosa_fecha_ini]);
							if( $fecha_ini[1] && $fecha_ini[1] != '00:00:00' )
								{ 
									list($hf,$mf,$sf)=split(':',$fecha_ini[1]);
									$hora_ini = $fecha_ini[1];
									$hora_fin = ($hf+4).':'.$mf.':'.$sf;
								}
							else
								{
									$hora_ini = '09:00:00';
									$hora_fin = '13:00:00';
								}
							$fecha_inicial = $fecha_ini[0].' '.$hora_ini;
							$fecha_final = $fecha_ini[0].' '.$hora_fin;
						}
					
						$descripcion = $row[$glosa_descripcion[0]];
						$cont=1;
						while( $glosa_descripcion[$cont] ) {
							if( $glosa_descripcion[$cont] == 'glosa_cliente' )
								$descripcion .= ' -Cliente: '.$row[$glosa_descripcion[$cont]];
							else if( $glosa_descripcion[$cont] == 'glosa_asunto' )
								$descripcion .= ' -Asunto: '.$row[$glosa_descripcion[$cont]];
							else if( $glosa_descripcion[$cont] == 'nombre_encargado' ) {
								$descripcion .= ' -Encargado: '.$row['nombre_encargado'];
								}
							else
								$descripcion .= ' - '.$row[$glosa_descripcion[$cont]];
							$cont++;
							}
						if( $row['nombre_encargado'] ) {
							$descripcion .= ' -Encargado: '.$row['nombre_encargado']; 
							if( $row['apellido1'] ) {
								$descripcion .= ' '.$row['apellido1'];
								if( $row['apellido2'] ) {
									$descripcion .= ' '.$row['apellido2'];
									}
								}
							}
					
						
					//echo $i.' - '.$row[$glosa_campo_id].' - '.$fecha_inicial.' - '.$fecha_final.' - '.$descripcion.'<br>';
					
					echo "<event id=\"".$i."\">";
					echo "<start_date>".$fecha_inicial."</start_date>";
					echo "<end_date>".$fecha_final."</end_date>";
					echo "<text>".arreglar_xml($descripcion)."</text>";
					echo "<type>".$tabla_datos."</type>";
					echo "<tabla_id>".$row[$glosa_campo_id]."</tabla_id>";
					echo "<icon>".$url_icon."</icon>";
					echo "<titulo>".arreglar_xml($glosa_datos)."</titulo>";
					echo "<event_pid>0</event_pid>";
					echo "<event_length></event_length>";
					echo "</event>"; 
					$i++;
				}
	}
echo "</data>";

?>
