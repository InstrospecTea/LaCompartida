<?php

$api = 'http://lemontech.librofres.co:3000/api/v1';
$product_items_id = array('timekeeper' => 19, 'administrative' => 20, 'casetracking' => 5);

if (!empty($_POST)) {
	$result = file_get_contents("{$api}/clients/subdomain/{$_POST['subdomain']}");
	if (!empty($result)) {
		$client = json_decode($result, true);
		$result = file_get_contents("{$api}/clients/{$client['id']}/recurring_profiles");
		if (!empty($result)) {
			$recurring_profiles = json_decode($result, true);
			foreach ($recurring_profiles as $recurring_profile) {
				foreach ($product_items_id as $key => $value) {
					$opts = array(
						'http' => array(
							'method'  => 'PUT',
							'header'  => 'Content-type: application/x-www-form-urlencoded',
							'content' => http_build_query(
								array(
									'product_item_id' => $value,
									'quantity' => $_POST[$key]
								)
							)
						)
					);

					$result = file_get_contents(
						"{$api}/recurring_profiles/{$recurring_profile['id']}/product_item",
						false,
						stream_context_create($opts)
					);
				}
			}
		}
	}
	echo "{}";
} else {
	echo "{}";
}
