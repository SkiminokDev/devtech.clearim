<?php
// local/modules/devtech.clearim/lib/Cleaner/MessageParamProcessor.php

namespace ClearIm\Cleaner;

class MessageParamProcessor extends AbstractProcessor
{
    public function findByMessageIds(array $messageIds): array
    {
        if (empty($messageIds)) {
            return [];
        }
        
        $in = implode(',', array_map('intval', $messageIds));
        $sql = "SELECT ID FROM b_im_message_param WHERE MESSAGE_ID IN ({$in})";
        $result = $this->connection->query($sql);
        
        $paramIds = [];
        while ($row = $result->fetch()) {
            $paramIds[] = $row['ID'];
        }
        
        $this->writeIdsToFile($paramIds);
        return $paramIds;
    }
    
    public function process(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $deleted = $this->batchDelete('b_im_message_param', 'ID', $ids);
        $this->log("Удалено параметров сообщений: {$deleted}");
        
        if (!$this->dryRun) {
            $this->writeIdsToFile([]);
        }
        
        return $deleted;
    }
    
    protected function getTableClass(): ?string
    {
        return null;
    }
    
    protected function getFilePrefix(): string
    {
        return 'spam_message_params';
    }
    
    protected function getLinkField(): string
    {
        return 'MESSAGE_ID';
    }
}