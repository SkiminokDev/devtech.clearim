<?php
// local/modules/devtech.clearim/admin/clearim_cleaner.php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use ClearIm\Cleaner\OpenLinesCleaner;
use ClearIm\Cleaner\ChatProcessor;
use ClearIm\Cleaner\MessageProcessor;
use ClearIm\Cleaner\MessageParamProcessor;
use ClearIm\Cleaner\RelationProcessor;
use ClearIm\Cleaner\SessionProcessor;
use ClearIm\Cleaner\LinkFileProcessor;

Loc::loadMessages(__FILE__);

$moduleId = 'devtech.clearim';

// Определяем AJAX запрос с самого начала
$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
    (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'Y')
);

// Обработка AJAX запросов - это должно быть ПЕРВЫМ и ЕДИНСТВЕННЫМ обработчиком
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json; charset=UTF-8');
	header('X-Content-Type-Options: nosniff');

	// Логирование для отладки (можно закомментировать в продакшене)
	error_log("AJAX request received: " . print_r($_POST, true));

	// Проверка sessid - проверяем sessid из POST данных
	$sessid = $_POST['sessid'] ?? '';
	if (empty($sessid) || !check_bitrix_sessid($sessid)) {
		echo json_encode(['success' => false, 'message' => 'Ошибка сессии (sessid)']);
		die();
	}

	// Проверка установки модулей
	if (!Loader::includeModule($moduleId)) {
		echo json_encode(['success' => false, 'message' => 'Модуль devtech.clearim не установлен']);
		die();
	}

	if (!Loader::includeModule('imopenlines')) {
		echo json_encode(['success' => false, 'message' => 'Модуль imopenlines не установлен']);
		die();
	}

	// Подключаем класс для обработки действий
	$cleanerActionsFile = __DIR__ . '/../classes/CleanerActions.php';
	if (!file_exists($cleanerActionsFile)) {
		echo json_encode(['success' => false, 'message' => 'Файл CleanerActions.php не найден: ' . $cleanerActionsFile]);
		die();
	}

	require_once($cleanerActionsFile);
	$cleanerActions = new \DevTech\ClearIm\Admin\CleanerActions($moduleId);

	$action = $_POST['action_type'] ?? null;
	$daysToKeep = (int)($_POST['days_to_keep'] ?? 0) ?: (int)\COption::GetOptionString($moduleId, 'days_to_keep', 30);
	$batchLimit = (int)($_POST['batch_limit'] ?? 0) ?: (int)\COption::GetOptionString($moduleId, 'batch_limit', 50);
	$dryRun = ($_POST['dry_run'] ?? 'N') === 'Y';

	if ($action) {
		$result = $cleanerActions->processAction($action, $dryRun, $daysToKeep, $batchLimit);
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
	} else {
		echo json_encode(['success' => false, 'message' => 'Не указано действие']);
	}
	die();
}

// Проверки установки модулей для обычного режима
if (!Loader::includeModule($moduleId)) {
	ShowError(Loc::getMessage('DEVTC_CLEARIM_MODULE_NOT_INSTALLED'));
	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
	die();
}

if (!Loader::includeModule('imopenlines')) {
	ShowError(Loc::getMessage('DEVTC_CLEARIM_IMOPENLINES_NOT_INSTALLED'));
	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
	die();
}

$APPLICATION->SetTitle(Loc::getMessage('DEVTC_CLEARIM_PAGE_TITLE'));
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/admin.css');

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

// Подключаем класс для обработки действий (для обычных POST запросов)
$cleanerActionsFile = __DIR__ . '/../classes/CleanerActions.php';
if (!file_exists($cleanerActionsFile)) {
	ShowError('Файл CleanerActions.php не найден: ' . $cleanerActionsFile);
	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
	die();
}

require_once($cleanerActionsFile);
$cleanerActions = new \DevTech\ClearIm\Admin\CleanerActions($moduleId);

// Обработка обычных POST запросов (не AJAX)
$actionResult = null;
if ($request->isPost() && check_bitrix_sessid()) {
	$action = $request->getPost('action_type');
	$daysToKeep = (int)$request->getPost('days_to_keep') ?: (int)\COption::GetOptionString($moduleId, 'days_to_keep', 30);
	$batchLimit = (int)$request->getPost('batch_limit') ?: (int)\COption::GetOptionString($moduleId, 'batch_limit', 50);
	$dryRun = $request->getPost('dry_run') === 'Y';

	if ($action) {
		$actionResult = $cleanerActions->processAction($action, $dryRun, $daysToKeep, $batchLimit);
	}
}

// Получаем данные для отображения
$statistics = [];
$recentChats = [];

try {
	$chatProcessor = new ChatProcessor(false);
	$statistics = $chatProcessor->getSpamStatistics();
	$recentChats = $chatProcessor->getRecentSpamChats(30); // 30 записей для таблицы

	// Валидация полученных данных
	if (!isset($statistics['by_days']) || !is_array($statistics['by_days'])) {
		$statistics['by_days'] = [];
	}
	if (!isset($statistics['by_lines']) || !is_array($statistics['by_lines'])) {
		$statistics['by_lines'] = [];
	}
	if (!isset($statistics['total'])) {
		$statistics['total'] = 0;
	}
	if (!isset($statistics['total_messages'])) {
		$statistics['total_messages'] = 0;
	}
	if (!isset($statistics['total_files'])) {
		$statistics['total_files'] = 0;
	}
	if (!isset($recentChats) || !is_array($recentChats)) {
		$recentChats = [];
	}

} catch (Exception $e) {
	CAdminMessage::ShowMessage([
		'MESSAGE' => 'Ошибка получения статистики',
		'DETAILS' => $e->getMessage(),
		'TYPE' => 'ERROR'
	]);
	$statistics = [
		'total' => 0,
		'by_days' => [],
		'by_lines' => [],
		'oldest_date' => null,
		'newest_date' => null,
		'total_messages' => 0,
		'total_files' => 0,
	];
	$recentChats = [];
}

// Показываем результат действия (только для не-AJAX запросов)
if ($actionResult) {
	if ($actionResult['success']) {
		CAdminMessage::ShowMessage([
			'MESSAGE' => $actionResult['message'],
			'DETAILS' => '<pre>' . print_r($actionResult['details'], true) . '</pre>',
			'TYPE' => 'OK'
		]);
	} else {
		CAdminMessage::ShowMessage([
			'MESSAGE' => 'Ошибка',
			'DETAILS' => $actionResult['message'],
			'TYPE' => 'ERROR'
		]);
	}
}

// Подключаем шаблон
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

// Получаем настройки для передачи в шаблон
$daysToKeepDefault = (int)\COption::GetOptionString($moduleId, 'days_to_keep', 30);
$batchLimitDefault = (int)\COption::GetOptionString($moduleId, 'batch_limit', 50);
$dryRunDefault = \COption::GetOptionString($moduleId, 'dry_run_default', 'N') === 'Y';

// Подключаем CSS и JS
$cssFile = '/local/modules/devtech.clearim/admin_templates/clearim/style.css';
$jsFile = '/local/modules/devtech.clearim/admin_templates/clearim/script.js';

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $cssFile)) {
	$APPLICATION->SetAdditionalCSS($cssFile);
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $jsFile)) {
	$APPLICATION->AddHeadScript($jsFile);
}

// Подключаем HTML шаблон
$templateFile = __DIR__ . '/../admin_templates/clearim/template.php';
if (file_exists($templateFile)) {
	include($templateFile);
} else {
	ShowError('Шаблон не найден: ' . $templateFile);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');

