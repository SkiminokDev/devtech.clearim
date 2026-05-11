<?php
// local/modules/devtech.clearim/lib/Cleaner/OpenLinesCleaner.php

namespace ClearIm\Cleaner;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

class OpenLinesCleaner
{
    private array $processors = [];
    private bool $dryRun;
    private \DateTime $dateLimit;
    private int $chatLimit;
    private string $moduleId = 'devtech.clearim';
    
    public function __construct(\DateTime $dateLimit, int $chatLimit = 50, bool $dryRun = false)
    {
        $this->dateLimit = $dateLimit;
        $this->chatLimit = $chatLimit;
        $this->dryRun = $dryRun;
        
        if (!Loader::includeModule('im') || !Loader::includeModule('imopenlines')) {
            throw new \Exception('Не удалось загрузить модули im или imopenlines');
        }
        
        $this->initProcessors();
    }
    
    private function initProcessors(): void
    {
        $this->processors = [
            'messageParam' => new MessageParamProcessor($this->dryRun),
            'linkFile'     => new LinkFileProcessor($this->dryRun),
            'message'      => new MessageProcessor($this->dryRun),
            'relation'     => new RelationProcessor($this->dryRun),
            'session'      => new SessionProcessor($this->dryRun),
            'chat'         => new ChatProcessor($this->dryRun),
        ];
    }
    
    public function fullClean(): array
    {
        $stats = [
            'start_time' => date('Y-m-d H:i:s'),
            'dry_run' => $this->dryRun,
        ];
        
        /** @var ChatProcessor $chatProcessor */
        $chatProcessor = $this->processors['chat'];
        $spamChatIds = $chatProcessor->findSpamChats($this->dateLimit, $this->chatLimit);
        
        if (empty($spamChatIds)) {
            $this->log("Нет спам-чатов для очистки");
            return ['status' => 'no_data', 'stats' => $stats];
        }
        
        $stats['chats_found'] = count($spamChatIds);
        
        /** @var MessageProcessor $messageProcessor */
        $messageProcessor = $this->processors['message'];
        $messageIds = $messageProcessor->findByChatIds($spamChatIds);
        
        if (!empty($messageIds)) {
            /** @var MessageParamProcessor $paramProcessor */
            $paramProcessor = $this->processors['messageParam'];
            $paramIds = $paramProcessor->findByMessageIds($messageIds);
            $stats['message_params_deleted'] = $paramProcessor->process($paramIds);
            $stats['messages_deleted'] = $messageProcessor->process($messageIds);
        }
        
        /** @var LinkFileProcessor $linkFileProcessor */
        $linkFileProcessor = $this->processors['linkFile'];
        $fileLinks = $linkFileProcessor->findByChatIds($spamChatIds);
        $stats['files_deleted'] = $linkFileProcessor->process($fileLinks);
        
        /** @var RelationProcessor $relationProcessor */
        $relationProcessor = $this->processors['relation'];
        $relationIds = $relationProcessor->findByChatIds($spamChatIds);
        $stats['relations_deleted'] = $relationProcessor->process($relationIds);
        
        /** @var SessionProcessor $sessionProcessor */
        $sessionProcessor = $this->processors['session'];
        $sessionIds = $sessionProcessor->findByChatIds($spamChatIds);
        $stats['sessions_deleted'] = $sessionProcessor->process($sessionIds);
        
        $stats['chats_deleted'] = $chatProcessor->process($spamChatIds);
        $stats['end_time'] = date('Y-m-d H:i:s');
        
        $this->log("Очистка завершена. Статистика: " . print_r($stats, true));
        
        return $stats;
    }
    
    public function cleanTable(string $processorName, array $ids): int
    {
        if (!isset($this->processors[$processorName])) {
            throw new \InvalidArgumentException("Процессор {$processorName} не найден");
        }
        
        return $this->processors[$processorName]->process($ids);
    }
    
    private function log(string $message): void
    {
        if (Option::get($this->moduleId, 'log_enabled', 'Y') !== 'Y') {
            return;
        }
        
        $logPath = Application::getDocumentRoot() . Option::get($this->moduleId, 'log_path', '/upload/devtech_clearim/logs/');
        $logFile = $logPath . 'cleaner.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}