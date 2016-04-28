<?php

use Aws\S3\S3Client;

class S3 {

	private $client;
	private $bucket;

	public function __construct($bucket) {
		$this->client = S3Client::factory(Conf::AmazonKey());
		$this->bucket = $bucket;
	}

	public function listBuckets() {
		$result = $this->client->listBuckets();
		$bukets = [];
		foreach ($result['Buckets'] as $bucket) {
			$bukets[] = [
				'name' => $bucket['Name'],
				'created' => new DateTime($bucket['CreationDate'])
			];
		}
		return $bukets;
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
				'time' => new DateTime($item['LastModified']),
				'size' => (int) $item['Size'],
				'hash' => substr($item['ETag'], 1, -1)
			];
		}
		return $values;
	}

	public function getFileContent($file_name) {
		$result = $this->client->getObject(array(
			'Bucket' => $this->bucket,
			'Key' => $file_name
		));
		return $result['Body'];
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

	public function putFileContents($file_name, $body) {
		return $this->client->putObject([
				'Bucket' => $this->bucket,
				'Key' => $file_name,
				'Body' => $body
		]);
	}

	public function uploadFile($file_name, $file_path, Array $attrs = []) {
		return  $this->client->putObject([
			'Bucket' => $this->bucket,
			'Key' => $file_name,
			'SourceFile' => $file_path
		] + $attrs);
	}

	public function deleteFile($file_name) {
		return $this->client->deleteObjects([
				'Bucket' => $this->bucket,
				'Objects' => [['Key' => $file_name]]
		]);
	}

	public function createBucket($bucket_name) {
		$this->client->waitUntil('BucketExists', array('Bucket' => $bucket_name));
	}

	public function fileExists($file_name) {
		return $this->client->doesObjectExist($this->bucket, $file_name);
	}

}
