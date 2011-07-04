<?
 require_once dirname(__FILE__).'/../app/conf.php';
 
/* PASO 1: Agregar los cambios en un case del switch de esta funcion. */
/*         Si ocurre un error, levantar una excepción, nunca hacer un exit o die */

/* IMPORTANTE:
	Escribir con un echo los cambios realizados (PHP) para poder anunciarlos a los clientes */

function Actualizaciones( &$dbh, $new_version )
{
	global $sesion;
	switch( $new_version )
	{
	case 1.0:
		echo 'Mensaje de prueba 1.<br>';
		break;

	case 1.1:
		echo 'Mensaje de prueba 2.<br>';

		$query = "ALTER TABLE `cobro` ADD `opc_moneda_total` INT NULL COMMENT 'Moneda total de impresión del DOC';";

		if( !mysql_query($query,$dbh) )
			throw new Exception(mysql_error());
		$query = "SELECT * FROM caca";
		break;

	case 1.2:
		echo 'Mensaje de prueba 2.<br>';

		break;

	 case 1.3:
        echo 'Mensaje de prueba 2.<br>';
        $query = "ALTER TABLE `cobro` ADD `opc_moneda_total_tipo_cambio` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Tipo de cambio de la moneda presentada en la impresión del DOC';";

        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

        $query = "SELECT * FROM caca";

	break;

	case 1.4:
        echo 'Mensaje de prueba 4.<br>';
        $query = "ALTER TABLE `cliente` CHANGE `dir_calle` `dir_calle` VARCHAR( 150 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ,
CHANGE `fono_contacto` `fono_contacto` INT( 20 ) NULL DEFAULT NULL";

        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

        $query = "SELECT * FROM caca";

	break;

	case 1.5:
        echo 'Mensaje de prueba 5.<br>';
        $query = "ALTER TABLE `cliente` CHANGE `rut` `rut` VARCHAR( 20 ) NULL DEFAULT '0'";
        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

        $query = "UPDATE cliente SET rut = CONCAT(rut,'-',dv) WHERE rut != '0'";
        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

        $query = "SELECT * FROM caca";

	break;


	case 1.6:
        echo 'Mensaje de prueba 6.<br>';

        $query = "CREATE TABLE `cobro_moneda` (
								`id_cobro` INT( 11 ) NOT NULL DEFAULT '0',
								`id_moneda` INT( 11 ) NOT NULL DEFAULT '0',
								`tipo_cambio` DOUBLE NOT NULL DEFAULT '0'
								) ENGINE = innodb;";
        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

        $query = "ALTER TABLE `cobro_moneda` ADD INDEX  (`id_cobro`)";
        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

				$query = "ALTER TABLE `cobro_moneda` ADD CONSTRAINT `cobro_moneda_fk` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE CASCADE ON UPDATE CASCADE";
				if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

				/* INSERT EN TABLA cobro_moneda */
        $sql_cobro = "SELECT id_cobro, id_moneda, tipo_cambio_moneda FROM cobro ORDER BY id_cobro";
				$resp = mysql_query($sql_cobro,$dbh);
				if( !mysql_query($sql_cobro,$dbh) )
            throw new Exception(mysql_error());

				while($row_cobro = mysql_fetch_array($resp))
				{
					$id_cobro = $row_cobro['id_cobro'];
					$cobro_tipo_cambio = $row_cobro['tipo_cambio_moneda'];
					$cobro_id_moneda = $row_cobro['id_moneda'];

					$sql_moneda = "SELECT id_moneda, tipo_cambio FROM prm_moneda";
					$resp_moneda = mysql_query($sql_moneda,$dbh);
					while($row_moneda = mysql_fetch_array($resp_moneda))
					{
						$query_insert = "INSERT INTO cobro_moneda SET ";
						$query_insert .= "id_cobro = ".$id_cobro.", id_moneda = ".$row_moneda['id_moneda'].", ";
						$query_insert .= "tipo_cambio = ".$row_moneda['tipo_cambio'];
						if( !mysql_query($query_insert,$dbh) )
            	throw new Exception(mysql_error());

						echo $id != $id_cobro ? '<hr size="2" align="left" width="600px"><br>' : '';
						echo $query_insert.'<br>';
						$id = $id_cobro;
					}

					$sql_update = "UPDATE cobro_moneda SET tipo_cambio = ".$cobro_tipo_cambio." WHERE id_cobro = ".$id_cobro." AND id_moneda = ".$cobro_id_moneda;
					if( !mysql_query($sql_update,$dbh) )
            	throw new Exception(mysql_error());
					echo $sql_update.'<br><br>';
				}

        $query = "SELECT * FROM caca";
	break;

	case 1.7:
				echo 'Mensaje de prueba 7.<br>';
        $query = "ALTER TABLE `cobro` CHANGE `opc_moneda_total` `opc_moneda_total` INT( 11 ) NOT NULL DEFAULT '1' COMMENT 'Moneda total de impresión del DOC',
									CHANGE `opc_moneda_total_tipo_cambio` `opc_moneda_total_tipo_cambio` DOUBLE NOT NULL DEFAULT '1' COMMENT 'Tipo de cambio de la moneda presentada en la impresión del DOC'";
        if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

				$query = "UPDATE cobro SET opc_moneda_total = 1 WHERE opc_moneda_total = 0";
				if( !mysql_query($query,$dbh) )
            throw new Exception(mysql_error());

        $query = "SELECT * FROM caca";
	break;

	case 1.8:
			echo 'Mensaje de prueba 8.<br>';
			$query = "UPDATE `menu` SET `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'CLI' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ASUN' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ACTIV' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '40' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ADM_USER' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "DELETE FROM menu WHERE codigo = 'ADMIN_DATA' AND tipo = 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Horas',
			`orden` = '2' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'PRO' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '30',
			`codigo_padre` = 'PRO' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REV' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "DELETE FROM menu WHERE codigo = 'REVI' AND tipo = 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '40',
			`codigo_padre` = 'PRO' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ADM_SEM' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '45' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'LISTA_COB' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `codigo_padre` = 'COBRANZA' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'GASTO' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '55' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'CAM' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '60',
			`codigo_padre` = 'COBRANZA' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_FAC_PE' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Horas por facturar' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_FAC_PE' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "DELETE FROM menu WHERE codigo = 'OFI' AND tipo = 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Profesional v/s Cliente',`orden` = '0' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'PLANI' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `orden` = '10' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_RES_CL' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Facturación clientes' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_RES_CL' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Rendimiento abogados' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_RES_AB' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Gráfico asuntos',`orden` = '40' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_AS' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Gráfico usuarios',`orden` = '50' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'REP_US' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Reporte genérico',`orden` = '60' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'OLAP' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Resumen semana' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ADM_SEM' LIMIT 1 ";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Avanzados', `url` = '/fw/tablas/mantencion_tablas.php', `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `codigo` USING utf8 ) = 'MANT' LIMIT 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "DELETE FROM menu WHERE codigo = 'CLI_PRO'";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Revisar horas', `url` = '/app/interfaces/horas.php', `codigo_padre` = 'PRO' WHERE CONVERT( `codigo` USING utf8 ) = 'MIS_HRS' LIMIT 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "DELETE FROM menu WHERE codigo = 'REV'";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "UPDATE `menu` SET `glosa` = 'Resumen', `url` = '/app/interfaces/resumen_semana.php', `codigo_padre` = 'PRO' WHERE CONVERT( `codigo` USING utf8 ) = 'ADM_SEM' LIMIT 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "SELECT * FROM caca";
	break;

	case 1.9:
			$query = "ALTER TABLE `cta_corriente` CHANGE `codigo_cliente` `codigo_cliente` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "SELECT * FROM caca";
	break;

	case 2:
			$query = "ALTER TABLE `cliente` CHANGE `cod_fono_contacto` `cod_fono_contacto` VARCHAR( 6 ) NULL DEFAULT NULL ,
								CHANGE `fono_contacto` `fono_contacto` VARCHAR( 20 ) NULL DEFAULT NULL";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `contrato` ADD `id_carta` INT NULL";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `cobro` ADD `opc_ver_carta` TINYINT( 1 ) NOT NULL DEFAULT '1', ADD `id_carta` INT NULL";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "SELECT * FROM caca";
	break;

	case 2.1:
			$query = "CREATE TABLE `carta` (
								`id_carta` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`descripcion` VARCHAR( 55 ) NULL ,
								`formato` TEXT NULL
								) ENGINE = innodb";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `carta` ADD `formato_css` TEXT NULL";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "SELECT * FROM caca";
	break;

	case 2.2:
			$query = "insert into menu_permiso values ('OFI', 'COBRANZA')";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "SELECT * FROM caca";

	break;
	case 2.21:
			/*$query = "ALTER TABLE `prm_si_no` DROP INDEX `codigo_si_no_2`;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "INSERT INTO `prm_si_no` ( `id_codigo_si_no` , `codigo_si_no` ) VALUES ('0', 'NO');";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());
*/
			$query = "SELECT * FROM caca";
	break;

	case 2.22:
			$query = "ALTER TABLE `cliente` CHANGE `fono_contacto` `fono_contacto` VARCHAR( 200 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` )
								VALUES (
								'RAP', 'Periódico', '/app/interfaces/resumen_actividades.php', '', '', '0', '70', 'REP');";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
								VALUES (
								'REP', 'RAP');";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "SELECT * FROM caca";
	break;

	#ACTUALIZACIONES CONTRATO-TARIFA
	case 2.23:
			$query = "CREATE TABLE `tarifa` (
									`id_tarifa` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
									`glosa_tarifa` VARCHAR( 150 ) NULL
									) ENGINE = innodb;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `usuario_tarifa` ADD `id_tarifa` INT NOT NULL ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `usuario_tarifa` ADD INDEX ( `id_tarifa` ) ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "ALTER TABLE `usuario_tarifa` ADD INDEX ( `id_tarifa` , `id_usuario` , `id_moneda` ) ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "ALTER TABLE `contrato` ADD `rut` VARCHAR( 20 ) NULL ,
								ADD `factura_razon_social` VARCHAR( 200 ) NULL ,
								ADD `factura_giro` VARCHAR( 200 ) NULL ,
								ADD `factura_direccion` MEDIUMTEXT NULL ,
								ADD `factura_telefono` VARCHAR( 100 ) NULL ,
								ADD `id_tarifa` INT NULL ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "ALTER TABLE `contrato` ADD `cod_factura_telefono` VARCHAR( 10 ) NULL AFTER `factura_telefono` ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "ALTER TABLE `cliente` ADD `id_contrato` INT NULL ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "ALTER TABLE `asunto` ADD `id_contrato_indep` INT NULL ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			/*$query = "ALTER TABLE `asunto` DROP INDEX `codigo_contrato` ,
									ADD INDEX `id_contrato` ( `id_contrato` ) ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());
			*/

			$query = "ALTER TABLE `contrato` CHANGE `glosa_contrato` `glosa_contrato` MEDIUMTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `tarifa` ADD `fecha_creacion` DATE NOT NULL DEFAULT '0000-00-00';";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `tarifa` ADD `fecha_modificacion` DATE NOT NULL DEFAULT '0000-00-00';";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `usuario_tarifa` DROP INDEX `id_usuario_2` ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

			$query = "ALTER TABLE `usuario_tarifa` DROP INDEX `id_tarifa_2` ,
									ADD UNIQUE `id_tarifa_2` ( `id_tarifa` , `id_usuario` , `id_moneda` ) ;";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());


			$query = "ALTER TABLE `tarifa` ADD `tarifa_defecto` TINYINT NOT NULL DEFAULT '0';";
			if( !mysql_query($query,$dbh) )
				throw new Exception(mysql_error());

            $query = "INSERT INTO `tarifa`  ( `id_tarifa` , `glosa_tarifa` , `fecha_creacion` , `fecha_modificacion` , `tarifa_defecto` )
                                        VALUES ( '1' , 'Standard', '2008-05-12', '0000-00-00', '1');";
            if( !mysql_query($query,$dbh) )
                throw new Exception(mysql_error());
            $id = mysql_insert_id($dbh);

	break;

	#script Update Cliente e insert nuevos contratos para cada cliente
	case 2.24:
			$query = "SELECT id_cliente, id_contrato, codigo_cliente, glosa_cliente, nombre_contacto, fono_contacto, mail_contacto, dir_calle, rut, rsocial, giro FROM cliente WHERE (id_contrato IS  NULL || id_contrato = '') ORDER BY id_cliente";
echo $query;
            if( ! ($resp = mysql_query($query,$dbh))  )
                throw new Exception(mysql_error());

			while( list($id_cliente, $id_contrato, $codigo_cliente, $glosa_cliente,$nombre_contacto, $fono_contacto, $mail_contacto, $dir_calle, $rut, $rsocial, $giro) = mysql_fetch_array($resp) )
			{
				$glosa_cliente = addslashes($glosa_cliente);
				$rsocial = addslashes($rsocial);
				$dir_calle = addslashes($dir_calle);
				$query_insert = "INSERT INTO `contrato` SET activo = 'SI', codigo_cliente = '".$codigo_cliente."',
				fecha_creacion = NOW(), fecha_modificacion = NOW(),  contacto='$nombre_contacto', fono_contacto='$fono_contacto', email_contacto='$mail_contacto',
				direccion_contacto='$dir_calle', rut='$rut', factura_razon_social='$rsocial', factura_giro='$giro', factura_direccion='$dir_calle', factura_telefono='$fono_contacto', id_tarifa='1' ";
echo $query;
	            if( !($resp_insert = mysql_query($query_insert,$dbh) ))
    	            throw new Exception(mysql_error());
				$id = mysql_insert_id($dbh);

				$query_up = "UPDATE cliente SET id_contrato = $id WHERE codigo_cliente = '".$codigo_cliente."' LIMIT 1";
				$resp_up = mysql_query($query_up,$dbh);

				echo $id_cliente.':'.$codigo_cliente.' >         Id: contrato:'.$id.'<br>';
			}

	break;

	case 2.25:

			$query = "UPDATE `usuario_tarifa` SET id_tarifa = 1";
			if( !mysql_query($query,$dbh) )
				throw new Exception($query."---".mysql_error());
echo $query;

			$query = "UPDATE `contrato` SET id_tarifa = 1 WHERE (id_tarifa IS NULL || id_tarifa = '') ";
			if( !mysql_query($query,$dbh) )
				throw new Exception($query ."---".mysql_error());

			$query = "SELECT * FROM caca";
	break;

	case 2.26:
            $query = "ALTER TABLE `usuario_tarifa` ADD CONSTRAINT `usuario_tarifa_fk2` FOREIGN KEY (`id_tarifa`) REFERENCES `tarifa` (`id_tarifa`) ON DELETE CASCADE ON UPDATE CASCADE";
echo $query;
            if( !mysql_query($query,$dbh) )
                throw new Exception($query ."---".mysql_error());

            $query = "ALTER TABLE `asunto` MODIFY COLUMN `id_contrato` INTEGER(11) NOT NULL";
echo $query;
            if( !mysql_query($query,$dbh) )
                throw new Exception($query ."---".mysql_error());

            $query = "ALTER TABLE `cliente` MODIFY COLUMN `id_contrato` INTEGER(11) NOT NULL";
echo $query;
            if( !mysql_query($query,$dbh) )
                throw new Exception($query ."---".mysql_error());

            $query = "SELECT id_usuario,id_moneda,tarifa,codigo_cliente, glosa_cliente FROM `usuario_tarifa_cliente` JOIN cliente using (codigo_cliente) WHERE 1  ORDER BY codigo_cliente";
