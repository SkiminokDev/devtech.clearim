<?php
// local/modules/devtech.clearim/options.php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

$module_id = 'devtech.clearim';

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$arTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('DEVTC_CLEARIM_TAB_SETTINGS'),
        'TITLE' => Loc::getMessage('DEVTC_CLEARIM_TAB_TITLE_SETTINGS'),
        'OPTIONS' => [
            Loc::getMessage('DEVTC_CLEARIM_GENERAL_SETTINGS'),
            [
                'days_to_keep',
                Loc::getMessage('DEVTC_CLEARIM_DAYS_TO_KEEP'),
                '',
                ['text', 5],
            ],
            [
                'batch_limit',
                Loc::getMessage('DEVTC_CLEARIM_BATCH_LIMIT'),
                '',
                ['text', 5],
            ],
            [
                'dry_run_default',
                Loc::getMessage('DEVTC_CLEARIM_DRY_RUN_DEFAULT'),
                '',
                ['checkbox'],
            ],
            Loc::getMessage('DEVTC_CLEARIM_AGENT_SETTINGS'),
            [
                'enable_agent',
                Loc::getMessage('DEVTC_CLEARIM_ENABLE_AGENT'),
                '',
                ['checkbox'],
            ],
            [
                'agent_time',
                Loc::getMessage('DEVTC_CLEARIM_AGENT_TIME'),
                '',
                ['text', 10],
            ],
            Loc::getMessage('DEVTC_CLEARIM_LOG_SETTINGS'),
            [
                'log_enabled',
                Loc::getMessage('DEVTC_CLEARIM_LOG_ENABLED'),
                '',
                ['checkbox'],
            ],
            [
                'log_path',
                Loc::getMessage('DEVTC_CLEARIM_LOG_PATH'),
                '',
                ['text', 50],
            ],
        ],
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('DEVTC_CLEARIM_TAB_CLEAN'),
        'TITLE' => Loc::getMessage('DEVTC_CLEARIM_TAB_TITLE_CLEAN'),
        'OPTIONS' => [],
    ],
];

if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($arTabs as $tab) {
        if (isset($tab['OPTIONS']) && is_array($tab['OPTIONS'])) {
            foreach ($tab['OPTIONS'] as $option) {
                if (is_array($option) && isset($option[0])) {
                    $optionName = $option[0];
                    if ($request->getPost($optionName) !== null) {
                        \COption::SetOptionString($module_id, $optionName, $request->getPost($optionName));
                    }
                }
            }
        }
    }
}

$tabControl = new \CAdminTabControl('tabControl', $arTabs);

?>
<div class="adm-detail-block">
    <div class="adm-detail-content-wrap">
        <form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
            <?= bitrix_sessid_post() ?>
            <?
            $tabControl->Begin();
            foreach ($arTabs as $tab):
                $tabControl->BeginNextTab();
                if ($tab['DIV'] === 'edit2'): ?>
                    <tr>
                        <td colspan="2" align="center">
                            <div style="padding: 20px;">
                                <h3><?= Loc::getMessage('DEVTC_CLEARIM_MANUAL_CLEAN') ?></h3>
                                <button type="button" 
                                        class="adm-btn adm-btn-save" 
                                        onclick="if(confirm('<?= Loc::getMessage('DEVTC_CLEARIM_CONFIRM_CLEAN') ?>')) 
                                                 window.location.href='?mid=<?= $module_id ?>&lang=<?= LANGUAGE_ID ?>&action=clean&<?= bitrix_sessid_get() ?>'">
                                    <?= Loc::getMessage('DEVTC_CLEARIM_RUN_CLEANER') ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?
                elseif (isset($tab['OPTIONS']) && is_array($tab['OPTIONS'])):
                    __AdmSettingsDrawList($module_id, $tab['OPTIONS']);
                endif;
            endforeach;
            $tabControl->Buttons(); ?>
            <input type="submit" name="apply" value="<?= Loc::getMessage('DEVTC_CLEARIM_SAVE') ?>" class="adm-btn-save">
            <? $tabControl->End(); ?>
        </form>
    </div>
</div>

<? if ($request->get('action') === 'clean' && check_bitrix_sessid()): 
    try {
        $daysToKeep = \COption::GetOptionString($module_id, 'days_to_keep', 30);
        $batchLimit = \COption::GetOptionString($module_id, 'batch_limit', 50);
        $dryRun = \COption::GetOptionString($module_id, 'dry_run_default', 'N') === 'Y';
        
        $dateLimit = new \DateTime('-' . $daysToKeep . ' days');
        $cleaner = new \ClearIm\Cleaner\OpenLinesCleaner($dateLimit, (int)$batchLimit, $dryRun);
        $result = $cleaner->fullClean();
        
        \CAdminMessage::ShowMessage([
            'MESSAGE' => Loc::getMessage('DEVTC_CLEARIM_CLEAN_SUCCESS'),
            'DETAILS' => print_r($result, true),
            'TYPE' => 'OK'
        ]);
    } catch (\Exception $e) {
        \CAdminMessage::ShowMessage([
            'MESSAGE' => Loc::getMessage('DEVTC_CLEARIM_CLEAN_ERROR'),
            'DETAILS' => $e->getMessage(),
            'TYPE' => 'ERROR'
        ]);
    }
endif; ?>