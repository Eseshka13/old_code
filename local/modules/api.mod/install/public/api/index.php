<?php
include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
global $APPLICATION;
CModule::IncludeModule('api.mod');
$api = [
    'users' => new UsersApi(),
    'product' => new ProductApi(),
    'order' => new OrderApi(),
    'basket' => new BasketApi(),
    'section' => new CategoriesApi()
];
$page = $APPLICATION->GetCurPage();
try {
    foreach ($api as $key => $class) {
        if (strpos($page, $key) !== false) {
            $result = $class->run();
            $arResult = json_decode($result, true);
//            echo $result;
            echo "<pre style='font-size: 10px;text-align: left;'>"; print_r($arResult); echo "</pre>"; //TODO del pre
        }
    }
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}