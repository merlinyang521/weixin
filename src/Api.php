<?php
namespace Kof\Weixin;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\CurlHandler as CurlHandler;
use Exception;

class Api
{
    const CONNECT_TIMEOUT = 5;

    const TIMEOUT = 30;

    const BASE_URI = 'https://api.weixin.qq.com/cgi-bin/';

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
                'base_uri' => self::BASE_URI,
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
     * @param array|null $query
     * @return array|null
     */
    public static function get($uri, array $query = null)
    {
        try {
            $url = self::BASE_URI . $uri;
            if ($query) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            return \GuzzleHttp\json_decode($response, true);
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
                $options['curl'] =  ['body_as_string' => true];
                $options['body'] = \json_encode($postData, JSON_UNESCAPED_UNICODE);
                $options['_conditional'] = ['Content-Type' => 'application/json'];
            }
            if ($multipart) {
                $options['multipart'] = $multipart;
            }
            $response = $httpClient->post($uri, $options);
            $body = $response->getBody();
            return \GuzzleHttp\json_decode($body->getContents(), true);
        } catch (Exception $e) {
            return null;
        }
    }
}
