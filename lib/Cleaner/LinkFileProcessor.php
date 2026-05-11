<?php
// local/modules/devtech.clearim/lib/Cleaner/LinkFileProcessor.php

namespace ClearIm\Cleaner;

use Bitrix\Main\Application;
use CFile;

class LinkFileProcessor extends AbstractProcessor
{
    public function findByChatIds(array $chatIds): array
    {
        if (empty($chatIds)) {
            return [];
        }
        
        $in = implode(',', array_map('intval', $chatIds));
        $sql = "SELECT ID, DISK_FILE_ID FROM b_im_link_file WHERE CHAT_ID IN ({$in})";
        $result = $this->connection->query($sql);
        
        $fileLinks = [];
        while ($row = $result->fetch()) {
            $fileLinks[$row['ID']] = $row['DISK_FILE_ID'];
        }
        
        $this->writeIdsToFile(array_keys($fileLinks), 'links');
        
        $diskFileIds = array_values(array_filter($fileLinks));
        $this->writeIdsToFile($diskFileIds, 'disk_files');
        
        $this->log("Найдено файловых связей: " . count($fileLinks));
        
        return $fileLinks;
    }
    
    public function process(array $fileLinks): int
    {
        if (empty($fileLinks)) {
            return 0;
        }
        
        $deletedLinks = 0;
        $deletedFiles = 0;
        
        foreach ($fileLinks as $linkId => $diskFileId) {
            if ($this->dryRun) {
                $deletedLinks++;
                if ($diskFileId) $deletedFiles++;
                continue;
            }
            
            if ($diskFileId && $diskFileId > 0) {
                if (CFile::Delete($diskFileId)) {
                    $deletedFiles++;
                    $this->log("Удален файл DISK_FILE_ID: {$diskFileId}");
                } else {
                    $this->log("ОШИБКА: не удалось удалить файл {$diskFileId}");
                }
            }
            
            $sql = "DELETE FROM b_im_link_file WHERE ID = {$linkId}";
            $this->connection->query($sql);
            if ($this->connection->getAffectedRowsCount() > 0) {
                $deletedLinks++;
            }
        }
        
        $this->log("Удалено связей файлов: {$deletedLinks}, удалено файлов: {$deletedFiles}");
        
        if (!$this->dryRun) {
            $this->writeIdsToFile([], 'links');
            $this->writeIdsToFile([], 'disk_files');
        }
        
        return $deletedLinks;
    }
    
    protected function getTableClass(): ?string
    {
        return null;
    }
    
    protected function getFilePrefix(): string
    {
        return 'spam_link_files';
    }
    
    protected function getLinkField(): string
    {
        return 'CHAT_ID';
    }
}