<?php
require_once dirname(__FILE__) . '/../../app/conf.php';

$matters = array();

/*$server = '200.87.127.179';
$user = 'lemontech';
$password = '20emba14';
$database_name = 'EMBA_PROD';

//connection to the database
$dbhandle = mssql_connect($server, $user, $password) || exit("Error connection to server {$server}");

//select a database to work with
mssql_select_db($database_name, $dbhandle) || exit("Error selecting database {$database_name}");

//declare the SQL statement that will query the database
$query = "SELECT
	OPRJ.U_ClienCode AS client_code,
	OPRJ.U_ClienNom AS client_name,
	OPRJ.PrjCode AS matter_code,
	OPRJ.PrjName AS matter_name
FROM OPRJ
ORDER BY OPRJ.U_ClienCode, OPRJ.PrjCode";

//execute the SQL query and return records
$rs = mssql_query($query);

while ($matter = mssql_fetch_assoc($rs)) {
	array_push($matters, $matter);
}

//close the connection
mssql_close($dbhandle);*/

// var_dump($matters); exit;

$matters = array(
	array(
		'client_code' => 'cli01',
		'client_name' => 'cliente 10',
		'matter_code' => 'cli01-001',
		'matter_name' => 'cliente 1 asunto 1'
	),
	array(
		'client_code' => 'cli02',
		'client_name' => 'cliente 20',
		'matter_code' => 'cli02-001',
		'matter_name' => 'cliente 2 asunto 1'
	),
	array(
		'client_code' => 'cli02',
		'client_name' => 'cliente 2',
		'matter_code' => 'cli02-002',
		'matter_name' => 'cliente 2 asunto 2'
	)
);

if (_empty($matters)) {
	exit('Matters empty! ' . __LINE__);
}

$Session = new Sesion(null, true);

foreach ($matters as $matter) {
	if (_empty($matter['client_code']) || _empty($matter['matter_code'])) {
		continue;
	}

	$matter['client_code'] = strtoupper($matter['client_code']);
	$matter['matter_code'] = strtoupper($matter['matter_code']);

	$Client = new Cliente($Session);
	$Agreement = new Contrato($Session);
	$Client->loadByCodigoSecundario($matter['client_code']);

	if (!$Client->Loaded()) {
		// New client
		$Client->Edit('codigo_cliente', $Client->AsignarCodigoCliente());
		$Client->Edit('codigo_cliente_secundario', $matter['client_code']);
		$Client->Edit('glosa_cliente', $matter['client_name']);
		$Client->Edit('id_moneda', 1);
		$Client->Edit('activo', 1);

		if ($Client->Write()) {
			$Agreement->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
			$Agreement->Edit('forma_cobro', 'FLAT FEE');

			if ($Agreement->Write()) {
				$Client->Edit('id_contrato', $Agreement->fields['id_contrato']);
				if (!$Client->Write()) {
					exit('Error save! ' . __LINE__);
				}
			} else {
				exit('Error save! ' . __LINE__);
			}
		} else {
			exit('Error save! ' . __LINE__);
		}
	} else if ($Client->fields['glosa_cliente'] != $matter['client_name']) {
		// Update client
		$Client->Edit('glosa_cliente', $matter['client_name']);
		if (!$Client->Write()) {
			exit('Error save! ' . __LINE__);
		}
	}

	if (!_empty($Client->fields['codigo_cliente'])) {
		if (!$Agreement->Loaded()) {
			$Agreement->loadById($Client->fields['id_contrato']);
			if (!$Agreement->Loaded()) {
				exit('Agreement doesn\'t exist!' . __LINE__);
			}
		}

		$Matter = new Asunto($Session);
		$Matter->loadByCodigoSecundario($matter['matter_code']);
		$Matter->log_update = false;

		if (!$Matter->Loaded()) {
			// New matter
			$Matter->Edit('codigo_asunto', $Matter->AsignarCodigoAsunto($Client->fields['codigo_cliente']));
			$Matter->Edit('codigo_asunto_secundario', $matter['matter_code']);
			$Matter->Edit('id_usuario', 'NULL');
			$Matter->Edit('codigo_cliente', $Client->fields['codigo_cliente']);
			$Matter->Edit('id_contrato', $Agreement->fields['id_contrato']);
			$Matter->Edit('glosa_asunto', $matter['matter_name']);

			if (!$Matter->Write()) {
				exit('Error save! ' . __LINE__);
			}
		} else if ($Matter->fields['glosa_asunto'] != $matter['matter_name']) {
			$Matter->Edit('glosa_asunto', $matter['matter_name']);

			// Update matter
			if (!$Matter->Write()) {
				exit('Error save! ' . __LINE__);
			}
		}
	}
}

function _empty($var) {
	return empty($var);
}
