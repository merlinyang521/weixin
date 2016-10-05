<?php
namespace Kof\Weixin;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\CurlHandler as CurlHandler;
use Exception;

class Api
{
    const CONNECT_TIMEOUT = 5;

    const TIMEOUT = 30;

    /**
     * @var HttpClient
     */
    protected static $httpClient = null;

    /**
     * @return HttpClient
     */
    protected static function getHttpClient()
    {
        if (self::$httpClient === null) {
            self::$httpClient = new HttpClient([
                'base_uri' => 'https://api.weixin.qq.com/cgi-bin/',
                'handler' => new CurlHandler()
            ]);
        }

        return self::$httpClient;
    }

    /**
     * @param HttpClient $httpClient
     */
    public static function setHttpClient(HttpClient $httpClient)
    {
        self::$httpClient = $httpClient;
    }

    /**
     * @param string $uri
     * @return array|null
     */
    public static function get($uri)
    {
        try {
            $httpClient = self::getHttpClient();
            $response = $httpClient->get($uri, ['timeout' => self::TIMEOUT, 'connect_timeout' => self::CONNECT_TIMEOUT]);
            $body = $response->getBody();
            return \GuzzleHttp\json_decode($body->getContents());
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $uri
     * @param array|null $postData
     * @param array|null $multipart
     * @return array|null
     */
    public static function post($uri, array $postData = null, array $multipart = null)
    {
        try {
            $httpClient = self::getHttpClient();
            $options = ['timeout' => self::TIMEOUT, 'connect_timeout' => self::CONNECT_TIMEOUT];
            if ($postData) {
                $options['body'] = \json_encode($postData, JSON_UNESCAPED_UNICODE);
                $options['_conditional'] = ['Content-Type' => 'application/json'];
            }
            if ($multipart) {
                $options['multipart'] = $multipart;
            }
            $response = $httpClient->post($uri, $options);
            $body = $response->getBody();
            return \GuzzleHttp\json_decode($body->getContents());
        } catch (Exception $e) {
            return null;
        }
    }
}