echo $query;
            if(! $resp = mysql_query($query,$dbh) )
                throw new Exception($query ."---".mysql_error());
            $codigo_cliente_actual = "";
            $tarifa_actual=1;
            while(list($id_usuario, $id_moneda, $tarifa, $codigo_cliente,$glosa_cliente) =  mysql_fetch_array($resp) )
            {
                if($codigo_cliente_actual != $codigo_cliente)
                {
                    $tarifa_actual = $tarifa_actual + 1;
                    $query = "INSERT into tarifa SET id_tarifa='$tarifa_actual', glosa_tarifa='Especial $glosa_cliente', fecha_creacion=NOW(), fecha_modificacion=NOW()";
echo $query;
                    if( !mysql_query($query,$dbh) )
                        throw new Exception($query ."---".mysql_error());

                    $query= "UPDATE cliente JOIN contrato ON cliente.id_contrato = contrato.id_contrato SET contrato.id_tarifa = '$tarifa_actual' WHERE cliente.codigo_cliente = '$codigo_cliente'";
echo $query;
                    if( !mysql_query($query,$dbh) )
                        throw new Exception($query ."---".mysql_error());
                    $codigo_cliente_actual = $codigo_cliente;
                }
echo $query;
                $query = " INSERT into usuario_tarifa SET id_tarifa='$tarifa_actual', id_usuario = '$id_usuario', id_moneda='$id_moneda',tarifa='$tarifa' ";
                if( !mysql_query($query,$dbh) )
                    throw new Exception($query ."---".mysql_error());
            }
	break;
	case 2.27:

	$query = "UPDATE asunto INNER JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		SET asunto.id_contrato = cliente.id_contrato, asunto.cobro_independiente = 'NO'
		WHERE (asunto.id_contrato = '' || asunto.id_contrato IS NULL)";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
	break;

  case 2.28:

    $query = "ALTER TABLE `asunto` CHANGE `cobrable` `cobrable` TINYINT( 4 ) NOT NULL DEFAULT '1'";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `asunto` CHANGE `activo` `activo` TINYINT( 4 ) NOT NULL DEFAULT '1'";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());
  break;

  #29-05-08 - cambios generacion cobro masivo; menu tarifa
  case 2.29:

    $query = "ALTER TABLE `contrato` ADD `fecha_inicio_cap` DATE NOT NULL DEFAULT '0000-00-00'";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());

    $query = "INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` )
								VALUES (
								'TARIFA', 'Tarifas', '/app/interfaces/agregar_tarifa.php?id_tarifa_edicion=1', '', '', '0', '54', 'COBRANZA'
								)";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());

    $query = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
								VALUES (
								'COB', 'TARIFA'
								)";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `contrato` ADD `opc_ver_modalidad` TINYINT NOT NULL DEFAULT '1',
									ADD `opc_ver_profesional` TINYINT NOT NULL DEFAULT '1',
									ADD `opc_ver_gastos` TINYINT NOT NULL DEFAULT '1',
									ADD `opc_ver_descuento` TINYINT NOT NULL DEFAULT '1',
									ADD `opc_ver_numpag` TINYINT NOT NULL DEFAULT '1',
									ADD `opc_ver_carta` TINYINT NOT NULL DEFAULT '1',
									ADD `opc_papel` VARCHAR( 16 ) NOT NULL DEFAULT 'LETTER',
									ADD `opc_moneda_total` TINYINT NOT NULL DEFAULT '1'";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `contrato` ADD `incluir_en_cierre` TINYINT NOT NULL DEFAULT '1'";
    if(! ($resp = mysql_query($query,$dbh)))
          throw new Exception($query ."---".mysql_error());

    $query = "DELETE FROM cobro_historial
									WHERE
									id_cobro NOT IN(SELECT id_cobro FROM cobro)";
    	if(! ($resp = mysql_query($query,$dbh)))
					throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cobro_historial` ADD INDEX  (`id_cobro`)";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cobro_historial` ADD CONSTRAINT `cobro_historial_fk` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE CASCADE ON UPDATE CASCADE";
        if(! ($resp = mysql_query($query,$dbh)))
            throw new Exception($query ."---".mysql_error());


    	$query = "SELECT * FROM caca";
  break;

  case 2.3:
  	$query = "UPDATE `menu` SET `glosa` = 'Generación de Cobros',
								`url` = '/app/interfaces/genera_cobros.php',
								`codigo_padre` = 'COBRANZA' WHERE CONVERT( `codigo` USING utf8 ) = 'COBROS' LIMIT 1";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "SELECT * FROM caca";
  break;

  case 2.31:
  	### Update cobro para los antiguos que no están relacionados a ningún contrato. ###
		$query = "UPDATE cobro
								JOIN cobro_asunto ON cobro.id_cobro = cobro_asunto.id_cobro
								JOIN asunto ON cobro_asunto.codigo_asunto = asunto.codigo_asunto
								SET cobro.id_contrato = asunto.id_contrato
								WHERE cobro.id_contrato IS NULL";
    	if(! ($resp = mysql_query($query,$dbh)))
					throw new Exception($query ."---".mysql_error());

		### Update llaves para tabla cobros_? 										###
		### Eliminamos los registros que no tengan cobro asociado ###
		### Luego creamos la llave																###
  	$query = "DELETE FROM cobro_moneda
									WHERE
									id_cobro NOT IN(SELECT id_cobro FROM cobro)";
    	if(! ($resp = mysql_query($query,$dbh)))
					throw new Exception($query ."---".mysql_error());

  	$query = "ALTER TABLE `cobro_moneda` ADD CONSTRAINT `cobro_moneda_fk` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE CASCADE ON UPDATE CASCADE";
  	if(! ($resp = mysql_query($query,$dbh)))
        echo ($query ."---".mysql_error());

    ### Cobro asunto ###
    $query = "DELETE FROM cobro_asunto
									WHERE
									id_cobro NOT IN(SELECT id_cobro FROM cobro)";
    	if(! ($resp = mysql_query($query,$dbh)))
					echo($query ."---".mysql_error());

  	$query = "ALTER TABLE `cobro_asunto` ADD CONSTRAINT `cobro_asunto_fk` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE CASCADE ON UPDATE CASCADE";
  	if(! ($resp = mysql_query($query,$dbh)))
        echo($query ."---".mysql_error());

    ### cobro_asunto RR con asunto ###
    $query = "DELETE FROM cobro_asunto
									WHERE
									codigo_asunto NOT IN(SELECT codigo_asunto FROM asunto)";
    	if(! ($resp = mysql_query($query,$dbh)))
					echo($query ."---".mysql_error());

  	$query = "ALTER TABLE `cobro_asunto` ADD CONSTRAINT `cobro_asunto_fk1` FOREIGN KEY (`codigo_asunto`) REFERENCES `asunto` (`codigo_asunto`) ON DELETE CASCADE ON UPDATE CASCADE";
  	if(! ($resp = mysql_query($query,$dbh)))
        echo($query ."---".mysql_error());


    ### cobro_historial ###
    $query = "DELETE FROM cobro_historial
									WHERE
									id_cobro NOT IN(SELECT id_cobro FROM cobro)";
    	if(! ($resp = mysql_query($query,$dbh)))
					throw new Exception($query ."---".mysql_error());

  	$query = "ALTER TABLE `cobro_historial` ADD CONSTRAINT `cobro_historial_fk` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE CASCADE ON UPDATE CASCADE";
  	if(! ($resp = mysql_query($query,$dbh)))
        echo($query ."---".mysql_error());

	break;

	case 2.32:
  	$query = "ALTER TABLE `contrato` ADD `codigo_idioma` VARCHAR( 5 ) NOT NULL DEFAULT 'es'";
  	if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

   	$query = "ALTER TABLE `cobro` ADD `codigo_idioma` VARCHAR( 5 ) NOT NULL DEFAULT 'es'";
  	if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "CREATE TABLE `cobro_proceso` (
								`id_proceso` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`fecha` DATE NOT NULL DEFAULT '0000-00-00',
								`id_usuario` INT NULL
								) ENGINE = innodb;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `id_proceso` INT NULL";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());


    ###########################################   FACTURADO COBRO   ####################################################
    $query = "ALTER TABLE `cobro` ADD `facturado` TINYINT NOT NULL DEFAULT '0' COMMENT '0 NO FACTURADO; 1 FACURADO';";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
   break;

	case 2.33:
    ########################################### UPDATE ESTADO COBROS ####################################################
		$query = "ALTER TABLE `cobro` CHANGE `estado` `estado` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'CREADO'";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD INDEX  (`estado`)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `prm_estado_cobro` ADD INDEX  (`codigo_estado_cobro`)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE cobro SET estado = 'CREADO' WHERE estado = '';";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD CONSTRAINT `cobro_fk1` FOREIGN KEY (`estado`) REFERENCES `prm_estado_cobro` (`codigo_estado_cobro`) ON DELETE RESTRICT ON UPDATE CASCADE";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `prm_estado_cobro` ADD `order` INT NOT NULL DEFAULT '1';";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE cobro SET facturado =1 WHERE cobro.estado = 'FACTURADO';";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `prm_estado_cobro` SET `codigo_estado_cobro` = 'PAGADO' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'COBRADO' LIMIT 1 ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `prm_estado_cobro` SET `order` = '2' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'EMITIDO' AND `order` =1 LIMIT 1 ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `prm_estado_cobro` SET `codigo_estado_cobro` = 'ENVIADO AL CLIENTE',
		`order` = '3' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'FACTURADO' AND `order` =1 LIMIT 1 ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `prm_estado_cobro` SET `order` = '4' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'PAGADO' AND `order` =1 LIMIT 1 ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `prm_estado_cobro` SET `order` = '5' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'INCOBRABLE' AND `order` =1 LIMIT 1 ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `prm_estado_cobro` CHANGE `order` `orden` INT( 11 ) NOT NULL DEFAULT '1'";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
		######################################### FIN UPDATE ESTADO COBROS ##################################################
	break;


	case 2.34:
    ###########################################  UPDATE DESCUENTOS  ####################################################
		$query = "ALTER TABLE `cobro` ADD `porcentaje_descuento` INT( 3 ) NOT NULL DEFAULT '0' AFTER `descuento` ,
							ADD `tipo_descuento` VARCHAR( 20 ) NOT NULL DEFAULT 'VALOR' AFTER `porcentaje_descuento` ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `contrato` ADD `porcentaje_descuento` INT( 3 ) NOT NULL DEFAULT '0',
							ADD `tipo_descuento` VARCHAR( 20 ) NOT NULL DEFAULT 'VALOR';";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `contrato` ADD `descuento` DOUBLE NOT NULL DEFAULT '0';";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

	break;

	case 2.35:
		###########################################    UPDATE MONEDA     ####################################################
		$query = "ALTER TABLE `cobro` ADD `id_moneda_monto` INT NOT NULL DEFAULT '0';";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `contrato` ADD `id_moneda_monto` INT NOT NULL DEFAULT '1';";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
	break;

	case 2.36:
		###############################    UPDATE MONEDA SEGUN MONEDA ALMACENADA EN COBRO    #################################
		$query = "UPDATE cobro SET id_moneda_monto = id_moneda";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
	break;

	case 2.4;

    $query = "ALTER TABLE `asunto` CHANGE `codigo_cliente` `codigo_cliente` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
        if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `actividad` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Este cÃ³digo es vacÃ­o si la actividad sirve para todos los asuntos'";
        if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cta_corriente`
  ADD CONSTRAINT `codigo_asunto_fk` FOREIGN KEY (`codigo_asunto`) REFERENCES `asunto` (`codigo_asunto`) ON DELETE RESTRICT ON UPDATE CASCADE;";
        if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cta_corriente`
  ADD CONSTRAINT `codigo_cliente_fk` FOREIGN KEY (`codigo_cliente`) REFERENCES `cliente` (`codigo_cliente`) ON DELETE RESTRICT ON UPDATE CASCADE;";
   if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = " ALTER TABLE `asunto` ADD INDEX ( `codigo_asunto` ) ";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = " ALTER TABLE `asunto` DROP INDEX `codigo_asunto`  ";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = " ALTER TABLE `asunto` ADD UNIQUE `codigo_asunto_unique` ( `codigo_asunto` )  ";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

	$cliente = new Cliente($sesion);
	$cliente->ActualizacionCodigosClientes();
	$asunto = new Asunto($sesion);
	$asunto->ActualizacionCodigosAsuntos();
	break;


	######################################  Tabla prm unidad; Elimina menu contrato #########################################
	case 2.41:
		$query = "DELETE FROM menu WHERE codigo = 'CONTRATOS' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "TRUNCATE TABLE prm_unidad";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `prm_unidad` (`codigo_unidad`, `tipo_unidad`, `glosa_unidad`) VALUES ('ANNUAL', 'TIEMPO', 'Anual'),
								('EVER', 'TIEMPO', 'Cada vez'),
								('MONTH', 'TIEMPO', 'Mensual'),
								('QUARTERLY', 'TIEMPO', 'Trimestral'),
								('SEMESTER', 'TIEMPO', 'Semestral');";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
	break;

	####################################################### Cambios en dato #####################################################
	case 2.42:
		$query  = "ALTER TABLE cobro DROP id_moneda_monto";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query  = "ALTER TABLE `cobro` ADD `id_moneda_monto` INT NOT NULL DEFAULT '1' AFTER `monto_contrato`";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query  = "UPDATE cobro
								JOIN contrato ON cobro.id_contrato = contrato.id_contrato
								SET cobro.id_moneda_monto = contrato.id_moneda_monto";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "UPDATE contrato SET id_moneda_monto = id_moneda";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
	break;


	###################################################### SQL CARPTETAS ###########################################################
	case 2.43:
		$query = "CREATE TABLE `carpeta` (
		`id_carpeta` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`codigo_asunto` VARCHAR( 30 ) NOT NULL ,
		`codigo_carpeta` VARCHAR( 20 ) NOT NULL ,
		`glosa_carpeta` VARCHAR( 255 ) NOT NULL ,
		`fecha_creacion` DATETIME NOT NULL ,
		`fecha_modificacion` DATETIME NOT NULL ,
		`id_tipo_carpeta` INT(11) NOT NULL ,
		`id_bodega` INT NOT NULL
		) ENGINE = innodb";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "CREATE TABLE `prm_tipo_carpeta` (
            `id_tipo_carpeta` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `glosa_tipo_carpeta` VARCHAR( 20 ) NOT NULL
        ) ENGINE = innodb";
         if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "CREATE TABLE `bodega` (
        `id_bodega` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `glosa_bodega` VARCHAR( 50 ) NOT NULL ,
        `fecha_creacion` DATETIME NOT NULL ,
        `fecha_modificacion` DATETIME NOT NULL
        ) ENGINE = innodb";
        if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `carpeta` ADD INDEX (`codigo_asunto`)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `carpeta` ADD UNIQUE (`codigo_carpeta`)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `carpeta` ADD INDEX (`id_bodega`)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `carpeta` ADD INDEX (`id_tipo_carpeta`)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE carpeta ADD (FOREIGN KEY (codigo_asunto) REFERENCES asunto(codigo_asunto) ON UPDATE CASCADE)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE carpeta ADD (FOREIGN KEY (id_bodega) REFERENCES bodega(id_bodega) ON UPDATE CASCADE)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE carpeta ADD (FOREIGN KEY (id_tipo_carpeta) REFERENCES prm_tipo_carpeta(id_tipo_carpeta) ON UPDATE CASCADE)";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());


		$query = "UPDATE `menu` SET `url` = '/fw/tablas/mantencion_tablas.php', `orden` = '60', `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `codigo` USING utf8 ) = 'MANT' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` ) VALUES ('CARPETA', 'Carpetas', '/app/interfaces/carpeta.php', '', '', '0', '50', 'ADMIN_SIS')";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` ) VALUES ('DAT', 'CARPETA')";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

	break;

	############################################## MODIFICACION CARPETAS ##################################################
	case 2.44:

		$query = "UPDATE `menu` SET `url` = '/fw/tablas/mantencion_tablas.php', `orden` = '50', `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `codigo` USING utf8 ) = 'MANT' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO prm_permisos ( codigo_permiso , glosa )
						VALUES ('LEE', 'Revisar Datos');";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO menu ( codigo , glosa , url , descripcion , foto_url , tipo , orden , codigo_padre )
						VALUES ( 'BIBLIO', 'Biblioteca' , NULL ,
						'Gestion de la Biblioteca del Estudio' , 'escritura_32.gif' , '1' , '9' , NULL);";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
						VALUES ('LEE', 'BIBLIO');";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `menu_permiso` SET  `codigo_permiso`='LEE' WHERE codigo_menu = 'CARPETA';";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "UPDATE menu SET glosa = 'Archivos',
						orden = '10' , codigo_padre = 'BIBLIO' WHERE codigo = 'CARPETA' LIMIT 1;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `carpeta` CHANGE `codigo_carpeta` `codigo_carpeta` INT( 20 ) NOT NULL ;";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

	break;

	case 2.45:

  	$query = "ALTER TABLE asunto DROP cobro_independiente";
 	  if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
  break;

  case 2.46:
    $query = "UPDATE `menu` SET `glosa` = 'Seguimiento Cobros',
			`url` = '/app/interfaces/seguimiento_cobro.php',
			`codigo_padre` = 'COBRANZA' WHERE CONVERT( `codigo` USING utf8 ) = 'LISTA_COB' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `actividad` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 10 )
    					CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Este código es vacío si la actividad sirve para todos los asuntos'";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

	break;

  case 2.47:
  	$query = "UPDATE `prm_permisos` SET `glosa` = 'Revisar Biblioteca'
  		WHERE CONVERT( `codigo_permiso` USING utf8 ) = 'LEE' LIMIT 1 ;";
  	if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

  	$query = "INSERT INTO `prm_permisos` ( `codigo_permiso` , `glosa` )
  		VALUES ('EDI', 'Editar Biblioteca');";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
	break;

	case 2.48:
		$query = "UPDATE contrato SET factura_razon_social=(SELECT glosa_cliente FROM cliente WHERE cliente.codigo_cliente=contrato.codigo_cliente)
							WHERE factura_razon_social IS NULL OR factura_razon_social=' ';";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
  break;

	case 2.49:
		$query = "ALTER TABLE `cobro` CHANGE `monto_subtotal` `monto_subtotal` DOUBLE NOT NULL DEFAULT '0.00'";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` CHANGE `descuento` `descuento` DOUBLE NOT NULL DEFAULT '0.00'";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` CHANGE `monto_contrato` `monto_contrato` DOUBLE NOT NULL DEFAULT '0.00'";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cobro` CHANGE `monto` `monto` DOUBLE NULL DEFAULT NULL";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cobro` CHANGE `monto_thh` `monto_thh` DOUBLE NOT NULL DEFAULT '0.00'";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

    $query = "ALTER TABLE `cobro` CHANGE `monto_gastos` `monto_gastos` DOUBLE NOT NULL DEFAULT '0.00'";
    if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
  break;

	case 2.5:
		$query = "CREATE TABLE `pagos` (
				`id_pago` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`fecha` DATE NULL ,
				`id_cobro` INT NOT NULL ,
				`monto` DOUBLE NOT NULL DEFAULT '0'
				) ENGINE = innodb";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `asunto` ADD `codigo_asunto_secundario` VARCHAR( 20 ) NULL AFTER `codigo_asunto`";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cliente` ADD `codigo_cliente_secundario` VARCHAR( 20 ) NULL AFTER `glosa_cliente`";
		if(! ($resp = mysql_query($query,$dbh)))
        throw new Exception($query ."---".mysql_error());
  break;

	case 2.51:
		$query = "UPDATE `menu` SET glosa = 'General', url = '/app/interfaces/resumen_actividades.php', codigo_padre = 'REP' WHERE CONVERT( `codigo` USING utf8 ) = 'RAP' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
	    	throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `menu` SET `glosa` = 'Gráfico profesionales',
		`url` = '/app/interfaces/reportes_usuarios.php',
		`codigo_padre` = 'REP' WHERE CONVERT( `codigo` USING utf8 ) = 'REP_US' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` )
					VALUES ('REP_ESPE', 'Específicos', '/app/interfaces/reportes_especificos.php', '', '', '0', '80', 'REP');";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
					VALUES ('REP', 'REP_ESPE')";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());


		#Quitando menu
		$query = "DELETE FROM menu WHERE codigo = 'PLANI' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());


		$query = "DELETE FROM menu WHERE codigo = 'REP_RES_CL' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());


		$query = "DELETE FROM menu WHERE codigo = 'REP_RES_AB' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());


		$query = "DELETE FROM menu WHERE codigo = 'REP_AS' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());


		$query = "DELETE FROM menu WHERE codigo = 'REP_US' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());


		$query = "DELETE FROM menu WHERE codigo = 'OLAP' LIMIT 1";
        	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `gastos_pagados` TINYINT( 1 ) NOT NULL DEFAULT '0'";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cta_corriente` ADD `documento_pago` VARCHAR( 50 ) NULL";
		if(! ($resp = mysql_query($query,$dbh)))
        	throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `fecha_pago_gastos` DATE NULL ,
				ADD `documento_pago_gastos` VARCHAR( 50 ) NULL";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	break;

	case 2.52:
		$query = "DELETE FROM menu WHERE codigo = 'REP_FAC_PE' LIMIT 1";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `fecha_enviado_cliente` DATETIME NULL AFTER `fecha_facturacion`";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cta_corriente` ADD `id_movimiento_pago` INT NULL";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `id_movimiento_pago` INT NULL";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `prm_permisos` ( `codigo_permiso` , `glosa` )
				VALUES ('SOC', 'Perfil Comercial')";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `saldo_final_gastos` DOUBLE NOT NULL DEFAULT '0'";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	break;

	case 2.53:
		#Agregando a los ADMINISTRADORES como encargados comerciales.
		$sql = "SELECT id_usuario FROM usuario_permiso WHERE codigo_permiso = 'ADM'";
		$resp_s = mysql_query($sql,$dbh);
		if( !mysql_query($sql,$dbh) )
		throw new Exception(mysql_error());
		while(list($id_usuario) = mysql_fetch_array($resp_s))
		{
			$query = "DELETE FROM usuario_permiso WHERE id_usuario = '".$id_usuario."' AND codigo_permiso = 'SOC' ";
			if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

			$query_i = "INSERT INTO usuario_permiso SET id_usuario = '".$id_usuario."', codigo_permiso = 'SOC' ";
			if(! ($resp_i = mysql_query($query_i,$dbh)))
			throw new Exception($query_i ."---".mysql_error());
		}
	break;

	case 2.54:
		echo "2.54 OK";
	break;

	############################### Tabla documentos asociados al contrato ################################
	case 2.55:
		$query = "CREATE TABLE `archivo` (
								`id_archivo` int(11) NOT NULL auto_increment,
								`id_contrato` int(11) NOT NULL default '0',
								`archivo_nombre` varchar(30) NOT NULL default '',
								`data_tipo` varchar(30) NOT NULL default '',
								`archivo_data` mediumblob NOT NULL,
								`descripcion` text,
								`fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
								`fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
								PRIMARY KEY  (`id_archivo`),
								KEY `id_contrato` (`id_contrato`)
							) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `archivo`
  			ADD CONSTRAINT `archivo_ibfk_1` FOREIGN KEY (`id_contrato`)
  			REFERENCES `contrato` (`id_contrato`) ON UPDATE CASCADE;";
		if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());
	break;

	######################### Visible en Trabajo #########################
	case 2.56:
	$query = "ALTER TABLE  `trabajo` ADD  `visible` INT( 11 ) NOT NULL DEFAULT  '1' AFTER  `cobrable` ;";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "UPDATE trabajo SET visible='0' WHERE cobrable='0';";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "UPDATE trabajo SET visible='1' WHERE cobrable='1';";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());
	break;

	######################## Duracion Retainer y Area Proyecto #####################
	case 2.57:
	$query = "ALTER TABLE  trabajo ADD  duracion_retainer TIME NULL AFTER  duracion_cobrada ;";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "CREATE TABLE  prm_area_proyecto (
 							id_area_proyecto INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 							glosa VARCHAR( 50 ) NOT NULL
							) ENGINE = INNODB;";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "ALTER TABLE  asunto ADD  id_area_proyecto INT NULL AFTER  id_tipo_asunto ;";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "ALTER TABLE  asunto ADD INDEX (  id_area_proyecto );";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "INSERT INTO `prm_area_proyecto` (`id_area_proyecto`, `glosa`) VALUES (1, 'Otra'),
							(2, 'Propiedad Intelectual'),
							(3, 'Litigios'),
							(4, 'Tributario');";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "UPDATE asunto SET id_area_proyecto=1 ;";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());

	$query = "ALTER TABLE `asunto`
							ADD CONSTRAINT `asunto_ibfk_18` FOREIGN KEY (`id_area_proyecto`)
							REFERENCES `prm_area_proyecto` (`id_area_proyecto`) ON DELETE SET NULL ON UPDATE CASCADE;";
	if(! ($resp = mysql_query($query,$dbh)))
		throw new Exception($query ."---".mysql_error());
	break;

	######################## Valores para Area Proyecto #####################
	case 2.58:

	break;

	############# Opcion de ver morosidad y Comentarios de gastos ###########
	case 2.59:
		$query = "ALTER TABLE  `cobro` ADD  `opc_ver_morosidad` TINYINT NOT NULL DEFAULT  '1'
								COMMENT  'Ver saldo adeudado' AFTER  `opc_ver_carta` ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE  `contrato` ADD  `opc_ver_morosidad` TINYINT NOT NULL DEFAULT  '1'
								COMMENT  'Ver saldo adeudado' AFTER  `opc_ver_carta` ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `cobro` SET `opc_ver_morosidad`=0;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE `contrato` SET `opc_ver_morosidad`=0;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` CHANGE `monto_gastos` `monto_gastos` DOUBLE NOT NULL DEFAULT '0'
								COMMENT 'Actualmente sin valor',
							CHANGE `saldo_cta_corriente` `saldo_cta_corriente` DOUBLE NULL DEFAULT NULL
								COMMENT 'Saldo de la cuenta corriente del cliente',
							CHANGE `saldo_final_gastos` `saldo_final_gastos` DOUBLE NOT NULL DEFAULT '0'
								COMMENT 'Saldo de la cuenta corriente del periodo';";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
	break;
	####################### E-mail mas de 20 caracteres (ahora 50) ##########################
	case 2.6:
		$query = "ALTER TABLE  `contrato` CHANGE  `email_contacto`  `email_contacto`
							VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
		$query = "ALTER TABLE  `cta_corriente` ADD  `monto_pago` DOUBLE NOT NULL DEFAULT  '0'
								COMMENT  'monto pagado del gasto' AFTER  `cobrable_actual`";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
		$query = "ALTER TABLE  `cta_corriente` ADD  `fecha_pago` DATE NULL
								COMMENT  'fecha de pago del gasto' AFTER  `monto_pago`";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
		$query = "ALTER TABLE  `cta_corriente` ADD  `pagado` TINYINT( 1 ) NOT NULL DEFAULT  '0'
								COMMENT  'pagado=1, no pagado=0' AFTER  `documento_pago`";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
	break;
	############################## Recordatorio ##################################
	case 2.61:
		echo "<br>Recordar revisar el conf.php:<br>ReportesCobranza()<br>ReportesGestion()<br>
					TipoIngresoHoras()<br>RecordarSesion()<br>";
	break;

	case 2.62:
		$query = "UPDATE prm_moneda SET cifras_decimales='2' WHERE simbolo='USD'";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "SELECT COUNT(*) FROM menu_permiso WHERE codigo_permiso='DAT' AND codigo_menu='ADMIN_SIS'";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());
		else
		{
			list($count)=mysql_fetch_array($resp);
			if($count == 0)
			{
				$query = "INSERT INTO menu_permiso (codigo_permiso,codigo_menu) VALUES('DAT','ADMIN_SIS')";
				if(! ($resp = mysql_query($query,$dbh)))
					throw new Exception($query ."---".mysql_error());
			}
		}

		echo "Cambios realizados:<br>
					- Filtro para asuntos (actividad)<br>
					- Se puede ingresar horas con decimales, deben pedir activacion<br>
					- Cambios Nota de Cobro, mayores detalles, deben pedir activacion<br>
					- Codigo asunto con codigo_cliente-año+dos_digitos (En el conf es TipoCodigoAsunto: agregar!!)<br>";
	break;
	########################### DOCUMENTOS, EDICIONES COBRO ############################
	case 2.63:
		$query = "CREATE TABLE `documento` (`id_documento` int(11) NOT NULL auto_increment,
							`id_tipo_documento` int(11) NOT NULL default '0' COMMENT 'tipo documento',
							`codigo_cliente` varchar(10) NOT NULL default '',
							`id_cobro` int(11) default NULL, `glosa_documento` varchar(50) NOT NULL default '',
							`monto` double NOT NULL default '0' COMMENT 'monto que paga el documento',
							`honorarios` double default '0',`gastos` double default '0',
							`pagado_honorarios` tinyint(4) NOT NULL default '0',
							`pagado_gastos` tinyint(4) NOT NULL default '0',
							`id_moneda` int(11) NOT NULL default '0',
							`monto_base` double NOT NULL default '0' COMMENT 'monto en moneda base',
							`id_moneda_base` int(11) NOT NULL default '1',
							`numero_doc` varchar(20) NOT NULL default '0000' COMMENT 'numero de documento en papel',
							`tipo_doc` char(1) NOT NULL default 'N' COMMENT 'C:Cheque T:Transferencia E:Efectivo F: Factura O:Otro N:NoAplica',
							`fecha` date NOT NULL default '0000-00-00' COMMENT 'fecha del documento',
							`fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
							`fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
							PRIMARY KEY  (`id_documento`),
							KEY `id_tipo_documento` (`id_tipo_documento`),
							KEY `codigo_cliente` (`codigo_cliente`),
							KEY `id_cobro` (`id_cobro`),
							KEY `id_moneda` (`id_moneda`),
							KEY `id_moneda_base` (`id_moneda_base`)
							) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tabla de los documentos contables';";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `documento`
							ADD CONSTRAINT `documento_ibfk_5` FOREIGN KEY (`codigo_cliente`) REFERENCES `cliente` (`codigo_cliente`) ON UPDATE CASCADE,
							ADD CONSTRAINT `documento_ibfk_6` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON UPDATE CASCADE,
							ADD CONSTRAINT `documento_ibfk_7` FOREIGN KEY (`id_moneda`) REFERENCES `prm_moneda` (`id_moneda`) ON UPDATE CASCADE,
							ADD CONSTRAINT `documento_ibfk_8` FOREIGN KEY (`id_moneda_base`) REFERENCES `prm_moneda` (`id_moneda`) ON UPDATE CASCADE;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` CHANGE `monto` `monto` DOUBLE NULL DEFAULT NULL COMMENT 'monto honorarios'";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `temp` VARCHAR( 2 ) NOT NULL ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE cobro SET temp = gastos_pagados;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` DROP `gastos_pagados`;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `honorarios_pagados` VARCHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'NO' AFTER `monto_gastos` ,
							ADD `gastos_pagados` VARCHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'NO' AFTER `honorarios_pagados` ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE cobro SET gastos_pagados=(IF(temp='1','SI','NO')), honorarios_pagados=(IF(estado='PAGADO','SI','NO'))";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` DROP `temp`; ";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `forma_envio` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'CARTA' AFTER `fecha_enviado_cliente` ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD `id_doc_pago_honorarios` INT( 11 ) NULL DEFAULT NULL AFTER `gastos_pagados` ,
							ADD `id_doc_pago_gastos` INT( 11 ) NULL DEFAULT NULL AFTER `id_doc_pago_honorarios` ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD INDEX ( `id_doc_pago_honorarios` ) ";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD INDEX ( `id_doc_pago_gastos` )";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE `cobro` ADD CONSTRAINT `cobro_ibfk_honorarios` FOREIGN KEY (`id_doc_pago_honorarios`)
							REFERENCES `documento` (`id_documento`), ADD CONSTRAINT `cobro_ibfk_gastos` FOREIGN KEY (`id_doc_pago_gastos`) REFERENCES `documento` (`id_documento`);";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` )
							VALUES ('CTA_CTE', 'Cuenta Corriente', '/app/interfaces/lista_documentos.php', '', '', '0', '52', 'COBRANZA');";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` ) VALUES ('COB', 'CTA_CTE');";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		echo "Cambios realizados:<br>
					- Documentos de ingresos y egresos por pagos. El cobro genera un documento de egreso al emitirse.<br>
					- Pantalla de Seguimiento de Cobro se le agregan mas detalles y cada estado tiene un panel diferente<br>
					- Se permite asociar uno o mas documentos de pago con un cobro; los honorarios y/o gastos.<br>
					- Pantalla de Cuenta Corriente por cliente con lista de documentos y suma como balance cuenta corriente<br>";
	break;

	########################### DOCUMENTOS, EDICIONES COBRO ############################
	case 2.64:
		$query = "UPDATE trabajo as t
					INNER JOIN cobro AS c ON (t.id_cobro = c.id_cobro)
					INNER JOIN tarifa AS t_e ON (t_e.tarifa_defecto = '1')
					LEFT JOIN usuario_tarifa AS u_t ON (u_t.id_usuario = t.id_usuario AND u_t.id_moneda = c.id_moneda AND t_e.id_tarifa = u_t.id_tarifa )
					SET  t.costo_hh = IFNULL(u_t.tarifa,0)";

		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		echo "Cambio realizado: <br>
				-Trabajo guarda la Tarifa por defecto (Costo del trabajo) en campo costo_hh. <br>
			";

		break;
	########################### TARIFA ESTANDAR POR TRABAJO, REPORTE AVANZADO #################
	case 2.65:
		$query = "ALTER TABLE `trabajo` ADD `tarifa_hh_estandar` DOUBLE NOT NULL DEFAULT '0' AFTER `tarifa_hh`";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "CREATE TABLE tarifa_estandar
					(
					id_trabajo int(11),
					tarifa_hh_estandar double,
					cifras_decimales int(4)
					)";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "INSERT INTO tarifa_estandar ( id_trabajo, tarifa_hh_estandar, cifras_decimales )
					SELECT
							t.id_trabajo as id_trabajo,
							IFNULL(mejor_tarifa.tarifa, IFNULL(MAX(toda_tarifa.tarifa * moneda_especifica.tipo_cambio / moneda_del_cobro.tipo_cambio ), 0) ) as
							tarifa_hh_estandar,
							moneda_del_cobro.cifras_decimales as cifras_decimales
					FROM
							trabajo AS t
							INNER JOIN cobro AS c ON (t.id_cobro = c.id_cobro)
							INNER JOIN tarifa AS tarifa_estandar ON (tarifa_estandar.tarifa_defecto = '1')
							LEFT JOIN usuario_tarifa AS toda_tarifa ON	(
																			toda_tarifa.id_usuario = t.id_usuario
																			AND tarifa_estandar.id_tarifa = toda_tarifa.id_tarifa
																		)

							LEFT JOIN prm_moneda AS moneda_especifica ON (
																			toda_tarifa.id_moneda = moneda_especifica.id_moneda
																		)

							LEFT JOIN usuario_tarifa AS mejor_tarifa ON	(
																			mejor_tarifa.id_usuario = t.id_usuario
																			AND mejor_tarifa.id_tarifa = tarifa_estandar.id_tarifa
																			AND mejor_tarifa.id_moneda = c.id_moneda
																		)
							JOIN prm_moneda AS moneda_del_cobro ON		(
																			moneda_del_cobro.id_moneda = c.id_moneda
																		)
							GROUP BY id_trabajo";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE trabajo t
					INNER JOIN tarifa_estandar as t_e ON (t.id_trabajo = t_e.id_trabajo)
					SET t.tarifa_hh_estandar = ROUND(t_e.tarifa_hh_estandar,t_e.cifras_decimales)";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE trabajo t
					INNER JOIN cobro AS c ON (c.id_cobro = t.id_cobro)
					SET t.id_moneda = c.id_moneda";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "drop table tarifa_estandar";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		echo "Cambio realizado: <br>
				-Reemplazo: Trabajo guarda la Tarifa por defecto (Costo del trabajo) en campo tarifa_hh_estandar. <br>
				-Arreglo: Trabajo guarda la id_moneda del cobro asociado.
			";
		break;
		##################### Cambios Revisor #############################
		case 2.66:
		$query = "INSERT INTO  `menu_permiso` (  `codigo_permiso` ,  `codigo_menu` )
							VALUES (
								'REV',  'PRO'
								), (
								'REV',  'TRAB'
								), (
								'REV',  'MIS_HRS'
								);";

		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		echo "Cambio realizado: <br>
				-Revisor tiene permiso para ingresar horas. <br>";

		break;
		##################### Cobros Pendientes ############################
		case 2.67:
		$query = "CREATE TABLE cobro_pendiente (
								id_cobro_pendiente int(11) NOT NULL auto_increment,
								id_contrato int(11) NOT NULL default '0',
								fecha_cobro date NOT NULL default '0000-00-00',
								descripcion varchar(100) default NULL,
								monto_estimado float NOT NULL default '0',
								id_cobro int(11) default NULL COMMENT 'id cobro si es que hay cobro creado',
								PRIMARY KEY (id_cobro_pendiente),
								KEY id_contrato (id_contrato),
								KEY id_cobro (id_cobro)
							) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='fechas de los proximos cobros del contrato';";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query ="ALTER TABLE cobro_pendiente
							ADD CONSTRAINT cobro_pendiente_ibfk_1
							FOREIGN KEY (id_contrato)
							REFERENCES contrato (id_contrato) ON DELETE CASCADE ON UPDATE CASCADE;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "ALTER TABLE cta_corriente ADD incluir_en_cobro CHAR( 2 ) CHARACTER
							SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'SI' AFTER
							documento_pago ;";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE cobro SET fecha_facturacion = NULL WHERE fecha_facturacion =
							'0000-00-00 00:00:00'";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		$query = "UPDATE cobro SET facturado = '1' WHERE (documento IS NOT NULL) OR
							(fecha_facturacion IS NOT NULL)";
		if(! ($resp = mysql_query($query,$dbh)))
			throw new Exception($query ."---".mysql_error());

		echo "Cambios realizados: <br>
				-Se pueden inrgesar cobros programados. <br>
				-En la generación de cobros se puede generar directamente por cobro programado. <br>
				-El la generación masiva te da la opción de generar programados o WIP. <br>
				-Se puede ingresar una descripcion a la cobranza. <br>
				-Los documentos se separaron del menu de cobranza. <br>";

		break;
		############ CORRECCIONES Y OTROS ##########
		case 2.68:
			$query = "ALTER TABLE  `contrato` ADD  `titulo_contacto` VARCHAR( 10 ) NOT NULL
								DEFAULT  'Sr.' AFTER  `centro_costo` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE  `prm_glosa_gasto` (
								`id_glosa_gasto` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`glosa_gasto` VARCHAR( 75 ) NOT NULL
								) ENGINE = INNODB;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE  `prm_categoria_usuario` (
								`id_categoria_usuario` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`glosa_categoria` VARCHAR( 20 ) NOT NULL
								) ENGINE = INNODB COMMENT =  'Categorias de los Usuarios';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `usuario` ADD  `id_categoria_usuario` INT NULL AFTER  `apellido2` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `usuario` ADD INDEX (  `id_categoria_usuario` );";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `usuario` ADD FOREIGN KEY (  `id_categoria_usuario` ) REFERENCES  `prm_categoria_usuario` (
								`id_categoria_usuario`
								) ON DELETE SET NULL ON UPDATE CASCADE";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `cobro` ADD `id_gasto_generado` INT( 11 ) NULL DEFAULT
								NULL AFTER `monto_gastos` ,
								ADD `id_provision_generada` INT( 11 ) NULL DEFAULT NULL AFTER
								`id_gasto_generado` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `cobro` ADD `monto_thh_estandar` DOUBLE NOT NULL DEFAULT
								'0' AFTER `monto_thh` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE monto_tarifa_estandar
								(
								id_cobro int(11),
								monto_thh_estandar double
								)";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO monto_tarifa_estandar ( id_cobro, monto_thh_estandar )
								SELECT  cobro.id_cobro as id_cobro,
								SUM( trabajo.tarifa_hh_estandar * TIME_TO_SEC(trabajo.duracion ))/ 3600 as monto_thh_estandar
								FROM cobro
								INNER JOIN trabajo ON (trabajo.id_cobro = cobro.id_cobro)
								GROUP BY cobro.id_cobro";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "UPDATE cobro JOIN monto_tarifa_estandar ON (cobro.id_cobro =
								monto_tarifa_estandar.id_cobro) SET cobro.monto_thh_estandar =
								monto_tarifa_estandar.monto_thh_estandar";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "DROP TABLE monto_tarifa_estandar;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		###### SOLICITANTE #####
		case 2.69:
			$query = "ALTER TABLE  `trabajo` ADD  `solicitante` VARCHAR( 75 ) NOT NULL COMMENT  'solicitante del trabajo' AFTER  `id_trabajo_local` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		###### APELLIDO CONTACTO ######
		case 2.70:
			$query = "ALTER TABLE  `contrato` ADD  `apellido_contacto` VARCHAR( 75 ) NULL COMMENT  'apellido del contacto para casos especiales' AFTER  `contacto` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;

		##### SISTEMA PAGOS PARCIALES ######
		case 2.71:
			$query = "CREATE TABLE `neteo_documento` (
			`id_neteo_documento` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`id_documento_cobro` INT( 11 ) NOT NULL ,
			`id_documento_pago` INT( 11 ) NOT NULL ,
			`valor_pago_honorarios` DOUBLE NOT NULL DEFAULT '0',
			`valor_pago_gastos` DOUBLE NOT NULL DEFAULT '0'
			) ENGINE = INNODB COMMENT = 'Relaciona pago entre documentos';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `neteo_documento` ADD `valor_cobro_honorarios` DOUBLE NOT NULL DEFAULT '0' AFTER `id_documento_pago` ,
			ADD `valor_cobro_gastos` DOUBLE NOT NULL DEFAULT '0' AFTER `valor_cobro_honorarios` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `neteo_documento` ADD `fecha_modificacion` DATETIME NULL DEFAULT NULL ,
			ADD `fecha_creacion` DATETIME NULL DEFAULT NULL ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `neteo_documento` ADD INDEX ( `id_documento_cobro` ); ";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `neteo_documento` ADD INDEX ( `id_documento_pago` );";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `neteo_documento`
			  ADD CONSTRAINT `neteo_documento_ibfk_1` FOREIGN KEY (`id_documento_cobro`) REFERENCES `documento` (`id_documento`) ON UPDATE CASCADE,
			 ADD CONSTRAINT `neteo_documento_ibfk_2` FOREIGN KEY (`id_documento_pago`) REFERENCES `documento` (`id_documento`) ON UPDATE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `documento` ADD `saldo_honorarios` DOUBLE NOT NULL DEFAULT '0' AFTER `gastos` ,
			ADD `saldo_gastos` DOUBLE NOT NULL DEFAULT '0' AFTER `saldo_honorarios` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `documento` ADD `saldo_pago` DOUBLE NOT NULL DEFAULT '0' AFTER `saldo_gastos` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `documento` ADD `honorarios_pagados` CHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'NO' AFTER `saldo_pago` ,
			ADD `gastos_pagados` CHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'NO' AFTER `honorarios_pagados` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `cta_corriente` ADD `neteo_pago` INT( 11 ) NULL DEFAULT NULL AFTER `documento_pago` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "UPDATE cobro SET opc_moneda_total=1, opc_moneda_total_tipo_cambio='1' WHERE opc_moneda_total IS NULL OR opc_moneda_total=0";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$documento = new Documento($sesion);
			$documento->ReiniciarDocumentos($sesion,1);
			break;

		#### SE INGRESA CARTA SOLO GASTOS Y VISIBILIDAD DE USUARIO EN LISTADOS ####
		case 2.72:
			$query = "ALTER TABLE  `usuario` ADD  `visible` TINYINT NOT NULL DEFAULT '1' AFTER  `activo`;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "UPDATE usuario SET visible =0 WHERE activo =0;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "UPDATE usuario SET visible =1 WHERE activo =1;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		############## HISTORIAL TRABAJO Y USUARIO REVISOR ####################
		case 2.73:
			$query = "CREATE TABLE `usuario_revisor` (
			 					 `id_revisor` int(11) NOT NULL default '0',
				 				 `id_revisado` int(11) NOT NULL default '0',
					  		 UNIQUE KEY `id_revisado` (`id_revisado`),
						  	 KEY `id_revisor` (`id_revisor`)
								 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "ALTER TABLE `usuario_revisor`
			  					ADD CONSTRAINT `usuario_revisor_ibfk_2` FOREIGN KEY (`id_revisado`)
										REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
				  				ADD CONSTRAINT `usuario_revisor_ibfk_1` FOREIGN KEY (`id_revisor`)
										REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `trabajo_historial` (
			  					`id_trabajo` int(11) NOT NULL default '0',
				  				`id_usuario` int(11) NOT NULL default '0',
					  			`fecha` datetime NOT NULL default '0000-00-00 00:00:00',
						  		`accion` varchar(9) NOT NULL default '',
							  	KEY `id_usuario` (`id_usuario`)
									) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO prm_categoria_usuario (glosa_categoria) VALUES ('Socio'),('Asociado Senior'),('Asociado Junior'),('Procurador')";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		############## COSTO MENSUAL POR USUARIO Y REPORTE FINANCIERO####################
		case 2.74:
			$query = "DROP TABLE IF EXISTS usuario_costo";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "CREATE TABLE `usuario_costo` (
			  					`id_usuario` int(11) NOT NULL default '0',
					  			`costo` double(15,2) NOT NULL default '0.00',
								`fecha` date NOT NULL default '0000-00-00',
								PRIMARY KEY  (`id_usuario`, `fecha`)
									) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "ALTER TABLE usuario_costo
							ADD CONSTRAINT usuario_costo_fk FOREIGN KEY (id_usuario)
							REFERENCES usuario (id_usuario) ON DELETE CASCADE ON UPDATE CASCADE";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "INSERT INTO `menu`
									(`codigo`, `glosa`, `url`,`descripcion`, `foto_url`, `tipo`, `orden`, `codigo_padre`)
								VALUES
									('COSTOS', 'Costo Profesionales', '/app/interfaces/costos.php', '', '', 0, 50, 'ADMIN_SIS');";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "INSERT INTO  `menu_permiso` (  `codigo_permiso` ,  `codigo_menu` )
									VALUES ('ADM',  'COSTOS');";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "ALTER TABLE  `cobro`
									ADD  `opc_ver_resumen_cobro` TINYINT NOT NULL DEFAULT  '1'
									AFTER  `opc_ver_morosidad` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "ALTER TABLE  `contrato`
									ADD  `opc_ver_resumen_cobro` TINYINT NOT NULL DEFAULT  '1'
									AFTER  `opc_ver_morosidad` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
