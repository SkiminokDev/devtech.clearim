<?php
// local/modules/devtech.clearim/lib/Cleaner/RelationProcessor.php

namespace ClearIm\Cleaner;

class RelationProcessor extends AbstractProcessor
{
    public function findByChatIds(array $chatIds): array
    {
        if (empty($chatIds)) {
            return [];
        }
        
        $in = implode(',', array_map('intval', $chatIds));
        $sql = "SELECT ID FROM b_im_relation WHERE CHAT_ID IN ({$in})";
        $result = $this->connection->query($sql);
        
        $relationIds = [];
        while ($row = $result->fetch()) {
            $relationIds[] = $row['ID'];
        }
        
        $this->writeIdsToFile($relationIds);
        return $relationIds;
    }
    
    public function process(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $deleted = $this->batchDelete('b_im_relation', 'ID', $ids);
        $this->log("Удалено связей (relation): {$deleted}");
        
        if (!$this->dryRun) {
            $this->writeIdsToFile([]);
        }
        
        return $deleted;
    }
    
    protected function getTableClass(): ?string
    {
        return '\\Bitrix\\Im\\RelationTable';
    }
    
    protected function getFilePrefix(): string
    {
        return 'spam_relations';
    }
    
    protected function getLinkField(): string
    {
        return 'CHAT_ID';
    }
}