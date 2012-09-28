<!DOCTYPE html>
<html>
<head>
	<title>Error SQL</title>
	<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />
		<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap-responsive.min.css" />
<link href="//static.thetimebilling.com/css/shThemeDefault.css" rel="stylesheet" type="text/css" />
<link href="//static.thetimebilling.com/css/shCore.css" rel="stylesheet" type="text/css" />
<script src="//static.thetimebilling.com/js/XRegExp.js" type="text/javascript"></script>
<script src="//static.thetimebilling.com/js/shCore.js" type="text/javascript"></script>
<script src="//static.thetimebilling.com/js/shAutoloader.js" type="text/javascript"></script>
 

</head>
<body>
<?php
echo '<div class="row">';
$Slim=Slim::getInstance('default',true);

$losdatos=$Slim->view()->getData();
$losdatos['Query']=str_replace(array(',','AND','SET','WHERE'),array(",\n","\n AND ","\n SET ","\n WHERE "),$losdatos['Query']);
echo '<div class="span9 offset1">';
echo 	'<h2>Excepción SQL</h2>';
						 echo "\n<ul>";
						echo "\n<li><b>Archivo</b>: ".$losdatos['File'];
						echo "\n<li><b>Línea</b>: ".$losdatos['Line'];
						echo "\n<li><b>Mensaje</b>: ".$losdatos['Mensaje'];
						echo "\n</ul><hr>";
		echo '</div>';		
echo '<div class="span11 offset1">';		
		if(isset($losdatos['Parametros'])) {
							echo '<div class="span5"><h5>Query</h5>'; 
							echo "\n<pre class='brush: sql;  '> ".$losdatos['Query'].'</pre>';							
							echo '</div>';	
							echo '<div class="span5"><h5>Parametros</h5>';
							echo "\n<pre class='brush: php; tab-size: 20;'>";
							print_r(json_decode($losdatos['Parametros']));
							echo '</pre></div>';
				} else {
							echo '<div class="span9 offset1"><h5>Query</h5>'; 
							echo "\n<pre class='brush: sql;  '> ".$losdatos['Query'].'</pre>';							
							echo '</div>';	
				}
echo '</div>';							
						
						 
						
 	
 echo '<hr><div class="span11 offset1">';
						 
						 echo '<h4>Traza PDO</h4>';
						echo "\n<div height:500px;overflow:auto;><pre class='brush: php; tab-size: 20;'>";
						print_r(json_decode($losdatos['Trace']));
						echo '</pre></div>';

						
							
 echo '</div>';
 echo '</div>';
 
?>
	<script type="text/javascript">
	SyntaxHighlighter.autoloader(
 	[ 'php',					'https://static.thetimebilling.com/js/shBrushPhp.js' ],
 	[ 'sql',			'https://static.thetimebilling.com/js/shBrushSql.js' ]
	);
     SyntaxHighlighter.all();
</script>
</body>
</html>