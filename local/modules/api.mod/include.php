<?
CModule::AddAutoloadClasses(
    "api.mod",
    array(
        "synchApi"            => "lib/synchApi.php",
        "UsersApi"            => "lib/UsersApi.php",
        "OrderApi"            => "lib/OrderApi.php",
        "ProductApi"          => "lib/ProductApi.php",
        "BasketApi"           => "lib/BasketApi.php",
        "CategoriesApi"       => "lib/CategoriesApi.php",
        "MainControllerClass" => "lib/MainControllerClass.php",
    )
);

