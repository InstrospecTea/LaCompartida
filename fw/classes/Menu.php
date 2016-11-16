<?php 
	class Menu
	{
		// numero de cotizaciones en la lista
		var $num = 0;

		// arreglo con las cotizaciones
		var $datos = null;

		// Sesion
		var $sesion = null;

		function Menu($sesion)
		{
			$this->sesion = $sesion;

			if($query != null)
			{
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

				while($fields = mysql_fetch_assoc($resp))
				{
					$obj = new $tipo($sesion);
					$obj->fields = $fields;
					$obj->loaded = true;

					$this->datos[$this->num++] = $obj;
				}
			}
		}

		function &Get( $index=0 )
		{
			return $this->datos[$index];
		}
	
		function Add( $object )
		{
			$this->datos[$this->num++] = $object;
		}

		function Clear()
		{
			$this->num = 0;
			$this->datos = null;
		}

		function &Find( $key, $val )
		{
			$null_var = null;

			for( $i=0; $i<$this->num; $i++ )
			{
				if( $this->datos[$i]->fields[$key] == $val )
					return $this->datos[$i];
			}

			return $null_var;
		}
	}
