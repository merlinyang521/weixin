<?php
namespace Kof\Weixin;

class Utils
{
    /**
     * 随机生成字符串
     * @param int $len
     * @return string 生成的字符串
     */
    public static function getRandomStr($len = 16)
    {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < $len; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }

        return $str;
    }

    /**
     * 数组转xml
     * @param array $array
     * @return string
     */
    public static function array2xml(array $array, $rootName = 'xml')
    {
        $xml = is_numeric($rootName) ? "" : "<{$rootName}>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } elseif (is_array($val)) {
                $xml .= self::array2xml($val, $key);
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= (is_numeric($rootName) ? "" : "</{$rootName}>");

        return $xml;
    }
}
