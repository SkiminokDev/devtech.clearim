<?php
// local/modules/devtech.clearim/lib/Cleaner/MessageProcessor.php

namespace ClearIm\Cleaner;

class MessageProcessor extends AbstractProcessor
{
    public function findByChatIds(array $chatIds): array
    {
        if (empty($chatIds)) {
            return [];
        }
        
        $in = implode(',', array_map('intval', $chatIds));
        $sql = "SELECT ID FROM b_im_message WHERE CHAT_ID IN ({$in})";
        $result = $this->connection->query($sql);
        
        $messageIds = [];
        while ($row = $result->fetch()) {
            $messageIds[] = $row['ID'];
        }
        
        $this->writeIdsToFile($messageIds);
        $this->log("Найдено сообщений для удаления: " . count($messageIds));
        
        return $messageIds;
    }
    
    public function process(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $deleted = $this->batchDelete('b_im_message', 'ID', $ids);
        $this->log("Удалено сообщений: {$deleted}");
        
        if (!$this->dryRun) {
            $this->writeIdsToFile([]);
        }
        
        return $deleted;
    }
    
    protected function getTableClass(): ?string
    {
        return '\\Bitrix\\Im\\MessageTable';
    }
    
    protected function getFilePrefix(): string
    {
        return 'spam_mess';
    }
    
    protected function getLinkField(): string
    {
        return 'ID';
    }
}