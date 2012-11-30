<?php
$key=$_REQUEST['accessKeyId'];
$secret=$_REQUEST['secretKey'];
$tabla=$_REQUEST['DomainName'];
$dominio= $_REQUEST['ItemName'];
$attribute=$_REQUEST['AttributeName'];
//file_put_contents('vars.txt',var_export($_REQUEST,true));
/*
$key='AKIAIQYFL5PYVQKORTBA';
$secret='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';
$tabla='clientes';
$dominio='demo1.thetimebilling.com';
$attribute='restringido';
*/

?><html>
  <head>
    <title>Amazon SimpleDBScratchpad</title>
    <script src="js/hmacsha1.js"></script>
    <script src="js/awssigner.js"></script>
    <script src="js/scratchpad.js"></script>
  <script>
	
    var accessKeyId =  '<?php echo $key; ?>';
    var secretKey =   '<?php echo $secret; ?>';
 
</script>

  </head>

  <body marginheight="0" marginwidth="0" bottommargin="0" rightmargin="0" leftmargin="0" topmargin="0">
    <form name="myform" id="myform" action="" enctype="application/x-www-form-urlencoded" method="get">
	<input class="input" type="hidden" name="DomainName" value="<?php echo $tabla; ?>"/>
	<input class="input" type="hidden" name="ItemName" value="<?php echo $dominio; ?>"/>
	<input class="input" type="hidden" name="AttributeName.1" value="<?php echo $attribute; ?>"/>
	</form>
	<script>
	var form = document.getElementById("myform");
	   var url = generateSignedURL("GetAttributes",form, accessKeyId, secretKey, "https://sdb.amazonaws.com", "2009-04-15");
	document.write(url);
	</script>
  </body>
</html>




