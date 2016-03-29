<?php
namespace lyt8384\pingpp;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
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

        if(!empty($this->private_key_path)){
            if(file_exists($this->private_key_path))
                throw new InvalidConfigException('The private key file not exists.');
            \Pingpp\Pingpp::setPrivateKeyPath($this->private_key_path);
        }
    }

    public function __call($method, $arg_array = null)
    {
        try {
            if ($this->method) {
                if (method_exists('\\Pingpp\\Pingpp\\' . $this->method, $method)) {
                    $func = '\\Pingpp\\Pingpp\\' . $this->method . '::' . $method;
                    $ret = forward_static_call_array($func, $arg_array);
                    return $ret;
                }
            } else {
                $class = '\\Pingpp\\Pingpp\\' . $method;
                if (class_exists($class)) {
                    $this->method = $method;
                    return $this;
                } else {
                    if (method_exists('\\Pingpp\\Pingpp\Charge', $method)) {
                        $func = '\\Pingpp\\Pingpp\Charge::' . $method;
                        $ret = forward_static_call_array($func, $arg_array);
                        return $ret;
                    }
                }
            }
        } catch (\Pingpp\Error\Base $e) {
            $this->err = $e;
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

    public function notice()
    {
        $data_raw = Yii::$app->request->getRawBody();
        $data = json_decode($data_raw,true);
        if (!isset($data['type'])) {
            Yii::$app->end(400,'fail');
        }

        if (!empty($this->pub_key_path) && file_exists($this->pub_key_path)) {
            $result = openssl_verify(
                $data_raw,
                base64_decode(Yii::$app->request->headers->get('x-pingplusplus-signature')),
                trim(file_get_contents($this->pub_key_path)),
                OPENSSL_ALGO_SHA256);
            if ($result !== 1) {
                Yii::$app->end(403,'fail');
            }
        }
        return $data;
    }
}
