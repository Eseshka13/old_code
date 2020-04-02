<?php
use \Bitrix\Sale;
use Bitrix\Sale\Order;
use Bitrix\Sale\OrderBase;
use Bitrix\Sale\PropertyValue;
use Bitrix\Sale\PropertyValueCollection;
use Bitrix\Main\Context,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem;
class UsersApi extends synchApi
{
    public $apiName = 'users';

    public function defaultMethod()
    {
        return 'Need Method';
    }
    public function get()
    {
        if($this->requestParams['login'] && $this->requestParams['password']){
            $result = $this->needAuth($this->requestParams['login'], $this->requestParams['password']);
        }elseif ($this->requestParams['hash'] && $this->requestParams['login']){
            $result = $this->ValidHash($this->requestParams['hash'], $this->requestParams['login']);
        }else{
            $result['error'] = 'need auth';
        }
        return $result;
    }
    public function needAuth($login, $password){
        $result = [];
        $userData = CUser::GetByLogin($login)->Fetch();
        $salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));
        $realPassword = substr($userData['PASSWORD'], -32);
        $password = md5($salt.$password);
        if($password == $realPassword){
            $cache = Bitrix\Main\Data\Cache::createInstance();
            if ($cache->initCache(2592000, $login, 'cache')) {
                $result = $cache->getVars();
            } elseif ($cache->startDataCache()) {
                $result['hash'] = md5(date('d.m.Y.H.i.s').$login);
                if (0 === count($result)) {
                    $cache->abortDataCache();
                }

                $cache->endDataCache($result);
            }else{
                $result['error'] = 'Permission denied';
            }
        }
        return json_encode($result);

    }
    public static function ValidHash($hash, $login) {
        $cache = Bitrix\Main\Data\Cache::createInstance();
        $search['cash'] = ($cache->initCache(2592000, $login, 'cache')) ? $cache->getVars()['hash'] : false;
        $search['hash'] = $hash;
        $result = ($search['hash'] == $search['cash'] && !empty($hash) && !empty($login)) ? true : false;
        return $result;
    }
    public static function GetUserId($hash, $login) {
        $cache = Bitrix\Main\Data\Cache::createInstance();
        $search['cash'] = ($cache->initCache(2592000, $login, 'cache')) ? $cache->getVars()['hash'] : false;
        $search['hash'] = $hash;
        $result = ($search['hash'] == $search['cash'] && !empty($hash) && !empty($login)) ? true : false;
        $result = ($result) ? CUser::GetByLogin($login)->Fetch()['ID'] : false;
        return $result;
    }
    public function add()
    {

    }
    public function update()
    {

    }
    public function delete()
    {

    }

}