<?php
/*
 *
 */

include 'bce-php-sdk/BaiduBce.phar';

use BaiduBce\Services\Bos\BosClient;

class BosService
{
	private $client;
	private $bucket;
	private $domain;
	private $endpoint = 'http://bj.bcebos.com';

	public function __construct($accessKey, $secretKey, $bucket, $domain)
	{
		$BOS_CONFIG   = array(
			'credentials' => array(
				'ak' => $accessKey,
				'sk' => $secretKey,
			),
			'endpoint'    => $this->endpoint,
		);
		$this->client = new BosClient($BOS_CONFIG);
		$this->bucket = $bucket;
		$this->domain = $domain;

		$this->checkBucket($this->bucket);
	}

	protected function checkBucket($bucketName)
	{
		$exist = $this->client->doesBucketExist($bucketName);
		if (! $exist) {
			$this->client->createBucket($bucketName);
		}
	}

	public function uploadFile($filename, $path)
	{
		return $this->client->putObjectFromFile($this->bucket, $path, $filename);
	}

	public function uploadFileWithData($data, $path)
	{
		$this->client->putObjectFromString($this->bucket, $path, $data);
	}

	public function removeFile($path)
	{
		$this->client->deleteObject($this->bucket, $path);
	}

	public function getObject($path)
	{
		return $this->client->getObjectAsString($this->bucket, $path);
	}

	public function getObjectMeta($path)
	{
		$meta = $this->client->getObjectMetadata($this->bucket, $path);

		return $meta;
	}

	public function getObjectUrl($path)
	{
		return Typecho_Common::url($path, empty($this->domain) ? ($this->endpoint . '/' . $this->bucket) : $this->domain);
	}
}
