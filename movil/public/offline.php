<?php
	<?php include("offline.php"); ?>
	
	$needle='Rev';
	#$dataOf="CACHE MANIFEST\n";
	
	$file=fopen("../views/app.manifest","r");
	
	$data = fgets($file);	
	while($data){
		
		$index = strrpos( $data, $needle);
		if(0 <$index){	
	
			$var =  floatval(substr($data,$index+4)) + 0.00001;
			$dataOf="$dataOf# Rev $var\n";
			
			#$date=date("F j, Y, g:i:s a");			
			#echo $data;
			#echo $dataOf;
			
			#fwrite($file,'holiwi :D\n');
			#echo $data;
		}
		else{
			$dataOf="$dataOf$data";
			#echo $data;
			#echo $dataOf;
			
			#fwrite($file,$data);
		}
		$data = fgets($file);
	}

	fclose($file);
	
	echo $dataOf;
	$file=fopen("../views/app.manifest","w");
	#fwrite($file,$dataOf);	
	#fclose($file);
?>