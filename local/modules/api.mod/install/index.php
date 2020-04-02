<?

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

Class api_mod extends CModule
{

    var $exclusionAdminFiles;

    function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__.'/version.php');
        $this->MODULE_ID = 'api.mod';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('API_MOD_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('API_MOD_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('API_MOD_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('API_MOD_PARTNER_URI');
        $this->MODULE_GROUP_RIGHTS = 'Y';
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
    }

    public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
    }
    function InstallEvents()
    {
    }
    function UnInstallEvents()
    {
    }
    function UnInstallDB()
    {
        Option::delete($this->MODULE_ID);
    }
    function InstallFiles(){
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/api.mod/install/public/api/", $_SERVER["DOCUMENT_ROOT"]."/api/");
    }
    function UnInstallFiles()
    {
        DeleteDirFilesEx("/api");
    }
    function DoInstall()
    {
        global $APPLICATION;
        if($this->isVersionD7())
        {
//            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();

            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage('API_MOD_ERROR_D7_VERSION'));
        }
    }

    function DoUninstall()
    {
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->UninstallEvents();
        $this->UnInstallFiles();
//        if ($request['savedata'] != 'Y')
//        {
//            $this->UnInstallDB();
//        }
    }
    function GetModuleRightList()
    {
        return array(
            'reference_id' => array('D','K', 'S', 'W'),
            'reference' => array(
                '[D] ' . Loc::getMessage('API_MOD_DENIED'),
                '[K] ' . Loc::getMessage('API_MOD_COMPONENT'),
                '[S] ' . Loc::getMessage('API_MOD_SETTINGS'),
                '[W] ' . Loc::getMessage('API_MOD_FULL'),
            )
        );
    }
}