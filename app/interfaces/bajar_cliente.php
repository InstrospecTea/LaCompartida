<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	$pagina->titulo = "Descarga de aplicación Windows XP";
	$pagina->PrintTop();
?>

	<!-- Begin Prerequisites -->
<script type="text/javascript">
var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
			|| this.searchVersion(navigator.appVersion)
			|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari"
		},
		{
			prop: window.opera,
			identity: "Opera"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

};
BrowserDetect.init();
function MostrarDivs()
{
	if(BrowserDetect.browser == "Firefox") {
		$$('.explorer','.chrome').invoke('hide');
	} else if(BrowserDetect.browser == "Firefox") {
		jQuery('.explorer .chrome').hide();
	} else if (BrowserDetect.browser == "Mozilla") {
		$$('.explorer','.firefox').invoke('hide');
	} else if(BrowserDetect.browser == "Explorer" && BrowserDetect.version <= 6) {
		jQuery('.explorer','.chrome','.firefox').invoke('hide');
		$$('.explorer6').invoke('show');
	} else if (BrowserDetect.browser == "Explorer") {
		$$('.chrome','.firefox').invoke('hide');
	}
}
function getDNETVersion(reportError)
{
	var result = "";
	var res = new Array();
	var ua = navigator.userAgent;
	var ie = ua.indexOf("MSIE");
	var i = ua.indexOf(".NET CLR");

	if(i != -1)
	{
		var j1 = ua.indexOf(";", i);
		var j2 = ua.indexOf(")", i);
		var j = ua.length - 1;
		if((j1 >= 0) || (j2 >= 0))
		{
			if(j1 >= 0) j = j1;
			if((j2 >= 0) && (j2 < j1))
				j = j2;
		}
		var dnet = ua.substring(i, j);
		if((dnet != null) && dnet.length > 0)
		result = ".NET ha sido detectado en su sistema!<br>La versión de .NET reportada por su browser es: " + dnet + "<br/>De todas formas si tiene problemas para utilizar la aplicación descarge .NET desde el siguiente enlace: <a href='http://www.microsoft.com/downloads/details.aspx?familyid=0856eacb-4362-4b0d-8edd-aab15c5e04f5'>Framework .NET</a>";
		res['conpuntonet'] = true;
	}
	else
	{
		if(reportError)
		{
			result = "¡La presencia de .NET <b>NO</b> ha sido reportada por su browser!<br />Descárguelo en el siguiente enlace: <a href='http://www.microsoft.com/downloads/details.aspx?familyid=0856eacb-4362-4b0d-8edd-aab15c5e04f5'>Framework .NET</a>";
			if(ie < 0)
			{
				result += "<br> Este browser no es Internet Explorer. La manera más fácil de chequear la presencia del framework .NET es abrir esta página en Internet Explorer.";
			}
		}
		else result = null;
		res['conpuntonet'] = false;
	}
	
	res['texto'] = result;

	return res;
}

function writeDNetReport(doc)
{
	var res = getDNETVersion(true);
	var color = res['conpuntonet'] ? "#92B901" : "#ff0000";
	doc.write("<div id=net style='background-color:" + color + ";border:1px solid black;font-size:1.3em;'>" + res['texto'] + "</div><br />");
}

// -->
		writeDNetReport(document);
</script>


		<div class="firefox" style="background-color:#ff0000; border:1px solid black; font-size:1.3em; display:block; width: 100%">
			Para poder instalar la aplicación en Mozilla Firefox debe descargar en primer lugar el add-on
			<a href="https://addons.mozilla.org/firefox/1608/" target="_blank">FFClickOnce</a>
			<br />
		</div>

		<div class="chrome" style="background-color:#ff0000; border:1px solid black; font-size:1.3em; display:block; width: 100%">
			En caso de descargar con chrome dar clic derecho a "Instalar y ejecutar" y "guardar enlace como".
			<br />
		</div>

		<div class="explorer explorer6" align="left">
			Usted está utilizando Internet Explorer 6. Por su seguridad, Lemontech recomienda actualizar a la última versión de <a target="_blank" href="http://www.microsoft.com/windows/ie/">Internet Explorer</a>
			<hr style="color:black;" size=1 />
		</div>		
		
		<div align=left>
			<ul>
			<li>A continuación usted podrá instalar la aplicación que le permitirá utilizar el sistema cuando no cuente con una conexión a Internet.</li>
			<li>Esta aplicación requiere el framework .NET de Microsoft. Al comienzo de esta página podrá ver si su computador necesita instalar este componente.</li>
			<li>Esta aplicación tiene algunos prerrequisitos que de no estar instalados en su computador, serán descargados desde el sitio web de Microsoft. Esta operación puede tardar varios minutos.</li>
			<li>Haga clic en el siguiente botón para instalar o ejecutar la aplicación:</li>
			<br /><br />
			<center>
			<SPAN style="border: 1px solid black; padding: 3px; background-color:#A7DF60">
<?
		if (method_exists('Conf','GetConf'))
		{
			$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
		}
		else
		{
			$PdfLinea1 = Conf::PdfLinea1();
		}
?>
			<A style="text-decoration: none;" HREF="../../cliente_windows/application.php?titulo=<?= urlencode($PdfLinea1); ?>&host=<?= urlencode(Conf::Host()); ?>&titulo_asunto=<?=__('Asunto')?>">Instalar y ejecutar</A>
			</SPAN>
			</center>
		</div>

<script type="text/javascript">
MostrarDivs();
</script>
<?
	$pagina->PrintBottom();
?>
