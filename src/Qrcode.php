<?php
namespace Kof\Weixin;

class Qrcode
{
    /**
     * @var string
     */
    protected $access_token;

    /**
     * UserTags constructor.
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
     * 生成带参数的二维码
     * @param string $scene_id
     * @param null|int $expire_seconds
     * @return mixed|null
     */
    public function create($scene_id, $expire_seconds = null)
    {
        $postData = array();
        if ($expire_seconds) {
            $postData['expire_seconds'] = $expire_seconds;
            $postData['action_name'] = 'QR_SCENE';
            $scene_id_key = 'scene_id';
        } else {
            $postData['action_name'] = 'QR_LIMIT_STR_SCENE';
            $scene_id_key = 'scene_str';
            $scene_id = strval($scene_id);
        }
        $postData['action_info'] = array(
            'scene' => array(
                $scene_id_key => $scene_id
            )
        );

        return Api::post('qrcode/create?access_token=' . $this->access_token, $postData);
    }

    public function getUrl($ticket)
    {
        return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $ticket;
    }
}
