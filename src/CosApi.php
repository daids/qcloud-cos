<?php 
namespace Daids\QcloudCos;

use Illuminate\Contracts\Config\Repository;
use GuzzleHttp\Client;

class CosApi
{
	private $appId;
	private $secretId;
	private $secretKey;


	public function __construct(Repository $config)
	{
		$this->appId = array_get($config, 'qcos.appId', '');
		$this->secretId = array_get($config, 'qcos.secretId', '');
		$this->secretKey = array_get($config, 'qcos.secretKey', '');
	}

	public function getSign($bucketName, $expired = 0, $fileId = null)
	{
		$now = time();
        $rdm = rand();
        $plainText = "a={$this->appId}&k={$this->secretId}&e=$expired&t=$now&r=$rdm&f=$fileId&b=$bucketName";
        $bin = hash_hmac('SHA1', $plainText, $this->secretKey, true);
        $bin = $bin.$plainText;
        $sign = base64_encode($bin);
        return $sign;
	}

	public function cosUrlEncode($path) {
	    return str_replace('%2F', '/',  rawurlencode($path));
	}

	public function generateResUrl($bucketName, $dstPath) {
	    return 'http://web.file.myqcloud.com/files/v1/'.$this->appId.'/'.$bucketName.'/'.$dstPath;
	}

	public function upload($srcPath, $bucketName, $dstPath, $bizAttr = null) {
		$srcPath = realpath($srcPath);
		$dstPath = $this->cosUrlEncode($dstPath);
		if (!file_exists($srcPath)) {
			return ['code' => 1, 'message' => '文件不存在'];
		}
		$expired = time() + 360;
		$url = $this->generateResUrl($bucketName, $dstPath);
		$sign = $this->getSign($bucketName, $expired);
		$sha1 = hash_file('sha1', $srcPath);

		$httpClient = new Client(['timeout' => 30]);

		try {	
			$response = $httpClient->post($url, [
				'multipart' => [
					[
						'name'     => 'op',
						'contents' => 'upload'
					],
					[
						'name'     => 'sha',
						'contents' => $sha1
					],
					[
						'name'     => 'filecontent',
						'contents' => fopen($srcPath, 'r')
					]
				],
				'headers' => [
					'Authorization' => $sign
				]
			]);
		} catch (\Exception $e) {
			info($e);
			return ['code' => 2, 'message' => '接口请求异常'];
		}

		return json_decode($response->getBody(), true);
	}


	// public function upload_impl($fileObj, $filetype, $bucket, $fileid, $userid, $magicContext, $params) {
	//         $expired = time() + 60;
	//         $url = self::generateResUrl($bucket, $userid, $fileid);
	//         $sign = Auth::getAppSignV2($bucket, $fileid, $expired);
	//         // add get params to url
	//         if (isset($params['get']) && is_array($params['get'])) {
	//             $queryStr = http_build_query($params['get']);
	//             $url .= '?'.$queryStr;
	//         }
	//         $data = array();
	//         if ($filetype == 0) {
	//             $data['FileContent'] = '@'.$fileObj;
	//         } else if ($filetype == 1) {
	//             $data['FileContent'] = $fileObj;
	//         }
	//         if ($magicContext) {
	//             $data['MagicContext'] = $magicContext;
	//         }
	//         $req = array(
	//             'url' => $url,
	//             'method' => 'post',
	//             'timeout' => 10,
	//             'data' => $data,
	//             'header' => array(
	//                 'Authorization:QCloud '.$sign,
	//             ),
	//         );
	//         $rsp = Http::send($req);
	//         $info = Http::info();
	//         $ret = json_decode($rsp, true);
	//         if ($ret) {
	//             if (0 === $ret['code']) {
	//                 $data = array(
	//                     'url' => $ret['data']['url'],
	//                     'downloadUrl' => $ret['data']['download_url'],
	//                     'fileid' => $ret['data']['fileid'],
	//                 );
	//                 if (array_key_exists('is_fuzzy', $ret['data'])) {
	//                     $data['isFuzzy'] = $ret['data']['is_fuzzy'];
	//                 }
	//                 if (array_key_exists('is_food', $ret['data'])) {
	//                     $data['isFood'] = $ret['data']['is_food'];
	//                 }
	//                 return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => $data);
	//             } else {
	//                 return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
	//             }
	//         } else {
	//             return array('httpcode' => $info['http_code'], 'code' => self::IMAGE_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
	//         }
	//     }

	    // public function generateResUrl($bucket, $userid=0, $fileid='', $oper = '') {
	    //     if ($fileid) {
	    //         $fileid = urlencode($fileid);
	    //         if ($oper) {
	    //             return Conf::API_IMAGE_END_POINT_V2 . Conf::APPID . '/' . $bucket . '/' . $userid . '/' . $fileid . '/' . $oper;
	    //         } else {
	    //             return Conf::API_IMAGE_END_POINT_V2 . Conf::APPID . '/' . $bucket . '/' . $userid . '/' . $fileid;
	    //         }
	    //     } else {
	    //         return Conf::API_IMAGE_END_POINT_V2 . Conf::APPID . '/' . $bucket . '/' . $userid;
	    //     }
	    // }

}