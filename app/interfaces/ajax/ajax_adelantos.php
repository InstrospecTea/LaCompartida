<?php

	require_once dirname(__FILE__).'/../../conf.php';
	
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/classes/Documento.php';
	
$sesion = new Sesion(array('OFI','COB','SEC'));	
$params_array['codigo_permiso'] = 'COB';
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	
function formatofecha($fechasucia) {
    $fechasucia=explode('-',str_replace('/','-',$fechasucia));
    $fechalimpia=intval($fechasucia[2].$fechasucia[1].$fechasucia[0]);
    return $fechalimpia;
}

        

      if($_REQUEST['accion']=='listaadelanto') {

$query = "SELECT
	 
	documento.id_documento,
	
	cliente.glosa_cliente,
	documento.fecha,
	cliente.codigo_cliente,
if(documento.id_contrato is null, 'Todos los Asuntos',  asuntos.glosa_asuntos) as asuntos, 

	IF(documento.monto = 0, 0, documento.monto*-1) AS monto,
	IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1) AS saldo_pago,
	CONCAT(prm_moneda.simbolo, ' ', IF(documento.monto = 0, 0, documento.monto*-1)) AS monto_con_simbolo,
	CONCAT(prm_moneda.simbolo, ' ', IF(documento.saldo_pago = 0, 0, documento.saldo_pago*-1)) AS saldo_pago_con_simbolo,
	documento.glosa_documento, prm_moneda.id_moneda
	
FROM
	documento
	  JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
	  JOIN cliente ON documento.codigo_cliente = cliente.codigo_cliente
	  left join (SELECT codigo_cliente, id_contrato, GROUP_CONCAT( codigo_asunto ) AS codigo_asuntos,
	  GROUP_CONCAT( codigo_asunto_secundario ) AS codigo_asuntos_secundarios, GROUP_CONCAT( glosa_asunto ) AS glosa_asuntos
		FROM asunto
		GROUP BY id_contrato,codigo_cliente) asuntos on documento.codigo_cliente=asuntos.codigo_cliente and (documento.id_contrato=asuntos.id_contrato) 
WHERE
	es_adelanto = 1";



if(isset($_GET['tiene_saldo']) && $_GET['tiene_saldo']==1) 	$query .= " AND saldo_pago < 0 ";
if (isset($_GET['id_documento'])  && intval($_GET['id_documento'])>0  ) 	$query .= " AND documento.id_documento = " .intval($_GET['id_documento']);
if (isset($_GET['campo_codigo_asunto']) && strlen($_GET['campo_codigo_asunto'])>0  ) $query .= " AND asuntos.codigo_asuntos like '%".$_GET['campo_codigo_asunto']."%'";
if (isset($_GET['codigo_cliente']) && strlen($_GET['codigo_cliente'])>0  ) $query .= " AND cliente.codigo_cliente = '" .$_GET['codigo_cliente'] . "' ";

if (isset($_GET['fecha1']) && strlen($_GET['fecha1'])>0  ) 	$query .= " AND documento.fecha >= ".formatofecha($_GET['fecha1']);
if (isset($_GET['fecha2'])  && strlen($_GET['fecha2'])>0  )	$query .= " AND documento.fecha <=  ".formatofecha($_GET['fecha2']);
if (isset($_GET['moneda'])  )	$query .= " AND documento.id_moneda = " . intval($_GET['moneda']);
if(isset($_GET['id_contrato'])) 	$query .= " AND (documento.id_contrato = '".intval($_GET['id_contrato'])."' OR documento.id_contrato IS NULL)";

 
//echo $query;
   $resp = mysql_query($query, $sesion->dbh) or die( 'Error MySQL '.mysql_error($sesion->dbh))                ;

   echo '{ "aaData": [';
	
	while($fila= mysql_fetch_row($resp)) {
	    if($i>0) echo ',';
            $i++;
	   
	  
            echo json_encode(
                    array(  $fila[0],
                          
                            utf8_encode($fila[1]),
						  $fila[2],
						
						 utf8_encode($fila[4]),
						 $fila[5],
						 $fila[6],
						
						 utf8_encode($fila[9]),$fila[10]
						
                           )
                    );
            
		
	    
	}
	
	echo '] }';
	
	  } elseif ($_REQUEST['accion']=='borraadelanto') {
		 
	$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso',$params_array);
	if($p_cobranza) {
		$documento=new Documento($sesion);
		$id_documento=intval($_POST['id_documento']);
		
			if(!$documento->Load($id_documento)) {
				echo "jQuery('#mensaje').html('El adelanto no existe en la base de datos.'); ";
			} else {
				if($documento->fields['id_cobro'] || $documento->fields['monto']!=$documento->fields['saldo_pago']) {
					
				$cadena=implode(';',$documento->fields);
				echo "jQuery('#mensaje').html('El adelanto no puede eliminarse: ha sido utilizado en al menos  ".__('un cobro')."'); ";
				} else {
					echo "jQuery('#mensaje').html('borrando adelanto...'); ";
					$documento->Delete();
				}
			}
		
		echo "jQuery('#boton_buscar').click();";
		  
		} else {
			echo "jQuery('#mensaje').html('Usted no tiene permiso para eliminar o editar adelantos'); ";
		}
	  }

