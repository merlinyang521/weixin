<?php
namespace Kof\Weixin;

class Menu
{
    /**
     * @var string
     */
    protected $access_token;

    /**
     * Menu constructor.
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
     * 获取微信菜单
     * @return array|null
     */
    public function get()
    {
        return Api::get('menu/get?access_token=' . $this->access_token);
    }


    /**
     * 删除微信菜单
     * @return array|null
     */
	public function delete()
	{
        return Api::get('menu/delete?access_token=' . $this->access_token);
	}

    /**
     * 创建微信菜单
     * @param array $menu
     * @return array|null
     */
	public function create(array $menu)
	{
        return Api::post('menu/create?access_token=' . $this->access_token, $menu);
	}

    /**
     * 创建个性化微信菜单
     * @param array $menu
     * @return array|null
     */
    public function addconditional(array $menu)
    {
        return Api::post('menu/addconditional?access_token=' . $this->access_token, $menu);
    }

    /**
     * 删除个性化菜单
     * @return array|null
     */
    public function delconditional()
    {
        return Api::get('menu/delconditional?access_token=' . $this->access_token);
    }

    /**
     * 测试个性化菜单匹配结果
     * @return array|null
     */
    public function trymatch($access_token)
    {
        return Api::get('menu/trymatch?access_token=' . $this->access_token);
    }
}
