<?php
// local/modules/devtech.clearim/lib/Agent/CleanerAgent.php

namespace ClearIm\Agent;

use ClearIm\Cleaner\OpenLinesCleaner;
use Bitrix\Main\Config\Option;

class CleanerAgent
{
    public static function run(): string
    {
        $moduleId = 'devtech.clearim';
        
        if (Option::get($moduleId, 'enable_agent', 'Y') !== 'Y') {
            return '';
        }
        
        try {
            $daysToKeep = (int)Option::get($moduleId, 'days_to_keep', 30);
            $batchLimit = (int)Option::get($moduleId, 'batch_limit', 50);
            $dryRun = Option::get($moduleId, 'dry_run_default', 'N') === 'Y';
            
            $dateLimit = new \DateTime('-' . $daysToKeep . ' days');
            $cleaner = new OpenLinesCleaner($dateLimit, $batchLimit, $dryRun);
            $result = $cleaner->fullClean();
            
            if (Option::get($moduleId, 'log_enabled', 'Y') === 'Y') {
                $logPath = Option::get($moduleId, 'log_path', '/upload/devtech_clearim/logs/');
                $logFile = $_SERVER['DOCUMENT_ROOT'] . $logPath . 'agent.log';
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Agent executed: ' . print_r($result, true) . PHP_EOL, FILE_APPEND);
            }
            
        } catch (\Exception $e) {
            if (Option::get($moduleId, 'log_enabled', 'Y') === 'Y') {
                $logPath = Option::get($moduleId, 'log_path', '/upload/devtech_clearim/logs/');
                $logFile = $_SERVER['DOCUMENT_ROOT'] . $logPath . 'agent_error.log';
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }
        
        return __METHOD__ . '();';
    }
}