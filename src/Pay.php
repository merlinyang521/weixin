<?php
namespace Kof\Weixin;

use Kof\Weixin\Pay\Api;
use Kof\Weixin\Pay\Sign;
use Kof\Weixin\Pay\Exception as PayException;

class Pay
{
    /**
     * @var string
     */
    protected $appid;

    /**
     * @var string
     */
    protected $mch_id;

    /**
     * @var string
     */
    protected $mch_key;

    /**
     * @var string|null
     */
    protected $sslcert_path;

    /**
     * @var string|null
     */
    protected $sslkey_path;

    /**
     * Pay constructor.
     * @param string $appid
     * @param string $mch_id
     * @param string $mch_key
     * @param null|string $sslcert_path
     * @param null|string $sslkey_path
     */
    public function __construct($appid, $mch_id, $mch_key, $sslcert_path = null, $sslkey_path = null)
    {
        $this->appid = $appid;
        $this->mch_id = $mch_id;
        $this->mch_key = $mch_key;
        $this->sslcert_path = $sslcert_path;
        $this->sslkey_path = $sslkey_path;
    }

    /**
     * 统一下单
     * @param array $params
     * @return array|null
     * @throws PayException
     */
	public function unifiedorder(array $params)
    {
        if (!isset($params['body'])) {
            throw new PayException("缺少统一支付接口必填参数body！");
        }

        if (isset($params['detail'])) {
            if (!is_array($params['detail'])) {
                throw new PayException("统一支付接口参数detail必须为数组！");
            }
            $params['detail'] = \json_encode($params['detail']);
        }

        if (!isset($params['out_trade_no'])) {
            throw new PayException("缺少统一支付接口必填参数out_trade_no！");
        }

        if (!isset($params['total_fee'])) {
            throw new PayException("缺少统一支付接口必填参数total_fee！");
        }

        if (!isset($params['spbill_create_ip'])) {
            throw new PayException("缺少统一支付接口必填参数spbill_create_ip！");
        }

        if (!isset($params['notify_url'])) {
            throw new PayException("缺少统一支付接口必填参数notify_url！");
        }

        if (!isset($params['trade_type'])) {
            throw new PayException("缺少统一支付接口必填参数trade_type！");
        }

        if ($params['trade_type'] == 'NATIVE' && !isset($params['product_id'])) {
            throw new PayException("缺少统一支付接口必填参数product_id！");
        }

        if ($params['trade_type'] == 'JSAPI' && !isset($params['openid'])) {
            throw new PayException("缺少统一支付接口必填参数openid！");
        }

        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('pay/unifiedorder', $params);
	}

    /**
     * 提交刷卡支付
     * @param array $params
     * @return array|null
     * @throws PayException
     */
	public function micropay(array $params)
    {
        if (!isset($params['body'])) {
            throw new PayException("缺少统一支付接口必填参数body！");
        }

        if (isset($params['detail'])) {
            if (!is_array($params['detail'])) {
                throw new PayException("统一支付接口参数detail必须为数组！");
            }
            $params['detail'] = \json_encode($params['detail']);
        }

        if (!isset($params['out_trade_no'])) {
            throw new PayException("缺少统一支付接口必填参数out_trade_no！");
        }

        if (!isset($params['total_fee'])) {
            throw new PayException("缺少统一支付接口必填参数total_fee！");
        }

        if (!isset($params['spbill_create_ip'])) {
            throw new PayException("缺少统一支付接口必填参数spbill_create_ip！");
        }

        if (!isset($params['auth_code'])) {
            throw new PayException("缺少统一支付接口必填参数auth_code！");
        }

        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('pay/micropay', $params);
    }

