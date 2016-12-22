<?php
namespace Kof\Weixin;

use Psr\Cache\CacheItemPoolInterface;
use Exception;

class AccessToken
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cacheItemPool;

    /**
     * AccessToken constructor.
     * @param CacheItemPoolInterface $cacheItemPool
     */
	public function __construct(CacheItemPoolInterface $cacheItemPool)
	{
		$this->cacheItemPool = $cacheItemPool;
	}

    /**
     * @param string $appid
     * @param string $appsecret
     * @return string
     */
	public function get($appid, $appsecret)
	{
        $accessTokenKey = 'wx_accesstoken_' . $appid;
        $accessTokenItem = $this->cacheItemPool->getItem($accessTokenKey);
        if ($accessTokenItem->isHit() && $accessToken = $accessTokenItem->get()) {
            return $accessToken;
        }

        try {
            $response = Api::get('token', array(
                'grant_type' => 'client_credential',
                'appid' => $appid,
                'secret' => $appsecret
            ));

            if (!isset($response['access_token']) || empty($response['access_token']) ||
                !isset($response['expires_in']) || empty($response['expires_in'])
            ) {
                return '';
            }

            $accessTokenItem->set($response['access_token']);
            $accessTokenItem->expiresAfter($response['expires_in'] - 1800);
            $this->cacheItemPool->save($accessTokenItem);

            return $response['access_token'];
        } catch (Exception $e) {
            return '';
        }
	}
}
