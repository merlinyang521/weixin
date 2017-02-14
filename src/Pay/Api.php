<?php
namespace Kof\Weixin\Pay;

use Kof\Weixin\Utils;
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
                'base_uri' => 'https://api.mch.weixin.qq.com/',
                'handler' => new CurlHandler()
            ]);
        }

        return self::$httpClient;
    }

    /**
     * @param string $uri
     * @param array $postData
     * @param string|null $sslcert_path
     * @param string|null $sslkey_path
     * @return array|null
     */
    public static function post($uri, array $postData, $sslcert_path = null, $sslkey_path = null)
    {
        try {
            $httpClient = self::getHttpClient();
            $options = [
                'timeout' => self::TIMEOUT,
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'body_as_string' => true,
                'body' => Utils::array2xml($postData),
                'curl' => [
                    CURLOPT_HEADER => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2
                ]
            ];
            if ($sslcert_path && $sslkey_path) {
                $options['curl'][CURLOPT_SSLCERTTYPE] = 'PEM';
                $options['curl'][CURLOPT_SSLCERT] = $sslcert_path;
                $options['curl'][CURLOPT_SSLKEYTYPE] = 'PEM';
                $options['curl'][CURLOPT_SSLKEY] = $sslkey_path;
            }

            $response = $httpClient->post($uri, $options);
            $body = $response->getBody();
            $contents = $body->getContents();
            if (!$contents) {
                return null;
            }

            if ($uri == 'pay/downloadbill') {
                return $contents;
            }

            libxml_disable_entity_loader(true);
            return \GuzzleHttp\json_decode(
                \GuzzleHttp\json_encode(simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA)),
                true
            );
        } catch (Exception $e) {
            return null;
        }
    }
}
