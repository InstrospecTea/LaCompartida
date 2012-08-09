<?php
	require_once dirname(__FILE__).'/../app/conf.php';
	
function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	} else {
		   $file =Conf::ServerDir() . '/../fw/classes/' . str_replace('_', DIRECTORY_SEPARATOR, substr($class,5)) . '.php';
			if ( file_exists($file) ) {
				require $file;
			}
	}
}

spl_autoload_register('autocargaapp');	

	$sesion = new Sesion(array('ADM'));
$pagina = new Pagina($sesion);
		$pagina->titulo = __('Importacion de Usuarios');
		$pagina->PrintTop(); 
	
		$identificador = UtilesApp::Getconf($sesion, 'NombreIdentificador');
		if( strtolower($identificador) == 'rut' ) $usadv=1;  // debiese haber un conf para definir si usa DV o no.
		
		
		
	if($_POST['accion']=='cargausuarios'  ) {
	     $texto= $_POST['usuarios'];
	     $arraytexto=explode("\n",$texto);
	     $filas=array();
	     $aux=array();
	   foreach ($arraytexto as $fila):
	     if($fila!='') $filas[]=explode("\t",$fila);
	   endforeach;
	       $i=0;
	       $querinsert=array();
	       $arreglomaestro=array();
	       
		

		 //  echo '<pre>';print_r($filas);echo '</pre>';
	       foreach ($filas as $fila) {
		if($fila[0]!='' AND $_POST['paso']=='inserta')    {
		    
		   
		    $arreglomaestro[]=$fila;
		    
		    }
		 
		 
		if($fila[0]!='' AND $_POST['paso']!='inserta') {
		$campo=0;    

		$cadena.="<tr class='user_row' id='fila_".++$i."'>";
		    $cadena.="<td><input  style='width:70px;' type='text' id='rut' name='rut[]' value='".$fila[$campo++]."'/></td>";
		//if($usadv==1);    $cadena.="<td ><input style='width:10px;' type='text' id='dvrut' name='dvrut[]' value='".$fila[$campo++]."'/></td>";
		    
		$cadena.="<td ><input style='width:80px;' type='text' id='nombre' name='nombre[]' value='".$fila[$campo++]."'/></td>";
		    $cadena.="<td><input  style='width:80px;' type='text' id='apellido1' name='apellido1[]' value='".$fila[$campo++]."'/></td>";
		    $cadena.="<td><input  style='width:80px;' type='text' id='apellido2' name='apellido2[]' value='".$fila[$campo++]."'/></td>";
		    $cadena.="<td ><input style='width:60px;' type='text' id='telefono1' name='telefono1[]' value='".$fila[$campo++]."'/></td>";
		    $cadena.="<td ><input style='width:90px;' type='text' id='email' name='email[]' value='".$fila[$campo++]."'/></td>";
				$campo++;
		    $cadena.="<td><input  style='width:10px;'type='checkbox' id='esadmin' name='esadmin[]' ".(($fila[$campo]=='1' || strtolower(trim($fila[$campo]))=='si')? 'value="1" checked="checked"':'value="0" '  )."/></td>";
		  
		$select_cats = superselectquery($sesion,"SELECT id_categoria_usuario, glosa_categoria from prm_categoria_usuario","select_cats[]",$fila[$campo++],'',' ','110');
		    $select_areas =superselectquery($sesion,"SELECT id, glosa from prm_area_usuario","select_areas[]",$fila[$campo++],'',' ','110');
			
		$cadena.="<td style='width:90px;'>".$select_cats."</td>";
		     $cadena.="<td style='width:90px;'>".$select_areas."</td>";
		 $campo++;
			if(!empty($fila[$campo])) 		 $cadena.="<td ><input style='width:30px;' type='text' id='username' name='username[]' value='".$fila[$campo]."'/></td>";
		    $cadena.="<td><input  style='width:50px;' type='text' id='password' name='password[]' value='12345'/></td></tr>";
		
		    
		    
		    
		}}
		 
	       
	      
	    if ($_POST['paso']=='inserta' ):
		   $usuarios=0;
	    
		   for  ($k=0;$k<=$registros;$k++) {
		       
		
		  
		       $usuario = new UsuarioExt($sesion);
				$usuario->guardar_fecha = false;
				$usuario->tabla = "usuario";
 


				$usuario->Edit('rut',	trim($rut[$k])	);
				if($usadv==1);  	$usuario->Edit('dv_rut',	trim($dvrut[$k])	);
				$usuario->Edit('nombre',	trim($nombre[$k])	);
				$usuario->Edit('apellido1',	trim($apellido1[$k])	);
				$usuario->Edit('apellido2',	trim($apellido2[$k])	);
				$usuario->Edit('telefono1',	$telefono1[$k]	);
				$usuario->Edit('email',	trim($email[$k])	);
				$usuario->Edit('id_categoria_usuario',	intval($select_cats[$k])	);
				$usuario->Edit('id_area_usuario',	intval($select_areas[$k])	);
				if(!empty($username[$k])) $usuario->Edit('username',	trim($username[$k])	);
				$usuario->Edit('password',	md5('12345')	);
				$usuario->Edit('fecha_creacion',	date('Y-m-d H:i:s')	);
				
		      	 
		      if($usuario->Write()) {
		       //echo $insercion.'<br>';
		   
			$lastid=  $usuario->fields['id_usuario'] ;
			
			$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'ALL');";
			if($select_cats[$k]!=5) $querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'PRO');";
			if($select_cats[$k]==5){
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'OFI');";
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'COB');";
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'DAT');";
				 
			}
			if($select_cats[$k]==1) {
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'SOC');";
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'OFI');";
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'COB');";
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'DAT');";
					$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'REP');";
				$querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'REV');";
			}
			if($esadmin[$k]==1) $querypermisos[]="insert into usuario_permiso (id_usuario, codigo_permiso) values ($lastid,'ADM');";
			
			 
			
			$usuarios++;
		       }
		    
		    
		     
		   }
		     echo "<br>Se insertaron ".$usuarios." usuarios.";
		     $permisos=0;
		     foreach ($querypermisos as $q)  {
			 if(mysql_query($q, $sesion->dbh)) ++$permisos ;
		     }
		     echo '<br>Se insertaron '.$permisos.' permisos de usuario.';
		  
	           
		   
		   
		   $tarifasfaltantes = "SELECT us.id_usuario, ct.id_moneda, ct.tarifa, ct.id_tarifa
			    FROM usuario us
			    JOIN usuario_permiso usp
			    USING ( id_usuario ) 
			    JOIN categoria_tarifa ct
			    USING ( id_categoria_usuario ) 
			    LEFT JOIN usuario_tarifa ut ON ut.id_usuario = us.id_usuario
			    AND ut.id_moneda = ct.id_moneda
			    AND ut.id_tarifa = ct.id_tarifa
			    WHERE usp.codigo_permiso =  'PRO'
			    AND id_usuario_tarifa IS NULL ";

		$resptarifas = mysql_query($tarifasfaltantes, $sesion->dbh);
		 $tarifa=0;
		while($fila= mysql_fetch_row($resptarifas)) {
		   $insertquery="insert ignore into usuario_tarifa (id_usuario, id_moneda, tarifa, id_tarifa) values ($fila[0],$fila[1],$fila[2],$fila[3])";

		   if(mysql_query($insertquery,$sesion->dbh)) { 
		   //echo $insertquery.'<br>';
		       ++$tarifa;
		   } 
		}
    echo '<br>Se insertaron '.$tarifa.' tarifas.';
		   
	$sesion->pdodbh->exec("update usuario set username=concat(left(nombre,1), left(apellido1,1), left(apellido2,1)) where username is null or username=''");
		$sesion->pdodbh->exec("insert ignore into usuario_permiso (select id_usuario, 'ALL' as codigo_permiso from usuario where activo=1);");	   
		   
	    else:
	    
		
		
		   echo '<form  method="POST"><div id="tablausuarios" style="padding:10px;margin:auto;">Se insertarán los siguientes datos. Puede <input type="submit" value="Confirmar"/>&nbsp;o&nbsp;<input type="button" value="volver" id="volver"/></div>';
		   echo '<table><tr><th>'.__('RUT').'</th>';
		   if($usadv==1) echo '<th>DV</th>';
		   echo '<th>Nombre</th><th>Apellido P</th><th>Apellido M</th><th>Fono</th><th>Mail</th><th>Es Admin?</th><th>Categoria</th><th>Area</th><th>Username</th><th>Pass</th></tr>';
		   echo $cadena;
		   echo '</table><input type="hidden" name="registros" value="'.$i.'"/><input type="hidden" name="accion" value="cargausuarios"/><input type="hidden" name="paso" value="inserta"/></form>';
	    endif;
	} else {
   
	    ?>
	    <form id="adjuntaclipboard" method="POST"     >
		<textarea id="usuarios" name="usuarios" rows="18" cols="100"></textarea><br /><br />
<input type="hidden" name="accion" value="cargausuarios"/>
<input type="submit" value="enviar">
</form>
<a href="ejemplo_carga.xls">¿Qué debo pegar en el recuadro? (descarga ejemplo)</a>
<?php

	}
	?>
<script language="javascript" type="text/javascript">
jQuery(document).ready(function() {
    

    jQuery('#volver').click(function() {
	document.location.href='usuarios_clipboard.php';
	
    });
    }); 
   
</script>
<?php
 
	$pagina->PrintBottom($popup);
 
 
function superselectquery($sesion,$query,$name=null,$idoglosa=null,$ancho=110) {
	$respuesta='<select name="'.$name.'" id="" style="width: '.$ancho.'px;">';  
	$resp = $sesion->pdodbh->query($query);
	  foreach($resp as $opcion) {
		    $respuesta.='<option value="'.$opcion[0].'" ';
			if (  (!is_numeric($idoglosa) && $idoglosa==$opcion[1]) ||  (is_numeric($idoglosa) && $idoglosa==$opcion[0]) )    $respuesta.= 'selected="selected"';
		    $respuesta.='>'.$opcion[1].'</option>';
	  }
	  $respuesta.='</select>';
	  return $respuesta;
	
}
 
