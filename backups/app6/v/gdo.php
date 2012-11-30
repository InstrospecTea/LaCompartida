<?php


// Datos de login a la API de Google
$clientlogin_url = "https://www.google.com/accounts/ClientLogin";
$clientlogin_post = array(
    "accountType" => "GOOGLE",
    "Email" => "amenadiel@gmail.com",
    "Passwd" => "veruca.,salt",
    "service" => "writely",
    "source" => "WPDOCS"
);
 
// Inicializamos el CURL
$curl = curl_init($clientlogin_url);
 
// Obtenemos el string de autenticación
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $clientlogin_post);
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($curl);
preg_match("/Auth=([a-z0-9_\-]+)/i", $response, $matches);
$auth = $matches[1];
 
// Cabeceras de autenticación
$headers = array(
    "Authorization: GoogleLogin auth=" . $auth,
    "GData-Version: 3.0",
);
 
// Recuperamos los ficheros y carpetas que tenemos en Google Docs para no crear dos veces la misma carpeta
curl_setopt($curl, CURLOPT_URL, "http://docs.google.com/feeds/default/private/full?showfolders=true");
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POST, false);
$listado = curl_exec($curl);
$nombre_carpeta = 'WPDOCS';
 
// Si no se ha creado la carpeta, la creamos
if (strpos($listado, '<title>'.$nombre_carpeta.'</title>') === FALSE) {
  // Make the request
  $h = array_merge($headers,array('Content-Type: application/atom+xml'));
  $xml = '<?xml version="1.0" encoding="UTF-8"?><entry xmlns="http://www.w3.org/2005/Atom"><category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/docs/2007#folder"/><title>'.$nombre_carpeta.'</title></entry>';
  curl_setopt($curl, CURLOPT_URL, "http://docs.google.com/feeds/default/private/full");
  curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
  curl_setopt($curl, CURLOPT_POST, true);
 
  $response = curl_exec($curl);
  $response = simplexml_load_string($response);
  $id_folder = $response->id;
} else {
  // Recuperamos la ID de la carpeta creada anteriormente
  preg_match("#<title>$nombre_carpeta</title><content type='application/atom\+xml;type=feed' src='([^']+)'#", $listado, $m);
  $id_folder = $m[1];
}
 
// Subimos el PPT
$h = array_merge($headers,array('Content-Type: application/vnd.ms-powerpoint', 'Slug: fichero'));
$filepath='/path/fichero.ppt';
$data=((fread(fopen($filepath, "rb"), filesize($filepath))));
curl_setopt($curl, CURLOPT_URL, "http://docs.google.com/feeds/default/private/full");
curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_POST, true);
$response = curl_exec($curl);
$response = simplexml_load_string($response);
$id_doc = $response->id;
 
// Limpiamos los IDs de los ficheros devueltos por Google, solo nos interesa del %3A para adelante
preg_match('/%3A(.+)/', $id_doc, $m);
$id_doc = $m[1];
preg_match('/%3A(.+)/', $id_folder, $m);
$id_folder = $m[1];
 
// Lo movemos a la carpeta
$h = array_merge($headers,array('Content-Type: application/atom+xml'));
$data = '<?xml version=\'1.0\' encoding=\'UTF-8\'?><entry xmlns="http://www.w3.org/2005/Atom"><id>https://docs.google.com/feeds/default/private/full/document%3A'.$id_doc.'</id></entry>';
curl_setopt($curl, CURLOPT_URL, "http://docs.google.com/feeds/default/private/full/folder%3A".$id_folder);
curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_POST, true);
$response = curl_exec($curl);
 
//header('Content-type: text/xml');
//echo $response;
// Parse the response
 
// Exportamos a HTML
curl_setopt($curl, CURLOPT_URL, "http://docs.google.com/feeds/download/presentations/Export?docID=$id_doc&exportFormat=pdf");
curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
curl_setopt($curl, CURLOPT_POST, false);
header('Content-type: application/pdf');
echo curl_exec($curl);
 
curl_close($curl);
?>