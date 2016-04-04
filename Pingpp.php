<?php
namespace lyt8384\pingpp;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\web\HttpException;

/**
 * Class Pingpp
 * @package lyt8384\pingpp
 * @property \Pingpp\Pingpp $Pingpp Ping++设置类
 * @property \Pingpp\Charge $Charge 支付对象
 * @property \Pingpp\RedEnvelope $RedEnvelope 微信红包对象
 * @property \Pingpp\WxpubOAuth $WxpubOAuth 微信公众号对象
 * @property \Pingpp\Event $Event Event事件对象
 * @property \Pingpp\Transfer $Transfer 微信企业付款对象
 * @property \Pingpp\CardInfo $CardInfo 卡片信息对象
 * @property \Pingpp\Customer $Customer 顾客对象
 * @property \Pingpp\Token $Token Token对象
 */

class Pingpp extends Component
{
    public $live = false;
    public $test_secret_key;
    public $live_secret_key;
    public $pub_key_path;
    public $private_key_path;

    protected $method;
    protected $err = null;

    public function init()
    {
        \Pingpp\Pingpp::setApiKey(
            $this->live
                ? $this->live_secret_key
                : $this->test_secret_key
        );

        if (!empty($this->private_key_path)) {
            if (file_exists($this->private_key_path))
                throw new InvalidConfigException('The private key file not exists.');
            \Pingpp\Pingpp::setPrivateKeyPath($this->private_key_path);
        }
    }

    public function __call($method, $arg_array = null)
    {
        try {
            if ($this->method) {
                if (method_exists('\\Pingpp\\' . $this->method, $method)) {
                    $func = '\\Pingpp\\' . $this->method . '::' . $method;
                    $ret = forward_static_call_array($func, $arg_array);
                    return $ret;
                }
            } else {
                $class = '\\Pingpp\\' . $method;
                if (class_exists($class)) {
                    $this->method = $method;
                    return $this;
                } else {
                    if (method_exists('\\Pingpp\\Charge', $method)) {
                        $func = '\\Pingpp\\Charge::' . $method;
                        $ret = forward_static_call_array($func, $arg_array);
                        return $ret;
                    }
                }
            }
        } catch (\Pingpp\Error\Base $e) {
            $this->err = $e;
            Yii::warning($e->getHttpBody(),'pingpp');
            return false;
        }
        return null;
    }

    public static function __callStatic($method, $arg_array = null)
    {
        return new self;
    }

    public function __get($property)
    {
        return $this->__call($property);
    }

    public function getError()
    {
        return $this->err;
    }

    /**
     * Webhooks接收类
     * @return mixed
     * @throws HttpException
     */
    public function notice()
    {
        $data_raw = Yii::$app->request->getRawBody();
        $data = json_decode($data_raw, true);
        if (!isset($data['type'])) {
            Yii::warning('Pingpp webhooks received to a unknown callback. Raw:' . $data_raw, 'pingpp');
            throw new HttpException(400,'fail');
        }

        if (!empty($this->pub_key_path) && file_exists($this->pub_key_path)) {
            $result = openssl_verify(
                $data_raw,
                base64_decode(Yii::$app->request->headers->get('x-pingplusplus-signature')),
                trim(file_get_contents($this->pub_key_path)),
                OPENSSL_ALGO_SHA256);
            if ($result !== 1) {
                Yii::warning('Pingpp webhooks received to a unauthenticated callback. Raw:' . $data_raw, 'pingpp');
                throw new HttpException(403,'fail');
            }
        }
        return $data;
    }
}
