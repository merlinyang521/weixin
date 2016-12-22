<?php
namespace Kof\Weixin;

class User
{
    protected $appid;

    protected $appsecret;

    protected $access_token;

    public function __construct($appid, $appsecret, $access_token)
    {
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        $this->access_token = $access_token;
    }

    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * 获取授权url
     * @param string $redirect_uri
     * @param string $scope
     * @param string $state
     * @return string
     */
	public function getAuthorizeUrl($redirect_uri, $scope = 'snsapi_base', $state = '')
	{
		return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query(array(
            'appid' => $this->appid,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state
        )) . '#wechat_redirect';
	}

    /**
     * 获取二维码授权url
     * @param string $redirect_uri
     * @param string $scope
     * @param string $state
     * @return string
     */
    public function getQrcodeAuthorizeUrl($redirect_uri, $scope = 'snsapi_login', $state = '')
    {
        return 'https://open.weixin.qq.com/connect/qrconnect?' . http_build_query(array(
            'appid' => $this->appid,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state
        )) . "#wechat_redirect";
    }

    /**
     * 获取授权access_token
     * @param string $code
     * @return array|null
     */
	public function getAuthorizeAccessToken($code)
	{
        return Api::get('sns/oauth2/access_token', array(
            'appid' => $this->appid,
            'secret' => $this->appsecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ));
	}

    /**
     * 获取授权用户信息
     * @param string $authorize_access_token
     * @param string $openid
     * @param string $lang
     * @return array|null
     */
	public function getAuthorizeUserInfo($authorize_access_token, $openid, $lang = 'zh_CN')
	{
        return Api::get('sns/userinfo', array(
            'access_token' => $authorize_access_token,
            'openid' => $openid,
            'lang' => $lang
        ));
	}

    /**
     * 获取关注用户信息
     * @param string $openid
     * @param string $lang
     * @return array|null
     */
	public function getSubscribeUserInfo($openid, $lang = 'zh_CN')
	{
        return Api::get('user/info', array(
            'access_token' => $this->access_token,
            'openid' => $openid,
            'lang' => $lang
        ));
	}

    /**
     * 获取用户列表
     * @param string $next_openid
     * @return array|null
     */
	public function getUserList($next_openid = '')
    {
        return Api::get('user/get', array(
            'access_token' => $this->access_token,
            'next_openid' => $next_openid,
        ));
	}

	/**
	 * 批量获取用户详细信息
	 * @param $openids
	 * @param string $lang
	 * @return array|null
	 */
	public function batchGetUserInfo(array $openids, $lang='zh_CN')
    {
        return Api::post('user/info/batchget?access_token=' . $this->access_token, array(
            'user_list' => array_map(function($openid) use ($lang) {
                return array(
                    'openid' => $openid,
                    'lang' => $lang
                );
            }, $openids)
        ));
	}

    /**
     * 设置用户备注名
     * @param $openid
     * @param $remark
     * @return array|null
     */
	public function updateremark($openid, $remark)
    {
        return Api::post('user/info/updateremark?access_token=' . $this->access_token, [
            'openid' => $openid,
            'remark' => $remark
        ]);
    }
}
