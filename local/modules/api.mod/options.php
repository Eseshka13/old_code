<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;

$module_id = 'api.mod';

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule($module_id);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$arTabs = array(
    array(
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('API_MOD_SETTING'),
        'OPTIONS' => array(
            array(
                'url', Loc::getMessage('API_MOD_URL'), '', array('text', 50)
            )
        )
    ),
    array(
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS')
    )
);

if ($request->isPost() && $request['Update'] && check_bitrix_sessid())
{
    foreach ($arTabs as $arTab) {
        foreach ($arTab['OPTIONS'] as $OPTION) {
            if (!is_array($OPTION))
                continue;

            if ($OPTION['note'])
            {
                continue;
            }


            $optName = $OPTION[0];

            $optVal = $request->getPost($optName);

            Option::set($module_id, $optName, is_array($optVal) ? implode(',', $optVal) : $optVal);

        }
    }
}

$tabControl = new CAdminTabControl('tabControl', $arTabs);

?>
<?$tabControl->Begin();?>
<form method="post" action="<?= $APPLICATION->GetCurPage()?>?mid=<?= htmlspecialcharsbx($request['mid'])?>&amp;lang=<?= $request['lang']?>" name="api_mod_settings">
    <?
    foreach ($arTabs as $arTab)
    {
        if ($arTab['OPTIONS'])
        {
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id,$arTab['OPTIONS']);
        }
    }
    $tabControl->BeginNextTab();

    $tabControl->Buttons();
    ?>

    <input type="submit" name="Update" value="<?= GetMessage('MAIN_SAVE')?>">
    <input type="reset" name="reset" value="<?= GetMessage('MAIN_RESET')?>">
    <?= bitrix_sessid_post();?>
</form>
<?$tabControl->End();?>

