<?php
// local/modules/devtech.clearim/install/index.php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class devtech_clearim extends CModule
{
    var $MODULE_ID = 'devtech.clearim';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'N';
    var $PARTNER_NAME = 'DevTech';
    var $PARTNER_URI = 'https://devtech.ru';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('DEVTC_CLEARIM_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('DEVTC_CLEARIM_MODULE_DESC');
    }

    public function DoInstall()
    {
        global $APPLICATION;
        
        if (!CheckVersion(ModuleManager::getVersion('main'), '20.00.00')) {
            $APPLICATION->ThrowException(Loc::getMessage('DEVTC_CLEARIM_INSTALL_ERROR_VERSION'));
            return false;
        }
        
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallEvents();
        $this->InstallFiles();
        $this->InstallAgents();
        $this->InstallOptions();
        
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('DEVTC_CLEARIM_INSTALL_TITLE'),
            __DIR__ . '/steps.php'
        );
        
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $request = $context->getRequest();
        
        if ($request['savedata'] !== 'Y') {
            $this->UnInstallDB();
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->UnInstallAgents();
            $this->UnInstallOptions();
        }
        
        ModuleManager::unRegisterModule($this->MODULE_ID);
        
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('DEVTC_CLEARIM_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
        
        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );
        
        CopyDirFiles(
            __DIR__ . '/assets',
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/assets',
            true,
            true
        );
        
        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );
        
        DeleteDirFilesEx('/local/modules/' . $this->MODULE_ID . '/assets');
        
        return true;
    }

    public function InstallEvents()
    {
//        $eventManager = EventManager::getInstance();
//        $eventManager->registerEventHandler(
//            'main',
//            'OnAfterEpilog',
//            $this->MODULE_ID,
//            '\\ClearIm\\Event\\Handlers',
//            'onPageStart'
//        );
        
        return true;
    }

    public function UnInstallEvents()
    {
//        $eventManager = EventManager::getInstance();
//        $eventManager->unRegisterEventHandler(
//            'main',
//            'OnAfterEpilog',
//            $this->MODULE_ID,
//            '\\ClearIm\\Event\\Handlers',
//            'onPageStart'
//        );
        
        return true;
    }

    public function InstallAgents()
    {
//        if (\COption::GetOptionString($this->MODULE_ID, 'enable_agent', 'Y') === 'Y') {
//            $agentTime = (int)\COption::GetOptionString($this->MODULE_ID, 'agent_time', 86400);
//
//            \CAgent::AddAgent(
//                '\\ClearIm\\Agent\\CleanerAgent::run();',
//                $this->MODULE_ID,
//                'N',
//                $agentTime,
//                '',
//                'Y',
//                date('Y-m-d H:i:s', time() + 3600)
//            );
//        }
        
        return true;
    }

    public function UnInstallAgents()
    {
//        \CAgent::RemoveModuleAgents($this->MODULE_ID);
//        return true;
    }

    public function InstallOptions()
    {
        $defaultOptions = include __DIR__ . '/../default_option.php';
        $defaultOptions = $defaultOptions[$this->MODULE_ID . '_default_option'] ?? [];
        
        foreach ($defaultOptions as $key => $value) {
            Option::set($this->MODULE_ID, $key, $value);
        }
        
        return true;
    }

    public function UnInstallOptions()
    {
        Option::delete($this->MODULE_ID);
        return true;
    }
}