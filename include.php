<?php
// local/modules/devtech.clearim/include.php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arJsConfig = [
    'devtech_clearim' => [
        'js' => '/local/modules/devtech.clearim/assets/script.js',
        'css' => '/local/modules/devtech.clearim/assets/style.css',
        'rel' => ['jquery'],
        'lang' => '/local/modules/devtech.clearim/lang/' . LANGUAGE_ID . '/script.php',
    ],
];

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}

Loader::registerAutoLoadClasses('devtech.clearim', [
    'ClearIm\\Cleaner\\AbstractProcessor' => 'lib/Cleaner/AbstractProcessor.php',
    'ClearIm\\Cleaner\\ChatProcessor' => 'lib/Cleaner/ChatProcessor.php',
    'ClearIm\\Cleaner\\MessageProcessor' => 'lib/Cleaner/MessageProcessor.php',
    'ClearIm\\Cleaner\\MessageParamProcessor' => 'lib/Cleaner/MessageParamProcessor.php',
    'ClearIm\\Cleaner\\RelationProcessor' => 'lib/Cleaner/RelationProcessor.php',
    'ClearIm\\Cleaner\\SessionProcessor' => 'lib/Cleaner/SessionProcessor.php',
    'ClearIm\\Cleaner\\LinkFileProcessor' => 'lib/Cleaner/LinkFileProcessor.php',
    'ClearIm\\Cleaner\\OpenLinesCleaner' => 'lib/Cleaner/OpenLinesCleaner.php',
    'ClearIm\\Agent\\CleanerAgent' => 'lib/Agent/CleanerAgent.php',
    'ClearIm\\Command\\CleanCommand' => 'lib/Command/CleanCommand.php',
    'ClearIm\\Event\\Handlers' => 'lib/Event/Handlers.php',
]);