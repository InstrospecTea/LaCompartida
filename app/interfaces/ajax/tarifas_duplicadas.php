<?php

	require_once dirname(__FILE__).'/../../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	 require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion(array('ADM'));

    
		

   
		$query_categoria="select distinct id_moneda, id_categoria_usuario,concat('M',id_moneda,'CAT',id_categoria_usuario) from categoria_tarifa";
		
			
		
		$categoriasymonedas=mysql_query($query_categoria,$sesion->dbh) or die(mysql_error($sesion->dbh));
		
$arraycm=array();
$groupby=array();
		while($resultadocym=mysql_fetch_row($categoriasymonedas)):
		    $groupby[]=$resultadocym[2];
		    $arraycm[]="SUM( IF(id_moneda=".$resultadocym[0]." AND id_categoria_usuario =".$resultadocym[1].", tarifa, 0 ) ) ".$resultadocym[2]." "   ;
		endwhile;
		
$mainquery="SELECT COUNT( * ) cant, MIN( id_tarifa ) min_id_tarifa , GROUP_CONCAT( id_tarifa ) clones, ".implode(',',$groupby)."
FROM (

SELECT id_tarifa, ".implode(',',$arraycm)."
FROM  `categoria_tarifa` 
WHERE 1 
GROUP BY id_tarifa
)tarifa_traspuesta
GROUP BY  ".implode(',',$groupby)."
HAVING cant >1"		;


//echo $mainquery;
    $tarifasdupe=  mysql_query($mainquery,$sesion->dbh) or die(mysql_error($sesion->dbh));;

    $arraytarifas=array();
    while($arraydupes=mysql_fetch_array($tarifasdupe)):
		    $arraytarifas[]=explode(',',$arraydupes['clones']);
		endwhile;
		$link='/'.ROOTDIR.'/app/interfaces/agregar_tarifa.php?id_tarifa_edicion=';

		if(sizeof($arraytarifas>0)) {
		    echo 'Las siguientes tarifas están duplicadas entre sí:';
		    foreach($arraytarifas as $tarifa) {
			echo '<br/> - ';
			foreach($tarifa as $idtarifa) echo '<a href="'.$link.$idtarifa.'">'.$idtarifa.'</a>, ';

		    }
				
		}
		
	
		
?>
