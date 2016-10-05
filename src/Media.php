<?php
namespace Kof\Weixin;

class Media
{
    /**
     * @var string
     */
    protected $access_token;

    /**
     * Media constructor.
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
     * 上传临时素材
     * @param string $type
     * @param string|resource $media
     * @return array|null
     */
    public function upload($type, $media)
    {
        return Api::post('media/upload?access_token=' . $this->access_token . '&type=' . $type, null, [
            [
                'name' => 'media',
                'contents' => $media
            ]
        ]);
    }

    /**
     * 获取临时的多媒体文件url
     * @param string $media_id
     * @return string
     */
    public function getMediaUrl($media_id)
    {
        return 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=' . $this->access_token . '&media_id=' . $media_id;
    }

    /**
     * 上传图文消息内的图片
     * @param string|resource $media $media
     * @return array|null
     */
    public function uploadimg($media)
    {
        return Api::post('media/uploadimg?access_token=' . $this->access_token, null, [
            [
                'name' => 'media',
                'contents' => $media
            ]
        ]);
    }

    /**
     * 上传图文消息素材，群发使用
     * @param array $articles
     * @return array|null
     */
    public function uploadnews(array $articles)
    {
        return Api::post('media/uploadnews?access_token=' . $this->access_token, ['articles' => $articles]);
    }

    /**
     * 上传视频消息素材
     * @param string $media_id
     * @param string $title
     * @param string $description
     * @return array|null
     */
    public function uploadvideo($media_id, $title, $description)
    {
        return Api::post(
            'https://file.api.weixin.qq.com/cgi-bin/media/uploadvideo?access_token=' . $this->access_token,
            [
                'media_id' => $media_id,
                'title' => $title,
                'description' => $description,
            ]
        );
    }

    /**
     * 新增永久图文素材
     * @param array $articles
     * @return array|null
     */
    public function addNews(array $articles)
    {
        return Api::post('material/add_news?access_token=' . $this->access_token, [
            'articles' => $articles
        ]);
    }

    /**
     * 修改永久图文素材
     * @param string $media_id
     * @param int $index
     * @param array $articles
     * @return array|null
     */
    public function updateNews($media_id, $index, array $articles)
    {
        return Api::post('material/del_material?access_token=' . $this->access_token, [
            'media_id' => $media_id,
            'index' => $index,
            'articles' => $articles
        ]);
    }

    /**
     * 新增永久图文素材
     * @param string $type
     * @param string|resource $media
     * @param array|null $postData
     * @return array|null
     */
    public function addMaterial($type, $media, array $postData = null)
    {
        return Api::post('material/add_material?access_token=' . $this->access_token . '&type' . $type, $postData, [
            [
                'name' => 'media',
                'contents' => $media
            ]
        ]);
    }

    /**
     * 获取永久素材
     * @param string $media_id
     * @return array|null
     */
    public function getMaterial($media_id)
    {
        return Api::post('material/get_material?access_token=' . $this->access_token, [
            'media_id' => $media_id
        ]);
    }

    /**
     * 删除永久素材
     * @param string $media_id
     * @return array|null
     */
    public function delMaterial($media_id)
    {
        return Api::post('material/del_material?access_token=' . $this->access_token, [
            'media_id' => $media_id
        ]);
    }

    /**
     * 获取永久素材总数
     * @return array|null
     */
    public function getMaterialcount()
    {
        return Api::get('material/get_materialcount?access_token=' . $this->access_token);
    }

    /**
     * 获取永久素材
     * @param string $type
     * @param int $offset
     * @param int $count
     * @return array|null
     */
    public function batchgetMaterial($type, $offset = 0, $count = 20)
    {
        return Api::post('material/batchget_material?access_token=' . $this->access_token, [
            'type' => $type,
            'offset' => $offset,
            'count' => $count
        ]);
    }
}
