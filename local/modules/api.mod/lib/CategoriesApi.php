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

class CategoriesApi extends synchApi
{
    public $apiName = 'section';

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
        if (($this->requestParams['filter_name'] && $this->requestParams['filter_val']) || $this->requestParams['limit']) {
            $result = $this->GetList($this->requestParams['filter_name'], $this->requestParams['filter_val']);
        }
        return $result;
    }

    public function getByID($id = 0)
    {
        $this->isVal();
        $filter = [
            'filter' => [
                'ID' => $id
            ],
            'select' => [
                '*',
                'ID',
                'NAME',
                'CODE',
                'DETAIL_PICTURE',
                'PICTURE',
            ],
        ];
        $db_res = \Bitrix\Iblock\SectionTable::getList($filter);
        $result = $db_res->fetchAll();
        $result = (!empty($result)) ? $result : ['error' => 'Product not found'];
        $result = json_encode($result);
        return $result;
    }

    function GetList($fName = 'ACTIVE', $fVal = 'Y')
    {
        $this->isVal();
        $filter = [];
        if (!empty($fName)) {
            $filter['filter'][$fName] = $fVal;
        }
        if ($this->requestParams['limit']) {
            $filter['limit'] = $this->requestParams['limit'];
            if ($this->requestParams['offset']) {
                $filter['offset'] = $this->requestParams['offset'];
            }
        }
        $filter['select'] = [
            '*',
            'ID',
            'NAME',
            'CODE',
            'DETAIL_PICTURE',
            'PICTURE',
        ];
        $db_res = \Bitrix\Iblock\SectionTable::getList($filter);
        $result = $db_res->fetchAll();
        $arId = [];
        unset($filter['select']);
        unset($filter['offset']);
        foreach ($result as $res) {
            $arId[] = $res['ID'];
        }
        foreach ($result as $k => $item) {
            $result[$k]['DETAIL_PICTURE'] = CFile::GetPath($item['DETAIL_PICTURE']);
            $result[$k]['PICTURE'] = CFile::GetPath($item['PREVIEW_PICTURE']);
        }
        $result = json_encode($result);
        return $result;
    }

    public function add()
    {

        $this->isVal();
        $arFields = Array(
            "ACTIVE" => 'Y',
            "IBLOCK_SECTION_ID" => $this->requestParams['parent'],
            "IBLOCK_ID" => ($this->requestParams['iblock_id']) ? $this->requestParams['iblock_id'] : '',
            "NAME" => $this->requestParams['name'],
            "SORT" => $this->requestParams['parent'],
        );

        if($ID > 0)
        {
            $res = $bs->Update($ID, $arFields);
        }
        else
        {
            $ID = $bs->Add($arFields);
            $res = ($ID>0);
        }

        unlink($outputFile);
        return json_encode($result);
    }

    public function update()
    {
        $this->isVal();
        if ($this->requestParams['PRODUCT_ID'] > 0) {
            $this->isVal();
            $lid = CSite::GetList($by, $order, ['DEFAULT ' => 'Y'])->Fetch()['LID'];
            $catalogId = CCatalog::GetList(["SORT" => 'ASC'], ['LID ' => $lid])->Fetch()['ID'];
            $ImageUrl = 'http://rest.test/upload/iblock/201/201c710b5f4a044875a4b5c2841e9dde.jpg';
            $Image = explode('/', $ImageUrl);
            $Image = $Image[count($Image) - 1];
            $outputFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/' . $Image;
            file_put_contents($outputFile, file_get_contents('http://rest.test/upload/iblock/201/201c710b5f4a044875a4b5c2841e9dde.jpg'));
            $el = new CIBlockElement;
            $arLoadProductArray = Array(
                "MODIFIED_BY"       => $this->user_id,
                // элемент изменен текущим пользователем
                "IBLOCK_SECTION_ID" => false,
                // элемент лежит в корне раздела
                "IBLOCK_ID"         => $catalogId,
                "ACTIVE"            => "Y",
                // активен
                "PREVIEW_TEXT"      => "текст для списка элементов",
                "DETAIL_TEXT"       => "текст для детального просмотра",
                "DETAIL_PICTURE"    => Cfile::MakeFileArray($outputFile),
                "PREVIEW_PICTURE"   => Cfile::MakeFileArray($outputFile),
                'PROPERTIES'        => [
                    'PROP_2055'  => 'Синий',
                    'CML2_TAXES' => [
                        '50',
                        '60'
                    ]
                ],
                'PRODUCT'           => [
                    'QUANTITY'          => 3,
                    'QUANTITY_RESERVED' => 1,
                    'QUANTITY_TRACE'    => 'Y',
                    'CAN_BUY_ZERO'      => 'Y',
                    'SUBSCRIBE'         => 'N'
                ],
                'PRICES'            => [
                    "CATALOG_GROUP_ID" => 1,
                    "PRICE"            => 29.95,
                    "CURRENCY"         => "USD",
                ]
            );
            $Properties = $arLoadProductArray['PROPERTIES'];
            $arProduct = $arLoadProductArray['PRODUCT'];
            $arPrice = $arLoadProductArray['PRICES'];
            unset($arLoadProductArray['PROPERTIES']);
            unset($arLoadProductArray['PRODUCT']);
            unset($arLoadProductArray['PRICES']);
            if ($PRODUCT_ID = $el->Update($this->requestParams['PRODUCT_ID'], $arLoadProductArray)) {
                $result['success'][] = 'Product added. id:' . $PRODUCT_ID;
                foreach ($Properties as $key => $value) {
                    $p = CIBlockElement::SetPropertyValueCode($PRODUCT_ID, $key, $value);
                    if ($p) {
                        $result['success'][] = 'property ' . $key . 'added to product ' . $PRODUCT_ID;
                    } else {
                        $result['error'][] = 'property ' . $key . 'failed ' . $PRODUCT_ID;
                    }
                }
                $arPrice['PRODUCT_ID'] = $PRODUCT_ID;
                $arProduct['ID'] = $PRODUCT_ID;
                if (CCatalogProduct::Add($arProduct)) {
                    $result['success'][] = 'Product char updated';
                } else {
                    $result['success'][] = 'Product char failed';
                }
                if (CPrice::Add($arPrice)) {
                    $result['success'][] = 'Price updated';
                } else {
                    $result['error'][] = 'Price failed';
                }

            } else {
                $result['error'][] = 'product failed';
            }
            unlink($outputFile);
        }
        return json_encode($result);
    }

    public function delete()
    {
        $this->isVal();
        $id = $this->requestParams['id'];
        CCatalogProduct::Delete($id);
        if (CIBlockElement::Delete($id)) {
            $result = ['success' => 'product deleted'];
        } else {
            $result = ['error' => 'product not deleted'];
        }
        return json_encode($result);

    }

}