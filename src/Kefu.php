<?php
namespace Kof\Weixin;

class Kefu
{
    /**
     * @var string
     */
    protected $access_token;

    /**
     * Kefu constructor.
     * @param string $access_token
     */
    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * @param $access_token
     * @return $this
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * 获取客服列表
     * @return array|null
     */
	public function getkflist()
	{
        return Api::get('customservice/getkflist?access_token=' . $this->access_token);
	}

    /**
     * 获取在线客服接待信息
     * @return array|null
     */
	public function getonlinekflist()
	{
        return Api::get('customservice/getonlinekflist?access_token=' . $this->access_token);
	}

    /**
     * 创建会话，将某个客户直接指定给客服工号接待
     * @param string $kf_account
     * @param string $openid
     * @return array|null
     */
	public function createkfsession($kf_account, $openid)
	{
        return Api::post('kfsession/create?access_token=' . $this->access_token, [
            'kf_account' => $kf_account,
            'openid' => $openid
        ]);
	}

    /**
     * 获取客服会话列表
     * @param string $kf_account
     * @return array|null
     */
	public function getsessionlist($kf_account)
    {
        return Api::get('kfsession/getsessionlist?access_token=' . $this->access_token . '&kf_account=' . $kf_account);
    }

    /**
     * 关闭会话
     * @param string $kf_account
     * @param string $openid
     * @return array|null
     */
	public function closefsession($kf_account, $openid, $access_token)
	{
        return Api::post('kfsession/create?access_token=' . $this->access_token, [
            'kf_account' => $kf_account,
            'openid' => $openid
        ]);
	}
}
