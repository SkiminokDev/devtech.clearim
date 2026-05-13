<?php
// local/modules/devtech.clearim/classes/CleanerActions.php

namespace DevTech\ClearIm\Admin;

use Bitrix\Main\Loader;
use ClearIm\Cleaner\OpenLinesCleaner;
use ClearIm\Cleaner\ChatProcessor;
use ClearIm\Cleaner\MessageProcessor;
use ClearIm\Cleaner\MessageParamProcessor;
use ClearIm\Cleaner\RelationProcessor;
use ClearIm\Cleaner\SessionProcessor;
use ClearIm\Cleaner\LinkFileProcessor;

class CleanerActions
{
	private $moduleId;

	public function __construct($moduleId)
	{
		$this->moduleId = $moduleId;
	}

	/**
	 * Обработка действий очистки
	 */
	public function processAction($action, $dryRun, $daysToKeep, $batchLimit)
	{
		$result = ['success' => false, 'message' => '', 'details' => []];

		try {
			$dateLimit = new \DateTime('-' . $daysToKeep . ' days');
			$cleaner = new OpenLinesCleaner($dateLimit, $batchLimit, $dryRun);

			switch ($action) {
				case 'clean_files':
					$result = $this->processCleanFiles($dryRun, $dateLimit, $batchLimit);
					break;

				case 'clean_messages':
					$result = $this->processCleanMessages($dryRun, $dateLimit, $batchLimit);
					break;

				case 'clean_chats':
					$result = $this->processCleanChats($dryRun, $dateLimit, $batchLimit);
					break;

				case 'full_clean':
					$result = $this->processFullClean($cleaner, $dryRun);
					break;
			}

		} catch (\Exception $e) {
			$result['message'] = 'Ошибка: ' . $e->getMessage();
			$result['success'] = false;
		}

		return $result;
	}

	private function processCleanFiles($dryRun, $dateLimit, $batchLimit)
	{
		$result = ['success' => false, 'message' => '', 'details' => []];

		$chatProcessor = new ChatProcessor($dryRun);
		$spamChatIds = $chatProcessor->findSpamChats($dateLimit, $batchLimit);

		if (!empty($spamChatIds)) {
			$linkFileProcessor = new LinkFileProcessor($dryRun);
			$fileLinks = $linkFileProcessor->findByChatIds($spamChatIds);
			$deleted = $linkFileProcessor->process($fileLinks);
			$result['details']['files_deleted'] = $deleted;
		}

		$result['message'] = $dryRun ? 'Тестовый режим: найдено файлов для удаления' : 'Файлы успешно удалены';
		$result['success'] = true;

		return $result;
	}

	private function processCleanMessages($dryRun, $dateLimit, $batchLimit)
	{
		$result = ['success' => false, 'message' => '', 'details' => []];

		$chatProcessor = new ChatProcessor($dryRun);
		$spamChatIds = $chatProcessor->findSpamChats($dateLimit, $batchLimit);

		if (!empty($spamChatIds)) {
			$messageProcessor = new MessageProcessor($dryRun);
			$messageIds = $messageProcessor->findByChatIds($spamChatIds);

			if (!empty($messageIds)) {
				$paramProcessor = new MessageParamProcessor($dryRun);
				$paramIds = $paramProcessor->findByMessageIds($messageIds);
				$result['details']['params_deleted'] = $paramProcessor->process($paramIds);
				$result['details']['messages_deleted'] = $messageProcessor->process($messageIds);
			}
		}

		$result['message'] = $dryRun ? 'Тестовый режим: найдено сообщений для удаления' : 'Сообщения успешно удалены';
		$result['success'] = true;

		return $result;
	}

	private function processCleanChats($dryRun, $dateLimit, $batchLimit)
	{
		$result = ['success' => false, 'message' => '', 'details' => []];

		if (!Loader::includeModule('im')) {
			$result['message'] = 'Модуль im не установлен';
			return $result;
		}

		// Лимит 30 чатов за одну операцию
		$limit = 30;
		$deletedCount = 0;
		$errorMessages = [];

		$dbChats = \Bitrix\Im\Model\ChatTable::getList([
			'order' => ['ID' => 'DESC'],
			'select' => ['ID', 'DISK_FOLDER_ID'],
			'limit' => $limit
		]);

		while ($chat = $dbChats->fetch()) {
			if ($dryRun) {
				$deletedCount++;
				continue;
			}

			try {
				\CIMChat::deleteChat($chat['ID']);
				$deletedCount++;
			} catch (\Exception $e) {
				$errorMessages[] = "Ошибка удаления чата {$chat['ID']}: " . $e->getMessage();
			}
		}

		$result['details']['chats_deleted'] = $deletedCount;
		
		if (!empty($errorMessages)) {
			$result['details']['errors'] = $errorMessages;
		}

		$result['message'] = $dryRun 
			? 'Тестовый режим: найдено чатов для удаления: ' . $deletedCount 
			: 'Чаты успешно удалены (' . $deletedCount . ')';
		$result['success'] = true;

		return $result;
	}

	private function processFullClean($cleaner, $dryRun)
	{
		$result = ['success' => false, 'message' => '', 'details' => []];

		$fullResult = $cleaner->fullClean();
		$result['details'] = $fullResult;
		$result['message'] = $dryRun ? 'Тестовый режим: полная очистка выполнена' : 'Полная очистка успешно выполнена';
		$result['success'] = true;

		return $result;
	}
}
