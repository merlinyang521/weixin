<?php
namespace Kof\Weixin;

class Datacube
{
    /**
     * @var string
     */
    protected $access_token;

    /**
     * Datacube constructor.
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
     * @param $name
     * @param $arguments
     * @return array|null
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141082&token=&lang=zh_CN
     */
    public function __call($name, $arguments)
    {
        if (count($arguments) != 2) {
            return null;
        }

        return Api::post('datacube/' . $name . '?access_token=' . $this->access_token, [
            'begin_date' => $arguments[0],
            'end_date' => $arguments[1]
        ]);
    }
}
