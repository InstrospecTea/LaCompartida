<?php

use Aws\S3\S3Client;

class S3 {

	private $client;
	private $bucket;

	public function __construct($bucket) {
		$this->client = S3Client::factory(Conf::AmazonKey());
		$this->bucket = $bucket;
	}

	public function listBucket($prefix) {
		$iterator = $this->client->getIterator('ListObjects', array(
			'Bucket' => $this->bucket,
			'Prefix' => $prefix
		));

		$values = [];
		foreach ($iterator as $item) {
			$values[] = [
				'name' => $item['Key'],
				'time' => strtotime($item['LastModified']),
				'size' => (int) $item['Size'],
				'hash' => substr($item['ETag'], 1, -1)
			];
		}
		return $values;
	}

	public function getFile($file_name) {
		$result = $this->client->getObject(array(
			'Bucket' => $this->bucket,
			'Key' => $file_name
		));
		return $result;
	}

	public function getURL($prefix, $file_name) {
		$command = $this->client->getCommand('GetObject', array(
			'Bucket' => $this->bucket,
			'Key' => "{$prefix}{$file_name}",
			'ResponseContentDisposition' => sprintf('attachment; filename="%s"', $file_name)
		));
		$signedUrl = $command->createPresignedUrl('+2 hours');
		return $signedUrl;
	}

}
