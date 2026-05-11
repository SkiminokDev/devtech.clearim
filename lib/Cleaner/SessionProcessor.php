<?php
// local/modules/devtech.clearim/lib/Cleaner/SessionProcessor.php

namespace ClearIm\Cleaner;

class SessionProcessor extends AbstractProcessor
{
    public function findByChatIds(array $chatIds): array
    {
        if (empty($chatIds)) {
            return [];
        }
        
        $in = implode(',', array_map('intval', $chatIds));
        $sql = "SELECT ID FROM b_imopenlines_session WHERE CHAT_ID IN ({$in})";
        $result = $this->connection->query($sql);
        
        $sessionIds = [];
        while ($row = $result->fetch()) {
            $sessionIds[] = $row['ID'];
        }
        
        $this->writeIdsToFile($sessionIds);
        return $sessionIds;
    }
    
    public function process(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $deleted = $this->batchDelete('b_imopenlines_session', 'ID', $ids);
        $this->log("Удалено сессий OpenLines: {$deleted}");
        
        if (!$this->dryRun) {
            $this->writeIdsToFile([]);
        }
        
        return $deleted;
    }
    
    protected function getTableClass(): ?string
    {
        return '\\Bitrix\\Imopenlines\\Model\\SessionTable';
    }
    
    protected function getFilePrefix(): string
    {
        return 'spam_sessions';
    }
    
    protected function getLinkField(): string
    {
        return 'CHAT_ID';
    }
}