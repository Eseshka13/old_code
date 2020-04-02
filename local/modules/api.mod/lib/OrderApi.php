<?php
Bitrix\Main\Loader::IncludeModule('sale');
Bitrix\Main\Loader::includeModule("catalog");

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

global $USER;

class OrderApi extends synchApi
{
    public $apiName = 'order';

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
        if ($this->requestParams['id']) {
            $result = $this->getByID($this->requestParams['id']);
        }
        if ($this->requestParams['filter_name'] && $this->requestParams['filter_val']) {
            $result = $this->GetList($this->requestParams['filter_name'], $this->requestParams['filter_val']);
        }
        return $result;
    }

    public function getByID($id = 0)
    {
        $this->isVal();
        $order = Order::load($id);
        $orderDetail = [];
        $propertyCollection = $order->getPropertyCollection();
        $orderDetail['USER_MAIL'] = $propertyCollection->getUserEmail()->getValue();
        $orderDetail['USER_ID'] = $order->getUserId();
        $orderDetail['USER_NAME'] = $propertyCollection->getPayerName()->getValue();
        $orderDetail['USER_LOCATION'] = $propertyCollection->getDeliveryLocation()->getValue();
        $orderDetail['TAX'] = $propertyCollection->getTaxLocation();
        $orderDetail['PROFILE_NAME'] = $propertyCollection->getProfileName()->getValue();
        $orderDetail['ZIPCODE'] = $propertyCollection->getDeliveryLocationZip()->getValue();
        $orderDetail['USER_PHONE'] = $propertyCollection->getPhone()->getValue();
        $orderDetail['USER_ADDRESS'] = $propertyCollection->getAddress()->getValue();
        $orderDetail['PAYMENT_SYSTEM'] = $order->getPaymentSystemId();
        $orderDetail['DELIVERY_SYSTEM'] = $order->getDeliverySystemId();
        $orderDetail['BASKET_ITEM'] = [];
        $basket = $order->getBasket()->getBasketItems();
        foreach ($basket as $basketItem) {
            $product = [];
            $item = $basketItem;
            $product['PRODUCT_ID'] = $item->getProductId();
            $product['PRODUCT_PRICE'] = $item->getPrice();
            $product['PRODUCT_QUANTITY'] = $item->getQuantity();
            $product['PRODUCT_TOTAL_PRICE'] = $item->getFinalPrice();
            $product['PRODUCT_WEIGHT'] = $item->getWeight();
            $product['PRODUCT_NAME'] = $item->getField('NAME');
            $product['CURRENCY'] = $order->getCurrency();
            $orderDetail['BASKET_ITEM'][$product['PRODUCT_ID']] = $product;
        }
        $result = json_encode($orderDetail);
        return $result;
    }

    function GetList($fName = 'LID', $fVal = 's1')
    {
        $this->isVal();
        $fName = ($fName != '') ? $fName : '!=ID';
        $fVal = ($fVal != '') ? $fVal : false;
        $orderList = [];
        $filter = [
            'filter' => [
                $fName => $fVal
            ],
            'select' => [
                '*'
            ]
        ];
        $db_res = \Bitrix\Sale\Order::getList($filter);
        $arResult = $db_res->fetchAll();
        foreach ($arResult as $item) {
            $orderList[] = json_decode($this->getByID($item['ID']));
            $result = json_encode($orderList);
        }
        return $result;
    }

    public function add()
    {
        $this->isVal();
        $comment = ($this->requestParams['comment']) ? $this->requestParams['comment'] : '';
        $userId = $this->requestParams['USER_ID'];
        if (empty($userId)) {
            $user = new CUser();
            $password = $this->requestParams['USER_MAIL'];
            $arField = array(
                'LOGIN'            => $this->requestParams['USER_MAIL'],
                'NAME'             => $this->requestParams['PROFILE_NAME'],
                'EMAIL'            => $this->requestParams['USER_MAIL'],
                'PASSWORD'         => $password,
                'CONFIRM_PASSWORD' => $password,
                'GROUP_ID'         => COption::GetOptionInt('main', 'new_user_registration_def_group'),
                'ACTIVE'           => "Y",
                'ADMIN_NOTES'      => "Зарегистрирован автоматически при оформлении заказа"
            );
            $ID = $user->Add($arField);
            if ($ID > 0) {
                CEvent::Send("NEW_AUTO_REGISTERED_USER", SITE_ID, array(
                    'LOGIN'    => $this->requestParams['USER_MAIL'],
                    'NAME'     => $this->requestParams['PROFILE_NAME'],
                    'EMAIL'    => $this->requestParams['EMAIL'],
                    'PASSWORD' => $password,
                ));
                $userId = $ID;
                $result['NEW_USER_ID'] = $ID;
            } else {
                $result = $user->LAST_ERROR;
            }
        }
        $siteId = Context::getCurrent()->getSite();
        $basketId = $this->requestParams['basketId'];
        $phone = $this->requestParams['PHONE'];
        $name = $this->requestParams['NAME'];
        $currencyCode = CurrencyManager::getBaseCurrency();
        $basket = Sale\Basket::loadItemsForFUser($basketId, Bitrix\Main\Context::getCurrent()->getSite());
        $rbasket = $basket->getBasketItems();
        if (!empty($userId) && !empty($rbasket)) {
            $order = Order::create($siteId, $userId);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', $currencyCode);
            if ($comment) {
                $order->setField('USER_DESCRIPTION', $comment);
            }
            $order->setBasket($basket);
            $shipmentCollection = $order->getShipmentCollection();
            $shipment = $shipmentCollection->createItem();
            $service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
            $shipment->setFields(array(
                'DELIVERY_ID'   => $service['ID'],
                'DELIVERY_NAME' => $service['NAME'],
            ));
            $shipmentItemCollection = $shipment->getShipmentItemCollection();

            foreach ($rbasket as $i => $product) {
                $shipmentItem = $shipmentItemCollection->createItem($product);
                $shipmentItem->setQuantity($product->getQuantity());
            }
            $paymentCollection = $order->getPaymentCollection();
            $payment = $paymentCollection->createItem();
            $paySystemService = PaySystem\Manager::getObjectById(1);
            $payment->setFields(array(
                'PAY_SYSTEM_ID'   => $paySystemService->getField("PAY_SYSTEM_ID"),
                'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
            ));
            $propertyCollection = $order->getPropertyCollection();
            $phoneProp = $propertyCollection->getPhone();
            $phoneProp->setValue($phone);
            $nameProp = $propertyCollection->getPayerName();
            $nameProp->setValue($name);
            $order->doFinalAction(true);
            $aRresult = $order->save();
            $result['ORDER_ID'] = $order->getId();
        }
        return json_encode($result);
    }

    public function update()
    {
        $this->isVal();
        $fields =
            [
                "EMAIL"    => '',
                "LOCATION" => '',
                "FIO"      => '',
                "PHONE"    => '',
                "ADDRESS"  => '',
            ];
        foreach ($fields as $key => $field) {
            if ($this->requestParams[$key]) {
                $prop = Bitrix\Sale\Internals\OrderPropsValueTable::getList([
                    'filter' => [
                        'ORDER_ID' => $this->requestParams['ORDER_ID'],
                        'CODE'     => $key
                    ]
                ])->Fetch();
                if ($prop && $prop['VALUE'] !== $this->requestParams[$key]) {
                    $result[$key] = CSaleOrderPropsValue::Update($prop['ID'], [
                        'VALUE' => $this->requestParams[$key]
                    ]);
                } else {
                    $result = ['error' => 'request data not modified'];
                }
            }
        }

        return json_encode($result);
    }

    public function delete()
    {
        $this->isVal();
        $id = $this->requestParams['id'];
        $order = \Bitrix\Sale\Order::load($id);
        if ($order) {
            if ($order::delete($id)) {
                $result = ['success' => 'order deleted'];
            } else {
                $result = ['error' => 'order undefind'];
            }
        } else {
            $result = ['error' => 'order undefind'];
        }
        return json_encode($result);

    }

}