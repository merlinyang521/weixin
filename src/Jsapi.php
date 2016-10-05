<?php
namespace Kof\Weixin;

use Psr\Cache\CacheItemPoolInterface;
use Exception;

class Jsapi
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cacheItemPool;

    /**
     * Jsapi constructor.
     * @param CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * @param string $appid
     * @param string $access_token
     * @return string
     */
	public function getTicket($appid, $access_token)
	{
        $ticketKey = 'wx_ticket:' . $appid;
        $ticketItem = $this->cacheItemPool->getItem($ticketKey);
        if ($ticketItem->isHit() && $ticket = $ticketItem->get()) {
            return $ticket;
        }

        try {
            $response = Api::get('ticket/getticket?' . http_build_query(array(
                'type' => 'jsapi',
                'access_token' => $access_token
            )));
            if (!isset($response['ticket']) || empty($response['ticket']) ||
                !isset($response['expires_in']) || empty($response['expires_in'])
            ) {
                return '';
            }

            $ticketItem->set($response['ticket']);
            $ticketItem->expiresAfter($response['expires_in'] - 600);
            $this->cacheItemPool->save($ticketItem);

            return $response['ticket'];
        } catch (Exception $e) {
            return '';
        }
	}

    /**
     * @param string $appid
     * @param string $access_token
     * @param string $url
     * @param array $jsApiList
     * @param bool $debug
     * @return array
     */
	public function getJsConfig(
        $appid,
        $access_token,
		$url,
		$jsApiList = array('onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ'),
		$debug = false
	) {
		$timestamp = time();
		$nonceStr = urlencode(uniqid());
		$jsapi_ticket = $this->getTicket($appid, $access_token);
		$string = 'jsapi_ticket=' . $jsapi_ticket . '&noncestr=' . $nonceStr . '&timestamp=' . $timestamp . '&url=' . $url;
		$signature = sha1($string);

		return array(
			'debug' => $debug,
			'appId' => $appid,
			'timestamp' => $timestamp,
			'nonceStr' => $nonceStr,
			'signature' => $signature,
			'jsApiList' => $jsApiList
		);
	}
}