#######################IMPUESTO#####################
		case 2.75:
			$query = "ALTER TABLE  `cobro`
									ADD `impuesto` double NOT NULL default '0'
									AFTER  `monto_subtotal` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "ALTER TABLE  `documento`
									ADD `impuesto` double NOT NULL default '0'
									AFTER  `honorarios` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
			$query = "ALTER TABLE  `cobro`
									ADD `porcentaje_impuesto` tinyint(3) unsigned NOT NULL default '0'
											COMMENT 'Para agregar el impuesto al final cuando no está incluido en el valor del cobro.'
									AFTER  `monto_subtotal` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
#####################IMPUESTO POR SEPARADO####################
		case 2.76:
			$query = "ALTER TABLE  `contrato`
									ADD `usa_impuesto_separado` tinyint(1) unsigned NOT NULL default '1';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
###############FACTURAS Y CONFIGURACION EN BD##################
		case 2.8:

			$query = "CREATE TABLE `configuracion` (
								`id` int(11) NOT NULL auto_increment COMMENT 'Necesario para la página de configuración.',
								`glosa_opcion` varchar(64) NOT NULL default '' COMMENT 'Nombre de la opcion para mostrar al usuario.',
								`valor_opcion` text NOT NULL,
								`comentario` varchar(255) default NULL COMMENT 'Comentario explicando la funcionalidad para mostrar al usuario.',
								`valores_posibles` varchar(255) NOT NULL default '' COMMENT 'Puede ser \"numero\" para que el usuario ingrese un número, \"string\" para string ingresado por el usuario, \"boolean\" para un checkbox o \"select;valor1;valor2;...\" para generar un select con los valores definidos.',
								`orden` int(11) NOT NULL default '-1' COMMENT 'Orden de aparición en la página de configuración, -1 para no mostrar la opción.',
								PRIMARY KEY  (`id`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=19;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `orden`)
									VALUES
									(1, 'MaxDiasIngresoTrabajo', '-1', 'Máximo de días hacia atrás en los que se pueden ingresar trabajos, -1 indica que no hay lí­mite.', 'numero', 1),
									(2, 'MaxDiasIngresoTrabajoRevisor', '-1', 'Máximo de días hacia atrás en los que un revisor puede ingresar o modificar trabajos, -1 indica que no hay lí­mite.', 'numero', 1),
									(3, 'MailAdmin', '', 'Email al que llegan los avisos del sistema.', 'string', 3),
									(4, 'Activar corrector ortográfico', '0', NULL, 'boolean', -1),
									(5, 'MailSistema', '', NULL, 'string', -1),
									(6, 'MaxLoggedTime', '14400', 'Tiempo en segundos que dura la sesión.', 'string', 3),
									(7, 'DireccionPdf', '', NULL, 'string', 2),
									(8, 'PdfLinea1', '', NULL, 'string', 3),
									(9, 'PdfLinea2', '', NULL, 'string', 3),
									(10, 'PdfLinea3', '', NULL, 'string', 3),
									(11, 'MailAsuntoNuevo', '0', 'Indica si se envía un email cada vez que se crea un asunto nuevo.', 'boolean', 4),
									(12, 'RecordarSesion', '1', NULL, 'boolean', 4),
									(13, 'OrdenadoPor', '0', '0 no se necesita, 1 obligatorio, 2 opcional', 'select;0;1;2', -1),
									(14, 'IdiomaGrande', '0', NULL, 'boolean', 5),
									(15, 'CorreosModificacionAdminDatos', '', 'Dejar en blanco para que no se envíen mails.', 'string', 5),
									(16, 'ImprimirDuracionTrabajada', '0', NULL, 'boolean', 7),
									(17, 'ImprimirValorTrabajo', '0', NULL, 'boolean', 7),
									(18, 'DiaMailSemanal', 'Fri', NULL, 'select;Mon;Tue;Wed;Thu;Fri;Sat;Sun', 7);";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `factura` (
								`id_factura` int(11) NOT NULL auto_increment,
								`numero` int(11) NOT NULL default '0',
								`fecha` date NOT NULL default '0000-00-00',
								`cliente` varchar(100) default NULL,
								`RUT_cliente` varchar(20) default NULL COMMENT 'En Colombia se usa NIT en vez de RUT',
								`direccion_cliente` varchar(255) default NULL,
								`subtotal` int(11) NOT NULL default '0',
								`iva` int(11) NOT NULL default '0',
								`total` int(11) NOT NULL default '0',
								`descripcion` varchar(255) NOT NULL default '',
								`numeracion_papel_desde` int(11) NOT NULL default '0',
								`numeracion_papel_hasta` int(11) NOT NULL default '0',
								`numeracion_computador_desde` int(11) NOT NULL default '0',
								`numeracion_computador_hasta` int(11) NOT NULL default '0',
								`id_cobro` int(11) default NULL,
								`fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
								`fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
								PRIMARY KEY  (`id_factura`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO `menu` (`codigo`, `glosa`, `url`, `descripcion`, `foto_url`, `tipo`, `orden`, `codigo_padre`)
								VALUES ('CONF', 'Configuración', '/app/interfaces/configuracion.php', '', '', '0', '45', 'ADMIN_SIS');";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO `menu_permiso` (`codigo_permiso`, `codigo_menu`) VALUES ('ADM', 'CONF');";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			echo "Recordar ingresar la función GetConf en el conf.";
		break;
		#####MOSTRAR TIPO CAMBIO NOTA DE COBRO####
		case 2.81:
			$query = "ALTER TABLE  `cobro` ADD  `opc_ver_tipo_cambio` TINYINT NOT NULL AFTER  `opc_ver_morosidad` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `contrato` ADD  `opc_ver_tipo_cambio` TINYINT NOT NULL AFTER  `opc_ver_morosidad` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		########LOGS DE RELOJ##########
		case 2.82:
			$query = "CREATE TABLE `log` (
									`id_log` int(11) NOT NULL auto_increment,
									`id_usuario` int(11) NOT NULL default '0',
									`inicio` datetime NOT NULL default '0000-00-00 00:00:00',
									`fin` datetime NOT NULL default '0000-00-00 00:00:00',
									PRIMARY KEY (`id_log`),
									KEY `id_usuario` (`id_usuario`)
									) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='log de programas espiados con el relojito';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `log_documento` (
								`id_documento` int(11) NOT NULL auto_increment,
								`id_programa` int(11) NOT NULL default '0',
								`nombre` varchar(200) NOT NULL default '',
								PRIMARY KEY (`id_documento`),
								KEY `id_programa` (`id_programa`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='documento que estaba abierto (el nombre de la ventana)';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `log_item` (
								`id_item` int(11) NOT NULL auto_increment,
								`id_log` int(11) NOT NULL default '0',
								`id_documento` int(11) NOT NULL default '0',
								`id_trabajo` int(11) default NULL,
								`tiempo` int(11) NOT NULL default '0',
								PRIMARY KEY (`id_item`),
								KEY `id_log` (`id_log`),
								KEY `id_trabajo` (`id_trabajo`),
								KEY `id_documento` (`id_documento`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tiempo usado en un doc';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `log_programa` (
								`id_programa` int(11) NOT NULL auto_increment,
								`path` varchar(200) NOT NULL default '',
								`nombre` varchar(100) NOT NULL default '',
								PRIMARY KEY (`id_programa`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='programa que estaba abierto (el .exe)';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `log_trabajo` (
								`id_trabajo` int(11) NOT NULL auto_increment,
								`id_usuario` int(11) NOT NULL default '0',
								`codigo_cliente` varchar(10) character set latin1 default NULL,
								`codigo_asunto` varchar(10) character set latin1 default NULL,
								`descripcion` mediumtext NOT NULL,
								PRIMARY KEY (`id_trabajo`),
								KEY `id_usuario` (`id_usuario`),
								KEY `codigo_asunto` (`codigo_asunto`),
								KEY `codigo_cliente` (`codigo_cliente`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='trabajo que estaba activo';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `log`
									ADD CONSTRAINT `log_ibfk_1` FOREIGN KEY (`id_usuario`)
										REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `log_documento`
								ADD CONSTRAINT `log_documento_ibfk_1` FOREIGN KEY (`id_programa`)
									REFERENCES `log_programa` (`id_programa`) ON DELETE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `log_item`
								ADD CONSTRAINT `log_item_ibfk_3` FOREIGN KEY (`id_trabajo`)
									REFERENCES `log_trabajo` (`id_trabajo`) ON DELETE CASCADE,
								ADD CONSTRAINT `log_item_ibfk_1` FOREIGN KEY (`id_log`)
									REFERENCES `log` (`id_log`) ON DELETE CASCADE,
								ADD CONSTRAINT `log_item_ibfk_2` FOREIGN KEY (`id_documento`)
									REFERENCES `log_documento` (`id_documento`) ON DELETE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `log_trabajo`
								ADD CONSTRAINT `log_trabajo_ibfk_2` FOREIGN KEY (`codigo_asunto`)
									REFERENCES `asunto` (`codigo_asunto`) ON DELETE CASCADE ON UPDATE CASCADE,
								ADD CONSTRAINT `log_trabajo_ibfk_3` FOREIGN KEY (`id_usuario`)
									REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
								ADD CONSTRAINT `log_trabajo_ibfk_4` FOREIGN KEY (`codigo_cliente`)
									REFERENCES `cliente` (`codigo_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		###### modificaciones usuario maximo dias ingreso trabajo, area #####
		case 2.83:
			$query = "CREATE TABLE `prm_area_usuario` (
							`id` int(11) NOT NULL auto_increment,
							`glosa` varchar(128) NOT NULL default '',
							PRIMARY KEY  (`id`)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO `prm_area_usuario` (`id`, `glosa`) VALUES (1, 'Corporativo'),
								(2, 'Tributario');";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `usuario` ADD  `id_area_usuario` int(11) NOT NULL default '1' AFTER `id_categoria_usuario` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `usuario` ADD  `dias_ingreso_trabajo` int(11) NOT NULL default '60' AFTER  `id_area_usuario` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE `usuario`
								ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`id_area_usuario`)
								REFERENCES `prm_area_usuario` (`id`) ON UPDATE CASCADE;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		###### restriccion mensual #####
		case 2.84:
			$query = "ALTER TABLE  `usuario` ADD  `restriccion_mensual` int(11) NOT NULL default '0' AFTER alerta_semanal ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		##### tareas #####
		case 2.85:
			$query = "CREATE TABLE `usuario_proyeccion` (
									`id_proyeccion` int(10) unsigned NOT NULL default '0',
									`id_usuario` int(10) unsigned NOT NULL default '0',
									`horasatrabajar` time default NULL
								) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `tarea_comentario_usuario` (
									`id_comentario` int(11) NOT NULL default '0',
									`id_usuario` int(11) NOT NULL default '0',
									PRIMARY KEY  (`id_comentario`,`id_usuario`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='manejo de novedades';";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `tarea_comentario` (
									`id_comentario` int(11) NOT NULL auto_increment,
									`id_tarea` int(11) NOT NULL default '0',
									`id_usuario` int(11) NOT NULL default '0',
									`id_archivo` int(11) default NULL,
									`comentario` text character set latin1 NOT NULL,
									`fecha_avance` date default NULL,
									`duracion_avance` time default NULL,
									`estado` varchar(220) character set latin1 NOT NULL default 'Por Asignar',
									`id_trabajo` int(11) default NULL,
									`fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
									`fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
									PRIMARY KEY  (`id_comentario`),
									KEY `id_tarea` (`id_tarea`,`id_usuario`),
									KEY `id_trabajo` (`id_trabajo`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='comentarios de una tarea' ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "CREATE TABLE `tarea` (
									`id_tarea` int(11) NOT NULL auto_increment,
									`codigo_cliente` varchar(10) character set latin1 NOT NULL default '0',
									`codigo_asunto` varchar(10) character set latin1 NOT NULL default '0',
									`usuario_encargado` int(11) default NULL,
									`usuario_generador` int(11) NOT NULL default '0',
									`usuario_revisor` int(11) NOT NULL default '0',
									`usuario_registro` int(11) NOT NULL default '0',
									`nombre` varchar(50) character set latin1 NOT NULL default '',
									`detalle` varchar(200) character set latin1 NOT NULL default '',
									`estado` varchar(20) character set latin1 NOT NULL default 'Por Asignar',
									`orden_estado` tinyint(2) NOT NULL default '1',
									`tiempo_estimado` time NOT NULL default '00:00:00',
									`fecha_entrega` datetime NOT NULL default '0000-00-00 00:00:00',
									`fecha_ultima_novedad` datetime default NULL,
									`fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
									`fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
									PRIMARY KEY  (`id_tarea`),
									KEY `codigo_cliente` (`codigo_cliente`,`codigo_asunto`,`usuario_encargado`,`usuario_generador`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;

		### Gasto -tipo  -n° documento
		case 2.86:
		$query = array();
		$query[] = "ALTER TABLE  `cta_corriente` ADD  `id_cta_corriente_tipo` INT( 11 ) NULL DEFAULT NULL AFTER  `id_usuario_orden` ;";

		$query[] = "CREATE TABLE  `prm_cta_corriente_tipo` (
		 `id_cta_corriente_tipo` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		 `glosa` VARCHAR( 30 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
		) ENGINE = INNODB;";

		$query[] = "ALTER TABLE  `cta_corriente` ADD INDEX (  `id_cta_corriente_tipo` )";

		$query[] = "INSERT INTO  `prm_cta_corriente_tipo` (  `id_cta_corriente_tipo` ,  `glosa` )
		VALUES (
		NULL ,  'Cargo contra documento'
		), (
		NULL ,  'Cargo Procurador'
		), (
		NULL ,  'Teléfono'
		), (
		NULL ,  'Fotocopias'
		);";

		$query[] = "ALTER TABLE  `cta_corriente` ADD  `numero_documento` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER  `cobrable` ;";

		foreach($query as $q)
		{
			if(! ($resp = mysql_query($q,$dbh)))
				throw new Exception($q ."---".mysql_error());
		}
		break;
		##### Número OT #####
		case 2.87:
			$query = "ALTER TABLE  `cta_corriente` ADD  `numero_ot` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER  `numero_documento` ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		##### Ultimo movimiento en carpetas #####
		case 2.88:
			$query = "CREATE TABLE  `prm_tipo_movimiento_carpeta` (
									`id_tipo_movimiento_carpeta` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
									`glosa_tipo_movimiento_carpeta` VARCHAR( 50 ) NOT NULL
									) ENGINE = INNODB;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `carpeta` ADD  `id_tipo_movimiento_carpeta` INT NULL ,
									ADD  `id_usuario_ultimo_movimiento` INT NULL ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `carpeta` ADD INDEX (  `id_tipo_movimiento_carpeta` ) ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `carpeta` ADD INDEX (  `id_usuario_ultimo_movimiento` );";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "INSERT INTO  `prm_tipo_movimiento_carpeta` (  `id_tipo_movimiento_carpeta` ,  `glosa_tipo_movimiento_carpeta` ) VALUES
									(NULL ,  'Egresada'),
									(NULL ,  'Ingresada'),
									(NULL ,  'No fue encontrada');";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());
		break;
		######### USUARIO MODIFICACION EN CARPETAS Y OPCION DE VER SOLICITANTE #########
		case 2.89:
			$query = "ALTER TABLE  `carpeta` ADD  `id_usuario_modificacion` INT NULL ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `carpeta` ADD INDEX (  `id_usuario_modificacion` ) ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `contrato` ADD opc_ver_solicitante INT NOT NULL DEFAULT '1' ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

			$query = "ALTER TABLE  `cobro` ADD opc_ver_solicitante INT NOT NULL DEFAULT '1' ;";
			if(! ($resp = mysql_query($query,$dbh)))
				throw new Exception($query ."---".mysql_error());

		break;

# Se agrega prioridades a tareas
      case 2.90:
	      $query = array();
   	   $query[] = "ALTER TABLE `tarea` ADD `prioridad` INT NOT NULL DEFAULT '0' AFTER `tiempo_estimado` ;";
     		$query[] = "ALTER TABLE `tarea` CHANGE `prioridad` `prioridad` INT( 11 ) NULL;";
			$query[] = "UPDATE tarea SET  prioridad = NULL;";
		$query[] = "ALTER TABLE  `carpeta` ADD  `nombre` VARCHAR( 255 ) NULL ;";
     		foreach($query as $q)
      	{
         	if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
      	}
      break;

		###### Tarifas por Categoria, Correo de modificacion de contrato, mejoras en historial #####
		case 2.91:
			$query = array();
			$query[] = "CREATE TABLE `categoria_tarifa` (
										`id_categoria_tarifa` int(11) NOT NULL auto_increment,
										`id_categoria_usuario` int(11) default NULL,
										`id_moneda` int(11) default NULL,
										`tarifa` double(15,2) default NULL,
										`id_tarifa` int(11) NOT NULL default '0',
										PRIMARY KEY  (`id_categoria_tarifa`),
										UNIQUE KEY `id_categoria2` (`id_categoria_usuario`,`id_moneda`,`id_tarifa`),
										KEY `id_moneda` (`id_moneda`),
										KEY `id_tarifa` (`id_tarifa`),
										CONSTRAINT `categoria_tarifa_ibfk_12` FOREIGN KEY (`id_tarifa`) REFERENCES `tarifa` (`id_tarifa`) ON UPDATE CASCADE,
										CONSTRAINT `categoria_tarifa_ibfk_10` FOREIGN KEY (`id_categoria_usuario`) REFERENCES `prm_categoria_usuario` (`id_categoria_usuario`) ON UPDATE CASCADE,
										CONSTRAINT `categoria_tarifa_ibfk_11` FOREIGN KEY (`id_moneda`) REFERENCES `prm_moneda` (`id_moneda`) ON UPDATE CASCADE
										) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$query[] = "CREATE TABLE `modificaciones_contrato` (
										`id_log_contrato` int(11) NOT NULL auto_increment,
										`id_contrato` int(11) NOT NULL default '0',
										`fecha_creacion` timestamp NOT NULL default '0000-00-00 00:00:00',
										`fecha_modificacion` timestamp NOT NULL default '0000-00-00 00:00:00',
										`id_usuario` int(11) NOT NULL default '0',
										`id_usuario_responsable` int(11) default NULL,
										`fecha_enviado` datetime NOT NULL default '0000-00-00 00:00:00',
										PRIMARY KEY  (`id_log_contrato`),
										KEY `id_usuario` (`id_usuario`),
										KEY `id_contrato` (`id_contrato`),
										KEY `id_usuario_responsable` (`id_usuario_responsable`),
										CONSTRAINT `modificaciones_contrato_ibfk_3` FOREIGN KEY (`id_usuario_responsable`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
										CONSTRAINT `modificaciones_contrato_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE
										) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$query[] = "ALTER TABLE `contrato` ADD COLUMN `correos_edicion` VARCHAR(255) COLLATE latin1_swedish_ci NOT NULL DEFAULT '' COMMENT 'correos que se avisa modificacion';";
			$query[] = "ALTER TABLE `trabajo_historial` ADD COLUMN `codigo_asunto` VARCHAR(10) COLLATE latin1_swedish_ci DEFAULT NULL;";
			$query[] = "ALTER TABLE `trabajo_historial` ADD COLUMN `cobrable` TINYINT(4) NOT NULL DEFAULT '0';";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### CORRECCIONES DE ALERTAS Y CARPETAS #####
		case 2.92:
		$query = array();
		$query[] = "ALTER TABLE  `asunto` ADD  `notificado_hr_excedido` TINYINT( 4 ) NOT NULL DEFAULT  '0' ;";
		$query[] = "ALTER TABLE  `asunto` ADD  `notificado_monto_excedido` TINYINT( 4 ) NOT NULL DEFAULT  '0' ;";
		$query[] = "ALTER TABLE  `asunto` ADD  `notificado_hr_excedida_ult_cobro` TINYINT( 4 ) NOT NULL DEFAULT  '0' ;";
		$query[] = "ALTER TABLE  `asunto` ADD  `notificado_monto_excedido_ult_cobro` TINYINT( 4 ) NOT NULL DEFAULT  '0' ;";
		$query[] = "ALTER TABLE  `carpeta` CHANGE  `nombre`  `nombre_carpeta` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;";
		foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### PANEL DE CONTROL DE LEMONTECH CATEGORIAS ####
		case 2.93:
			$query[] = "ALTER TABLE  `prm_categoria_usuario` ADD  `id_categoria_lemontech` INT NOT NULL ;";
			$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech=1 WHERE id_categoria_usuario IN (1,2,3,108,109);";
			$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech=2 WHERE id_categoria_usuario IN (4,5,6,110);";
			$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech=3 WHERE id_categoria_usuario IN (7,8,9,111,113);";
			$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech=4 WHERE id_categoria_usuario IN (10,112);";
			$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech=5 WHERE id_categoria_usuario IN (11);";
			$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech=6 WHERE id_categoria_lemontech=0;";


			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		###### GASTOS INCOBRABLES ######
		case 2.94:
			$query[] = "ALTER TABLE  `cta_corriente` ADD  `monto_cobrable` DOUBLE NOT NULL ;";
			$query[] = "UPDATE cta_corriente SET monto_cobrable=egreso;";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### NOTIFICACIONES PAGINA INICIAL ####
		case 2.95:
			$query = array();
			$query[] = "CREATE TABLE `notificacion` (
									`id_notificacion` int(11) NOT NULL auto_increment,
									`fecha` datetime NOT NULL default '0000-00-00 00:00:00',
									`texto_notificacion` text NOT NULL,
									PRIMARY KEY  (`id_notificacion`)
									) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
			$query[] = "ALTER TABLE usuario ADD `id_notificacion_tt` int(11) NOT NULL default '0';";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		###### CORREOS DE NOTIFICACION DE TAREAS #####
		case 2.96:
			$query = array();
			$query[] = "CREATE TABLE `log_tarea` (
								`id_log_tarea` int(11) NOT NULL auto_increment,
								`subject` varchar(255) NOT NULL default '',
								`mensaje` text NOT NULL,
								`fecha` datetime NOT NULL default '0000-00-00 00:00:00',
								`mail` varchar(100) NOT NULL default '',
								`nombre` varchar(30) NOT NULL default '',
								`enviado` tinyint(1) NOT NULL default '0',
								PRIMARY KEY  (`id_log_tarea`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### CAMBIOS EN CARPETAS Y SUBIR EXCEL ####
		case 2.97:
			$query = array();
			$query[] = "CREATE TABLE `trabajo_respaldo_excel` (
									`id_trabajo_respaldo_excel` int(11) NOT NULL auto_increment,
									`id_trabajo` int(11) NOT NULL default '0',
									`fecha` date NOT NULL default '0000-00-00',
									`solicitante` varchar(75) default NULL,
									`descripcion` mediumtext NOT NULL,
									`duracion_cobrada` time NOT NULL default '00:00:00',
									PRIMARY KEY  (`id_trabajo_respaldo_excel`)
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$query[] = "ALTER TABLE `carpeta` ADD COLUMN `fecha_ultimo_movimiento` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';";
			$query[] = "ALTER TABLE `trabajo_historial` ADD COLUMN `id_trabajo_respaldo_excel` INTEGER(11) DEFAULT NULL;";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### tabla correos ####
		case 2.98:
			$query = array();
			$query[] = "RENAME TABLE log_tarea TO log_correo;";
			$query[] = "ALTER TABLE  `log_correo` CHANGE  `id_log_tarea`  `id_log_correo` INT( 11 ) NOT NULL AUTO_INCREMENT";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### guardado en tarifas ####
		case 2.99:
			$query = array();
			$query[] = "ALTER TABLE tarifa ADD COLUMN guardado int(1) NOT NULL default '0'";
			$query[] = "UPDATE tarifa SET guardado=1";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### parametrización glosas excel ####
		case 3.00:
			$query = array();
			$query[] = "CREATE TABLE `prm_excel_cobro` (
										`id_prm_excel_cobro` int(11) NOT NULL auto_increment,
										`nombre_interno` varchar(60) NOT NULL default '',
										`glosa` varchar(60) NOT NULL default '',
										`grupo` varchar(60) NOT NULL default '',
										PRIMARY KEY  (`id_prm_excel_cobro`)
									) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=50 ;";
			$query[] = "INSERT INTO `prm_excel_cobro` (`id_prm_excel_cobro`, `nombre_interno`, `glosa`, `grupo`) VALUES (1, 'id_trabajo', 'Nº', 'Listado de trabajos'),
										(2, 'fecha', 'Fecha', 'Listado de trabajos'),
										(3, 'abogado', 'Abogado', 'Listado de trabajos'),
										(4, 'asunto', 'Asunto', 'Listado de trabajos'),
										(5, 'solicitante', 'Solicitante', 'Listado de trabajos'),
										(6, 'descripcion', 'Descripción', 'Listado de trabajos'),
										(7, 'duracion_trabajada', 'Duración Trabajada', 'Listado de trabajos'),
										(8, 'duracion_cobrable', 'Duración', 'Listado de trabajos'),
										(9, 'duracion_retainer', 'Duración Retainer', 'Listado de trabajos'),
										(10, 'cobrable', 'Cobrable', 'Listado de trabajos'),
										(11, 'tarifa_hh', 'Tarifa (%glosa_moneda%)', 'Listado de trabajos'),
										(12, 'valor_trabajo', 'Valor (%glosa_moneda%)', 'Listado de trabajos'),
										(13, 'cliente', 'Cliente', 'Encabezado'),
										(14, 'direccion', 'Dirección', 'Encabezado'),
										(15, 'rut', 'RUT', 'Encabezado'),
										(16, 'contacto', 'Contacto', 'Encabezado'),
										(17, 'telefono', 'Teléfono', 'Encabezado'),
										(18, 'titulo', 'Resumen cobro', 'Resumen'),
										(19, 'fecha', 'Fecha:', 'Resumen'),
										(20, 'fecha_desde', 'Fecha desde:', 'Resumen'),
										(21, 'fecha_hasta', 'Fecha hasta:', 'Resumen'),
										(22, 'forma_cobro', 'Tipo de Honorarios:', 'Resumen'),
										(23, 'horas_retainer', 'Horas Retainer', 'Resumen'),
										(24, 'monto_retainer', 'Monto Retainer', 'Resumen'),
										(25, 'monto_cap_inicial', 'Monto Inicial Cap', 'Resumen'),
										(26, 'monto_cap_usado', 'Monto Cap utilizado', 'Resumen'),
										(27, 'monto_cap_restante', 'Monto Restante Cap', 'Resumen'),
										(28, 'total_horas', 'Total horas:', 'Resumen'),
										(29, 'honorarios', 'Honorarios:', 'Resumen'),
										(30, 'equivalente', 'Equivalente a:', 'Resumen'),
										(31, 'descuento', 'Descuento:', 'Resumen'),
										(32, 'subtotal', 'Subtotal:', 'Resumen'),
										(33, 'gastos', 'Gastos:', 'Resumen'),
										(34, 'impuesto', 'IVA:', 'Resumen'),
										(35, 'total_cobro', 'Total cobro:', 'Resumen'),
										(36, 'titulo', 'Detalle profesional', 'Detalle profesional'),
										(37, 'nombre', 'Nombre', 'Detalle profesional'),
										(38, 'horas_trabajadas', 'Hr. Trabajadas', 'Detalle profesional'),
										(39, 'horas_cobrables', 'Hr. Cobrables', 'Detalle profesional'),
										(40, 'horas_tarificadas', 'Hr. Tarificadas', 'Detalle profesional'),
										(41, 'tarifa_hh', 'Tarifa HH. (%glosa_moneda%)', 'Detalle profesional'),
										(42, 'total', 'Total (%glosa_moneda%)', 'Detalle profesional'),
										(43, 'titulo', 'Gastos', 'Listado de gastos'),
										(44, 'fecha', 'Fecha', 'Listado de gastos'),
										(45, 'descripcion', 'Descripción', 'Listado de gastos'),
										(46, 'monto', 'Monto', 'Listado de gastos'),
										(47, 'horas_retainer', 'Hr. Retainer', 'Detalle profesional'),
										(48, 'factura', 'Factura Nº', 'Resumen'),
										(49, 'minuta', 'Minuta de cobro Nº', 'Encabezado');";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		case 3.01:
			$query = array();
			$query[] = "ALTER TABLE trabajo ADD `id_tramite` int(11) NOT NULL default '0';";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### parametrización glosas en excel (español e ingles) ####
		case 3.02:
			$query = array();
			$query[] = "ALTER TABLE `prm_excel_cobro` DROP COLUMN `glosa`;";
			$query[] = "ALTER TABLE `prm_excel_cobro` ADD COLUMN `glosa_es` VARCHAR(60) COLLATE utf8_general_ci NOT NULL DEFAULT '';";
			$query[] = "ALTER TABLE `prm_excel_cobro` ADD COLUMN `glosa_en` VARCHAR(60) COLLATE utf8_general_ci NOT NULL DEFAULT '';";
			$query[] = "UPDATE `prm_excel_cobro` SET `nombre_interno` = 'id_trabajo', `glosa_es` = 'Nº', `glosa_en` = 'Nº', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 1;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'fecha', `glosa_es` = 'Fecha', `glosa_en` = 'Date', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 2;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'abogado', `glosa_es` = 'Abogado', `glosa_en` = 'Lawyer', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 3;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'asunto', `glosa_es` = 'Asunto', `glosa_en` = 'Matter', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 4;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'solicitante', `glosa_es` = 'Solicitante', `glosa_en` = 'Solicitante', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 5;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'descripcion', `glosa_es` = 'Descripción', `glosa_en` = 'Description', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 6;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'duracion_trabajada', `glosa_es` = 'Duración Trabajada', `glosa_en` = 'Worked duration', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 7;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'duracion_cobrable', `glosa_es` = 'Duración', `glosa_en` = 'Collectible duration', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 8;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'duracion_retainer', `glosa_es` = 'Duración Retainer', `glosa_en` = 'Retained duration', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 9;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'cobrable', `glosa_es` = 'Cobrable', `glosa_en` = 'Chargeable', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 10;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'tarifa_hh', `glosa_es` = 'Tarifa (%glosa_moneda%)', `glosa_en` = 'Rate (%glosa_moneda%)', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 11;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'valor_trabajo', `glosa_es` = 'Valor (%glosa_moneda%)', `glosa_en` = 'Amount (%glosa_moneda%)', `grupo` = 'Listado de trabajos' WHERE  `id_prm_excel_cobro` = 12;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'cliente', `glosa_es` = 'Cliente', `glosa_en` = 'Client', `grupo` = 'Encabezado' WHERE  `id_prm_excel_cobro` = 13;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'direccion', `glosa_es` = 'Dirección', `glosa_en` = 'Address', `grupo` = 'Encabezado' WHERE  `id_prm_excel_cobro` = 14;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'rut', `glosa_es` = 'RUT', `glosa_en` = 'Tax Payer Number', `grupo` = 'Encabezado' WHERE  `id_prm_excel_cobro` = 15;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'contacto', `glosa_es` = 'Contacto', `glosa_en` = 'Contact', `grupo` = 'Encabezado' WHERE  `id_prm_excel_cobro` = 16;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'telefono', `glosa_es` = 'Teléfono', `glosa_en` = 'Phone Number', `grupo` = 'Encabezado' WHERE  `id_prm_excel_cobro` = 17;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'titulo', `glosa_es` = 'Resumen cobro', `glosa_en` = 'Invoice summary', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 18;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'fecha', `glosa_es` = 'Fecha:', `glosa_en` = 'Date:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 19;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'fecha_desde', `glosa_es` = 'Fecha desde:', `glosa_en` = 'From:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 20;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'fecha_hasta', `glosa_es` = 'Fecha hasta:', `glosa_en` = 'To:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 21;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'forma_cobro', `glosa_es` = 'Tipo de Honorarios:', `glosa_en` = 'Fee type:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 22;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'horas_retainer', `glosa_es` = 'Horas Retainer', `glosa_en` = 'Hrs Retainer', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 23;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'monto_retainer', `glosa_es` = 'Monto Retainer', `glosa_en` = 'Amount Retainer', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 24;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'monto_cap_inicial', `glosa_es` = 'Monto Inicial Cap', `glosa_en` = 'Initial Cap Amount', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 25;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'monto_cap_usado', `glosa_es` = 'Monto Cap utilizado', `glosa_en` = 'Used Cap Amount', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 26;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'monto_cap_restante', `glosa_es` = 'Monto Restante Cap', `glosa_en` = 'Cap Amount Left', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 27;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'total_horas', `glosa_es` = 'Total horas:', `glosa_en` = 'Total hours:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 28;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'honorarios', `glosa_es` = 'Honorarios:', `glosa_en` = 'Fees:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 29;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'equivalente', `glosa_es` = 'Equivalente a:', `glosa_en` = 'Equivalent to:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 30;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'descuento', `glosa_es` = 'Descuento:', `glosa_en` = 'Discount:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 31;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'subtotal', `glosa_es` = 'Subtotal:', `glosa_en` = 'Subtotal:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 32;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'gastos', `glosa_es` = 'Gastos:', `glosa_en` = 'Disbursements:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 33;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'impuesto', `glosa_es` = 'IVA:', `glosa_en` = 'TAX:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 34;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'total_cobro', `glosa_es` = 'Total cobro:', `glosa_en` = 'Total:', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 35;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'titulo', `glosa_es` = 'Detalle profesional', `glosa_en` = 'Summary of fees', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 36;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'nombre', `glosa_es` = 'Nombre', `glosa_en` = 'Name', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 37;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'horas_trabajadas', `glosa_es` = 'Hr. Trabajadas', `glosa_en` = 'Hrs Worked', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 38;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'horas_cobrables', `glosa_es` = 'Hr. Cobrables', `glosa_en` = 'Chargeable Hrs', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 39;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'horas_tarificadas', `glosa_es` = 'Hr. Tarificadas', `glosa_en` = 'Hours to pricing', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 40;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'tarifa_hh', `glosa_es` = 'Tarifa HH. (%glosa_moneda%)', `glosa_en` = 'Rate HH. (%glosa_moneda%)', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 41;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'total', `glosa_es` = 'Total (%glosa_moneda%)', `glosa_en` = 'Total (%glosa_moneda%)', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 42;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'titulo', `glosa_es` = 'Gastos', `glosa_en` = 'Expenses', `grupo` = 'Listado de gastos' WHERE  `id_prm_excel_cobro` = 43;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'fecha', `glosa_es` = 'Fecha', `glosa_en` = 'Date', `grupo` = 'Listado de gastos' WHERE  `id_prm_excel_cobro` = 44;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'descripcion', `glosa_es` = 'Descripción', `glosa_en` = 'Descrption', `grupo` = 'Listado de gastos' WHERE  `id_prm_excel_cobro` = 45;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'monto', `glosa_es` = 'Monto', `glosa_en` = 'Amount', `grupo` = 'Listado de gastos' WHERE  `id_prm_excel_cobro` = 46;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'horas_retainer', `glosa_es` = 'Hr. Retainer', `glosa_en` = 'Hrs Retainer', `grupo` = 'Detalle profesional' WHERE  `id_prm_excel_cobro` = 47;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'factura', `glosa_es` = 'Factura Nº', `glosa_en` = 'Invoice Nº', `grupo` = 'Resumen' WHERE  `id_prm_excel_cobro` = 48;";
			$query[] = "UPDATE prm_excel_cobro SET `nombre_interno` = 'minuta', `glosa_es` = 'Minuta de cobro Nº', `glosa_en` = 'Bill of charge N°', `grupo` = 'Encabezado' WHERE  `id_prm_excel_cobro` = 49;";
			$query[] = "ALTER TABLE cobro ADD `solo_gastos` int(1) NOT NULL default '0';";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### CAMBIOS EN FACTURA ####
		case 3.03:
			$query=array();
			$query[]="ALTER TABLE `factura` ADD COLUMN `codigo_cliente` VARCHAR(10) COLLATE latin1_swedish_ci NOT NULL DEFAULT '';";
			$query[]="ALTER TABLE `factura` ADD COLUMN `honorarios` INTEGER(11) NOT NULL DEFAULT '0';";
			$query[]="ALTER TABLE `factura` ADD COLUMN `gastos` INTEGER(11) NOT NULL DEFAULT '0';";
			$query[]="ALTER TABLE `factura` ADD COLUMN `id_moneda` INTEGER(11) NOT NULL DEFAULT '1';";
			foreach($query as $q)
			{
				if(! ($resp = mysql_query($q,$dbh)))
					throw new Exception($q ."---".mysql_error());
			}
		break;
		#### Agregación de las tablas del tema trámites ####
		case 3.04:
			$query=array();
			$query[]="ALTER TABLE `contrato` ADD COLUMN `id_tramite_tarifa` INT(11) NULL";
			$query[]="ALTER TABLE `contrato` ADD COLUMN `id_moneda_tramite` INT(11) NOT NULL DEFAULT '1'";
			$query[]="CREATE TABLE `tramite` (
								  `id_tramite` int(11) NOT NULL auto_increment,
								  `codigo_asunto` varchar(10) NOT NULL default '',
								  `fecha` date NOT NULL default '0000-00-00',
								  `id_tramite_tipo` int(11) NOT NULL default '0',
								  `trabajo_si_no` int(1) NOT NULL default '0',
								  `cobrable` tinyint(4) NOT NULL default '1',
								  `duracion` time NOT NULL default '00:00:00',
								  `descripcion` mediumtext character set latin1,
								  `id_usuario` int(11) NOT NULL default '0',
								  `id_cobro` int(11) default NULL,
								  `revisado` tinyint(4) NOT NULL default '0',
								  `fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
								  `fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
								  PRIMARY KEY  (`id_tramite`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=78;";
			$query[]="CREATE TABLE `tramite_tarifa` (
								  `id_tramite_tarifa` int(11) NOT NULL auto_increment,
								  `glosa_tramite_tarifa` varchar(30) default NULL,
								  `fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
								  `fecha_modificacion` datetime default NULL,
								  `tarifa_defecto` int(1) NOT NULL default '0',
								  `guardado` int(1) NOT NULL default '0',
								  PRIMARY KEY  (`id_tramite_tarifa`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;";
			$query[]="CREATE TABLE `tramite_tipo` (
								  `id_tramite_tipo` int(11) NOT NULL auto_increment,
								  `glosa_tramite` varchar(60) default NULL,
								  `duracion_defecto` time NOT NULL default '00:00:00',
								  `trabajo_si_no_defecto` tinyint(1) NOT NULL default '0',
								  `fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
								  `fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
								  PRIMARY KEY  (`id_tramite_tipo`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=89 ;";
			$query[]="CREATE TABLE `tramite_valor`(
									`id_tramite_valor` int(11) NOT NULL auto_increment,
									`id_tramite_tipo` int(11) default NULL,
									`id_moneda` int(11) default NULL,
									`tarifa` double(15,2) default NULL,
									`id_tramite_tarifa` int(11) NOT NULL default '0',
									PRIMARY KEY (`id_tramite_valor`),
									UNIQUE KEY `id_tramite_tarifa_2` (`id_tramite_tipo`,`id_moneda`,`id_tramite_tarifa`)
									) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=346;";
			$query[]="UPDATE `menu` SET `glosa` =  'Gestión', `url` = NULL ,
								`codigo_padre` = NULL WHERE CONVERT( `codigo` USING utf8 ) = 'PRO' LIMIT 1;";
			$query[]= "INSERT INTO menu ( codigo , glosa , url , descripcion , foto_url , tipo , orden , codigo_padre )
									VALUES ( 'ADM_TRA', 'Adm. Trámites' , '/app/interfaces/tramites.php' , NULL, NULL, '0' , '25' , 'ADM'),
												( 'TRA_HRS' , 'Trámites' , '/app/interfaces/horas_tramites.php', NULL , NULL , '0' , '15' , 'PRO' ),
												( 'TAR_TRA' , 'Tarifa trámites' , '/app/interfaces/tarifas_tramites.php?id_tramite_tarifa_edicion=2' , NULL , NULL , '0' , '56' , 'COBRANZA') ;";
			$query[]= "INSERT INTO menu_permiso ( codigo_permiso, codigo_menu )
									VALUES('ADM' , 'ADM_TRA'),
												('PRO' , 'TRA_HRS'),
												('COB' , 'TAR_TRA');";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
						throw new Exception($q ."---".mysql_error());
				}
			break;
		case 3.05:
			$query=array();
			$query[]="UPDATE `menu` SET `codigo_padre` = 'ADMIN_SYS' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ADM_TRA' LIMIT 1 ;";
			$query[]="UPDATE `menu` SET `codigo_padre` = 'ADMIN_SIS' WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'ADM_TRA' LIMIT 1 ;";
			$query[]="INSERT INTO `tramite_tarifa` ( `id_tramite_tarifa` , `glosa_tramite_tarifa` , `fecha_creacion` , `fecha_modificacion` , `tarifa_defecto` , `guardado` )
			VALUES (
			'1', 'Estandar', '0000-00-00 00:00:00', NULL , '1', '1'
			);
			";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
						throw new Exception($q ."---".mysql_error());
				}
			break;
      case 3.06:
         $query=array();
         $query[]="ALTER TABLE `cobro` ADD `subtotal_gastos` DOUBLE NOT NULL AFTER `total_minutos` ,
ADD `impuesto_gastos` DOUBLE NOT NULL AFTER `subtotal_gastos` ;";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
                  throw new Exception($q ."---".mysql_error());
            }
         break;

			case 3.07:
					$query=array();
					$query[]="UPDATE `menu` SET `url` =  '/app/interfaces/tarifas_tramites.php?id_tramite_tarifa_edicion=1'  WHERE CONVERT( `menu`.`codigo` USING utf8 ) = 'TAR_TRA' LIMIT 1 ;";
				foreach($query as $q)
							{
								if(! ($resp = mysql_query($q,$dbh)))
			                  throw new Exception($q ."---".mysql_error());
			        }
         break;

      case 3.08:
      		$query=array();
      		$query[]="INSERT INTO `prm_excel_cobro` (`id_prm_excel_cobro`, `nombre_interno`, `glosa_es`, `glosa_en`, `grupo`) VALUES (50, 'titulo', 'Trámites', 'Trámites', 'Listado de trámites'),
											(51, 'fecha', 'Fecha', 'Date', 'Listado de trámites'),
											(52, 'id_trabajo', 'N°', 'N°', 'Listado de trámites'),
											(53, 'abogado', 'Abogado', 'Lawyer', 'Listado de trámites'),
											(54, 'asunto', 'Asunto', 'Matter', 'Listado de trámites'),
											(55, 'solicitante', 'Solicitante', 'Solicitante', 'Listado de trámites'),
											(56, 'descripcion', 'Descripción', 'Description', 'Listado de trámites'),
											(57, 'duracion', 'Duración', 'Duration', 'Listado de trámites'),
											(58, 'valor', 'Valor', 'Amount', 'Listado de trámites');";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

			case 3.09:
					$query=array();
					$query[]="ALTER TABLE `tramite_tarifa` CHANGE `guardado` `guardado` INT( 1 ) NOT NULL DEFAULT '0' COMMENT 'cuando se guarda el tarifa se pone 1, si no guarda el tarifa que ya esta creado se borra cuando salgas de la pantalla';";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

      case 3.10:
      $query=array();
      $query[]="ALTER TABLE `tramite` ADD `id_moneda_tramite` INT NULL AFTER `id_cobro` ;";
      $query[]="ALTER TABLE `tramite`  ENGINE =  innodb;";
      $query[]="ALTER TABLE `tramite_tipo`  ENGINE =  innodb;";
      $query[]="ALTER TABLE `tramite_valor` ENGINE =  innodb;";
      $query[]="ALTER TABLE `tramite_tarifa`  ENGINE =  innodb;";
      $query[]="ALTER TABLE `tramite` CHANGE `codigo_asunto` `codigo_asunto` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;";
      $query[]="ALTER TABLE `tramite` ADD INDEX `id_asunto` ( `codigo_asunto` ) ;";
      $query[]="ALTER TABLE `tramite` ADD INDEX `id_tramite_tipo` ( `id_tramite_tipo` ) ;";
      $query[]="ALTER TABLE `tramite` ADD INDEX `id_usuario` ( `id_usuario` ) ;";
      $query[]="ALTER TABLE `tramite` ADD INDEX `id_moneda` ( `id_moneda_tramite` ) ;";
      $query[]="ALTER TABLE `tramite` ADD INDEX `id_cobro` ( `id_cobro` );";
      $query[]="ALTER TABLE `tramite` ADD INDEX `fecha` ( `fecha` );";
      $query[]="ALTER TABLE `tramite` ADD INDEX `cobrable` ( `cobrable` );";
      $query[]="ALTER TABLE `tramite`
                                  ADD CONSTRAINT `tramite_ibfk_23` FOREIGN KEY (`id_moneda_tramite`) REFERENCES `prm_moneda` (`id_moneda`);";
      $query[]="ALTER TABLE `tramite`
                                  ADD CONSTRAINT `tramite_ibfk_21` FOREIGN KEY (`codigo_asunto`) REFERENCES `asunto` (`codigo_asunto`) ON UPDATE CASCADE;";
      $query[]="ALTER TABLE `tramite`
                                  ADD CONSTRAINT `tramite_ibfk_22` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;";
      $query[]="ALTER TABLE `tramite`
                                  ADD CONSTRAINT `tramite_ibfk_25` FOREIGN KEY (`id_tramite_tipo`) REFERENCES `tramite_tipo` (`id_tramite_tipo`) ON UPDATE CASCADE;";
      $query[]="ALTER TABLE `tramite`
                                  ADD CONSTRAINT `tramite_ibfk_9` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE SET NULL ON UPDATE CASCADE;";


			$query[]="ALTER TABLE `tramite_valor` ADD INDEX `id_tramite_tipo` (`id_tramite_tipo` );";
			$query[]="ALTER TABLE `tramite_valor` ADD INDEX `id_moneda` ( `id_moneda` );";
			$query[]="ALTER TABLE `tramite_valor` ADD INDEX `id_tramite_tarifa` ( `id_tramite_tarifa` );";
            $query[]="ALTER TABLE `tramite_valor`
                                  ADD CONSTRAINT `tramite_valor_ibfk_3` FOREIGN KEY (`id_tramite_tarifa`) REFERENCES `tramite_tarifa` (`id_tramite_tarifa`) ON DELETE CASCADE ON UPDATE CASCADE;";
            $query[]="ALTER TABLE `tramite_valor`
                                  ADD CONSTRAINT `tramite_valor_ibfk_1` FOREIGN KEY (`id_tramite_tipo`) REFERENCES `tramite_tipo` (`id_tramite_tipo`) ON DELETE CASCADE ON UPDATE CASCADE;";
            $query[]="ALTER TABLE `tramite_valor`
                                  ADD CONSTRAINT `tramite_valor_ibfk_2` FOREIGN KEY (`id_moneda`) REFERENCES `prm_moneda` (`id_moneda`) ON DELETE CASCADE ON UPDATE CASCADE;";

			$query[]="UPDATE `menu` SET `glosa` =  'Tareas',
												`url` = '/app/interfaces/tareas.php',
												`codigo_padre` = 'PRO' WHERE CONVERT( `codigo` USING utf8 ) = 'GESTION' LIMIT 1 ;";
			$query[]="DELETE FROM `menu` WHERE `codigo` = 'PROFESIONL' LIMIT 1;";
			$query[]="ALTER TABLE `tramite` ADD `tarifa_tramite` DOUBLE NULL AFTER `descripcion` ;";
			$query[]="ALTER TABLE `tramite`
									ADD `tarifa_tramite_defecto` DOUBLE NULL AFTER `tarifa_tramite` ,
									ADD `tarifa_tramite_estandar` DOUBLE NULL AFTER `tarifa_tramite_defecto` ;";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

      case 3.11:
      $query=array();
      $query[]="ALTER TABLE `cobro`
													ADD `monto_trabajos` DOUBLE NULL AFTER `id_moneda_monto` ,
													ADD `monto_tramites` DOUBLE NULL AFTER `monto_trabajos` ;";
			$query[]="UPDATE `cobro` SET monto_trabajos=(monto-impuesto);";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

      case 3.12:
      $query=array();
      $query[]="ALTER TABLE `cobro` DROP `opc_moneda_total_tipo_cambio`;";
      $query[]="CREATE TABLE `factura_rtf` (
								  `id_factura_formato` int(11) NOT NULL auto_increment,
								  `factura_template` text character set latin1 NOT NULL,
								  `factura_css` text character set latin1 NOT NULL,
								  PRIMARY KEY  (`id_factura_formato`)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
			$query[]="DELETE FROM `configuracion` WHERE `id` = 1 LIMIT 1;";
			$query[]="DELETE FROM `configuracion` WHERE `id` = 2 LIMIT 1;";
			$query[]="DELETE FROM `configuracion` WHERE `id` = 4 LIMIT 1;";
			$query[]="CREATE TABLE `configuracion_categoria` (
												`id_configuracion_categoria` INT(11) NOT NULL auto_increment,
												`glosa_configuracion_categoria` varchar(30) character set latin1 NOT NULL default '',
												PRIMARY KEY (`id_configuracion_categoria`)
											) ENGINE=Innodb DEFAULT CHARSET=utf8 AUTO_INCREMENT=5;";
			$query[]="ALTER TABLE `configuracion` ADD `id_configuracion_categoria` INT(11) NOT NULL DEFAULT '0' AFTER `valores_posibles`;";
			$query[]="ALTER TABLE `configuracion` ADD INDEX ( `id_configuracion_categoria` );";
			$query[]="ALTER TABLE `configuracion` ENGINE = innodb;";
			$query[]="INSERT INTO `configuracion` ( `id` , `glosa_opcion` , `valor_opcion` , `comentario` , `valores_posibles` , `id_configuracion_categoria` , `orden` )
								VALUES ( 19 , 'CiudadSignatura', '' , 'La ciudad ingresada va a aparecer en la firma junto con la fecha del documento', 'string', '4', '425'),
											 ( 20 , 'Numeracion', '310000039689', 'Numeración autorizada por la dian mediante res. No.', 'numero','4', '430'),
											 ( 21 , 'NumeracionFecha', '2009-05-20', 'Fecha de Numeración', 'string','4', '435'),
											 ( 22 , 'NumeracionDesde', '41008', NULL , 'numero','4', '440'),
											 ( 23 , 'NumeracionHasta', '45000', NULL , 'numero','4', '445'),
											 ( 24 , 'NombreEmpresa', '' , NULL , 'string','1', '130'),
											 ( 25 , 'SubtituloEmpresa', '' , NULL , 'string','1', '135'),
											 ( 26 , 'LogoDoc', '' , NULL , 'string','1', '-1'),
											 ( 27 , 'ValorImpuesto', '16' , 'Porcentaje de impuestos', 'numero','2', '220');";
			$query[]="UPDATE `configuracion` SET `comentario` =  'Email al cual llegan los avisos del sistema.',
												`id_configuracion_categoria`='1', `orden` = '105' WHERE `id` =3 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  NULL , `id_configuracion_categoria`='1',
												`orden` = '110' WHERE `id` =5 LIMIT 1 ;";
      $query[]="UPDATE `configuracion` SET `comentario` =  'Tiempo en segundos que dura la sesión.',
												`id_configuracion_categoria`='1', `orden` = '115' WHERE `id` =6 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  NULL , `id_configuracion_categoria`='1',
												`orden` = '120' WHERE `id` =12 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  NULL , `id_configuracion_categoria`='1',
												`orden` = '125' WHERE `id` =14 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  '0 no se necesita, 1 obligatorio, 2 opcional',
												`id_configuracion_categoria`='2', `orden` = '205' WHERE `id` =13 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  NULL ,
												`id_configuracion_categoria`='2', `orden` = '210' WHERE `id` =16 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  NULL ,
												`id_configuracion_categoria`='2', `orden` = '215' WHERE `id` =17 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  'Indica si se envía un email cada vez que se crea un asunto nuevo.',
												`id_configuracion_categoria`='3', `orden` = '305' WHERE `id` =11 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  'Dejar en blanco para que no se envíen mails.',
												`id_configuracion_categoria`='3', `orden` = '310' WHERE `id` =15 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `comentario` =  NULL ,
												`id_configuracion_categoria`='3', `orden` = '315' WHERE `id` =18 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `id_configuracion_categoria`='4', `orden`='405' WHERE `id`=7 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `id_configuracion_categoria`='4', `orden`='410' WHERE `id`=8 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `id_configuracion_categoria`='4', `orden`='415' WHERE `id`=9 LIMIT 1 ;";
			$query[]="UPDATE `configuracion` SET `id_configuracion_categoria`='4', `orden`='420' WHERE `id`=10 LIMIT 1 ;";
			$query[]="INSERT INTO `configuracion_categoria` (`id_configuracion_categoria`, `glosa_configuracion_categoria`)
											VALUES (1, 'Datos Generales'),
														 (2, 'Datos Cobranza'),
														 (3, 'Configuración alertas'),
														 (4, 'Opciones documentos');";
			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

     case 3.13:
     $query=array();
     	$query[]="ALTER TABLE `contrato` ADD `opc_ver_asuntos_separados` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_moneda_total` ,
												ADD `opc_ver_horas_trabajadas` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_asuntos_separados` ,
												ADD `opc_ver_cobrable` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_horas_trabajadas` ;";
			$query[]="ALTER TABLE `cobro` ADD `opc_ver_asuntos_separados` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_resumen_cobro`  ,
												ADD `opc_ver_horas_trabajadas` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_asuntos_separados` ,
												ADD `opc_ver_cobrable` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_horas_trabajadas` ;";


      foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

      case 3.14:
      $query=array();
      $query[]="ALTER TABLE `prm_excel_cobro` ADD `tamano` INT( 11 ) NOT NULL DEFAULT '0' AFTER `glosa_en` ;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '5' WHERE `id_prm_excel_cobro` = 1 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '9' WHERE `id_prm_excel_cobro` = 2 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '10' WHERE `id_prm_excel_cobro` = 3 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '8' WHERE `id_prm_excel_cobro` = 4 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '10' WHERE `id_prm_excel_cobro` = 5 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '39' WHERE `id_prm_excel_cobro` = 6 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '7' WHERE `id_prm_excel_cobro` = 7 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '7' WHERE `id_prm_excel_cobro` = 8 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '7' WHERE `id_prm_excel_cobro` = 9 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '5' WHERE `id_prm_excel_cobro` = 10 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '8' WHERE `id_prm_excel_cobro` = 11 LIMIT 1;";
      $query[]="UPDATE `prm_excel_cobro` SET `tamano` = '14' WHERE `id_prm_excel_cobro` = 12 LIMIT 1;";


      foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

      case 3.15:
      $query=array();
      $query[]="UPDATE `prm_estado_cobro` SET `orden` = '6' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'INCOBRABLE' AND `orden` =5 LIMIT 1 ;";
      $query[]="UPDATE `prm_estado_cobro` SET `orden` = '5' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'PAGADO' AND `orden` =4 LIMIT 1 ;";
      $query[]="UPDATE `prm_estado_cobro` SET `orden` = '4' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'ENVIADO AL CLIENTE' AND `orden` =3 LIMIT 1 ;";
      $query[]="UPDATE `prm_estado_cobro` SET `orden` = '3' WHERE CONVERT( `codigo_estado_cobro` USING utf8 ) = 'EMITIDO' AND `orden` =2 LIMIT 1 ;";
      $query[]="INSERT INTO `prm_estado_cobro` ( `codigo_estado_cobro` , `orden` )
      					VALUES
      							('EN REVISION','2');";
      $query[]="ALTER TABLE `cobro` ADD `fecha_en_revision` DATETIME NULL AFTER `fecha_creacion` ;";

      foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

 			case 3.16:
 			$query=array();
			$query[]="ALTER TABLE `factura` ADD `anulado` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `id_cobro` ;";
			$query[] = 'INSERT INTO prm_forma_cobro (forma_cobro, descripcion) VALUES ("PROPORCIONAL", "Proporcional");';

			foreach($query as $q)
				{
					if(! ($resp = mysql_query($q,$dbh)))
            throw new Exception($q ."---".mysql_error());
		    }
      break;

		case 3.17:
			$query=array();
			$query[] = 'UPDATE asunto SET id_tipo_asunto = "1" WHERE id_tipo_asunto IS NULL;';
			$query[] = 'UPDATE asunto SET id_area_proyecto = "1" WHERE id_area_proyecto IS NULL;';
			$query[] = 'ALTER TABLE asunto CHANGE id_tipo_asunto id_tipo_asunto INT(11) NOT NULL DEFAULT "1",
						CHANGE id_area_proyecto id_area_proyecto INT(11) NOT NULL DEFAULT "1";';

			foreach($query as $q)
				if(!($resp = mysql_query($q, $dbh)))
					throw new Exception($q."---".mysql_error());
		break;

		case 3.18:
			$query=array();
			$query[] = 'CREATE TABLE reporte_consolidado (
							id_reporte_consolidado int(11) NOT NULL auto_increment,
							fecha_generacion timestamp NOT NULL default CURRENT_TIMESTAMP,
							periodo date NOT NULL default "0000-00-00" COMMENT "Solo se toman en cuenta el año y mes.",
							contenido longblob NOT NULL COMMENT "Archivo PDF con el reporte.",
							id_moneda int(11) NOT NULL default "0",
							PRIMARY KEY  (id_reporte_consolidado),
							UNIQUE KEY periodo (periodo)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8;';

			foreach($query as $q)
				if(!($resp = mysql_query($q, $dbh)))
					throw new Exception($q."---".mysql_error());
		break;
		
		case 3.19:
			$query=array();
			$query[] = "CREATE TABLE `prm_titulo_persona` (
										  `id_titulo` int(11) NOT NULL auto_increment,
										  `titulo` varchar(30) character set latin1 NOT NULL default '',
										  `glosa_titulo` varchar(30) default NULL,
										  PRIMARY KEY  (`id_titulo`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;";
			$query[] = "INSERT INTO `prm_titulo_persona` (`id_titulo`, `titulo`, `glosa_titulo`) 
											 VALUES (1, 'Sr.', 'Señor'),
															(2, 'Sra.', 'Señora'),
															(3, 'Srta.', 'Señorita');";
															
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
			break;
			
			case 3.20:
				$query=array();
				$query[] = "ALTER TABLE  `cobro` ADD  `nota_cobro` VARCHAR( 20 ) NULL COMMENT  'valor que se utiliza cuando tienen notas de cobros extras';";
					foreach($query as $q)
						if(!($resp = mysql_query($q, $dbh)))
							throw new Exception($q."---".mysql_error());
			break;
			
			case 3.21:
				$query=array();
				$query[] = "ALTER TABLE  `factura` CHANGE  `subtotal`  `subtotal` DOUBLE NOT NULL DEFAULT  '0',
											CHANGE  `honorarios`  `honorarios` DOUBLE NOT NULL DEFAULT  '0',
											CHANGE  `gastos`  `gastos` DOUBLE NOT NULL DEFAULT  '0',
											CHANGE  `iva`  `iva` DOUBLE NOT NULL DEFAULT  '0',
											CHANGE  `total`  `total` DOUBLE NOT NULL DEFAULT  '0';";
				$query[] = "ALTER TABLE  `documento` ADD  `subtotal_honorarios` DOUBLE NOT NULL DEFAULT  '0' AFTER  `monto` ;";
				$query[] = "ALTER TABLE  `documento` ADD  `subtotal_gastos` DOUBLE NOT NULL DEFAULT '0' AFTER `impuesto` ;";
				$query[] = "ALTER TABLE  `documento` ADD  `descuento_honorarios` DOUBLE NOT NULL DEFAULT  '0' AFTER  `subtotal_honorarios` ;";
				$query[] = "ALTER TABLE  `factura` ADD  `subtotal_gastos` DOUBLE NOT NULL DEFAULT  '0' AFTER  `honorarios` ;";
				$query[] = "ALTER TABLE  `factura` ADD  `descuento_honorarios` DOUBLE NOT NULL DEFAULT  '0' AFTER  `subtotal` ;";
				$query[] = "ALTER TABLE  `documento` ADD  `subtotal_sin_descuento` DOUBLE NOT NULL DEFAULT  '0' AFTER  `subtotal_honorarios` ;";
				$query[] = "ALTER TABLE  `factura` ADD  `subtotal_sin_descuento` DOUBLE NOT NULL DEFAULT  '0' AFTER  `subtotal` ;";
				
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
			case 3.22:
				$query=array();
				$query[] = "ALTER TABLE  `trabajo_respaldo_excel` ADD  `id_usuario` INT( 11 ) NOT NULL AFTER  `fecha` ;";
				$query[] = "ALTER TABLE  `contrato` ADD  `usa_impuesto_gastos` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
				$query[] = "ALTER TABLE  `cobro` ADD  `porcentaje_impuesto_gastos` TINYINT( 3 ) UNSIGNED NOT NULL AFTER  `porcentaje_impuesto` ;";
				$query[] = "CREATE TABLE `trabajo_respaldo_excel_eliminados` (
											  `id_trabajo` int(11) NOT NULL auto_increment,
											  `codigo_asunto` varchar(10) default NULL,
											  `id_usuario` int(11) default NULL,
											  `codigo_actividad` varchar(5) default NULL,
											  `descripcion` mediumtext,
											  `fecha` date default NULL,
											  `hora_inicio` time default NULL,
											  `duracion` time default NULL,
											  `duracion_cobrada` time default NULL,
											  `duracion_retainer` time default NULL,
											  `monto_cobrado` double default NULL COMMENT 'Se refiere al monto que aparece respecto a este trabajo en el cobro',
											  `id_moneda` int(11) NOT NULL default '1',
											  `cobrable` int(11) NOT NULL default '1',
											  `visible` int(11) NOT NULL default '1',
											  `id_cobro` int(11) default NULL,
											  `fecha_cobro` datetime default NULL,
											  `revisado` tinyint(4) NOT NULL default '0',
											  `id_trabajo_local` int(11) default NULL COMMENT 'Este es el id que se le asigna al trabajo en la base de datos local del cliente Windows. Se usa para no insertar trabajos duplicados.',
											  `solicitante` varchar(75) NOT NULL default '' COMMENT 'solicitante del trabajo',
											  `fecha_creacion` datetime default NULL,
											  `fecha_modificacion` datetime default NULL,
											  `costo_hh` double default NULL,
											  `costo_hh_monedabase` double default NULL,
											  `tarifa_hh` double default NULL,
											  `tarifa_hh_estandar` double NOT NULL default '0',
											  `id_tramite` int(11) NOT NULL default '0',
											  PRIMARY KEY  (`id_trabajo`),
											  KEY `id_actividad` (`codigo_actividad`),
											  KEY `id_usuario` (`id_usuario`),
											  KEY `id_moneda` (`id_moneda`),
											  KEY `id_asunto` (`codigo_asunto`),
											  KEY `id_cobro` (`id_cobro`),
											  KEY `cobrable` (`cobrable`),
											  KEY `fecha` (`fecha`)
											) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=13142 ;";
				
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.23:
				$query = array();
				$query[] = "ALTER TABLE  `cobro_rtf` ADD  `html_header` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER  `formato_cobro_fila_movimiento` ,
																						 ADD  `html_pie` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER  `html_header` ;";
																						 
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.24:
				$query = array();
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `id_trabajo_historial` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `fecha_trabajo` DATETIME NULL AFTER  `fecha` ,
																										 ADD  `descripcion` MEDIUMTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL AFTER  `fecha_trabajo` ;";
				$query[] = "UPDATE trabajo_historial 
											JOIN trabajo ON trabajo_historial.id_trabajo = trabajo.id_trabajo 
											 SET trabajo_historial.descripcion = trabajo.descripcion,
													 trabajo_historial.fecha = trabajo.fecha";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `duracion_cobrada` TIME NULL AFTER  `descripcion` ,
																										 ADD  `id_usuario_trabajador` INT( 11 ) NULL DEFAULT  '0' AFTER  `duracion_cobrada` ,
																										 ADD  `duracion` TIME NULL AFTER `id_usuario_trabajador` ;";
				$query[] = "UPDATE trabajo_historial 
											JOIN trabajo ON trabajo_historial.id_trabajo = trabajo.id_trabajo 
											 SET trabajo_historial.duracion_cobrada = trabajo.duracion_cobrada,
													 trabajo_historial.id_usuario_trabajador = trabajo.id_usuario";
				$query[] = "UPDATE trabajo_historial 
											JOIN trabajo ON trabajo_historial.id_trabajo = trabajo.id_trabajo 
											 SET trabajo_historial.duracion = trabajo.duracion";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `fecha_trabajo_modificado` DATE NOT NULL AFTER  `fecha_trabajo` ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `descripcion_modificado` MEDIUMTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER  `descripcion` ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `duracion_modificado` TIME NOT NULL AFTER  `duracion` ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `duracion_cobrada_modificado` TIME NULL AFTER  `duracion_cobrada` ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `id_usuario_trabajador_modificado` INT( 11 ) NULL AFTER  `id_usuario_trabajador` ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `codigo_asunto_modificado` VARCHAR( 10 ) NULL AFTER  `codigo_asunto` ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD  `cobrable_modificado` TINYINT( 4 ) NOT NULL AFTER  `cobrable` ;";
				$query[] = "UPDATE trabajo_historial 
											JOIN trabajo ON trabajo_historial.id_trabajo = trabajo.id_trabajo 
											 SET fecha_trabajo_modificado = trabajo.fecha,
													 descripcion_modificado = trabajo.descripcion,
													 duracion_modificado = trabajo.duracion,
													 duracion_cobrada_modificado = trabajo.duracion_cobrada,
													 id_usuario_trabajador_modificado = trabajo.id_usuario,
													 codigo_asunto_modificado = trabajo.codigo_asunto,
													 cobrable_modificado = trabajo.cobrable";
				
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.25:
				$query = array();
				$query[] = "ALTER TABLE  `cobro_historial` CHANGE  `es_modificacble`  `es_modificable` CHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  'SI'";
				$query[] = "CREATE TABLE `gasto_historial` (
																  `id_gasto_historial` int(11) NOT NULL default '0',
																  `id_movimiento` int(11) NOT NULL default '0',
																  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
																  `id_usuario` int(11) NOT NULL default '0',
																  `accion` varchar(10) NOT NULL default '',
																  `fecha_movimiento` datetime default NULL,
																  `fecha_movimiento_modificado` datetime default NULL,
																  `codigo_cliente` varchar(10) default NULL,
																  `codigo_cliente_modificado` varchar(10) default NULL,
																  `codigo_asunto` varchar(10) default NULL,
																  `codigo_asunto_modificado` varchar(10) default NULL,
																  `egreso` double default NULL,
																  `egreso_modificado` double default NULL,
																  `ingreso` double default NULL,
																  `ingreso_modificado` double default NULL,
																  `monto_cobrable` double NOT NULL default '0',
																  `monto_cobrable_modificado` double NOT NULL default '0',
																  `descripcion` mediumtext,
																  `descripcion_modificado` mediumtext
																) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
				$query[] = "UPDATE  `prm_excel_cobro` SET  `tamano` =  '15' WHERE  `id_prm_excel_cobro` =3 LIMIT 1;";
				$query[] = "UPDATE  `prm_excel_cobro` SET  `tamano` =  '12' WHERE  `id_prm_excel_cobro` =2 LIMIT 1;";
				$query[] = "INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('ZonaHoraria', 'America/Santiago', 'Se debe agregar el nombre de la zona horaria que utilizará el sistema', 'string', 1, -1);";
				$query[] = "ALTER TABLE  `trabajo_respaldo_excel` 
														ADD  `codigo_asunto` VARCHAR( 10 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER  `id_usuario` ,
														ADD  `id_cobro` INT( 11 ) NULL AFTER  `codigo_asunto` ;";
				$query[] = "ALTER TABLE  `gasto_historial` ADD PRIMARY KEY (  `id_gasto_historial` )";
				$query[] = "ALTER TABLE  `gasto_historial` CHANGE  `id_gasto_historial`  `id_gasto_historial` INT( 11 ) NOT NULL AUTO_INCREMENT";
				
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.26:
				$query = array();
				$query[] = "ALTER TABLE  `gasto_historial` 
														ADD  `id_moneda` INT( 11 ) NULL ,
														ADD  `id_moneda_modificado` INT( 11 ) NULL ;";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD INDEX (  `fecha` )";
				$query[] = "ALTER TABLE  `trabajo_historial` ADD INDEX (  `accion` )";
				$query[] = "UPDATE cliente 
											 SET codigo_cliente_secundario = codigo_cliente 
										 WHERE codigo_cliente_secundario = '' OR codigo_cliente_secundario IS NULL";
				$query[] = "UPDATE asunto 
											 SET codigo_asunto_secundario = codigo_asunto 
										 WHERE codigo_asunto_secundario =  '' OR codigo_asunto_secundario IS NULL";
														
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;

				case 3.27:
				$query = array();

				//Agrego la categoría Administración, si no existe.
				$query[] = "INSERT INTO prm_categoria_usuario (glosa_categoria,id_categoria_lemontech) SELECT 'Administración' as glosa_categoria, 5 as id_categoria_lemontech FROM DUAL WHERE NOT EXISTS (SELECT * FROM `prm_categoria_usuario` WHERE  glosa_categoria  LIKE '%dminis%')";
				$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech = 2 WHERE glosa_categoria = 'Asociado Senior';";
				$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech = 3 WHERE glosa_categoria = 'Asociado Junior';";
				$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech = 3 WHERE glosa_categoria = 'Asesor Externo';";
				$query[] = "UPDATE prm_categoria_usuario SET id_categoria_lemontech = 4 WHERE glosa_categoria = 'Procurador';";

				//Busco los usuarios sin categoria
				$query_usuarios = "	SELECT usuario.id_usuario, profesional.codigo_permiso as profesional, comercial.codigo_permiso as comercial 
						FROM  `usuario`
						LEFT JOIN usuario_permiso as profesional ON (usuario.id_usuario = profesional.id_usuario AND profesional.codigo_permiso = 'PRO')
						LEFT JOIN usuario_permiso as comercial ON (usuario.id_usuario = comercial.id_usuario AND comercial.codigo_permiso = 'SOC')
						WHERE id_categoria_usuario IS NULL ";

				//Obtengo las categorías a las que se puede asignar.
				$query_admin = " SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE id_categoria_lemontech = 5 LIMIT 1 "; 
				$query_socio = " SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE id_categoria_lemontech = 1 LIMIT 1 "; 
				$query_junior = " SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE id_categoria_lemontech = 3 LIMIT 1 "; 
				$query_minimo = " SELECT id_categoria_usuario FROM prm_categoria_usuario ORDER BY id_categoria_lemontech DESC LIMIT 1 "; 

				if(! ($resp_admin = mysql_query($query_admin,$sesion->dbh)))
					throw new Exception($query_admin ."---".mysql_error());
				if(! ($resp_socio = mysql_query($query_socio,$sesion->dbh)))
					throw new Exception($query_socio ."---".mysql_error());
				if(! ($resp_junior = mysql_query($query_junior,$sesion->dbh)))
					throw new Exception($query_junior ."---".mysql_error());
				if(! ($resp_minimo = mysql_query($query_minimo,$sesion->dbh)))
					throw new Exception($query_minimo ."---".mysql_error());

				list($admin) = mysql_fetch_array($resp_admin);
				list($socio) = mysql_fetch_array($resp_socio);
				list($junior) = mysql_fetch_array($resp_junior);
				list($minimo) = mysql_fetch_array($resp_minimo);

				if(!$admin) $admin = $minimo;
				if(!$socio) $socio = $minimo;
				if(!$junior) $junior = $minimo;

				if(! ($resp_usuarios = mysql_query($query_usuarios,$sesion->dbh)))
					throw new Exception($query_usuarios ."---".mysql_error());
				while( list($id_usuario, $profesional, $comercial) = mysql_fetch_array($resp_usuarios) )
				{
					//por cada usuario sin categoría, puede tener la categoría 'Admin', 'Asociado Junior', o 'Socio' dependiendo de los permisos
					if(!$profesional)
					{
						$query[] = "UPDATE usuario SET id_categoria_usuario = '".$admin."' WHERE id_usuario = '".$id_usuario."'"; 
					}
					else
					{
						if($comercial)
							$query[] = "UPDATE usuario SET id_categoria_usuario = '".$socio."' WHERE id_usuario = '".$id_usuario."'";
						else
							$query[] = "UPDATE usuario SET id_categoria_usuario = '".$junior."' WHERE id_usuario = '".$id_usuario."'";
					}
				}				
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;

				/*Clase ayuda invocada en cada pagina por el Header, imprime ayuda para esa pagina.*/
				/*Soporte para glosa de los reportes consolidados*/
				case 3.28: 
					$query = array();
					$query[] = "
					CREATE TABLE  `prm_ayuda` (
					 `id_ayuda` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					 `pagina` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
					 `descripcion` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
					 `id_ayuda_anterior` INT( 11 ) NULL ,
					 `id_ayuda_siguiente` INT( 11 ) NULL
					) ENGINE = INNODB COMMENT =  'tabla de paginas de ayuda';";
					$query[] = "ALTER TABLE  `reporte_consolidado` ADD  `glosa_reporte` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER  `id_moneda` ;";
					
					foreach($query as $q)
						if(!($resp = mysql_query($q, $dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				/*Achicar tamaño de las columnas fecha y abogado al tamaño que tenía antes de lo modificacion 3.25*/
				case 3.29:
					$query = array();
					$query[] = "UPDATE  `prm_excel_cobro` SET  `tamano` =  '9' WHERE  `id_prm_excel_cobro` =2 LIMIT 1;";
					$query[] = "UPDATE  `prm_excel_cobro` SET  `tamano` =  '10' WHERE  `id_prm_excel_cobro` =3 LIMIT 1;";
					
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.30:
					$query = array();
					$query[] = "ALTER TABLE  `contrato` ADD  `opc_restar_retainer` TINYINT( 4 ) NOT NULL DEFAULT  '1' AFTER  `opc_ver_solicitante` ;";
					$query[] = "ALTER TABLE  `cobro` ADD  `opc_restar_retainer` TINYINT( 4 ) NOT NULL DEFAULT  '1' AFTER  `opc_ver_cobrable` ;";
					
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				/*Rollback Ayuda*/
				case 3.31: 
					$query = array();
					$query[] = "DROP TABLE  `prm_ayuda`;";
					foreach($query as $q)
						if(!($resp = mysql_query($q, $dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.32:
					$query = array();
					$query[] = "ALTER TABLE  `usuario` CHANGE  `dias_ingreso_trabajo`  `dias_ingreso_trabajo` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT  '30'";
					
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.33:
					$query = array();
					$query[] = "ALTER TABLE `cta_corriente` ADD COLUMN `con_impuesto` CHAR(2) NOT NULL DEFAULT 'SI' AFTER `id_movimiento_pago`;";
					$query[] = "ALTER TABLE `documento` ADD COLUMN `subtotal_gastos_sin_impuesto` double  NOT NULL AFTER `fecha_modificacion`;";

				foreach($query as $q)
                    if(!($resp = mysql_query($q, $dbh)))
                        throw new Exception($q."---".mysql_error());
                break;

				case 3.34:
                    $query = array();
                    $query[] = "ALTER TABLE  `documento` ADD  `monto_trabajos` DOUBLE NOT NULL DEFAULT  '0' AFTER  `subtotal_honorarios` ;";
                    $query[] = "ALTER TABLE  `documento` ADD  `monto_tramites` DOUBLE NOT NULL DEFAULT  '0' AFTER  `monto_trabajos` ;";
                    $query[] = "UPDATE  documento as d 
                                                JOIN cobro as c  ON ( c.id_cobro=d.id_cobro AND d.tipo_doc='N' ) 
                                                JOIN cobro_moneda AS cm ON ( c.id_cobro=cm.id_cobro AND c.id_moneda=cm.id_moneda ) 
                                                JOIN cobro_moneda AS cmt ON ( c.id_cobro=cmt.id_cobro AND c.opc_moneda_total=cmt.id_moneda ) 
                                                JOIN prm_moneda AS mt ON c.opc_moneda_total=mt.id_moneda 
                                                SET d.monto_trabajos=ROUND(c.monto_trabajos*cm.tipo_cambio/cmt.tipo_cambio,mt.cifras_decimales), 
                                                d.monto_tramites=ROUND(c.monto_tramites*cm.tipo_cambio/cmt.tipo_cambio,mt.cifras_decimales);";
                    $query[] = "UPDATE contrato SET id_moneda_monto = 1 WHERE id_moneda_monto = 0;";
                    $query[] = "ALTER TABLE  `contrato` CHANGE  `id_moneda_monto`  `id_moneda_monto` INT( 11 ) NOT NULL DEFAULT  '1';";

                foreach($query as $q)
          			if(!($resp = mysql_query($q, $dbh)))
            			throw new Exception($q."---".mysql_error());
        		break;
				
				case 3.35:
					$query = array();
					//1:CREAR TABLA
                    $query[] = "CREATE TABLE  `documento_moneda` (
                                             `id_documento` INT( 11 ) NOT NULL ,
                                             `id_moneda` INT( 11 ) NOT NULL ,
                                             `tipo_cambio` DOUBLE NOT NULL DEFAULT  '0',
                                            PRIMARY KEY (  `id_documento` ,  `id_moneda` )
                                            ) ENGINE = INNODB COMMENT =  'Tipo de cambio de pago';";
                    //2:SELECCIONAR cobros sin documento.

					$query[] = "INSERT INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
                					SELECT documento.id_documento,
                    					cobro_moneda.id_moneda,
                    					cobro_moneda.tipo_cambio
                					FROM documento
                					JOIN cobro ON documento.id_cobro = cobro.id_cobro
                					JOIN cobro_moneda ON cobro.id_cobro = cobro_moneda.id_cobro
                					WHERE documento.tipo_doc = 'N'
                				";

					foreach($query as $q)
                    if(!($resp = mysql_query($q, $dbh)))
                        throw new Exception($q."---".mysql_error());
                break;

				case 3.36:
					$query = array();

						//2.a1: seleccionar cobros sin su documento
						$sel = " SELECT cobro.id_cobro, cobro.estado, documento.id_documento FROM cobro
											LEFT JOIN documento ON (documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N')  HAVING
											cobro.estado NOT IN ('CREADO','EN REVISION') AND documento.id_documento
											IS NULL";
						$resp = mysql_query($sel,$dbh) or Utiles::errorSQL($sel,__FILE__,__LINE__,$dbh);
						while( list($id_cobro,$estado_cobro, $id_documento) = mysql_fetch_array($resp))
						{
							$cobro = new Cobro($sesion);
							$cobro->Load($id_cobro);
							$cobro->ReiniciarDocumento();
							if($estado_cobro == 'INCOBRABLE')
							{
								$documento = new Documento($sesion);
								$documento->LoadByCobro($id_cobro);
								$documento->AnularMontos();
							}
						}
				/*2.B: Futuro: insertar documento_monedas
				INSERT INTO documento_moneda (id_documento, id_moneda, tipo_cambio)
				SELECT documento.id_documento,
					cobro_moneda.id_moneda,
					cobro_moneda.tipo_cambio
				FROM documento
				JOIN cobro ON documento.id_cobro = cobro.id_cobro
				JOIN cobro_moneda ON cobro.id_cobro = cobro_moneda.id_cobro
				*/		
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break; 
				
				case 3.37:
					$query = array();
					$query[] = "TRUNCATE TABLE `configuracion`;";
					$query[] = "TRUNCATE TABLE `configuracion_categoria`;";
					$query[] = "ALTER TABLE `configuracion` DROP INDEX `id_configuracion_categoria`;";
					$query[] = "DROP TABLE configuracion;";
					$query[] = "DROP TABLE configuracion_categoria;";
					$query[] = "CREATE TABLE `configuracion_categoria` (
												  `id_configuracion_categoria` int(11) NOT NULL auto_increment,
												  `glosa_configuracion_categoria` varchar(50) character set latin1 NOT NULL default '',
												  PRIMARY KEY  (`id_configuracion_categoria`)
												) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;";
					$query[] = "CREATE TABLE `configuracion` (
												  `id` int(11) NOT NULL auto_increment COMMENT 'Necesario para la página de configuración.',
												  `glosa_opcion` varchar(64) NOT NULL default '' COMMENT 'Nombre de la opcion para mostrar al usuario.',
												  `valor_opcion` text NOT NULL,
												  `comentario` varchar(255) default NULL COMMENT 'Comentario explicando la funcionalidad para mostrar al usuario.',
												  `valores_posibles` varchar(255) NOT NULL default '' COMMENT 'Puede ser \"numero\" para que el usuario ingrese un número, \"string\" para string ingresado por el usuario, \"boolean\" para un checkbox o \"select;valor1;valor2;...\" para generar un select con los valores definidos.',
												  `id_configuracion_categoria` int(11) NOT NULL default '0',
												  `orden` int(11) NOT NULL default '-1' COMMENT 'Orden de aparición en la página de configuración, -1 para no mostrar la opción.',
												  PRIMARY KEY  (`id`)
												) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
					$query[] = "ALTER TABLE `configuracion` ADD INDEX (  `id_configuracion_categoria` );";
					$query[] = "ALTER TABLE `configuracion` ADD CONSTRAINT `id_configuracion_categoria` FOREIGN KEY `id_configuracion_categoria` (`id_configuracion_categoria`)
										   REFERENCES `configuracion_categoria` (`id_configuracion_categoria`)
										   ON DELETE RESTRICT ON UPDATE CASCADE;";
					$query[] = "ALTER TABLE  `configuracion` ADD UNIQUE (`glosa_opcion`);";
					$query[] = "INSERT INTO `configuracion_categoria` (`id_configuracion_categoria`, `glosa_configuracion_categoria`) VALUES (1, 'Datos Generales'),
													(2, 'Datos Cobranza'),
													(3, 'Configuración alertas'),
													(4, 'Opciones documentos'),
													(5, 'Administracion Reportes por Lemontech'),
													(6, 'Configuracion por Lemontech');";
					if( method_exists('Conf','MaxLoggedTime') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(31, 'MaxLoggedTime', '".Conf::MaxLoggedTime()."', 'Tiempo en segundos que dura la sesión', 'string', 6, 10);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(31, 'MaxLoggedTime', '14400', 'Tiempo en segundos que dura la sesión', 'string', 6, 10);";
													
					if( method_exists('Conf','MailSistema') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(32, 'MailSistema', '".Conf::MailSistema()."', NULL, 'string', 1, 20);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(32, 'MailSistema', '', NULL, 'string', 1, 20);";
													
					if( method_exists('Conf','Intervalo') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(33, 'Intervalo', '".Conf::Intervalo()."', 'Intervalo del ingreso trabajo', 'numero', 1, 200);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(33, 'Intervalo', '5', 'Intervalo del ingreso trabajo', 'numero', 1, 200);";
													
					if( method_exists('Conf','Idioma') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(34, 'Idioma', '".Conf::Idioma()."', 'Idioma del sistma', 'select;ES;EN;', 6, 30);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(34, 'Idioma', 'ES', 'Idioma del sistma', 'select;ES;EN;', 6, 30);";
													
					if( method_exists('Conf','MailAdmin') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(35, 'MailAdmin', '".Conf::MailAdmin()."', 'Email al que llegan los avisos del sistema', 'string', 1, 40);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(35, 'MailAdmin', '', 'Email al que llegan los avisos del sistema', 'string', 1, 40);";
													
					if( method_exists('Conf','SitioWeb') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(36, 'SitioWeb', '".Conf::SitioWeb()."', 'Sitio Web del estudio', 'string', 1, 50);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(36, 'SitioWeb', '', 'Sitio Web del estudio', 'string', 1, 50);";
													
					if( method_exists('Conf','Email') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(37, 'Email', '".Conf::Email()."', 'Mail contacto del estudio', 'string', 1, 60);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(37, 'Email', '', 'Mail contacto del estudio', 'string', 1, 60);";
																				
					if( method_exists('Conf','TipoSelectCliente') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(40, 'TipoSelectCliente', '".(Conf::TipoSelectCliente()?'autocompletador':'selector')."', 'Tipo de selection del cliente', 'select;autocompletador;selector_cliente', 1, 90);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(40, 'TipoSelectCliente', 'selector', 'Tipo de selection del cliente', 'select;autocompletador;selector;', 1, 90);";
													
					if( method_exists('Conf','AgregarAsuntosPorDefecto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(41, 'AgregarAsuntosPorDefecto', '".implode(';',Conf::AgregarAsuntosPorDefecto())."', 'Asuntos que se crean por defecto', 'array', 1, 100);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(41, 'AgregarAsuntosPorDefecto', '', 'Asuntos que se crean por defecto', 'array', 1, 100);";
													
					if( method_exists('Conf','UsarImpuestoSeparado') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(42, 'UsarImpuestoSeparado', '1', '".Conf::UsarImpuestoSeparado()."', 'boolean', 2, 210);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(42, 'UsarImpuestoSeparado', '0', false, 'boolean', 2, 210);";
													
					if( method_exists('Conf','ValorImpuesto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(43, 'ValorImpuesto', '".Conf::ValorImpuesto()."', 'Porcentaje de los Impuestos', 'numero', 2, 220);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(43, 'ValorImpuesto', 0, 'Porcentaje de los Impuestos', 'numero', 2, 220);";
													
					if( method_exists('Conf','UsarImpuestoPorGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(44, 'UsarImpuestoPorGastos', '".Conf::UsarImpuestoPorGastos()."', 'Usar impuestos por los gastos', 'boolean', 2, 230);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(44, 'UsarImpuestoPorGastos', false, 'Usar impuestos por los gastos', 'boolean', 2, 230);";
													
					if( method_exists('Conf','ValorImpuestoGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(45, 'ValorImpuestoGastos', '".Conf::ValorImpuestoGastos()."', 'Porcentaje de impuestos que se cobra a los gastos', 'numero', 2, 240);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(45, 'ValorImpuestoGastos', 0, 'Porcentaje de impuestos que se cobra a los gastos', 'numero', 2, 240);";
													
					if( method_exists('Conf','ComisionGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(46, 'ComisionGastos', '".Conf::ComisionGastos()."', 'Porcentaje de comision que se cobra a los gastos', 'numero', 2, 250);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(46, 'ComisionGastos', 0, 'Porcentaje de comision que se cobra a los gastos', 'numero', 2, 250);";
													
					if( method_exists('Conf','Ordenado_Por') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(47, 'OrdenadoPor', '".Conf::Ordenado_Por()."', '0 no se necesita; 1 es obligatorio; 2 opcional;', 'select;0;1;2', 1, 260);";
					else	
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(47, 'OrdenadoPor', 0, '0 no se necesita; 1 es obligatorio; 2 opcional;', 'select;0;1;2', 1, 260);";
													
					if( method_exists('Conf','TipoIngresoHoras') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(48, 'TipoIngresoHoras', '".Conf::TipoIngresoHoras()."', 'Tipo de ingreso de horas', 'select;java;decimal;selector', 1, 270);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(48, 'TipoIngresoHoras', 'java', 'Tipo de ingreso de horas', 'select;java;decimal;selector', 1, 270);";
													
					if( method_exists('Conf','PermitirFactura') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(49, 'PermitirFactura', '".Conf::PermitirFactura()."', 'Permitir factura por el sistema', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(49, 'PermitirFactura', false, 'Permitir factura por el sistema', 'boolean', 6, -1);";
													
					if( method_exists('Conf','UsaNumeracionAutomatica') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(50, 'UsaNumeracionAutomatica', '".Conf::UsaNumeracionAutomatica()."', 'Hacer numeracion de las facturas automaticamente', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(50, 'UsaNumeracionAutomatica', false, 'Hacer numeracion de las facturas automaticamente', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NumeracionDesde') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(51, 'NumeracionDesde', '".Conf::NumeracionDesde()."', 'Numeracion minima de factura', 'numero', 2, 300);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(51, 'NumeracionDesde', '', 'Numeracion minima de factura', 'numero', 2, 300);";
													
					if( method_exists('Conf','NumeracionHasta') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(52, 'NumeracionHasta', '".Conf::NumeracionHasta()."', 'Numeracion maxima de factura', 'numero', 2, 310);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(52, 'NumeracionHasta', '', 'Numeracion maxima de factura', 'numero', 2, 310);";
													
					if( method_exists('Conf','MailAsuntoNuevo') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(53, 'MailAsuntoNuevo', '".Conf::MailAsuntoNuevo()."', 'Enviar mail cuando se crea un asunto nuevo', 'boolean', 3, 400);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(53, 'MailAsuntoNuevo', false, 'Enviar mail cuando se crea un asunto nuevo', 'boolean', 3, 400);";
													
					if( method_exists('Conf','DiaMailSemanal') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(54, 'DiaMailSemanal', '".Conf::DiaMailSemanal()."', '', 'select;Mon;Tue;Wed;Thu;Fri;Sat;Sun', 3, 410);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(54, 'DiaMailSemanal', 'Fri', '', 'select;Mon;Tue;Wed;Thu;Fri;Sat;Sun', 3, 410);";
													
					if( method_exists('Conf','CorreosModificacionAdminDatos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(55, 'CorreosModificacionAdminDatos', '".Conf::CorreosModificacionAdminDatos()."', '', 'string', 3, 420);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(55, 'CorreosModificacionAdminDatos', '', '', 'string', 3, 420);";
													
					if( method_exists('Conf','MensajeRestriccionSemanal') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(56, 'MensajeRestriccionSemanal', '".Conf::MensajeRestriccionSemanal()."', '', 'text', 3, 430);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(56, 'MensajeRestriccionSemanal', '', '', 'text', 3, 430);";
													
					if( method_exists('Conf','CorreosMensuales') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(57, 'CorreosMensuales', '".Conf::CorreosMensuales()."', '', 'boolean', 3, 440);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(57, 'CorreosMensuales', false, '', 'boolean', 3, 440);";
													
					if( method_exists('Conf','PdfLinea1') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(58, 'PdfLinea1', '".Conf::PdfLinea1()."', '', 'string', 4, 600);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(58, 'PdfLinea1', '', '', 'string', 4, 600);";
													
					if( method_exists('Conf','PdfLinea2') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(59, 'PdfLinea2', '".Conf::PdfLinea2()."', '', 'string', 4, 610);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(59, 'PdfLinea2', '', '', 'string', 4, 610);";
													
					if( method_exists('Conf','PdfLinea3') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(60, 'PdfLinea3', '".Conf::PdfLinea3()."', '', 'string', 4, 620);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(60, 'PdfLinea3', '', '', 'string', 4, 620);";
													
					if( method_exists('Conf','DireccionPdf') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(61, 'DireccionPdf', '".Conf::DireccionPdf()."', '', 'string', 4, 630);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(61, 'DireccionPdf', '', '', 'string', 4, 630);";
													
					if( method_exists('Conf','TituloContacto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(62, 'TituloContacto', '".Conf::TituloContacto()."', 'Indicar titulo antes de nombre', 'boolean', 4, 640);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(62, 'TituloContacto', false, 'Indicar titulo antes de nombre', 'boolean', 4, 640);";
													
					if( method_exists('Conf','OrdenarPorFechaCategoria') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(63, 'OrdenarPorFechaCategoria', '".Conf::OrdenarPorFechaCategoria()."', 'Ordenar detalle profesional por fecha', 'radio;ordenar', 4, 650);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(63, 'OrdenarPorFechaCategoria', false, 'Ordenar detalle profesional por fecha', 'radio;ordenar', 4, 650);";
													
					if( method_exists('Conf','OrdenarPorTarifa') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(64, 'OrdenarPorTarifa', '".Conf::OrdenarPorTarifa()."', 'Ordenar detalle profesional por tarifa', 'radio;ordenar', 4, 660);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(64, 'OrdenarPorTarifa', false, 'Ordenar detalle profesional por tarifa', 'radio;ordenar', 4, 660);";
													
					if( method_exists('Conf','OrdenarPorCategoriaUsuario') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(65, 'OrdenarPorCategoriaUsuario', '".Conf::OrdenarPorCategoriaUsuario()."', 'Ordenar detalle profesional por categoria', 'radio;ordenar', 4, 670);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(65, 'OrdenarPorCategoriaUsuario', false, 'Ordenar detalle profesional por categoria', 'radio;ordenar', 4, 670);";
													
					if( method_exists('Conf','ImprimirDuracionTrabajada') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(66, 'ImprimirDuracionTrabajada', '".Conf::ImprimirDuracionTrabajada()."', '', 'boolean', 4, 680);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(66, 'ImprimirDuracionTrabajada', false, '', 'boolean', 4, 680);";
													
					if( method_exists('Conf','TodoMayuscula') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(67, 'TodoMayuscula', '".Conf::TodoMayuscula()."', 'Descripciones y nombres en mayuscula', 'boolean', 4, 690);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(67, 'TodoMayuscula', false, 'Descripciones y nombres en mayuscula', 'boolean', 4, 690);";
													
					if( method_exists('Conf','SepararGastosPorAsunto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(68, 'SepararGastosPorAsunto', '".Conf::SepararGastosPorAsunto()."', 'En la carta de cobro separar los gastos por asunto', 'boolean', 4, 700);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(68, 'SepararGastosPorAsunto', false, 'En la carta de cobro separar los gastos por asunto', 'boolean', 4, 700);";
					if( method_exists('Conf','ValorSinEspacio') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(69, 'ValorSinEspacio', '".Conf::ValorSinEspacio()."', 'En carta de cobro mostra los valores sin espacio entre simbolos y montos', 'boolean', 4, 710);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(69, 'ValorSinEspacio', false, 'En carta de cobro mostra los valores sin espacio entre simbolos y montos', 'boolean', 4, 710);";
					if( method_exists('Conf','ParafoGastosSoloSiHayGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(70, 'ParafoGastosSoloSiHayGastos', '".Conf::ParafoGastosSoloSiHayGastos()."', '', 'boolean', 4, 720);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(70, 'ParafoGastosSoloSiHayGastos', false, '', 'boolean', 4, 720);";
					if( method_exists('Conf','ParafoAsuntosSoloSiHayTrabajos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(71, 'ParafoAsuntosSoloSiHayTrabajos', '".Conf::ParafoAsuntosSoloSiHayTrabajos()."', '', 'boolean', 4, 730);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(71, 'ParafoAsuntosSoloSiHayTrabajos', false, '', 'boolean', 4, 730);";
					if( method_exists('Conf','ColorTituloPagina') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(72, 'ColorTituloPagina', '".Conf::ColorTituloPagina()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(72, 'ColorTituloPagina', '', '', 'string', 6, -1);";
					if( method_exists('Conf','ColorLineaSuperior') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(73, 'ColorLineaSuperior', '".Conf::ColorLineaSuperior()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(73, 'ColorLineaSuperior', '', '', 'string', 6, -1);";
					if( method_exists('Conf','Telefono') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(75, 'Telefono', '".Conf::Telefono()."', '', 'numero', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(75, 'Telefono', '', '', 'numero', 6, -1);";
													
					if( method_exists('Conf','UsoActividades') )  
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(78, 'UsoActividades', '".Conf::UsoActividades()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(78, 'UsoActividades', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','RecordarSesion') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(82, 'RecordarSesion', '".Conf::RecordarSesion()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(82, 'RecordarSesion', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','IdiomaGrande') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(83, 'IdiomaGrande', '".Conf::IdiomaGrande()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(83, 'IdiomaGrande', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','UsernameMail') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(84, 'UsernameMail', '".Conf::UsernameMail()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(84, 'UsernameMail', '', '', 'string', 6, -1);";
													
					if( method_exists('Conf','PasswordMail') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(85, 'PasswordMail', '".Conf::PasswordMail()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(85, 'PasswordMail', '', '', 'string', 6, -1);";
													
					if( method_exists('Conf','SoloGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(86, 'SoloGastos', '".Conf::SoloGastos()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(86, 'SoloGastos', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','TipoCodigoAsunto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(87, 'TipoCodigoAsunto', '".Conf::TipoCodigoAsunto()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(87, 'TipoCodigoAsunto', '1', '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','FacturaSeguimientoCobros') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(88, 'FacturaSeguimientoCobros', '".Conf::FacturaSeguimientoCobros()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(88, 'FacturaSeguimientoCobros', false, '', 'boolean', 6, -1);";
														
					if( method_exists('Conf','ReportesAvanzados') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(92, 'ReportesAvanzados', '".Conf::ReportesAvanzados()."', '', 'boolean', 5, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(92, 'ReportesAvanzados', false, '', 'boolean', 5, -1);";
													
					if( method_exists('Conf','TipoGasto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(93, 'TipoGasto', '".Conf::TipoGasto()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(93, 'TipoGasto', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','SinAproximacion') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(94, 'SinAproximacion', '".Conf::SinAproximacion()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(94, 'SinAproximacion', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CodigoObligatorio') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(95, 'CodigoObligatorio', '".Conf::CodigoObligatorio()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(95, 'CodigoObligatorio', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CatidadHorasDia') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(96, 'CatidadHorasDia', '".Conf::CatidadHorasDia()."', '', 'numero', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(96, 'CatidadHorasDia', '1439', '', 'numero', 6, -1);";
													
					if( method_exists('Conf','MostrarHorasCero') ) 
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(97, 'MostrarHorasCero', '".Conf::MostrarHorasCero()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(97, 'MostrarHorasCero', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NumeroGasto') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(98, 'NumeroGasto', '".Conf::Idioma()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(98, 'NumeroGasto', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NumeroOT') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(99, 'NumeroOT', '".Conf::NumeroOT()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(99, 'NumeroOT', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CodigoEspecialGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(100, 'CodigoEspecialGastos', '".Conf::CodigoEspecialGastos()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(100, 'CodigoEspecialGastos', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NotaCobroExtra') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(101, 'NotaCobroExtra', '".Conf::NotaCobroExtra()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(101, 'NotaCobroExtra', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CalculacionCyC') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(102, 'CalculacionCyC', '".Conf::CalculacionCyC()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(102, 'CalculacionCyC', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','UsaFechaDesdeCobranza') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(103, 'UsaFechaDesdeCobranza', '".Conf::UsaFechaDesdeCobranza()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(103, 'UsaFechaDesdeCobranza', false, '', 'boolean', 6, -1);";
													
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(104, 'UsaDisenoNuevo', true, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','XLSFormatoEspecial') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(105, 'XLSFormatoEspecial', '".Conf::XLSFormatoEspecial()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(105, 'XLSFormatoEspecial', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CodigoUsuario') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(106, 'CodigoUsuario', '".Conf::CodigoUsuario()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(106, 'CodigoUsuario', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','InfoBancariaCYC') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(107, 'InfoBancariaCYC', '".Conf::InfoBancariaCYC()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(107, 'InfoBancariaCYC', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','UsaMontoCobrable') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(108, 'UsaMontoCobrable', '".Conf::UsaMontoCobrable()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(108, 'UsaMontoCobrable', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NuevaLibreriaNusoap') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(111, 'NuevaLibreriaNusoap', '".Conf::NuevaLibreriaNusoap()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(111, 'NuevaLibreriaNusoap', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','LoginDesdeSitio') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(112, 'LoginDesdeSitio', '".Conf::LoginDesdeSitio()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(112, 'LoginDesdeSitio', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CreacionYEmisionDeLosCobrosAutomatico') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(113, 'CreacionYEmisionDeLosCobrosAutomatico', '".Conf::CreacionYEmisionDeLosCobrosAutomatico()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(113, 'CreacionYEmisionDeLosCobrosAutomatico', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','FicheroLogoDoc') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(114, 'FicheroLogoDoc', '".Conf::FicheroLogoDoc()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(114, 'FicheroLogoDoc', '', '', 'string', 6, -1);";
													
					if( method_exists('Conf','PrmGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(115, 'PrmGastos', '".Conf::PrmGastos()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(115, 'PrmGastos', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CSSSoloGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(116, 'CSSSoloGastos', '".Conf::CSSSoloGastos()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(116, 'CSSSoloGastos', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','ImprimirResumenAsuntosEnCarta') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(118, 'ImprimirResumenAsuntosEnCarta', '".Conf::ImprimirResumenAsuntosEnCarta()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(118, 'ImprimirResumenAsuntosEnCarta', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NoImprimirValorTrabajo') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(119, 'NoImprimirValorTrabajo', '".Conf::NoImprimirValorTrabajo()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(119, 'NoImprimirValorTrabajo', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','ImprimirValorTrabajo') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(120, 'ImprimirValorTrabajo', '".Conf::ImprimirValorTrabajo()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(120, 'ImprimirValorTrabajo', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','MostrarSoloMinutos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(121, 'MostrarSoloMinutos', '".Conf::MostrarSoloMinutos()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(121, 'MostrarSoloMinutos', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','ImprimirFacturaPdf') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(122, 'ImprimirFacturaPdf', '".Conf::ImprimirFacturaPdf()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(122, 'ImprimirFacturaPdf', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','CobranzaExcel') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(123, 'CobranzaExcel', '".Conf::CobranzaExcel()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(123, 'CobranzaExcel', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','UsarEgresoPositivo') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(124, 'UsarEgresoPositivo', '".Conf::UsarEgresoPositivo()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(124, 'UsarEgresoPositivo', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','ColumnaNotificacion') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(125, 'ColumnaNotificacion', '".Conf::ColumnaNotificacion()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(125, 'ColumnaNotificacion', '', '', 'string', 6, -1);";
													
					if( method_exists('Conf','TieneTablaVisitante') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(126, 'TieneTablaVisitante', '".Conf::TieneTablaVisitante()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(126, 'TieneTablaVisitante', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','UsarSoloGastos') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(127, 'UsarSoloGastos', '".Conf::UsarSoloGastos()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(127, 'UsarSoloGastos', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','ReporteMorosidadEnviados') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(128, 'ReporteMorosidadEnviados', false, '', 'boolean', 5, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(128, 'ReporteMorosidadEnviados', false, '', 'boolean', 5, -1);";
													
					if( method_exists('Conf','CodigoSecundario') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(129, 'CodigoSecundario', '".Conf::CodigoSecundario()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(129, 'CodigoSecundario', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','SistemaCarpetasEspecial') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(130, 'SistemaCarpetasEspecial', '".Conf::SistemaCarpetasEspecial()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(130, 'SistemaCarpetasEspecial', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NumeroCuentaCorriente') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(131, 'NumeroCuentaCorriente', '".Conf::NumeroCuentaCorriente()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(131, 'NumeroCuentaCorriente', '', '', 'string', 6, -1);";
													
					if( method_exists('Conf','BancoCuentaCorriente') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(132, 'BancoCuentaCorriente', '".Conf::BancoCuentaCorriente()."', '', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(132, 'BancoCuentaCorriente', '', '', 'string', 6, -1);";
													
					if( method_exists('Conf','UsarResumenExcel') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(134, 'UsarResumenExcel', '".Conf::UsarResumenExcel()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(134, 'UsarResumenExcel', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','GlosaAsuntoSinCodigo') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(135, 'GlosaAsuntoSinCodigo', '".Conf::GlosaAsuntoSinCodigo()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(135, 'GlosaAsuntoSinCodigo', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','NotaDeCobroVFC') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(136, 'NotaDeCobroVFC', '".Conf::NotaDeCobroVFC()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(136, 'NotaDeCobroVFC', false, '', 'boolean', 6, -1);";
													
					if( method_exists('Conf','ResumenProfesionalVial') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(137, 'ResumenProfesionalVial', '".Conf::ResumenProfesionalVial()."', '', 'boolean', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(137, 'ResumenProfesionalVial', false, '', 'boolean', 6, -1);";
					
					if( method_exists('Conf','ZonaHoraria') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(138, 'ZonaHoraria', '".Conf::ZonaHoraria()."', 'Se debe agregar el nombre de la zona horaria que utilizará el sistema', 'string', 6, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(138, 'ZonaHoraria', 'America/Santiago', 'Se debe agregar el nombre de la zona horaria que utilizará el sistema', 'string', 6, -1);";
													
					if( method_exists('Conf','CiudadSignatura') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(139, 'CiudadSignatura', '".Conf::CiudadSignatura()."', 'La ciudad ingresada aquí va a aparecer en la signatura junto con la fecha', 'string', 4, 760);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(139, 'CiudadSignatura', '', 'La ciudad ingresada aquí va a aparecer en la signatura junto con la fecha', 'string', 4, 760);";
													
					if( method_exists('Conf','NombreEmpresa') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(140, 'NombreEmpresa', '".Conf::NombreEmpresa()."', NULL, 'string', 1, 160);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(140, 'NombreEmpresa', '', NULL, 'string', 1, 160);";
													
					if( method_exists('Conf','SubtituloEmpresa') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(141, 'SubtituloEmpresa', '".Conf::SubtituloEmpresa()."', NULL, 'string', 1, 161);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(141, 'SubtituloEmpresa', '', NULL, 'string', 1, 161);";
													
					if( method_exists('Conf','ReportesAvanzados_FiltrosExtra') )
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(142, 'ReportesAvanzados_FiltrosExtra', '".Conf::ReportesAvanzados_FiltrosExtra()."', NULL, 'boolean', 5, -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(142, 'ReportesAvanzados_FiltrosExtra', false, NULL, 'boolean', 5, -1);";
					
					if( method_exists('Conf','NombreIdentificador') )
						$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) VALUES 
													(143 ,  'NombreIdentificador',  '".Conf::NombreIdentificador()."', NULL ,  'string',  '6',  -1);";
					else
						$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(143, 'NombreIdentificador', 'RUT', NULL, 'boolean', 5, -1);";
					
					$query[] = "INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES 
													(144, 'EsAmbientePrueba', '0', 'Se aplica a lab y las sistemas demo para hacer testing', 'boolean', '6', '-1');";
													
				foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.38:
          $query = array();
          $query[] = "ALTER TABLE `cobro` ADD COLUMN `modalidad_calculo` TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1 calculacion nueva, 0 calculacon vieja' AFTER `nota_cobro`;";
          $query[] = "UPDATE cobro set modalidad_calculo = 0;";

        foreach($query as $q)
          if(!($resp = mysql_query($q, $dbh)))
            throw new Exception($q."---".mysql_error());
        break;

        case 3.39:
		
					$query = array();
										
					$query[] = "UPDATE usuario SET username = CONCAT_WS(' ',nombre, apellido1, apellido2) WHERE username = ''"; 
					
					$query_malos = 
					"SELECT cobro.id_cobro, cobro.estado, COUNT(documento.id_documento) as num_documentos 
					FROM cobro
					JOIN documento ON (documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N') 
					GROUP BY cobro.id_cobro 
					HAVING ( ( cobro.estado IN ('CREADO','EN REVISION') AND num_documentos > 0 ) OR ( cobro.estado NOT IN ('CREADO','EN REVISION') AND num_documentos > 1 ) ) ";
					$resp = mysql_query($query_malos,$dbh) or Utiles::errorSQL($query_malos,__FILE__,__LINE__,$dbh);
					while( list($id_cobro,$estado,$num_documentos) = mysql_fetch_array($resp))
					{
						$cobro = new Cobro($sesion);
						$cobro->Load($id_cobro);
						$cobro->AnularDocumento();
						echo "<br>Revisar cobro: ".$id_cobro."<br>";
					}
	
					$query[] = "ALTER TABLE  `asunto` CHANGE  `alerta_hh`  `alerta_hh` DOUBLE NOT NULL DEFAULT  '0',
CHANGE  `alerta_monto`  `alerta_monto` DOUBLE NOT NULL DEFAULT  '0',
CHANGE  `alerta_porctje_lim_hh`  `alerta_porctje_lim_hh` DOUBLE NOT NULL DEFAULT  '0',
CHANGE  `alerta_porctje_lim_monto`  `alerta_porctje_lim_monto` DOUBLE NOT NULL DEFAULT  '0',
CHANGE  `limite_hh`  `limite_hh` DOUBLE NOT NULL DEFAULT  '0',
CHANGE  `limite_monto`  `limite_monto` DOUBLE NOT NULL DEFAULT  '0'";

			$query[] = "ALTER TABLE  `contrato` ADD  `alerta_hh` DOUBLE NOT NULL DEFAULT  '0' AFTER  `fecha_inicio_cap` ,
 ADD  `alerta_monto` DOUBLE NOT NULL DEFAULT  '0' AFTER  `alerta_hh` ,
 ADD  `limite_hh` DOUBLE NOT NULL DEFAULT  '0' AFTER  `alerta_monto` ,
ADD  `limite_monto` DOUBLE NOT NULL DEFAULT  '0' AFTER  `limite_hh` ;";

			$query[] = "ALTER TABLE  `cliente` ADD  `alerta_hh` DOUBLE NOT NULL DEFAULT  '0' AFTER  `id_contrato` ,
 ADD  `alerta_monto` DOUBLE NOT NULL DEFAULT  '0' AFTER  `alerta_hh` ,
 ADD  `limite_hh` DOUBLE NOT NULL DEFAULT  '0' AFTER  `alerta_monto` ,
ADD  `limite_monto` DOUBLE NOT NULL DEFAULT  '0' AFTER  `limite_hh` ;";
							 
			$query[] = "ALTER table contrato ADD `notificado_hr_excedido` tinyint(4) NOT NULL default '0' AFTER `usa_impuesto_gastos`,
ADD `notificado_monto_excedido_ult_cobro` tinyint(4) NOT NULL default '0' AFTER `notificado_hr_excedido`,
ADD `notificado_hr_excedida_ult_cobro` tinyint(4) NOT NULL default '0' AFTER `notificado_monto_excedido_ult_cobro`,
ADD `notificado_monto_excedido` tinyint(4) NOT NULL default '0' AFTER `notificado_hr_excedida_ult_cobro`";

			$query[] = "ALTER table cliente ADD `notificado_hr_excedido` tinyint(4) NOT NULL default '0' AFTER `limite_hh`,
ADD `notificado_monto_excedido_ult_cobro` tinyint(4) NOT NULL default '0' AFTER `notificado_hr_excedido`,
ADD `notificado_hr_excedida_ult_cobro` tinyint(4) NOT NULL default '0' AFTER `notificado_monto_excedido_ult_cobro`,
ADD `notificado_monto_excedido` tinyint(4) NOT NULL default '0' AFTER `notificado_hr_excedida_ult_cobro`";


			$query[] = "ALTER TABLE  `documento` CHANGE  `glosa_documento`  `glosa_documento` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";

			$query[] = "ALTER TABLE  `log_correo` ADD  `id_archivo_anexo` INT( 11 ) NULL DEFAULT NULL AFTER  `nombre` ;";
			
			$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
VALUES (
NULL ,  'AlertaCliente',  '0',  'Permite que los clientes tengan límites de Alerta.',  'boolean',  '6',  '20'
);";

					foreach($query as $q)
					if(!($resp = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;

		case 3.40:
					$query = array();
					$query[] = "ALTER TABLE  `contrato` ADD  `id_documento_legal` INT( 11 ) NULL AFTER  `centro_costo` ;";
					
				foreach($query as $q)
          if(!($resp = mysql_query($q, $dbh)))
            throw new Exception($q."---".mysql_error());
        break;

		case 3.41:
					$query = array();
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` , `orden` ) 
								VALUES ( NULL ,  'UsarGastosConSinImpuesto',  '0', NULL ,  'boolean',  '2',  '245');";

		foreach($query as $q)
			if(!($res = mysql_query($q, $dbh)))
				throw new Exception($q."---".mysql_error());
		break;
		
		case 3.42:
					$query = array();
					$query[] = "UPDATE cta_corriente SET monto_cobrable = ingreso WHERE (ingreso > 0) AND (monto_cobrable = 0 OR monto_cobrable IS NULL)";
					
					$query[] = "CREATE TABLE `factura_cobro` (
								`id_factura` INT( 11 ) NOT NULL ,
								`id_cobro` INT( 11 ) NOT NULL ,
								`id_documento` INT( 11 ) NOT NULL ,
								`monto_factura` DOUBLE NOT NULL ,
								`id_moneda_factura` INT( 11 ) NOT NULL ,
								`monto_documento_honorarios` DOUBLE NOT NULL ,
								`monto_documento_gastos` DOUBLE NOT NULL ,
								`id_moneda_documento` INT( 11 ) NOT NULL ,
								PRIMARY KEY ( `id_factura` , `id_cobro` ) 
								) ENGINE = INNODB COMMENT = 'Relación de facturas que incluyen cobros';";

					$query[] = "ALTER TABLE `factura_cobro` ADD `impuesto_factura` DOUBLE NOT NULL DEFAULT '0' AFTER `monto_factura` ;";

					$query[] = "ALTER TABLE `factura_cobro` ADD INDEX ( `id_documento` )";

					$query[] = "INSERT INTO factura_cobro( id_factura, id_cobro, id_documento, monto_factura, id_moneda_factura, id_moneda_documento ) (
								SELECT factura.id_factura, cobro.id_cobro, documento.id_documento, documento.monto, documento.id_moneda, documento.id_moneda
								FROM factura
								JOIN cobro ON factura.id_cobro = cobro.id_cobro
								JOIN documento ON documento.id_cobro = cobro.id_cobro
								AND documento.tipo_doc = 'N'
								WHERE 1)";

					$query[] = "ALTER TABLE `factura` ADD `id_factura_padre` INT( 11 ) NULL DEFAULT NULL AFTER `id_factura` ;";

					$query[] = "ALTER TABLE `factura` ADD INDEX ( `id_factura_padre` ) ;";


					$query[] = "CREATE TABLE `prm_documento_legal` (
								  `id_documento_legal` int(11) NOT NULL auto_increment,
								  `glosa` varchar(50) NOT NULL default '',
								  PRIMARY KEY  (`id_documento_legal`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1;";


					$query[] = "INSERT INTO `prm_documento_legal` VALUES  (1,'Factura'),
						 (2,'Nota de crédito'),
						 (3,'Nota de débito'),
						 (4,'Boleta');";

					$query[] = "
						CREATE TABLE `prm_documento_legal_motivo` (
						  `id_documento_legal_motivo` int(11) NOT NULL auto_increment,
						  `glosa` varchar(50) NOT NULL default '',
						  `id_documento_legal` int(11) NOT NULL default '0',
						  PRIMARY KEY  (`id_documento_legal_motivo`),
						  KEY `id_documento_legal` (`id_documento_legal`),
						  CONSTRAINT `prm_documento_legal_motivo_ibfk_1` FOREIGN KEY (`id_documento_legal`) REFERENCES `prm_documento_legal` (`id_documento_legal`) ON UPDATE CASCADE
						) ENGINE=InnoDB DEFAULT CHARSET=latin1";


				$query[] = "ALTER TABLE `factura` ADD COLUMN `id_documento_legal` INTEGER  NOT NULL AFTER `id_factura_padre`;";

				$query[] = "ALTER TABLE `factura` ADD COLUMN `id_documento_legal_motivo` INTEGER  NOT NULL AFTER `id_documento_legal`;";

				$query[] = "update factura set id_documento_legal = 1;";

				$query[] = "ALTER TABLE `factura` ADD `estado` VARCHAR( 12 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'ABIERTA' AFTER `id_cobro` ;";

				$query[] = "UPDATE factura SET estado = 'ANULADA' WHERE anulado =1";

				$query[] = "ALTER TABLE `prm_documento_legal` ADD `codigo` VARCHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'FA' AFTER`id_documento_legal` ;";

				$query[] = "UPDATE `prm_documento_legal` SET `codigo` = 'NC' WHERE `id_documento_legal` =2 LIMIT 1 ;";

				$query[] = "UPDATE `prm_documento_legal` SET `codigo` = 'ND' WHERE `id_documento_legal` =3 LIMIT 1 ;";

				$query[] = "UPDATE `prm_documento_legal` SET `codigo` = 'BO' WHERE `id_documento_legal` =4 LIMIT 1 ;";

				$query[] = "ALTER TABLE `factura` ADD `letra` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER `anulado` ;";

				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.43:
					$query = array();
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
												VALUES ( NULL ,  'NuevoModuloFactura',  '0', NULL ,  'boolean',  '6',  '-1' );";
												
				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.44:
					$query = array();
					$query[] = "ALTER TABLE `cobro` ADD COLUMN `id_formato` INTEGER  NOT NULL AFTER `id_carta`;";
					$query[] = "ALTER TABLE `contrato` ADD COLUMN `id_formato` INTEGER  NOT NULL AFTER `id_carta`;";
												
				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.45:
					$query = array();
					$query[] = "ALTER TABLE `cobro_rtf` ADD COLUMN `descripcion` varchar(60)  AFTER `id_formato`;";
												
				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.46:
					$query = array();
					$query[] = "ALTER TABLE `factura` ADD `subtotal_gastos_sin_impuesto` DOUBLE NOT NULL DEFAULT '0' AFTER `subtotal_gastos` ;";
					$query[] = "ALTER TABLE  `prm_tipo_proyecto` ADD  `orden` INT( 11 ) NOT NULL DEFAULT  '0';";
					$query[] = "UPDATE prm_tipo_proyecto SET orden = id_tipo_proyecto + 10";
					$query[] = "ALTER TABLE  `prm_area_proyecto` ADD  `orden` INT( 11 ) NOT NULL DEFAULT  '0';";
					$query[] = "UPDATE `prm_area_proyecto` SET orden = id_area_proyecto + 10";
					
				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.47:
					$query = array();
					$query[] = "INSERT INTO `configuracion` ( `id` , `glosa_opcion` , `valor_opcion` , `comentario` , `valores_posibles` , `id_configuracion_categoria` , `orden` )
											VALUES (
											NULL , 'LibreriaMenu', 'jquery', NULL , 'select; jquery; prototype', '6', '-1'
											);";
					
				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.48:
					$query = array();
					$query[] = "INSERT INTO `configuracion` ( `id` , `glosa_opcion` , `valor_opcion` , `comentario` , `valores_posibles` , `id_configuracion_categoria` , `orden` )
												VALUES (
												NULL , 'DesgloseFactura', 'sin_desglose', 'sin_desglose es la forma antigua de mostrar facturas,donde solo se muestra una unica glosa, condesglose separa honorario, gastos sin iva y con iva', 'select;sin_desglose;con_desglose', '6', '-1'
												);";
					//$query[] = "ALTER TABLE `factura` ADD `descripcion_honorarios` VARCHAR( 255 ) NOT NULL AFTER `subtotal_gastos_sin_impuesto` ;";
					$query[] = "ALTER TABLE `factura` ADD `descripcion_subtotal_gastos` VARCHAR( 255 ) NOT NULL AFTER `subtotal_gastos_sin_impuesto` ;";
					$query[] = "ALTER TABLE `factura` ADD `descripcion_subtotal_gastos_sin_impuesto` VARCHAR( 255 ) NOT NULL AFTER `descripcion_subtotal_gastos` ;";
				
				foreach($query as $q)
					if(!($res = mysql_query($q, $dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.49:
					$query = array();
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
												VALUES ( NULL ,  'CiudadEstudio',  'Santiago', NULL ,  'string',  '6',  '-1' );";
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
													 VALUES ( NULL ,  'PaisEstudio',  'Chile', NULL ,  'string',  '6',  '-1' );";
				
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.50:
					$query = array();
					$query[] = "INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('UsarGastosCobrable', '0', 'seleccionar si gastos es cobrable o no,siendo cobrable 1 y no cobrable 0', 'boolean', 2, 210);";
				
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.51:
					$query = array();
					$query[] = "ALTER TABLE `usuario` ADD `alerta_revisor` INT( 11 ) NOT NULL DEFAULT '0' AFTER `alerta_semanal` ;";
					$query[] = "ALTER TABLE `usuario` CHANGE `alerta_revisor` `alerta_revisor` TINYINT( 1 ) NOT NULL DEFAULT '0';";
					$query[] = "ALTER TABLE `usuario` ADD `restriccion_diario` SMALLINT( 6 ) NOT NULL DEFAULT '0' AFTER `retraso_max` ;";
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
													VALUES (
													NULL ,  'UsernameEnListaDeTrabajos',  '0',  'para que en la lista de trabajos sale el username como nombre y asi sea modificable por el administrador del estudio',  'boolean',  '6',  '-1'
													);";
					
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				
				case 3.52:
					$query = array();
					$query[] = "ALTER TABLE `cobro` CHANGE `documento` `documento` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Se refiere a la boleta o factura asociada'";
					
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.53:
					$query = array();
					$query[] = "ALTER TABLE  `usuario_revisor` DROP INDEX  `id_revisado` ,
												ADD INDEX  `id_revisado` (  `id_revisado` );";
				
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.54:
					$query = array();
					$query[] = "ALTER TABLE `prm_documento_legal` ADD `numero_inicial` INT NOT NULL COMMENT 'ultimo numero usado como instalación del documento legal para usar correlativo';";
				
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.55:
					$query = array();
					$query[] = "CREATE TABLE `prm_banco` (
								  `id_banco` int(11) NOT NULL auto_increment,
								  `nombre` varchar(50) NOT NULL default '',
								  `orden` int(11) NOT NULL default '0',
								  PRIMARY KEY  (`id_banco`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1";
					$query[] = "CREATE TABLE `cuenta_banco` (
								  `id_cuenta` int(11) NOT NULL auto_increment,
								  `id_banco` int(11) NOT NULL default '0',
								  `numero` varchar(40) NOT NULL default '',
								  PRIMARY KEY  (`id_cuenta`,`id_banco`),
								  KEY `id_cuenta` (`id_cuenta`),
								  KEY `id_banco` (`id_banco`),
								  CONSTRAINT `id_banco_fk` FOREIGN KEY (`id_banco`) REFERENCES `prm_banco` (`id_banco`) ON DELETE NO ACTION ON UPDATE CASCADE
								) ENGINE=InnoDB DEFAULT CHARSET=latin1";	
					$query[] = "ALTER TABLE `documento` ADD `id_banco` INT NOT NULL ,
								ADD `id_cuenta` INT NOT NULL ,
								ADD `numero_operacion` VARCHAR( 40 ) NOT NULL ,
								ADD `numero_cheque` VARCHAR( 40 ) NOT NULL ;";					
				
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.56:
					$query = array();
					$query_fact = "SELECT count(*) FROM menu WHERE codigo = 'FACT'";
					$resp = mysql_query($query_fact,$dbh) or Utiles::errorSQL($query_fact,__FILE__,__LINE__,$dbh);
					list( $factura_existe ) = mysql_fetch_array($resp);
					if( !$factura_existe && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NuevoModuloFactura') ) || !method_exists('Conf','GetConf') ) )
						{
							$query[] = "INSERT INTO `menu` (`codigo`, `glosa`, `url`, `descripcion`, `foto_url`, `tipo`, `orden`, `codigo_padre`) VALUES ('FACT', 'Factura', '/app/interfaces/facturas.php', '', '', 0, 53, 'COBRANZA');";
							$query[] = "INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
															VALUES (
															'ADM', 'FACT'
															);";					
						}
						
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.57:
					$query = array();
					$query[] = "ALTER TABLE `documento` ADD  `pago_retencion` VARCHAR( 1 ) NOT NULL DEFAULT  '0';";
					$query[] = "UPDATE usuario SET username = CONCAT_WS(' ', nombre, apellido1, apellido2) WHERE username = '' OR username IS NULL;";
				
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;
				
				case 3.58:
					$query = array();
					$query[] = "ALTER TABLE  `factura_cobro` CHANGE  `id_documento`  `id_documento` INT( 11 ) NULL DEFAULT  '0'";
					$query[] = "UPDATE factura SET id_documento_legal = 1 WHERE id_documento_legal = 0";

					foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.59:
					$query = array();
					$query[] = "INSERT INTO `menu` (`codigo`, `glosa`, `url`, `descripcion`, `foto_url`, `tipo`, `orden`, `codigo_padre`) VALUES ('FACTURA', 'Facturación', '/app/interfaces/mantencion_facturacion.php', '', '', 0, 300, 'ADMIN_SIS');";
					$query[] = "INSERT INTO `menu_permiso` (`codigo_permiso`, `codigo_menu`) VALUES ('ADM', 'FACTURA');";
				
					foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.60:
					$query = array();
					$query[] = "INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('LimpiarTrabajo', '0', 'Limpiar todos los campos luego de ingresar un trabajo', 'boolean', 2, 275)";

					foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.61:
					$query = array();
					$query[] = "CREATE TABLE `usuario_reporte` (
  `id_reporte` int(11) NOT NULL auto_increment,
  `id_usuario` int(11) NOT NULL default '0',
  `reporte` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`id_reporte`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='reportes avanzados guardados por un usuario' AUTO_INCREMENT=1 ;";

					$query[] = "ALTER TABLE `usuario_reporte`
  ADD CONSTRAINT `usuario_reporte_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
";
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.62:
					$query = array();
					$query[] = "ALTER TABLE `factura_rtf` ADD `id_tipo` INT( 11 ) NOT NULL ,
ADD `descripcion` VARCHAR( 40 ) NOT NULL ;";
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.63:
					$query = array();
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
												VALUES (
												NULL ,  'PagoRetencionImpuesto',  '0',  'Indica si se usa la funcionalidad del pago de retención',  'boolean',  '6',  '-1'
												);";
												
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.64:
					$query = array();
					$query[] = "INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('OcultarHorasTarificadasExcel', '0', 'Oculta columna horas tarificadas en generar excel, pero es usado para los calculos', 'boolean', 6, -1);";
												
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.65:
					$query_moneda_base = "SELECT glosa_moneda FROM prm_moneda WHERE moneda_base = 1";
					if(!($resp_moneda_base = mysql_query($query_moneda_base, $dbh)))
						throw new Exception($query_moneda_base."---".mysql_error());
					list($glosa_moneda_base) = mysql_fetch_array($resp_moneda_base);
				
					$query = array();
					$query[] = " INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
													VALUES (
													NULL ,  'MonedaTarifaPorDefecto',  '".$glosa_moneda_base."',  '',  'select;Peso;Dólar;UF;UTM;Euro;UTA',  '2',  '299'
													);";
													
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.66:
					$query = array();
					$query[] = "ALTER TABLE  `contrato` ADD  `opc_ver_detalle_retainer` TINYINT( 1 ) NOT NULL DEFAULT  '1' AFTER  `opc_restar_retainer` ;";
					$query[] = "ALTER TABLE  `cobro` ADD  `opc_ver_detalle_retainer` TINYINT( 1 ) NOT NULL DEFAULT  '1' AFTER  `opc_restar_retainer` ;";
					
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.67:
					$query = array();
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
												VALUES (
												NULL ,  'UsaUsernameEnTodoElSistema',  '1', NULL ,  'boolean',  '6',  '-1'
												);";
												
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.68:
					$query = array();
					$query[] = "CREATE TABLE `prm_color` (
													  `id_color` int(11) NOT NULL auto_increment,
													  `codigo_color` varchar(10) NOT NULL default '',
													  PRIMARY KEY  (`id_color`)
													) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=89 ;";
					$query[] = "INSERT INTO `prm_color` (`id_color`, `codigo_color`) VALUES (1, '#B4D3B5'),(2, '#E7EABB'),(3, '#FFCAD8'),(4, '#FFD2C4'),(5, '#FFE9A6'),
																	(6, '#A4BE81'),(7, '#BEFFA8'),(8, '#A8FFBE'),(9, '#E0E0E0'),(10, '#BEF'),(11, '#DD9'),(12, '#A4BBFF'),(13, '#CDA9FE'),(14, '#FDDC9F'),
																	(15, '#BABC5C'),(16, '#CCF'),(17, '#EAADAA'),(18, '#AD9F69'),(19, '#CABCE0'),(20, '#EB9C63'),(21, '#45989C'),(22, '#C0ACD7'),
																	(23, '#AFCC91'),(24, '#FFB0B0'),(25, '#BDAD75'),(26, '#E8E471'),(27, '#DC7E7E'),(28, '#BDD76F'),(29, '#A0A5D6'),(30, '#AACCAC'),
																	(31, '#A4BBFF'),(32, '#ECD7CA'),(33, '#D5C9AC'),(34, '#EDBFA9'),(35, '#FFEBAE'),(36, '#8E9C43'),(37, '#68BB77'),(38, '#85B188'),
																	(39, '#DAB6B6'),(40, '#429D99'),(41, '#FC9'),(42, '#E7CEFF'),(43, '#69C'),(44, '#FFEBAE'),(45, '#D2009E'),(46, '#D9C6FF'),(47, '#3DAB9A'),
																	(48, '#AB6573'),(49, '#C1CDBC'),(50, '#99CC66'),(51, '#9999CC'),(52, '#99CC99'),(53, '#CCFFCC'),(54, '#CCFF99'),(55, '#FFFF66'),
																	(56, '#F2F587'),(57, '#55A6AA'),(58, '#75808A'),(59, '#C7826D'),(60, '#C6DEAD'),(61, '#C6DEDA'),(62, '#AEAE00'),(63, '#3F7C7C'),
																	(64, '#FFCCF2'),(65, '#92CB45'),(66, '#8D8E82'),(67, '#BDEE73'),(68, '#CCC'),(69, '#996'),(70, '#CC6'),(71, '#699'),(72, '#F2F2F2'),
																	(73, '#E9BBBA'),(74, '#8C4646'),(75, '#C8EEFD'),(76, '#C5DEFA'),(77, '#D1D8D7'),(78, '#FF99FF'),(79, '#FC9'),(80, '#3C7B91'),
																	(81, '#5EA6BD'),(82, '#FEEEBC'),(83, '#CAC793'),(84, '#FEB1B4'),(85, '#CC6633'),(86, '#CCC'),(87, '#66CC66'),(88, '#66FF66');";
																	
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.69:
					$query = array();
					$query[] = "ALTER TABLE `tarea` ADD `alerta` INT( 2 ) NOT NULL DEFAULT '0' AFTER `prioridad` ;";
					
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.70:
					$query = array();
					$query[] = "INSERT INTO documento_moneda ( id_documento, id_moneda, tipo_cambio )
											SELECT dp.id_documento, dm.id_moneda, dm.tipo_cambio  
											FROM documento_moneda AS dm 
											LEFT JOIN neteo_documento AS nd ON nd.id_documento_cobro = dm.id_documento 
											JOIN documento AS dp ON dp.id_documento = nd.id_documento_pago 
											ON DUPLICATE KEY UPDATE tipo_cambio = dm.tipo_cambio ";
											
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.71:
					$query = array();
					$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
												VALUES (
												NULL ,  'ReporteRevisadosATodosLosAbogados',  '0', NULL ,  'boolean',  '3',  '460'
												);";
												
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.72:
					$query = array();
					if( !(method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura')) )
						$query[] = "DELETE FROM factura WHERE total = 0 AND id_cobro IS NULL AND cliente IS NULL";
					
				foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.73:
					$query = array();
					$query[] = "INSERT INTO `configuracion` SET 
						`glosa_opcion` =  'PrellenarTrabajoConActividad',
						`valor_opcion` = '0',
						`comentario` = 'Permite prellenar el detalle del trabajo con la glosa de la actividad',
						`valores_posibles` = 'boolean',
						`id_configuracion_categoria` = '6',
						`orden` = '-1';";
						
					foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;

				case 3.74:
					$query = array();
					$query[] = "INSERT INTO `configuracion` SET 
						`glosa_opcion` =  'CantidadDecimalesTotalFactura',
						`valor_opcion` = '-1',
						`comentario` = '',
						`valores_posibles` = 'numero',
						`id_configuracion_categoria` = '6',
						`orden` = '-1';";
						
					foreach($query as $q)
						if(!($res = mysql_query($q,$dbh)))
							throw new Exception($q."---".mysql_error());
				break;
				
				case 3.75:
					$query = array();
					$query[] = "ALTER TABLE `prm_area_proyecto` ADD `codigo_centro_costo` VARCHAR( 20 ) NOT NULL AFTER `glosa`";
					
				foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
						throw new Exception($q."---".mysql_error());
				break;







				case 3.76:
					$query = array();

					$q = "DESCRIBE cliente;";
					if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
					$campos = array();
					while(list($campos[]) = mysql_fetch_array($res));

					if(!in_array('fecha_creacion', $campos)){
						$query[] = "ALTER TABLE `cliente` ADD `fecha_creacion` DATETIME NOT NULL , ADD `fecha_modificacion` DATETIME NOT NULL ;";

						$q = "SELECT codigo_cliente, min( fecha_creacion ) FROM contrato GROUP BY codigo_cliente;";
						if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
						while(list($codigo, $fecha) = mysql_fetch_array($res)){
							$query[] = "UPDATE cliente set fecha_creacion = '$fecha' WHERE codigo_cliente = '$codigo';";
						}
					}

					foreach($query as $q)
						if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
					break;































































































































































































































































































































































































				
				case 4:
					$query = array(); 
					$query[] = "CREATE TABLE factura_documento_cobro (
  id int(11) NOT NULL auto_increment,
  id_factura int(11) NOT NULL default '0',
  id_documento_cobro int(11) default NULL,
  id_cobro int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY id_factura (id_factura),
  KEY id_documento_cobro (id_documento_cobro),
  KEY id_cobro (id_cobro)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

					$query[] = "CREATE TABLE prm_estado_factura (
  id_estado int(11) NOT NULL auto_increment,
  codigo char(1) NOT NULL default '',
  glosa varchar(50) NOT NULL default '',
  PRIMARY KEY  (id_estado),
  KEY codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

					$query[] = "INSERT INTO `prm_estado_factura` (`id_estado`, `codigo`, `glosa`)
						VALUES  (1, 'F', 'Facturado'),
								(2, 'C', 'Cobrado'),
								(3, 'O', 'Obsequio'),
								(4, 'L', 'Canjeado por letra'),
								(5, 'A', 'Anulado');";

					$query[] = "ALTER TABLE `factura` CHANGE `id_documento_legal_motivo` `id_documento_legal_motivo` INT( 11 ) NULL;";
					$query[] = "UPDATE `factura` SET `id_documento_legal_motivo` = NULL WHERE `id_documento_legal_motivo` = 0;";

					$query[] = "ALTER TABLE `factura` CHANGE `id_documento_legal` `id_documento_legal` INT( 11 ) NOT NULL DEFAULT '1' COMMENT 'tipo de documento legal';";
					$query[] = "ALTER TABLE `factura` ADD `id_estado` INT( 11 ) DEFAULT NULL AFTER `anulado` ;";

					$query[] = "UPDATE `factura` SET `id_documento_legal_motivo` = 1 WHERE `id_documento_legal_motivo` = 0;";

					$query[] = "ALTER TABLE `factura`
  ADD INDEX ( `id_documento_legal_motivo` ),
  ADD CONSTRAINT factura_ibfk_1 FOREIGN KEY (id_estado) REFERENCES prm_estado_factura (id_estado),
  ADD CONSTRAINT factura_ibfk_2 FOREIGN KEY (id_documento_legal_motivo) REFERENCES prm_documento_legal_motivo (id_documento_legal_motivo);";

					$query[] = "ALTER TABLE `factura_documento_cobro`
  ADD CONSTRAINT factura_documento_cobro_ibfk_3 FOREIGN KEY (id_cobro) REFERENCES cobro (id_cobro) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT factura_documento_cobro_ibfk_1 FOREIGN KEY (id_factura) REFERENCES factura (id_factura) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT factura_documento_cobro_ibfk_2 FOREIGN KEY (id_documento_cobro) REFERENCES documento (id_documento) ON DELETE SET NULL ON UPDATE CASCADE;";

					$query[] = "INSERT INTO factura_documento_cobro( id_factura, id_cobro, id_documento_cobro )
SELECT f.id_factura AS id_factura, f.id_cobro AS id_cobro, d.id_documento AS id_documento_cobro
FROM factura f
LEFT JOIN documento d ON ( f.id_cobro = d.id_cobro AND d.tipo_doc = 'N' )
INNER JOIN cobro c ON ( f.id_cobro = c.id_cobro );";

					$query[] = "ALTER TABLE `contrato` ADD `separar_liquidaciones` TINYINT( 1 ) NOT NULL DEFAULT '0' COMMENT 'generar las liquidaciones de honorarios y gastos por separado' AFTER `usa_impuesto_gastos` ;";

					$query[] = "ALTER TABLE `cobro` ADD `incluye_honorarios` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `id_cobro` ,
ADD `incluye_gastos` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `incluye_honorarios` ;";

					$query[] = "CREATE TABLE `contrato_documento_legal` (
											  `id_contrato_documento_legal` int(11) NOT NULL auto_increment,
											  `id_contrato` int(11) NOT NULL default '0',
											  `id_tipo_documento_legal` int(11) NOT NULL default '0',
											  `honorarios` tinyint(1) NOT NULL default '0',
											  `gastos_con_impuestos` tinyint(1) NOT NULL default '0',
											  `gastos_sin_impuestos` tinyint(1) NOT NULL default '0',
											  `fecha_creacion` date default NULL,
											  PRIMARY KEY  (`id_contrato_documento_legal`),
											  KEY `id_contrato` (`id_contrato`),
											  KEY `id_tipo_documento_legal` (`id_tipo_documento_legal`)
											) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='documentos legales que se generaran por defecto tras emitir ' AUTO_INCREMENT=1 ;";

					$query[] = "ALTER TABLE `contrato_documento_legal`
											  ADD CONSTRAINT `contrato_documento_legal_ibfk_2` FOREIGN KEY (`id_tipo_documento_legal`) REFERENCES `prm_documento_legal` (`id_documento_legal`) ON DELETE CASCADE ON UPDATE CASCADE,
											  ADD CONSTRAINT `contrato_documento_legal_ibfk_1` FOREIGN KEY (`id_contrato`) REFERENCES `contrato` (`id_contrato`) ON DELETE CASCADE ON UPDATE CASCADE;";

					$query[] = "ALTER TABLE `cobro_pendiente` ADD `incluye_gastos` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `id_cobro` ,
												ADD `incluye_honorarios` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `incluye_gastos` ;";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
				
				case 4.01:
					$query = array();

					$query[] = "ALTER TABLE `contrato_documento_legal` CHANGE `id_contrato` `id_contrato` INT( 11 ) NULL ";

					$query[] = "ALTER TABLE `contrato` ADD `opc_moneda_gastos` TINYINT( 4 ) NOT NULL DEFAULT '1' AFTER `opc_moneda_total` ;";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.02:
					$query = array();

					$query[] = "ALTER TABLE  `cobro` ADD  `se_esta_cobrando` VARCHAR( 254 ) NULL COMMENT  'glosa resumen de lo que se esta cobrando';";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.03:
						$query = array();
						
						$query[] = "CREATE TABLE `cta_cte_fact_mvto` (
												  `id_cta_cte_mvto` int(11) NOT NULL auto_increment,
												  `tipo_mvto` varchar(10) NOT NULL default '',
												  `id_factura` int(11) default NULL COMMENT 'si es un documento legal, apunta a la tabla factura (si no es null)',
												  `id_factura_pago` int(11) default NULL COMMENT 'si el movimiento es un pago, esto apunta al pago (si no es null)',
												  `id_moneda` int(11) default NULL,
												  `monto_neto` double NOT NULL default '0',
												  `monto_iva` double NOT NULL default '0',
												  `monto_bruto` double NOT NULL default '0',
												  `saldo` double NOT NULL default '0',
												  `id_cta_banco` int(11) default NULL,
												  `fecha_movimiento` datetime NOT NULL default '0000-00-00 00:00:00',
												  `fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
												  `fecha_modificacion` datetime NOT NULL default '0000-00-00 00:00:00',
												  PRIMARY KEY  (`id_cta_cte_mvto`),
												  UNIQUE KEY `id_factura` (`id_factura`),
												  UNIQUE KEY `id_factura_pago` (`id_factura_pago`)
												) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Movimientos de CtaCte Factura';";
						$query[] = "CREATE TABLE `cta_cte_fact_mvto_moneda` (
												  `id_cta_cte_fact_mvto_moneda` int(11) NOT NULL auto_increment,
												  `id_moneda` int(11) NOT NULL default '0',
												  `tipo_cambio` double NOT NULL default '0',
												  PRIMARY KEY  (`id_cta_cte_fact_mvto_moneda`)
												) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='tipos de cambio de los movimientos';";
						$query[] = "CREATE TABLE `cta_cte_fact_mvto_neteo` (
												  `id_cta_cte_mvto_neteo` int(11) NOT NULL auto_increment,
												  `id_mvto_deuda` int(11) NOT NULL default '0' COMMENT 'la factura. A este se le suma el monto',
												  `id_mvto_pago` int(11) NOT NULL default '0' COMMENT 'el pago. A este se le resta el valor',
												  `monto` double NOT NULL default '0',
												  `fecha_movimiento` datetime NOT NULL default '0000-00-00 00:00:00',
												  PRIMARY KEY  (`id_cta_cte_mvto_neteo`),
												  KEY `id_mvto_ingreso` (`id_mvto_deuda`),
												  KEY `id_mvto_egreso` (`id_mvto_pago`)
												) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Neteo de cuenta corriente facturas';";
						
					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.04:
					$query = array();
					
					$query[] = "CREATE TABLE  `factura_pago` (
											 `id_factura_pago` INT( 11 ) NOT NULL AUTO_INCREMENT ,
											 `fecha` DATE NOT NULL DEFAULT  '0000-00-00',
											 `codigo_cliente` VARCHAR( 10 ) NOT NULL DEFAULT  '',
											 `monto` DOUBLE NOT NULL DEFAULT  '0',
											 `id_moneda` INT( 11 ) NOT NULL DEFAULT  '0',
											 `tipo_doc` VARCHAR( 4 ) NOT NULL DEFAULT  '',
											 `nro_documento` INT( 30 ) DEFAULT NULL ,
											 `nro_cheque` INT( 30 ) DEFAULT NULL ,
											 `descripcion` TEXT,
											 `id_banco` INT( 11 ) NOT NULL DEFAULT  '0',
											 `id_cuenta` INT( 11 ) NOT NULL DEFAULT  '0',
											PRIMARY KEY (  `id_factura_pago` )
											) ENGINE = INNODB DEFAULT CHARSET = latin1 AUTO_INCREMENT =1;";
					$query[] = "ALTER TABLE `cta_cte_fact_mvto`
												  ADD CONSTRAINT `cta_cte_fact_mvto_ibfk_2` FOREIGN KEY (`id_factura_pago`) REFERENCES `factura_pago` (`id_factura_pago`) ON DELETE CASCADE ON UPDATE CASCADE,
												  ADD CONSTRAINT `cta_cte_fact_mvto_ibfk_1` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE;";
					$query[] = "ALTER TABLE `cta_cte_fact_mvto_neteo`
												  ADD CONSTRAINT `cta_cte_fact_mvto_neteo_ibfk_4` FOREIGN KEY (`id_mvto_pago`) REFERENCES `cta_cte_fact_mvto` (`id_cta_cte_mvto`) ON UPDATE CASCADE,
												  ADD CONSTRAINT `cta_cte_fact_mvto_neteo_ibfk_3` FOREIGN KEY (`id_mvto_deuda`) REFERENCES `cta_cte_fact_mvto` (`id_cta_cte_mvto`) ON UPDATE CASCADE;";
					$query[] = "ALTER TABLE  `factura` CHANGE  `cliente`  `cliente` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT  'Razón Social Cliente'";
					
					
					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.05:
					$query = array();

					$query[] = "ALTER TABLE  `cobro` ADD  `descuento_incobrable` DOUBLE NOT NULL ,
ADD  `descuento_obsequio` DOUBLE NOT NULL ;";
					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.06:
					$query = array();

					$query[] = "INSERT INTO cta_cte_fact_mvto (
									tipo_mvto,
									id_factura,
									id_factura_pago,
									id_moneda,
									monto_neto,
									monto_iva,
									monto_bruto,
									saldo,
									id_cta_banco,
									fecha_movimiento,
									fecha_creacion,
									fecha_modificacion) 

									SELECT
										tipo.codigo,
										fac.id_factura,
										NULL,
										fac.id_moneda,
										-fac.subtotal_sin_descuento - fac.subtotal_gastos - fac.subtotal_gastos_sin_impuesto,
										-fac.iva,
										-fac.total,
										-fac.total,
										NULL,
										fac.fecha,
										fac.fecha,
										fac.fecha

									FROM `factura` fac
									LEFT JOIN prm_documento_legal tipo ON fac.id_documento_legal = tipo.id_documento_legal
									LEFT JOIN cta_cte_fact_mvto mvto ON fac.id_factura = mvto.id_factura

									WHERE mvto.id_factura IS NULL;";
					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;


					case 4.07:
					$query = array();

					$query[] = "ALTER TABLE `cta_cte_fact_mvto_moneda` CHANGE `id_cta_cte_fact_mvto_moneda` `id_cta_cte_fact_mvto` INT( 11 ) NOT NULL;";
					$query[] = "ALTER TABLE `cta_cte_fact_mvto_moneda` DROP PRIMARY KEY;";
					$query[] = "ALTER TABLE `cta_cte_fact_mvto_moneda` ADD PRIMARY KEY ( `id_cta_cte_fact_mvto` , `id_moneda` ) ;";

					$query[] = "ALTER TABLE `cta_cte_fact_mvto_moneda`
  ADD CONSTRAINT `cta_cte_fact_mvto_moneda_ibfk_2` FOREIGN KEY (`id_moneda`) REFERENCES `prm_moneda` (`id_moneda`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cta_cte_fact_mvto_moneda_ibfk_1` FOREIGN KEY (`id_cta_cte_fact_mvto`) REFERENCES `cta_cte_fact_mvto` (`id_cta_cte_mvto`) ON DELETE CASCADE ON UPDATE CASCADE;";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;


					case 4.08:
					$query = array();

					$query[] = "INSERT INTO cta_cte_fact_mvto_moneda (id_cta_cte_fact_mvto, id_moneda, tipo_cambio) 
								SELECT mvto.id_cta_cte_mvto, cm.id_moneda, cm.tipo_cambio
								FROM cta_cte_fact_mvto mvto
								INNER JOIN factura fac ON fac.id_factura = mvto.id_factura
								JOIN cobro_moneda cm ON cm.id_cobro = fac.id_cobro;";

					$query[] = "ALTER TABLE `documento` ADD `id_factura_pago` INT NULL COMMENT 'si es un pago generado desde un pago a facturas, apunta al factura_pago q lo creo';";

					$query[] = "ALTER TABLE `documento` ADD INDEX ( `id_factura_pago` ) ;";

					$query[] = "ALTER TABLE `documento`
  ADD CONSTRAINT `documento_ibfk_13` FOREIGN KEY (`id_factura_pago`) REFERENCES `factura_pago` (`id_factura_pago`) ON DELETE CASCADE ON UPDATE CASCADE;";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.09:
					$query = array();

					$query[] = "CREATE TABLE  `factura_pago_rtf` (
								  `id_formato` int(11) NOT NULL auto_increment,
								  `descripcion` varchar(60) default NULL,
								  `factura_pago_formato` mediumtext NOT NULL,
								  `html_header` text NOT NULL,
								  `html_pie` text NOT NULL,
								  `factura_pago_template` text NOT NULL,
								  `factura_pago_css` text NOT NULL,
								  PRIMARY KEY  (`id_formato`)
								) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.10:
					$query = array();

					$query[] = "ALTER TABLE  `factura_pago` ADD  `pago_retencion` TINYINT( 1 ) NOT NULL DEFAULT  '0';";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.11:
					$query = array();

					$query[] = "ALTER TABLE  `prm_moneda` ADD  `glosa_moneda_plural` VARCHAR( 100 ) NOT NULL COMMENT  'este campo se usa principalmente en cambiar montos por palabras' AFTER `glosa_moneda` ;";
					$query[] = "UPDATE prm_moneda SET glosa_moneda_plural =  'Pesos' WHERE glosa_moneda =  'Peso'";
					$query[] = "UPDATE prm_moneda SET glosa_moneda_plural =   'Dólares' WHERE glosa_moneda =  'Dólar'";
					$query[] = "UPDATE prm_moneda SET glosa_moneda_plural =   'Euros' WHERE glosa_moneda =  'Euro'";
					$query[] = "UPDATE prm_moneda SET glosa_moneda_plural =   'Soles' WHERE glosa_moneda =  'Soles'";
					$query[] = "UPDATE prm_moneda SET glosa_moneda_plural =   'UF' WHERE glosa_moneda =  'UF'";
					$query[] = "UPDATE prm_moneda SET glosa_moneda_plural =   'UTM'WHERE glosa_moneda =  'UTM'";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.12:
					$query = array();

					$query[] = "ALTER TABLE  `cuenta_banco` ADD  `id_moneda` INT NOT NULL ;";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.13:
					$query = array();

					$query[] = "CREATE TABLE `prm_factura_pago_concepto` (
  `id_concepto` int(11) NOT NULL auto_increment,
  `glosa` varchar(50) NOT NULL default '',
  `orden` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_concepto`)
);";
					$query[] = "ALTER TABLE  `factura_pago` ADD  `id_concepto` INT NOT NULL COMMENT  'es la glosa que se muestra en el voucher';";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.14:
					$query = array();

					$query[] = 	"ALTER TABLE  `prm_factura_pago_concepto` ADD  `pje_variable` INT NOT NULL COMMENT  'si es 1, se hace un replace de % por el % que corresponda' AFTER  `glosa` ;";
				
					$query[] = "INSERT INTO `prm_factura_pago_concepto` (`id_concepto`, `glosa`, `pje_variable`, `orden`) VALUES
(1, 'Cobranza al 100%', 0, 2),
(2, 'Cobranza de detracción 12%', 0, 6),
(3, 'Comisión y gastos bancarios', 0, 7),
(4, 'Retención', 0, 15),
(5, 'Dscto. nota de credito', 0, 8),
(6, 'Cobranza al 88%', 0, 3),
(7, 'Gastos Financieros', 0, 10),
(8, 'Pago a cuenta', 0, 13),
(9, 'Saldo de factura', 0, 14),
(10, 'Pago por canje', 0, 12),
(11, 'Otros ingresos', 0, 11),
(12, 'Ajuste por tipo de cambio', 0, 1),
(13, 'Cobranza al 6%', 0, 4),
(14, 'Cobranza al 94%', 0, 5),
(15, 'Dscto. nota de credito parcial', 0, 9),
(16, 'Concepto pagado', 0, 16);";

					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.15:
					$query = array();

					$query[] = 	"ALTER TABLE `cta_cte_fact_mvto` ADD `anulado` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `saldo` ;";
				
					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;

					case 4.16:
						$query = array();

						$query[] = "ALTER TABLE `contrato` ADD `opc_ver_valor_hh_flat_fee` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_detalle_retainer` ;";
						$query[] = "ALTER TABLE `cobro` ADD `opc_ver_valor_hh_flat_fee` TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER `opc_ver_detalle_retainer` ;";
						$query[] = "INSERT INTO `configuracion` ( `id` , `glosa_opcion` , `valor_opcion` , `comentario` , `valores_posibles` , `id_configuracion_categoria` , `orden` )
						VALUES (
						NULL , 'SerieDocumentosLegales', '2', NULL , 'numero', '6', '-1'
						);";
						$query[] = "ALTER TABLE `factura` ADD `serie_documento_legal` TINYINT( 4 ) NOT NULL DEFAULT '1' AFTER `numero` ;";

					foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
					throw new Exception($q."---".mysql_error());
					break;

					case 4.17:
					$query = array();

					$query[] = "ALTER TABLE `factura` ADD UNIQUE (
					`id_documento_legal` ,
					`numero` ,
					`serie_documento_legal`
					)";

					foreach($query as $q)
					if(!($res = mysql_query($q,$dbh)))
					throw new Exception($q."---".mysql_error());
					break;


					case 4.18:
					$query = array();

					$query[] = 	"ALTER TABLE  `prm_documento_legal` ADD  `grupo` VARCHAR( 40 ) NOT NULL ;";
					$query[] =  "UPDATE  `prm_documento_legal` SET  `grupo` =  'VENTA' WHERE  `prm_documento_legal`.`id_documento_legal` =1 LIMIT 1 ;";
					$query[] =  "UPDATE  `prm_documento_legal` SET  `grupo` =  'VENTA' WHERE  `prm_documento_legal`.`id_documento_legal` =2 LIMIT 1 ;";
					foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					/*Crea tbl usuario_vacacion*/
					case 4.19:
						$query = array();
						$query[] = "CREATE TABLE `usuario_vacacion` (
							  `id` int(11) NOT NULL auto_increment,
							  `id_usuario` int(10) NOT NULL default '0',
							  `id_usuario_creador` int(10) default NULL,
							  `fecha_inicio` date NOT NULL default '0000-00-00',
							  `fecha_fin` date NOT NULL default '0000-00-00',
							  PRIMARY KEY  (`id`),
							  KEY `id_usuario` (`id_usuario`),
							  KEY `usuario_creador` (`id_usuario_creador`)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='vacaciones de usuarios' AUTO_INCREMENT=1;";
						$query[] = "ALTER TABLE `usuario_vacacion`
				  		ADD CONSTRAINT `usuario_vacacion_ibfk_2` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE SET NULL,
				  		ADD CONSTRAINT `usuario_vacacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;";
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.20:
						$query = array();
						$query[] = "ALTER TABLE `factura_pago` ADD `monto_moneda_cobro` DOUBLE NOT NULL COMMENT 'monto en la moneda del cobro' AFTER `id_moneda` ;";
						$query[] = "ALTER TABLE `factura_pago` ADD `id_moneda_cobro` INT NOT NULL DEFAULT '1' COMMENT 'moneda del cobro' AFTER `monto_moneda_cobro` ;";
						$query[] = "ALTER TABLE `cta_cte_fact_mvto_neteo` ADD `monto_pago` DOUBLE NOT NULL COMMENT 'monto en la moneda del pago' AFTER `monto` ;";
						$query[] = "ALTER TABLE `cta_cte_fact_mvto_neteo` CHANGE `monto` `monto` DOUBLE NOT NULL DEFAULT '0' COMMENT 'monto en la moneda de la deuda'";
						$query[] = "UPDATE `factura_pago` SET `monto_moneda_cobro` = `monto`, `id_moneda_cobro` = `id_moneda` WHERE 1";
						$query[] = "UPDATE `cta_cte_fact_mvto_neteo` SET `monto_pago` = `monto` WHERE 1"; 
						
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.21:
						$query = array();
						$query[] = "ALTER TABLE  `factura` ADD  `porcentaje_impuesto` DOUBLE NOT NULL COMMENT  'cada factura almacena su % impuesto, y en base a este se deben realizar los calculos';";
						$query[] = "UPDATE factura SET porcentaje_impuesto = ((iva*100)/honorarios)";
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.22:
						$query = array();
						$query[] = "CREATE TABLE `usuario_cambio_historial` (
						  `id_usuario` int(11) default NULL,
						  `id_usuario_creador` int(11) NOT NULL default '0',
						  `nombre_dato` varchar(255) default NULL,
						  `valor_original` text,
						  `valor_actual` text,
						  `fecha` datetime default NULL,
						  KEY `id_usuario` (`id_usuario`),
						  KEY `usuario_creador` (`id_usuario_creador`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial de cambios en usuarios';";
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.23:
						$query_consulta = "SELECT glosa_moneda FROM prm_moneda";
						$resp_consulta = mysql_query($query_consulta,$dbh) or Utiles::errorSQL($query_consulta,__FILE__,__LINE__,$dbh);
						
						$query_moneda_base = "SELECT glosa_moneda FROM prm_moneda WHERE moneda_base = 1";
						if(!($resp_moneda_base = mysql_query($query_moneda_base, $dbh)))
							throw new Exception($query_moneda_base."---".mysql_error());
						list($glosa_moneda_base) = mysql_fetch_array($resp_moneda_base);
					
						$valores_posibles = "select";
						while(list($glosa)=mysql_fetch_array($resp_consulta))
							$valores_posibles .= ";$glosa";
							
						$query = array();
						$query[] = "ALTER TABLE  `contrato` CHANGE  `centro_costo`  `centro_costo` VARCHAR( 20 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT  'Depricated, no se usa'";
						$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
													VALUES (
														NULL ,  'MonedaTotalPorDefecto',  '".$glosa_moneda_base."',  '',  '".$valores_posibles."', 2, 299
													);";
						$query[] = "ALTER TABLE `factura` ADD INDEX ( `id_cobro` );";
						$query[] = "ALTER TABLE `factura` ADD INDEX ( `id_estado` );";
						$query[] = "ALTER TABLE `factura` ADD INDEX ( `id_moneda` );";
						$query[] = "ALTER TABLE `factura` ADD FOREIGN KEY ( `id_cobro` ) REFERENCES `rebaza_timetracking`.`cobro` (
												`id_cobro`
												) ON DELETE RESTRICT ON UPDATE CASCADE ;";
						$query[] = "ALTER TABLE `factura` ADD FOREIGN KEY ( `id_estado` ) REFERENCES `rebaza_timetracking`.`prm_estado_factura` (
												`id_estado`
												) ON DELETE RESTRICT ON UPDATE CASCADE ;";
						$query[] = "ALTER TABLE `factura` ADD FOREIGN KEY ( `id_moneda` ) REFERENCES `rebaza_timetracking`.`prm_moneda` (
												`id_moneda`
												) ON DELETE RESTRICT ON UPDATE CASCADE ;";
						
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
					
					case 4.24:
						$query = array();
						$query[] = "INSERT INTO  `configuracion` ( `id` , `glosa_opcion` , `valor_opcion` , `comentario` , `valores_posibles` , `id_configuracion_categoria` , `orden` ) 
													VALUES (
													NULL ,  'ExcelGastosDesglosado', '0', NULL ,  'boolean',  '6',  '-1'
													);";
						
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;
			
					case 4.25:
						$query = array();
						$query[] = "INSERT INTO `configuracion` ( `id` , `glosa_opcion` , `valor_opcion` , `comentario` , `valores_posibles` , `id_configuracion_categoria` , `orden` ) VALUES (NULL , 'ValidacionesCliente', '0', NULL , 'boolean', '6', '-1');";
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
					break;

					case 4.26:
						$query = array();
						$query[] = "UPDATE  `prm_excel_cobro` SET  `glosa_es` =  'N°', `glosa_en` =  'N°' WHERE  `prm_excel_cobro`.`id_prm_excel_cobro` =1 LIMIT 1 ;";
						$query[] = "UPDATE  `prm_excel_cobro` SET  `glosa_es` =  'Factura N°', `glosa_en` =  'Invoice N°' WHERE  `prm_excel_cobro`.`id_prm_excel_cobro` =48 LIMIT 1 ;";
						$query[] = "UPDATE  `prm_excel_cobro` SET  `glosa_es` =  'Minuta de cobro N°', `glosa_en` =  'Bill of charge N°' WHERE  `prm_excel_cobro`.`id_prm_excel_cobro` =49 LIMIT 1 ;";
						$query[] = "UPDATE  `prm_excel_cobro` SET  `glosa_es` =  'N°', `glosa_en` =  'N°' WHERE  `prm_excel_cobro`.`id_prm_excel_cobro` =52 LIMIT 1 ;";
						
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
					break;

					//este cambio se implemento paralelamente en la version antigua (case 3.76), asi q primero se revisa si el campo ya se habia agregado para no tratar de hacerlo de nuevo
					case 4.27:
						$query = array();

						$q = "DESCRIBE cliente;";
						if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
						$campos = array();
						while(list($campos[]) = mysql_fetch_array($res));

						if(!in_array('fecha_creacion', $campos)){
							$query[] = "ALTER TABLE `cliente` ADD `fecha_creacion` DATETIME NOT NULL , ADD `fecha_modificacion` DATETIME NOT NULL ;";

							$q = "SELECT codigo_cliente, min( fecha_creacion ) FROM contrato GROUP BY codigo_cliente;";
							if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
							while(list($codigo, $fecha) = mysql_fetch_array($res)){
								$query[] = "UPDATE cliente set fecha_creacion = '$fecha' WHERE codigo_cliente = '$codigo';";
							}
						}

						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
					break;

					case 4.28:
						$query_consulta = "SELECT count(*) FROM tramite_tarifa";
						$resp_consulta = mysql_query($query_consulta,$dbh) or Utiles::errorSQL($query_consulta,__FILE__,__LINE__,$dbh);
						list($cantidad) = mysql_fetch_array($resp_consulta);
						
						$query = array();
						if( $cantidad = 0 )
							$query[] = "INSERT INTO `tramite_tarifa` ( `id_tramite_tarifa` , `glosa_tramite_tarifa` , `fecha_creacion` , `fecha_modificacion` , `tarifa_defecto` , `guardado` )
															 VALUES ( '1',  'TARIFA BASE',  '0000-00-00 00:00:00', NULL ,  '1',  '1' );";
						
						$query[] = "INSERT INTO  `configuracion` (  `id` ,  `glosa_opcion` ,  `valor_opcion` ,  `comentario` ,  `valores_posibles` ,  `id_configuracion_categoria` ,  `orden` ) 
														VALUES (
														NULL ,  'MaxDuracionTrabajo',  '14',  'duración maxima que puede tener un trabajo',  'numero',  '2',  '240'
														);";
							
						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh)))
								throw new Exception($q."---".mysql_error());
					break;


					case 4.29:
						$query = array();
						$query[] = "CREATE TABLE `prm_proveedor` (
									  `id_proveedor` int(11) NOT NULL auto_increment,
									  `rut` varchar(12) NOT NULL default '',
									  `glosa` varchar(50) NOT NULL default '',
									  PRIMARY KEY  (`id_proveedor`)
									) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
						$query[] = "ALTER TABLE `cta_corriente` ADD  `id_proveedor` INT( 11 ) NOT NULL ;";

						foreach($query as $q)
							if(!($res = mysql_query($q,$dbh))) throw new Exception($q."---".mysql_error());
					break;


 	}
}


/* PASO 2: Agregar el numero de version al arreglo VERSIONES.
	(No olvidar agregar la notificacion de los cambios)*/

	$num = 0;
	$VERSIONES[$num++] = 1.0;
	$VERSIONES[$num++] = 1.1;
	$VERSIONES[$num++] = 1.2;
	$VERSIONES[$num++] = 1.3;
	$VERSIONES[$num++] = 1.4;
	$VERSIONES[$num++] = 1.5;
	$VERSIONES[$num++] = 1.6;
	$VERSIONES[$num++] = 1.7;
	$VERSIONES[$num++] = 1.8;
	$VERSIONES[$num++] = 1.9;
	$VERSIONES[$num++] = 2;
	$VERSIONES[$num++] = 2.1;
	$VERSIONES[$num++] = 2.2;
	$VERSIONES[$num++] = 2.21;
	$VERSIONES[$num++] = 2.22;
	$VERSIONES[$num++] = 2.23;
	$VERSIONES[$num++] = 2.24;
	$VERSIONES[$num++] = 2.25;
	$VERSIONES[$num++] = 2.26;
	$VERSIONES[$num++] = 2.27;
	$VERSIONES[$num++] = 2.28;
	$VERSIONES[$num++] = 2.29;
	$VERSIONES[$num++] = 2.3;
	$VERSIONES[$num++] = 2.31;
	$VERSIONES[$num++] = 2.32;
	$VERSIONES[$num++] = 2.33;
	$VERSIONES[$num++] = 2.34;
	$VERSIONES[$num++] = 2.35;
	$VERSIONES[$num++] = 2.36;
	$VERSIONES[$num++] = 2.4;
	$VERSIONES[$num++] = 2.41;
	$VERSIONES[$num++] = 2.42;
	$VERSIONES[$num++] = 2.43;
	$VERSIONES[$num++] = 2.44;
	$VERSIONES[$num++] = 2.45;
	$VERSIONES[$num++] = 2.46;
	$VERSIONES[$num++] = 2.47;
	$VERSIONES[$num++] = 2.48;
	$VERSIONES[$num++] = 2.49;
	$VERSIONES[$num++] = 2.5;
	$VERSIONES[$num++] = 2.51;
	$VERSIONES[$num++] = 2.52;
	$VERSIONES[$num++] = 2.53;
	$VERSIONES[$num++] = 2.54;
	$VERSIONES[$num++] = 2.55;
	$VERSIONES[$num++] = 2.56;
	$VERSIONES[$num++] = 2.57;
	$VERSIONES[$num++] = 2.59;
	$VERSIONES[$num++] = 2.6;
	$VERSIONES[$num++] = 2.61;
	$VERSIONES[$num++] = 2.62;
	$VERSIONES[$num++] = 2.63;
	$VERSIONES[$num++] = 2.64;
	$VERSIONES[$num++] = 2.65;
	$VERSIONES[$num++] = 2.66;
	$VERSIONES[$num++] = 2.67;
	$VERSIONES[$num++] = 2.68;
	$VERSIONES[$num++] = 2.69;
	$VERSIONES[$num++] = 2.70;
	$VERSIONES[$num++] = 2.71;
	$VERSIONES[$num++] = 2.72;
	$VERSIONES[$num++] = 2.73;
	$VERSIONES[$num++] = 2.74;
	$VERSIONES[$num++] = 2.75;
	$VERSIONES[$num++] = 2.76;
	$VERSIONES[$num++] = 2.8;
	$VERSIONES[$num++] = 2.81;
	$VERSIONES[$num++] = 2.82;
	$VERSIONES[$num++] = 2.83;
	$VERSIONES[$num++] = 2.84;
	$VERSIONES[$num++] = 2.85;
	$VERSIONES[$num++] = 2.86;
	$VERSIONES[$num++] = 2.87;
	$VERSIONES[$num++] = 2.88;
	$VERSIONES[$num++] = 2.89;
	$VERSIONES[$num++] = 2.90;
	$VERSIONES[$num++] = 2.91;
	$VERSIONES[$num++] = 2.92;
	$VERSIONES[$num++] = 2.93;
	$VERSIONES[$num++] = 2.94;
	$VERSIONES[$num++] = 2.95;
	$VERSIONES[$num++] = 2.96;
	$VERSIONES[$num++] = 2.97;
	$VERSIONES[$num++] = 2.98;
	$VERSIONES[$num++] = 2.99;
	$VERSIONES[$num++] = 3.00;
	$VERSIONES[$num++] = 3.01;
	$VERSIONES[$num++] = 3.02;
	$VERSIONES[$num++] = 3.03;
	$VERSIONES[$num++] = 3.04;
	$VERSIONES[$num++] = 3.05;
	$VERSIONES[$num++] = 3.06;
	$VERSIONES[$num++] = 3.07;
	$VERSIONES[$num++] = 3.08;
	$VERSIONES[$num++] = 3.09;
	$VERSIONES[$num++] = 3.10;
	$VERSIONES[$num++] = 3.11;
	$VERSIONES[$num++] = 3.12;
	$VERSIONES[$num++] = 3.13;
	$VERSIONES[$num++] = 3.14;
	$VERSIONES[$num++] = 3.15;
	$VERSIONES[$num++] = 3.16;
	$VERSIONES[$num++] = 3.17;
	$VERSIONES[$num++] = 3.18;
	$VERSIONES[$num++] = 3.19;
	$VERSIONES[$num++] = 3.20;
	$VERSIONES[$num++] = 3.21;
	$VERSIONES[$num++] = 3.22;
	$VERSIONES[$num++] = 3.23;
	$VERSIONES[$num++] = 3.24;
	$VERSIONES[$num++] = 3.25;
	$VERSIONES[$num++] = 3.26;
	$VERSIONES[$num++] = 3.27;
	$VERSIONES[$num++] = 3.28;
	$VERSIONES[$num++] = 3.29;
	$VERSIONES[$num++] = 3.30;
	$VERSIONES[$num++] = 3.31;
	$VERSIONES[$num++] = 3.32;
	$VERSIONES[$num++] = 3.33;
	$VERSIONES[$num++] = 3.34;
	$VERSIONES[$num++] = 3.35;
	$VERSIONES[$num++] = 3.36;
	$VERSIONES[$num++] = 3.37;
	$VERSIONES[$num++] = 3.38;
	$VERSIONES[$num++] = 3.39;
	$VERSIONES[$num++] = 3.40;
	$VERSIONES[$num++] = 3.41;
	$VERSIONES[$num++] = 3.42;
	$VERSIONES[$num++] = 3.43;
	$VERSIONES[$num++] = 3.44;
	$VERSIONES[$num++] = 3.45;
	$VERSIONES[$num++] = 3.46;
	$VERSIONES[$num++] = 3.47;
	$VERSIONES[$num++] = 3.48;
	$VERSIONES[$num++] = 3.49;
	$VERSIONES[$num++] = 3.50;
	$VERSIONES[$num++] = 3.51;
	$VERSIONES[$num++] = 3.52;
	$VERSIONES[$num++] = 3.53;
	$VERSIONES[$num++] = 3.54;
	$VERSIONES[$num++] = 3.55;
	$VERSIONES[$num++] = 3.56;
	$VERSIONES[$num++] = 3.57;
	$VERSIONES[$num++] = 3.58;
	$VERSIONES[$num++] = 3.59;
	$VERSIONES[$num++] = 3.60;
	$VERSIONES[$num++] = 3.61;
	$VERSIONES[$num++] = 3.62;
	$VERSIONES[$num++] = 3.63;
	$VERSIONES[$num++] = 3.64;
	$VERSIONES[$num++] = 3.65;
	$VERSIONES[$num++] = 3.66;
	$VERSIONES[$num++] = 3.67;
	$VERSIONES[$num++] = 3.68;
	$VERSIONES[$num++] = 3.69;
	$VERSIONES[$num++] = 3.70;
	$VERSIONES[$num++] = 3.71;
	$VERSIONES[$num++] = 3.72;
	$VERSIONES[$num++] = 3.73;
	$VERSIONES[$num++] = 3.74;
	$VERSIONES[$num++] = 3.75;
	$VERSIONES[$num++] = 4;
	$VERSIONES[$num++] = 4.01;
	$VERSIONES[$num++] = 4.02;
	$VERSIONES[$num++] = 4.03;
	$VERSIONES[$num++] = 4.04;
	$VERSIONES[$num++] = 4.05;
	$VERSIONES[$num++] = 4.06;
	$VERSIONES[$num++] = 4.07;
	$VERSIONES[$num++] = 4.08;
	$VERSIONES[$num++] = 4.09;
	$VERSIONES[$num++] = 4.10;
	$VERSIONES[$num++] = 4.11;
	$VERSIONES[$num++] = 4.12;
	$VERSIONES[$num++] = 4.13;
	$VERSIONES[$num++] = 4.14;
	$VERSIONES[$num++] = 4.15;
	$VERSIONES[$num++] = 4.16;
	$VERSIONES[$num++] = 4.17;
	$VERSIONES[$num++] = 4.18;
	$VERSIONES[$num++] = 4.19;
	$VERSIONES[$num++] = 4.20;
	$VERSIONES[$num++] = 4.21;
	$VERSIONES[$num++] = 4.22;
	$VERSIONES[$num++] = 4.23;
	$VERSIONES[$num++] = 4.24;
	$VERSIONES[$num++] = 4.25;
	$VERSIONES[$num++] = 4.26;
	$VERSIONES[$num++] = 4.27;
	$VERSIONES[$num++] = 4.28;
	$VERSIONES[$num++] = 4.29;

/* LISTO, NO MODIFICAR NADA MÁS A PARTIR DE ESTA LÍNEA */

function IngresarNotificacion($notificacion,$permisos=array('ALL'))
{
	global $sesion;
	$q = "INSERT INTO notificacion SET fecha=NOW(),texto_notificacion='".$notificacion."'";
	if(! ($resp = mysql_query($q,$sesion->dbh)))
					throw new Exception($q ."---".mysql_error());

	$where = "usuario_permiso.codigo_permiso='ADM'";
	foreach($permisos as $p)
	{
		$where .= " OR usuario_permiso.codigo_permiso='".$p."'";
	}

	$query = "UPDATE usuario
						SET usuario.id_notificacion_tt=LAST_INSERT_ID()
						WHERE usuario.id_usuario NOT IN
						(SELECT usuario_permiso.id_usuario FROM usuario_permiso WHERE $where)";
	if(! ($resp = mysql_query($query,$sesion->dbh)))
		throw new Exception($query ."---".mysql_error());
}

    require_once dirname(__FILE__).'/../app/conf.php';
    require_once dirname(__FILE__).'/../fw/classes/Sesion.php';
    require_once dirname(__FILE__).'/../app/classes/Cliente.php';
    require_once dirname(__FILE__).'/../app/classes/Asunto.php';
    require_once dirname(__FILE__).'/../app/classes/Cobro.php';
    require_once dirname(__FILE__).'/../app/classes/Documento.php';

	if( $_GET['hash'] != Conf::Hash() )
		die('Credenciales inválidas.');

	$versionFileName = dirname(__FILE__).'/../app/version.php';

	if( !file_exists($versionFileName) )
		die('Error, el archivo de versión no se encuentra.');
	if( !is_writable($versionFileName) )
		die('Error, el archivo de versión no se puede escribir.');

    require_once $versionFileName;

	if( !isset($VERSION) or $VERSION < 0.01 )
		die('Error en la versión del software.');

	$sesion = new Sesion();

	foreach( $VERSIONES as $key => $new_version )
	{
		if( $VERSION <  $new_version )
		{
			echo '<hr>Comienzo de proceso de cambios para versión '.number_format($new_version,2,'.','').'<br>';

			try
			{
				if( !mysql_query("START TRANSACTION", $sesion->dbh) )
					throw new Exception(mysql_error($sesion->dbh));

				if( !mysql_query("BEGIN", $sesion->dbh) )
					throw new Exception(mysql_error($sesion->dbh));

				Actualizaciones( $sesion->dbh, $new_version );

				if(!mysql_query("COMMIT", $sesion->dbh))
					throw new Exception(mysql_error($sesion->dbh));
			}
			catch( Exception $exc )
			{
				if(!mysql_query("ROLLBACK", $sesion->dbh))
					echo 'Error en ROLLBACK: '.mysql_error($sesion->dbh);

				echo 'Error en proceso de cambios para versión '.number_format($new_version,2,'.','').'<br>';
				echo( 'Se encontró un error: '.$exc->getMessage() );
				exit(1);
			}

			GuardarVersion( $versionFileName, $new_version );
			echo 'Proceso de cambios para versión '.number_format($new_version,2,'.','').' finalizado<br>';
		}
	}

	function GuardarVersion( $versionFileName, $new_version )
	{
		$data = '<?	$VERSION = '.number_format($new_version,2,'.','').' ; if( $_GET[\'show\'] == 1 ) echo \'Ver. \'.$VERSION; ?>';
		file_put_contents( $versionFileName, $data );
	}
?>
