<?php 
namespace Daids\QcloudCos;

use Illuminate\Contracts\Config\Repository;

class CosApi
{
	private $addId;
	private $secretId;
	private $secretKey;


	public function __construct(Repository $config)
	{
		$this->addId = array_get($config, 'appId', '');
		$this->secretId = array_get($config, 'secretId', '');
		$this->secretKey = array_get($config, 'secretKey', '');
	}


	public function getSign($fileId, $bucketName, $expired)
	{
		$now = time();
        $rdm = rand();
        $plainText = "a=$this->appId&k=$this->secretId&e=$expired&t=$now&r=$rdm&f=$fileId&b=$bucketName";
        $bin = hash_hmac('SHA1', $plainText, $this->secretKey, true);
        $bin = $bin.$plainText;
        $sign = base64_encode($bin);
        return $sign;
	}
}