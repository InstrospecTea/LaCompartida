    //Este javascript se usa para guardar los campos por ajax en los listados de trabajos
	function GuardarCampoTrabajo(id,campo,valor)
	{
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=actualizar_trabajo&id=' + id + '&campo=' + campo + '&valor=' + valor;

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;

				/*if(response.indexOf('OK') == -1)
				{
					alert(response);
				}*/

				offLoading();
			}
		};
		http.send(null);
	}
