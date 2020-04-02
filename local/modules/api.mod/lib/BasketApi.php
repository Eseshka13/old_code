<?php
Bitrix\Main\Loader::IncludeModule('sale');
Bitrix\Main\Loader::includeModule("catalog");
Bitrix\Main\Loader::includeModule("iblock");

use \Bitrix\Sale;
use Bitrix\Sale\Order;
use Bitrix\Sale\OrderBase;
use Bitrix\Sale\PropertyValue;
use Bitrix\Sale\PropertyValueCollection;
use Bitrix\Main\Context;
use \Bitrix\Iblock\ElementTable,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem;

global $USER;

class BasketApi extends synchApi
{
    public $apiName = 'basket';
    public $basketId = false;
    public function defaultMethod()
    {
        return 'Need Method';
    }

    public function isVal()
    {
        if ($this->valid === false) {
            die(json_encode(['error' => 'expires hash']));
        }
    }

    public function get()
    {
        $this->isVal();
        if($this->requestParams['init'] == 'Y'){
            $result = $this->Init();
            $result = json_encode($result);
        }
        if ($this->requestParams['basketId']){
            $result = Sale\Basket::loadItemsForFUser($this->Init(), Bitrix\Main\Context::getCurrent()->getSite())->getListOfFormatText();
            $result = json_encode($result);
        }
        if ($this->requestParams['delete'] == 'Y' && $this->requestParams['id']){
        }
        return $result;
    }
    public function Init(){
        $result = $this->basketId = ($this->requestParams['basketId']) ? $this->requestParams['basketId'] : CSaleBasket::GetBasketUserID(false);
        return $result;
    }

    public function add()
    {

        $this->isVal();
        $result = $this->addProduct($this->requestParams['id'], $this->requestParams['quantity']);
        return json_encode($result);
    }
    public function update()
    {
        $this->isVal();
        $result = $this->addProduct($this->requestParams['id'], $this->requestParams['quantity']);
        return json_encode($result);
    }

    public function delete()
    {
        $this->isVal();
        $result = $this->deleteProduct($this->requestParams['id']);
        return json_encode($result);

    }
    public function addProduct($ProductId, $q){
        $basket = Sale\Basket::loadItemsForFUser($this->Init(), Bitrix\Main\Context::getCurrent()->getSite());
        $new = true;
        $arItems =$basket->getBasketItems();
        foreach ($arItems as $item){
            if ($item->getProductId() == $ProductId){
                $new = false;
            }
        }
        $product = ($new) ? $basket->createItem('catalog', $ProductId) : $basket->getExistsItem('catalog', $ProductId);
        $quantity = ($q != $product->getQuantity()) ? $q : false;
        if ($quantity){
            $product->setFields(array(
                'QUANTITY' => $quantity,
                'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
            ));
            $basket->save();
            $result = $basket->getListOfFormatText();
        }else{
            $result = ['error' => 'quantity matched'];
        }
        return $result;
    }

    public function deleteProduct($ProductId){
        $basketId = $this->Init();
        $basket = Sale\Basket::loadItemsForFUser($basketId, Bitrix\Main\Context::getCurrent()->getSite());
        $arItems = $basket->getBasketItems();
        foreach ($arItems as $item) {
            if ($item->getProductId() == $ProductId)
                $item->delete();
        }
        $basket->save();
        $result = $basket->getListOfFormatText();
        return $result;
    }

}
