<?php
// local/modules/devtech.clearim/lib/Cleaner/AbstractProcessor.php

namespace ClearIm\Cleaner;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\Connection;

abstract class AbstractProcessor
{
    protected Connection $connection;
    protected string $baseDir;
    protected bool $dryRun;
    protected array $processedIds = [];
    protected string $moduleId = 'devtech.clearim';
    
    public function __construct(bool $dryRun = false)
    {
        $this->connection = Application::getConnection();
        $this->dryRun = $dryRun;
        $this->baseDir = Option::get($this->moduleId, 'log_path', '/upload/devtech_clearim/logs/');
        
        $this->ensureDirectories();
    }
    
    private function ensureDirectories(): void
    {
        $docRoot = Application::getDocumentRoot();
        $paths = [
            $this->baseDir,
            Option::get($this->moduleId, 'backup_path', '/upload/devtech_clearim/backup/')
        ];
        
        foreach ($paths as $path) {
            $fullPath = $docRoot . $path;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
    }
    
    abstract public function process(array $ids): int;
    abstract protected function getTableClass(): ?string;
    abstract protected function getFilePrefix(): string;
    abstract protected function getLinkField(): string;
    
    public function writeIdsToFile(array $ids, string $suffix = ''): void
    {
        $docRoot = Application::getDocumentRoot();
        $filename = $this->getFilePrefix() . ($suffix ? '_' . $suffix : '') . '.txt';
        $filePath = $docRoot . $this->baseDir . $filename;
        
        file_put_contents($filePath, implode(PHP_EOL, $ids), LOCK_EX);
        
        if (Option::get($this->moduleId, 'backup_enabled', 'Y') === 'Y') {
            $backupPath = $docRoot . Option::get($this->moduleId, 'backup_path', '/upload/devtech_clearim/backup/');
            $backupFile = $backupPath . $filename . '.' . date('Ymd_His');
            copy($filePath, $backupFile);
        }
    }
    
    public function readIdsFromFile(string $suffix = ''): array
    {
        $filePath = Application::getDocumentRoot() . $this->baseDir . $this->getFilePrefix() . ($suffix ? '_' . $suffix : '') . '.txt';
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        return $content ? array_filter(array_map('trim', explode(PHP_EOL, $content))) : [];
    }
    
    protected function batchDelete(string $tableName, string $fieldName, array $ids, int $batchSize = 200): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $deletedTotal = 0;
        $chunks = array_chunk($ids, $batchSize);
        
        foreach ($chunks as $chunk) {
            if ($this->dryRun) {
                $deletedTotal += count($chunk);
                continue;
            }
            
            $inCondition = implode(',', array_map('intval', $chunk));
            $sql = "DELETE FROM {$tableName} WHERE {$fieldName} IN ({$inCondition})";
            $this->connection->query($sql);
            $deletedTotal += $this->connection->getAffectedRowsCount();
        }
        
        return $deletedTotal;
    }
    
    protected function log(string $message): void
    {
        if (Option::get($this->moduleId, 'log_enabled', 'Y') !== 'Y') {
            return;
        }
        
        $logPath = Application::getDocumentRoot() . $this->baseDir . 'cleaner.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . '] [' . static::class . '] ' . $message . PHP_EOL;
        file_put_contents($logPath, $logMessage, FILE_APPEND | LOCK_EX);
    }
}