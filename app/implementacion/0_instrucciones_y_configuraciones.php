<? 
	/* 
	 * BASE DE DATOS ANTIGUO QUE ALOJA LOS DATOS QUE HAY QUE MIGRAR
	 */
	class ConfImplementacion
	{
		function dbHost() { return 'localhost'; 		}
		function dbName() { return 'DBMIT_rebaza_dbo'; }
		function dbUser() { return 'root'; 					}
		function dbPass() { return 'chantasio'; 		}
	}
	
	class ConfDestinoBD
	{
		function dbHost() { return 'localhost'; 		}
		function dbName() { return 'time_tracking_rebaza'; }
		function dbUser() { return 'root'; 					}
		function dbPass() { return 'chantasio'; 		}
	}
	
	/*
	* 1. HAY QUE AGREGAR A LA TABLA TBFI_HORAS UNA COLUMNA ID_TRABAJO CON LA OPCION AUTOINCREMENT.
	*
	* 2. HAY QUE AGREGAR UNA COLUMNA ID_FACTURA_LEMONTECH A LA TABLA TBFI_FACTURA.
	*
	* 3. A LA TABLA TBFI_FACTURAPAGO TAMBIEN HAY QUE AGREGAR UNA COLUMNA ID_FACTURA_LEMONTECH, 
	*    HAY QUE LLENARLA VIA UN JOIN A LA TABLA TBFI_FACTURA 
	*
	*    UPADATE tbfi_facturapago as fp JOIN tbfi_factura as f ON f.id_factu=fp.id_factu 
	*		 SET fp.id_factura_lemontech = f.id_factura_lemontech 
	*/
?>
