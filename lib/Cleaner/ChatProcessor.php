<?php
// local/modules/devtech.clearim/lib/Cleaner/ChatProcessor.php

namespace ClearIm\Cleaner;

use Bitrix\Imopenlines\Model\SessionTable;
use Bitrix\Main\Application;

class ChatProcessor extends AbstractProcessor
{
    /**
     * Поиск спам-чатов по критерию (STATUS=65, старее $dateLimit)
     */
    public function findSpamChats(\DateTime $dateLimit, int $limit = 50): array
    {
        $sessions = SessionTable::getList([
            'select' => ['ID', 'CHAT_ID', 'DATE_CREATE', 'STATUS', 'OPERATOR_ID', 'USER_ID'],
            'filter' => [
                'STATUS' => 65,
                '<DATE_CREATE' => $dateLimit->format('Y-m-d H:i:s')
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => $limit
        ])->fetchAll();
        
        $chatIds = array_column($sessions, 'CHAT_ID');
        $chatIds = array_unique(array_filter($chatIds, 'is_numeric'));
        
        $this->writeIdsToFile($chatIds);
        $this->log("Найдено чатов для удаления: " . count($chatIds));
        
        return $chatIds;
    }
    
    /**
     * Получение полной статистики по спам-чатам
     */
    public function getSpamStatistics(): array
    {
        $stats = [
            'total' => 0,
            'by_days' => [],
            'by_lines' => [],
            'oldest_date' => null,
            'newest_date' => null,
            'total_messages' => 0,
            'total_files' => 0,
        ];
        
        try {
            // Общее количество спам-чатов через D7
            $stats['total'] = SessionTable::getCount(['=STATUS' => 65]);
            
            // Количество спам-чатов по дням (последние 30 дней)
            $sql = "SELECT 
                        DATE(DATE_CREATE) as DATE_VALUE,
                        COUNT(*) as COUNT_VALUE,
                        MIN(DATE_CREATE) as FIRST_VALUE,
                        MAX(DATE_CREATE) as LAST_VALUE
                    FROM b_imopenlines_session 
                    WHERE STATUS = 65 
                        AND DATE_CREATE >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(DATE_CREATE)
                    ORDER BY DATE_VALUE DESC";
            
            $result = $this->connection->query($sql);
            while ($row = $result->fetch()) {
                $dateKey = (string)$row['DATE_VALUE']; // Явно приводим к строке
                $stats['by_days'][$dateKey] = [
                    'count' => (int)$row['COUNT_VALUE'],
                    'first' => $row['FIRST_VALUE'],
                    'last' => $row['LAST_VALUE']
                ];
            }
            
            // Количество спам-чатов по открытым линиям
            $sql = "SELECT 
                        s.CONFIG_ID,
                        c.LINE_NAME,
                        COUNT(*) as COUNT_VALUE
                    FROM b_imopenlines_session s
                    LEFT JOIN b_imopenlines_config c ON s.CONFIG_ID = c.ID
                    WHERE s.STATUS = 65
                    GROUP BY s.CONFIG_ID, c.LINE_NAME
                    ORDER BY COUNT_VALUE DESC
                    LIMIT 20";
            
            $result = $this->connection->query($sql);
            while ($row = $result->fetch()) {
                $stats['by_lines'][] = [
                    'config_id' => (int)$row['CONFIG_ID'],
                    'line_name' => (string)($row['LINE_NAME'] ?: 'Неизвестная линия'),
                    'count' => (int)$row['COUNT_VALUE']
                ];
            }
            
            // Самая старая запись
            $oldest = SessionTable::getRow([
                'select' => ['DATE_CREATE'],
                'filter' => ['=STATUS' => 65],
                'order' => ['DATE_CREATE' => 'ASC']
            ]);
            $stats['oldest_date'] = $oldest ? $oldest['DATE_CREATE'] : null;
            
            // Самая новая запись
            $newest = SessionTable::getRow([
                'select' => ['DATE_CREATE'],
                'filter' => ['=STATUS' => 65],
                'order' => ['DATE_CREATE' => 'DESC']
            ]);
            $stats['newest_date'] = $newest ? $newest['DATE_CREATE'] : null;
            
            // Подсчет сообщений в спам-чатах
            $sql = "SELECT COUNT(*) as CNT
                    FROM b_im_message m
                    INNER JOIN b_imopenlines_session s ON m.CHAT_ID = s.CHAT_ID
                    WHERE s.STATUS = 65";
            
            $result = $this->connection->query($sql);
            if ($row = $result->fetch()) {
                $stats['total_messages'] = (int)$row['CNT'];
            }
            
            // Подсчет файлов в спам-чатах
            $sql = "SELECT COUNT(DISTINCT lf.DISK_FILE_ID) as CNT
                    FROM b_im_link_file lf
                    INNER JOIN b_imopenlines_session s ON lf.CHAT_ID = s.CHAT_ID
                    WHERE s.STATUS = 65 AND lf.DISK_FILE_ID > 0";
            
            $result = $this->connection->query($sql);
            if ($row = $result->fetch()) {
                $stats['total_files'] = (int)$row['CNT'];
            }
            
        } catch (\Exception $e) {
            $this->log("Ошибка при получении статистики: " . $e->getMessage());
            // Логируем ошибку, но возвращаем пустую статистику
        }
        
        return $stats;
    }
    
    /**
     * Получение последних спам-чатов
     */
    public function getRecentSpamChats(int $limit = 15): array
    {
        $chats = [];
        
        try {
            $sessions = SessionTable::getList([
                'select' => ['ID', 'CHAT_ID', 'DATE_CREATE', 'OPERATOR_ID', 'USER_ID', 'CONFIG_ID'],
                'filter' => ['=STATUS' => 65],
                'order' => ['DATE_CREATE' => 'DESC'],
                'limit' => $limit
            ])->fetchAll();
            
            // Получаем названия линий одним запросом
            $configIds = array_unique(array_column($sessions, 'CONFIG_ID'));
            $lineNames = [];
            
            if (!empty($configIds)) {
                // Фильтруем пустые ID
                $configIds = array_filter($configIds, function($id) {
                    return $id > 0;
                });
                
                if (!empty($configIds)) {
                    $inCondition = implode(',', array_map('intval', $configIds));
                    $sql = "SELECT ID, LINE_NAME FROM b_imopenlines_config WHERE ID IN ({$inCondition})";
                    $result = $this->connection->query($sql);
                    while ($row = $result->fetch()) {
                        $lineNames[$row['ID']] = $row['LINE_NAME'];
                    }
                }
            }
            
            foreach ($sessions as $session) {
                $chats[] = [
                    'session_id' => (int)$session['ID'],
                    'chat_id' => (int)$session['CHAT_ID'],
                    'date_create' => $session['DATE_CREATE'],
                    'operator_id' => (int)$session['OPERATOR_ID'],
                    'user_id' => (int)$session['USER_ID'],
                    'config_id' => (int)$session['CONFIG_ID'],
                    'line_name' => isset($lineNames[$session['CONFIG_ID']]) ? (string)$lineNames[$session['CONFIG_ID']] : 'Неизвестная линия'
                ];
            }
            
        } catch (\Exception $e) {
            $this->log("Ошибка при получении последних чатов: " . $e->getMessage());
        }
        
        return $chats;
    }
    
    public function process(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $deleted = $this->batchDelete('b_im_chat', 'ID', $ids);
        $this->log("Удалено чатов: {$deleted}");
        
        if (!$this->dryRun) {
            $this->writeIdsToFile([]);
        }
        
        return $deleted;
    }
    
    protected function getTableClass(): ?string
    {
        return '\\Bitrix\\Im\\ChatTable';
    }
    
    protected function getFilePrefix(): string
    {
        return 'spam_chat';
    }
    
    protected function getLinkField(): string
    {
        return 'ID';
    }
}