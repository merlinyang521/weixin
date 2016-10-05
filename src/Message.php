<?php
namespace Kof\Weixin;

use SimpleXMLElement;
use Exception;

class Message
{
    /**
     * @var SimpleXMLElement
     */
	protected $weixinPostData = null;

    /**
     * @var string
     */
	protected $appid;

    /**
     * @var string
     */
	protected $token;

    /**
     * @var string
     */
    protected $encodingAESKey;

    /**
     * @var int
     */
    protected $encrypt_type;

    /**
     * @var string
     */
    protected $access_token;

    /**
     * Message constructor.
     * @param string $appid
     * @param string $token
     * @param string $encodingAESKey
     * @param int $encrypt_type
     * @param string $access_token
     */
	public function __construct($appid, $token, $encodingAESKey = null, $encrypt_type = 0, $access_token = null)
	{
		$this->appid = $appid;
		$this->token = $token;
        $this->encodingAESKey = base64_decode($encodingAESKey . '=');
        $this->encrypt_type = $encrypt_type;
        $this->access_token = $access_token;
	}

    /**
     * @param string $access_token
     */
	public function setAccessToken($access_token)
	{
        $this->access_token = $access_token;
	}

    /**
     * 获取微信端post过来的数据
     * @param null|string $key
     * @return null|false|SimpleXMLElement|string
     */
	public function getWeixinPostData($key = null)
	{
        if ($this->weixinPostData === null) {
            if ($xml = file_get_contents("php://input")) {
                $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
                if (($this->encrypt_type == 1 || $this->encrypt_type == 2) &&
                    $xml && $xml->Encrypt && ($encrypt = $xml->Encrypt->__toString())
                ) {
                    $timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"] : null;
                    $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : null;
                    $msg_signature = isset($_GET["msg_signature"]) ? $_GET["msg_signature"] : null;
                    if ($timestamp && $nonce && $msg_signature &&
                        $msg_signature == $this->makeSignature($timestamp, $nonce, $encrypt)
                    ) {
                        $xml = $this->decrypt($encrypt);
                        $xml = $xml ? simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA) : false;
                    } else {
                        $xml = false;
                    }
                }
            }

            $this->weixinPostData = $xml;
        }

		if ($key === null) {
			return $this->weixinPostData;
		}

