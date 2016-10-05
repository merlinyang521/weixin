<?php
namespace Kof\Weixin\Pay;

class Sign
{
    /**
     * 格式化参数格式化成url参数
     * @return string
     */
    protected static function toUrlParams(array $params)
    {
        $buff = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        return trim($buff, "&");
    }

    /**
     * 生成签名
     * @param string $mch_key
     * @param array $params
     */
    public static function make($mch_key, array $params)
    {
        //签名步骤一：按字典序排序参数
        ksort($params);
        $string = self::toUrlParams($params);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $mch_key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
}
