<?php

 $sesion->pdodbh->exec("CREATE TABLE IF NOT EXISTS `prm_plugin` (
						`id_plugin` smallint(3) NOT NULL AUTO_INCREMENT,
						`archivo_nombre` varchar(100) COLLATE latin1_spanish_ci NOT NULL DEFAULT 'plugin.php' ,
						`orden` smallint(3) NOT NULL DEFAULT '1',
						`activo` tinyint(1) NOT NULL,
						PRIMARY KEY (`id_plugin`),
						UNIQUE KEY `archivo_nombre` (`archivo_nombre`)
						) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci");
	
	
        echo '<form id="formplugins"><ul class="buttonset" id="plugins" style="list-style:none;">';
				echo 'Hemos detectado los siguientes plugins';

        $archivos=array();
        $maxid=0;
        $orden=0;
        
         foreach($sesion->pdodbh->query("select * from prm_plugin order by orden ASC") as $archivo)  {
	$maxid=$archivo[0];
        $orden=$archivo[2];
           $archivos[$archivo[1]]=$archivo[1];
        //   echo '<tr><td> <input type="checkbox" class="checkbox"  id="'.$archivo[1].'_'.$archivo[1].'" name="'.$archivo[1].'" value="'.$archivo[3].'" /><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'.$archivo[1].'</td></tr>';

         echo '<li > <input type="checkbox" class="checkbox"  id="'.$archivo[0].'_'.$archivo[1].'" name="'.$archivo[1].'" value="1" '.($archivo[3]? 'checked="checked"':'').'" /><label for="'.$archivo[0].'_'.$archivo[1].'">'.$archivo[1].'</label>  <span class="updown ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
        
        }
		
   $eldirectorio=Conf::ServerDir().'/plugins/';
		 
       if ($myDirectory = opendir( $eldirectorio)) {

while($entryName = readdir($myDirectory)) {
	if(!array_key_exists($entryName, $archivos) && is_file(Conf::ServerDir().'/plugins/'.$entryName))  {
		echo '<li > <input type="checkbox" class="checkbox"  id="'.++$maxid.'_'.$entryName.'" name="'.$entryName.'" value="1" />';
		echo '<label for="'.$maxid.'_'.$entryName.'">'.$entryName.'</label> <span class="updown ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
		$sesion->pdodbh->exec("insert into prm_plugin (archivo_nombre, orden, activo) values ('$entryName',0,0)");
	}
}

// close directory
closedir($myDirectory); 
	   }
 
	echo '</ul>';
        echo '<input type="hidden" id="cantidad" name="cantidad" value="'.$maxid.'"/>';
        echo '<input type="hidden" id="accion" name="accion" value="actualiza_plugins"/>';
        echo '</form>';

        
        