		return ($this->weixinPostData && $this->weixinPostData->{$key})
            ? $this->weixinPostData->{$key}->__toString()
            : '';
	}

    /**
     * 获取signature
     * @param int $timestamp
     * @param string $nonce
     * @param string|null $encrypt
     * @return string
     */
	public function makeSignature($timestamp, $nonce, $encrypt = null)
    {
        $tmpArr = array($this->token, $timestamp, $nonce);
        if ($encrypt) {
            $tmpArr[] = $encrypt;
        }
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);

        return sha1($tmpStr);
    }

    /**
     * 对明文进行加密
     * @param string $text 需要加密的明文
     * @return string 加密后的密文
     */
    public function encrypt($text)
    {
        try {
            //获得16位随机字符串，填充到明文之前
            $random = Utils::getRandomStr(16);
            $text = $random . pack("N", strlen($text)) . $text . $this->appid;
            // 网络字节序
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->encodingAESKey, 0, 16);
            //使用自定义的填充方式对明文进行补位填充
            $text = PKCS7Encoder::encode($text);
            mcrypt_generic_init($module, $this->encodingAESKey, $iv);
            //加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);

            return base64_encode($encrypted);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @return string 解密得到的明文
     */
    public function decrypt($encrypted)
    {
        try {
            //使用BASE64对需要解密的字符串进行解码
            $ciphertext_dec = base64_decode($encrypted);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->encodingAESKey, 0, 16);
            mcrypt_generic_init($module, $this->encodingAESKey, $iv);

            //解密
            $decrypted = mdecrypt_generic($module, $ciphertext_dec);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (Exception $e) {
            return null;
        }

        try {
            //去除补位字符
            $result = PKCS7Encoder::decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16) {
                return "";
            }
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
        } catch (Exception $e) {
            return null;
        }

        if ($from_appid != $this->appid) {
            return null;
        }

        return $xml_content;
    }

    /**
     * 获取消息
     * @param array $content
     * @return string
     */
    public function getMessage(array $content)
    {
        $xml = Utils::array2xml($content);
        if ($this->encrypt_type == 1 || $this->encrypt_type == 2) {
            $encrypt = $this->encrypt($xml);
            $timestamp = time();
            $nonce = Utils::getRandomStr();
            $signature = $this->makeSignature($timestamp, $nonce, $encrypt);
            $xml = Utils::array2xml([
                'Encrypt' => $encrypt,
                'MsgSignature' => $signature,
                'TimeStamp' => $timestamp,
                'Nonce' => $nonce
            ]);
        }
        
        return $xml;
    }

    /**
     * 获取文本消息
     * @param $content
     * @return string
     */
	public function getTextMessage($content)
	{
	    return $this->getMessage([
	        'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => $content
        ]);
	}

    /**
     * 获取多客服消息
     * @return string
     */
	public function getTransferCustomerServiceMessage()
	{
        return $this->getMessage([
            'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'transfer_customer_service'
        ]);
	}

    /**
     * 通过上传多媒体文件id获取图片消息
     * @param $mediaId
     * @return string
     */
	public function getImageMessage($mediaId)
	{
        return $this->getMessage([
            'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'image',
            'Image' => [
                'MediaId' => $mediaId
            ]
        ]);
	}

    /**
     * 获取图文消息
     * @param array $articles
     * @return string
     */
    public function getNewsMessages(array $articles)
    {
        $articlesXml = [];
        foreach ($articles as $article) {
            $articlesXml[] = ['item' => $article];
        }

        return $this->getMessage([
            'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'news',
            'ArticleCount' => count($articles),
            'Articles' => $articlesXml
        ]);
    }

    /**
     * 获取语音消息
     * @param $mediaId
     * @return string
     */
	public function getVoiceMessage($mediaId)
	{
        return $this->getMessage([
            'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'voice',
            'Voice' => [
                'MediaId' => $mediaId
            ]
        ]);
	}

    /**
     * 获取视频消息
     * @param string $mediaId
     * @param string $title
     * @param string $description
     * @return string
     */
	public function getVideoMessage($mediaId, $title = '', $description = '')
	{
        return $this->getMessage([
            'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'video',
            'Voice' => [
                'MediaId' => $mediaId,
                'Title' => $title,
                'Description' => $description
            ]
        ]);
	}

    /**
     * 获取音乐消息
     * @param string $mediaId
     * @param string $musicUrl
     * @param string $hqMusicUrl
     * @param string $title
     * @param string $description
     * @return string
     */
	public function getMusicMessage($mediaId, $musicUrl, $hqMusicUrl, $title = '', $description = '')
	{
        return $this->getMessage([
            'ToUserName' => $this->getWeixinPostData('FromUserName'),
            'FromUserName' => $this->getWeixinPostData('ToUserName'),
            'CreateTime' => time(),
            'MsgType' => 'music',
            'Music' => [
                'Title' => $title,
                'Description' => $description,
                'MusicUrl' => $musicUrl,
                'HQMusicUrl' => $hqMusicUrl,
                'ThumbMediaId' => $mediaId
            ]
        ]);
	}

    /**
     * 发送客服消息
     * @param array $params
     * @return array|null
     */
	protected function postServiceMsg(array $params)
	{
        return Api::post('message/custom/send?access_token=' . $this->access_token, $params);
	}

    /**
     * 发送客服消息
     * @param string $openid
     * @param string $content
     * @return array|null
     */
	public function postTextMsg($openid, $content)
	{
	    return $this->postServiceMsg([
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ]);
	}

    /**
     * 通过上传多媒体文件id发送图片消息
     * @param string $openid
     * @param string $media_id
     * @return array|null
     */
	public function postNewsMsgByMediaId($openid, $media_id)
	{
        return $this->postServiceMsg([
            'touser' => $openid,
            'msgtype' => 'image',
            'image' => [
                'media_id' => $media_id
            ]
        ]);
	}

    /**
     * 发送语音消息
     * @param string $openid
     * @param string $media_id
     * @return array|null
     */
	public function postVoiceMsg($openid, $media_id)
	{
        return $this->postServiceMsg([
            'touser' => $openid,
            'msgtype' => 'voice',
            'voice' => [
                'media_id' => $media_id
            ]
        ]);
	}

    /**
     * 发送视频消息
     * @param string $openid
     * @param string $media_id
     * @param string $title
     * @param string $description
     * @return array|null
     */
	public function postVideoMsg($openid, $media_id, $title = '', $description = '')
	{
        return $this->postServiceMsg([
            'touser' => $openid,
            'msgtype' => 'video',
            'video' => [
                'media_id' => $media_id,
                'title' => $title,
                'description' => $description
            ]
        ]);
	}

    /**
     * 发送音乐消息
     * @param string $openid
     * @param string $thumb_media_id
     * @param string $musicurl
     * @param string $hqmusicurl
     * @param string $title
     * @param string $description
     * @return array|null
     */
	public function postMusicMsg($openid, $thumb_media_id, $musicurl, $hqmusicurl, $title = '', $description = '')
	{
        return $this->postServiceMsg([
            'touser' => $openid,
            'msgtype' => 'music',
            'music' => [
                'title' => $title,
                'description' => $description,
                'musicurl' => $musicurl,
                'hqmusicurl' => $hqmusicurl,
                'thumb_media_id' => $thumb_media_id
            ]
        ]);
	}

    /**
     * 发送图文消息
     * @param string $openid
     * @param array $articles
     * @return array|null
     */
	public function postNewsMsg($openid, array $articles)
	{
        return $this->postServiceMsg([
            'touser' => $openid,
            'msgtype' => 'news',
            'news' => [
                'articles' => $articles
            ]
        ]);
	}

    /**
     * 发送模板消息
     * @param array $params
     * @return array|null
     */
	public function postTemplateMsg(array $params)
	{
        return Api::post('message/template/send?access_token=' . $this->access_token, $params);
	}

    /**
     * 根据标签进行群发
     * @param array $params
     * @return array|null
     */
	public function massSendall(array $params)
    {
        return Api::post('message/mass/sendall?access_token=' . $this->access_token, $params);
    }

    /**
     * 根据OpenID列表进行群发
     * @param array $params
     * @return array|null
     */
    public function massSend(array $params)
    {
        return Api::post('message/mass/send?access_token=' . $this->access_token, $params);
    }

    /**
     * 删除群发
     * @param string $msg_id
     * @return array|null
     */
    public function massDelete($msg_id)
    {
        return Api::post('message/mass/delete?access_token=' . $this->access_token, ['msg_id' => $msg_id]);
    }

    /**
     * 删除群发
     * @param string $msg_id
     * @return array|null
     */
    public function massGet($msg_id)
    {
        return Api::post('message/mass/get?access_token=' . $this->access_token, ['msg_id' => $msg_id]);
    }

    /**
     * 预览接口
     * @param array $params
     * @return array|null
     */
    public function massPreview(array $params)
    {
        return Api::post('message/mass/preview?access_token=' . $this->access_token, $params);
    }
}
