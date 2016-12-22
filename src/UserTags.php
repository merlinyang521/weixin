<?php
namespace Kof\Weixin;

class UserTags
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
     * 创建标签
     * @param string $tagname
     * @return array|null
     */
    public function create($tagname)
    {
        return Api::post('tags/create?access_token=' . $this->access_token, [
            'tag' => [
                'name' => $tagname
            ]
        ]);
    }

    /**
     * 获取公众号已创建的标签
     * @return array|null
     */
    public function get()
    {
        return Api::get('tags/get', [
            'access_token' => $this->access_token
        ]);
    }

    /**
     * 编辑标签
     * @param string $tagid
     * @param string $tagname
     * @return array|null
     */
    public function update($tagid, $tagname)
    {
        return Api::post('tags/update?access_token=' . $this->access_token, [
            'tag' => [
                'id' => $tagid,
                'name' => $tagname
            ]
        ]);
    }

    /**
     * 删除标签
     * @param string $tagid
     * @return array|null
     */
    public function delete($tagid)
    {
        return Api::post('tags/delete?access_token=' . $this->access_token, [
            'tag' => [
                'id' => $tagid
            ]
        ]);
    }

    /**
     * 获取标签下粉丝列表
     * @param string $tagid
     * @param string $next_openid
     * @return array|null
     */
    public function getUserList($tagid, $next_openid = '')
    {
        return Api::post('user/tag/get?access_token=' . $this->access_token, [
            'tagid' => $tagid,
            'next_openid' => $next_openid
        ]);
    }

    /**
     * 获取标签下粉丝列表
     * @param string $openid
     * @return array|null
     */
    public function getUserTagidList($openid)
    {
        return Api::post('tags/getidlist?access_token=' . $this->access_token, [
            'openid' => $openid
        ]);
    }

    /**
     * @param array $openid_list
     * @param string$tagid
     * @return array|null
     */
    public function batchtagging(array $openid_list, $tagid)
    {
        return Api::post('tags/members/batchtagging?access_token=' . $this->access_token, [
            'openid_list' => $openid_list,
            'tagid' => $tagid
        ]);
    }

    /**
     * @param array $openid_list
     * @param string$tagid
     * @return array|null
     */
    public function batchuntagging(array $openid_list, $tagid)
    {
        return Api::post('tags/members/batchuntagging?access_token=' . $this->access_token, [
            'openid_list' => $openid_list,
            'tagid' => $tagid
        ]);
    }

    /**
     * 获取公众号的黑名单列表
     * @param string $begin_openid
     * @return array|null
     */
    public function getblacklist($begin_openid = '')
    {
        return Api::post('tags/members/getblacklist?access_token=' . $this->access_token, [
            'begin_openid' => $begin_openid
        ]);
    }

    /**
     * 拉黑用户
     * @param array $opened_list
     * @return array|null
     */
    public function batchblacklist(array $opened_list)
    {
        return Api::post('tags/members/batchblacklist?access_token=' . $this->access_token, [
            'opened_list' => $opened_list
        ]);
    }

    /**
     * 取消拉黑用户
     * @param array $opened_list
     * @return array|null
     */
    public function batchunblacklist(array $opened_list)
    {
        return Api::post('tags/members/batchunblacklist?access_token=' . $this->access_token, [
            'opened_list' => $opened_list
        ]);
    }
}
