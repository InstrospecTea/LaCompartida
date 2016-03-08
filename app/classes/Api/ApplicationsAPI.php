<?php

/**
 *
 * Clase con métodos para aplicaciones
 *
 */
class ApplicationsAPI extends AbstractSlimAPI {

	public function getReleasesList() {
		$Session = $this->session;
		$Slim = $this->slim;

		$bucket = 'timebilling-uploads';
		$os = $Slim->request()->params('os');
		$app_guid = $Slim->request()->params('guid');
		$action_name = $Slim->request()->params('name');

		$s3 = new AmazonS3(array(
	 		'key' => 'AKIAIQYFL5PYVQKORTBA',
	 		'secret' => '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'
	 	));
		$response = $s3->get_object_list($bucket, array('prefix' => "apps/$action_name/$app_guid/$os/1"));

		if ($response && gettype($response) === 'array' && count($response)) {
			$object = $s3->get_object_list($bucket, array('prefix' => "apps/$action_name/$app_guid/$os/1"));
			$dir = $object[0];
			$version_directory = $s3->get_object_headers($bucket, "{$dir}");
			$parts = explode('/', $version_directory->header['_info']['url']);
	 		$version = $parts[count($parts)-2];

	 		$manifest_headers = $s3->get_object_headers($bucket, "{$dir}manifest");
			$manifest = file_get_contents($manifest_headers->header['_info']['url']);
			$manifest .= "#version: {$version}";

			$appudate = $s3->get_object_headers($bucket, "{$dir}appupdate.zip");

			$response = array(
			'success' => 'true',
				'releases' => array(
					array(
						'version' => $version,
						'manifest' => $manifest,
						'release_notes' => 'app://CHANGELOG.md',
						'url' => $appudate->header['_info']['url']
					),
				)
			);
		} else {
			$response = array(
			'success' => 'false',
				'releases' => array(
					array(
						'version' => '0',
						'manifest' => '',
						'release_notes' => 'app://CHANGELOG.md',
						'url' => 'localhost'
					),
				)
			);
		}

		$this->outputJson($response);
	}

	public function downloadRelease() {
		$Session = $this->session;
		$Slim = $this->slim;

		$os = $Slim->request()->params('os');
		$app_guid = $Slim->request()->params('guid');
		$action_name = 'app-update';
		$version = $Slim->request()->params('version');
		$bucket = 'timebilling-uploads';
		$s3 = new AmazonS3(array(
	 		'key' => 'AKIAIQYFL5PYVQKORTBA',
	 		'secret' => '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'
	 	));
		$url = $s3->get_object_url($bucket, "apps/$action_name/$app_guid/$os/$version/appupdate.zip");
		if (!is_null($url)) {
			$Slim->redirect($url);
		} else {
			$this->halt(__('Invalid params'), 'InvalidParams');
		}
	}

	public function trackAnalytic() {
		$this->outputJson(array('result' => 'OK'));
	}

}