    /**
     * 支付结果通知
     * @param callable $success
     * @param callable|null $fail
     * @return string
     */
    public function notifyHandle(callable $success, callable $fail = null)
    {
        $xml = file_get_contents("php://input");
        if (empty($xml)) {
            $fail && $fail(null);
            return Utils::array2xml([
                'return_code' => 'FAIL',
                'return_msg' => 'xml错误'
            ]);
        }

        try {
            libxml_disable_entity_loader(true);
            $result = \GuzzleHttp\json_decode(
                \GuzzleHttp\json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)),
                true
            );
        } catch (\Exception $e) {
            $fail && $fail(null);
            return Utils::array2xml([
                'return_code' => 'FAIL',
                'return_msg' => $e->getMessage()
            ]);
        }

        if (!isset($result['sign']) || empty($result['sign'])) {
            $fail && $fail($result);
            return Utils::array2xml([
                'return_code' => 'FAIL',
                'return_msg' => '签名错误'
            ]);
        }

        if (Sign::make($this->mch_key, $result) != $result['sign']) {
            $fail && $fail($result);
            return Utils::array2xml([
                'return_code' => 'FAIL',
                'return_msg' => '签名错误'
            ]);
        }

        if (!isset($result['transaction_id']) || empty($result['transaction_id'])) {
            $fail && $fail($result);
            return Utils::array2xml([
                'return_code' => 'FAIL',
                'return_msg' => 'transaction_id错误'
            ]);
        }

        try {
            $orderInfo = $this->orderquery(null, $result['transaction_id']);
            if (array_key_exists("return_code", $orderInfo) &&
                array_key_exists("result_code", $orderInfo) &&
                $orderInfo["return_code"] == "SUCCESS" &&
                $orderInfo["result_code"] == "SUCCESS"
            ) {
                $success($result);
                return Utils::array2xml([
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'OK'
                ]);
            }
        } catch (PayException $e) {

        }

        $fail && $fail($result);
        return Utils::array2xml([
            'return_code' => 'FAIL',
            'return_msg' => '订单查询失败'
        ]);
    }

    /**
     * 查询订单
     * @param null|string $out_trade_no
     * @param null|string $transaction_id
     * @return array|null
     * @throws PayException
     */
	public function orderquery($out_trade_no = null, $transaction_id = null)
    {
        if (!strlen($transaction_id) || !strlen($out_trade_no)) {
            throw new PayException("缺少查询订单接口必填参数transaction_id或out_trade_no！");
        }

        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        if (strlen($out_trade_no)) {
            $params['out_trade_no'] = $out_trade_no;
        }
        if (strlen($transaction_id)) {
            $params['transaction_id'] = $transaction_id;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('pay/orderquery', $params);
    }

    /**
     * 关闭订单
     * @param string $out_trade_no
     * @return array|null
     */
    public function closeorder($out_trade_no)
    {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['out_trade_no'] = $out_trade_no;
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('pay/closeorder', $params);
    }

    /**
     * 申请退款
     * @param $out_refund_no
     * @param $total_fee
     * @param $refund_fee
     * @param null|string $out_trade_no
     * @param null|string $transaction_id
     * @param null|string $op_user_id
     * @param null|string $device_info
     * @param null|string $refund_fee_type
     * @param null|string $refund_account
     * @return array|null
     * @throws PayException
     */
    public function refund(
        $out_refund_no, $total_fee, $refund_fee, $out_trade_no = null, $transaction_id = null,
        $op_user_id = null, $device_info = null, $refund_fee_type = null, $refund_account = null
    ) {
        if (!strlen($transaction_id) || !strlen($out_trade_no)) {
            throw new PayException("缺少申请退款接口必填参数transaction_id或out_trade_no！");
        }

        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        if (strlen($out_trade_no)) {
            $params['out_trade_no'] = $out_trade_no;
        }
        if (strlen($transaction_id)) {
            $params['transaction_id'] = $transaction_id;
        }
        $params['out_refund_no'] = $out_refund_no;
        $params['total_fee'] = $total_fee;
        $params['refund_fee'] = $refund_fee;
        $params['op_user_id'] = $op_user_id ? $op_user_id : $this->mch_id;
        if (strlen($device_info)) {
            $params['device_info'] = $device_info;
        }
        if (strlen($refund_fee_type)) {
            $params['refund_fee_type'] = $refund_fee_type;
        }
        if (strlen($refund_account)) {
            $params['refund_account'] = $refund_account;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('secapi/pay/refund', $params, $this->sslcert_path, $this->sslkey_path);
    }

    /**
     * 查询退款
     * @param null|string $transaction_id
     * @param null|string $out_trade_no
     * @param null|string $out_refund_no
     * @param null|string $refund_id
     * @return array|null
     * @throws PayException
     */
    public function refundquery($transaction_id = null, $out_trade_no = null, $out_refund_no = null, $refund_id = null)
    {
        if (!strlen($transaction_id) || !strlen($out_trade_no) || !strlen($out_refund_no) || !strlen($refund_id)) {
            throw new PayException("缺少申请退款接口必填参数transaction_id或out_trade_no或out_refund_no或refund_id！");
        }

        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        if (strlen($out_trade_no)) {
            $params['out_trade_no'] = $out_trade_no;
        }
        if (strlen($transaction_id)) {
            $params['transaction_id'] = $transaction_id;
        }
        if (strlen($out_refund_no)) {
            $params['out_refund_no'] = $out_refund_no;
        }
        if (strlen($refund_id)) {
            $params['refund_id'] = $refund_id;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('pay/refundquery', $params);
    }

    /**
     * 下载对账单
     * @param string $bill_date
     * @param string $bill_type
     * @return array|null
     */
    public function downloadbill($bill_date, $bill_type = 'ALL')
    {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['bill_date'] = $bill_date;
        $params['bill_type'] = $bill_type;
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('pay/downloadbill', $params);
    }

    /**
     * 转换短链接
     * @param string $long_url
     * @return array|null
     */
    public function shorturl($long_url)
    {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['long_url'] = $long_url;
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('tools/shorturl', $params);
    }

    /**
     * 授权码查询OPENID接口
     * @param string $auth_code
     * @return array|null
     */
    public function authcodetoopenid($auth_code)
    {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['auth_code'] = $auth_code;
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('tools/authcodetoopenid', $params);
    }

    /**
     * 查询代金券批次信息
     * @param string $coupon_stock_id
     * @param null|int $op_user_id
     * @param null|string $device_info
     * @param null|string $version
     * @param null|string $type
     * @return array|null
     */
    public function queryCouponStock(
        $coupon_stock_id, $op_user_id = null, $device_info = null, $version = null, $type = null
    ) {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['coupon_stock_id'] = $coupon_stock_id;
        $params['op_user_id'] = $op_user_id ? $op_user_id : $this->mch_id;
        if ($device_info) {
            $params['device_info'] = $device_info;
        }
        if ($version) {
            $params['version'] = $version;
        }
        if ($type) {
            $params['type'] = $type;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/query_coupon_stock', $params);
    }

    /**
     * 发放代金券
     * @param string $coupon_stock_id
     * @param string $partner_trade_no
     * @param string $openid
     * @param int $openid_count
     * @param null|int $op_user_id
     * @param null|string $device_info
     * @param null|string $version
     * @param null|string $type
     */
    public function sendCoupon(
        $coupon_stock_id, $partner_trade_no, $openid, $openid_count = 1,
        $op_user_id = null, $device_info = null, $version = null, $type = null
    ) {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['coupon_stock_id'] = $coupon_stock_id;
        $params['partner_trade_no'] = $partner_trade_no;
        $params['openid'] = $openid;
        $params['openid_count'] = $openid_count;
        $params['op_user_id'] = $op_user_id ? $op_user_id : $this->mch_id;
        if ($device_info) {
            $params['device_info'] = $device_info;
        }
        if ($version) {
            $params['version'] = $version;
        }
        if ($type) {
            $params['type'] = $type;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/send_coupon', $params, $this->sslcert_path, $this->sslkey_path);
    }

    /**
     * 查询代金券信息
     * @param string $coupon_id
     * @param string $openid
     * @param string $stock_id
     * @param null|string $op_user_id
     * @param null|string $device_info
     * @param null|string $version
     * @param null|string $type
     * @return array|null
     */
    public function querycouponsinfo(
        $coupon_id, $openid, $stock_id, $op_user_id = null, $device_info = null, $version = null, $type = null
    ) {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['coupon_id'] = $coupon_id;
        $params['openid'] = $openid;
        $params['stock_id'] = $stock_id;
        $params['op_user_id'] = $op_user_id ? $op_user_id : $this->mch_id;
        if ($device_info) {
            $params['device_info'] = $device_info;
        }
        if ($version) {
            $params['version'] = $version;
        }
        if ($type) {
            $params['type'] = $type;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/querycouponsinfo', $params);
    }

    /**
     * 发放普通红包
     * @param string $mch_billno
     * @param string $send_name
     * @param string $re_openid
     * @param string $total_amount
     * @param string $wishing
     * @param string $client_ip
     * @param string $act_name
     * @param string $remark
     * @param int $total_num
     * @param null|string $scene_id
     * @param null|string $risk_info
     * @param null|string $consume_mch_id
     * @return array|null
     */
    public function sendredpack(
        $mch_billno, $send_name, $re_openid, $total_amount, $wishing, $client_ip, $act_name, $remark, $total_num = 1,
        $scene_id = null, $risk_info = null, $consume_mch_id = null
    ) {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['mch_billno'] = $mch_billno;
        $params['send_name'] = $send_name;
        $params['re_openid'] = $re_openid;
        $params['total_amount'] = $total_amount;
        $params['wishing'] = $wishing;
        $params['client_ip'] = $client_ip;
        $params['act_name'] = $act_name;
        $params['remark'] = $remark;
        $params['total_num'] = $total_num;
        if ($scene_id) {
            $params['scene_id'] = $scene_id;
        }
        if ($risk_info) {
            $params['risk_info'] = $risk_info;
        }
        if ($consume_mch_id) {
            $params['consume_mch_id'] = $consume_mch_id;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/sendredpack', $params, $this->sslcert_path, $this->sslkey_path);
    }

    /**
     * 发放裂变红包
     * @param string $mch_billno
     * @param string $send_name
     * @param string $re_openid
     * @param string $total_amount
     * @param string $wishing
     * @param string $client_ip
     * @param string $act_name
     * @param string $remark
     * @param int $total_num
     * @param null|string $scene_id
     * @param null|string $risk_info
     * @param null|string $consume_mch_id
     * @return array|null
     */
    public function sendgroupredpack(
        $mch_billno, $send_name, $re_openid, $total_amount, $wishing, $client_ip, $act_name, $remark,
        $total_num = 3, $scene_id = null, $risk_info = null, $consume_mch_id = null
    ) {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['mch_billno'] = $mch_billno;
        $params['send_name'] = $send_name;
        $params['re_openid'] = $re_openid;
        $params['total_amount'] = $total_amount;
        $params['wishing'] = $wishing;
        $params['client_ip'] = $client_ip;
        $params['act_name'] = $act_name;
        $params['remark'] = $remark;
        $params['total_num'] = $total_num;
        if ($scene_id) {
            $params['scene_id'] = $scene_id;
        }
        if ($risk_info) {
            $params['risk_info'] = $risk_info;
        }
        if ($consume_mch_id) {
            $params['consume_mch_id'] = $consume_mch_id;
        }
        $params['amt_type'] = 'ALL_RAND';
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/sendgroupredpack', $params, $this->sslcert_path, $this->sslkey_path);
    }

    /**
     * 查询红包记录
     * @param string $mch_billno
     * @param string $bill_type
     * @return array|null
     */
    public function gethbinfo($mch_billno, $bill_type = 'MCHT')
    {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['mch_billno'] = $mch_billno;
        $params['bill_type'] = $bill_type;
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/gethbinfo', $params, $this->sslcert_path, $this->sslkey_path);
    }

    /**
     * 企业付款
     * @param string $partner_trade_no
     * @param string $openid
     * @param string $amount
     * @param string $desc
     * @param string $spbill_create_ip
     * @param string $check_name
     * @param null|string $re_user_name
     * @return array|null
     * @throws PayException
     */
    public function transfers(
        $partner_trade_no, $openid, $amount, $desc, $spbill_create_ip, $check_name = 'NO_CHECK', $re_user_name = null
    ) {
        if (($check_name == 'FORCE_CHECK' || $check_name == 'OPTION_CHECK') && !strlen($re_user_name)) {
            throw new PayException("缺少企业付款接口必填参数re_user_name！");
        }

        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['partner_trade_no'] = $partner_trade_no;
        $params['openid'] = $openid;
        $params['amount'] = $amount;
        $params['desc'] = $desc;
        $params['spbill_create_ip'] = $spbill_create_ip;
        $params['check_name'] = $check_name;
        if ($re_user_name) {
            $params['re_user_name'] = $re_user_name;
        }
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/promotion/transfers', $params, $this->sslcert_path, $this->sslkey_path);
    }

    /**
     * 查询企业付款
     * @param string $partner_trade_no
     * @return array|null
     */
    public function gettransferinfo($partner_trade_no)
    {
        $params = [];
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mch_id;
        $params['nonce_str'] = Utils::getRandomStr(32);
        $params['partner_trade_no'] = $partner_trade_no;
        $params['sign'] = Sign::make($this->mch_key, $params);

        return Api::post('mmpaymkttransfers/gettransferinfo', $params, $this->sslcert_path, $this->sslkey_path);
    }
}
