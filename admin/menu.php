<?php
// local/modules/devtech.clearim/admin/menu.php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight('devtech.clearim') >= 'R') {
    $aMenu = [
        'parent_menu' => 'global_menu_services',
        'sort' => 100,
        'text' => Loc::getMessage('DEVTC_CLEARIM_MENU_TEXT'),
        'title' => Loc::getMessage('DEVTC_CLEARIM_MENU_TITLE'),
        'icon' => 'devtech_clearim_menu_icon',
        'page_icon' => 'devtech_clearim_page_icon',
        'items_id' => 'menu_devtech_clearim',
        'items' => [
            [
                'text' => Loc::getMessage('DEVTC_CLEARIM_MENU_CLEANER'),
                'url' => 'clearim_cleaner.php?lang=' . LANGUAGE_ID,
                'more_url' => ['clearim_cleaner.php'],
                'title' => Loc::getMessage('DEVTC_CLEARIM_MENU_CLEANER_TITLE'),
            ]
        ]
    ];
    
    return $aMenu;
}

return false;