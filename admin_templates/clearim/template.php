<?php
// local/modules/devtech.clearim/admin_templates/clearim/template.php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Передаем данные из основного файла в шаблон
/** @var array $statistics Статистика */
/** @var array $recentChats Последние чаты */
/** @var int $daysToKeepDefault Значение по умолчанию для дней хранения */
/** @var int $batchLimitDefault Значение по умолчанию для лимита пакета */
/** @var bool $dryRunDefault Значение по умолчанию для dry-run */
/** @var string $moduleId ID модуля */
?>

<div class="devtech-clearim-container">
	<!-- Статистика -->
	<div class="devtech-stats-block">
		<div class="devtech-stats-title">
			<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_TITLE') ?>
		</div>

		<div class="devtech-stats-grid">
			<div class="devtech-stat-card">
				<div class="devtech-stat-number" id="stat_total_chats"><?= number_format($statistics['total'], 0, '.', ' ') ?></div>
				<div class="devtech-stat-label"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_TOTAL_CHATS') ?></div>
				<?php if ($statistics['oldest_date']): ?>
					<div class="devtech-stat-date">
						<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_OLDEST') ?>: <?= date('d.m.Y', strtotime($statistics['oldest_date'])) ?>
					</div>
				<?php endif; ?>
				<?php if ($statistics['newest_date']): ?>
					<div class="devtech-stat-date">
						<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_NEWEST') ?>: <?= date('d.m.Y', strtotime($statistics['newest_date'])) ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="devtech-stat-card">
				<div class="devtech-stat-number" id="stat_total_messages"><?= number_format($statistics['total_messages'], 0, '.', ' ') ?></div>
				<div class="devtech-stat-label"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_MESSAGES') ?></div>
				<div class="devtech-stat-date">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_AVG_MESSAGES') ?>:
					<?= $statistics['total'] > 0 ? round($statistics['total_messages'] / $statistics['total'], 1) : 0 ?>
				</div>
			</div>

			<div class="devtech-stat-card">
				<div class="devtech-stat-number" id="stat_total_files"><?= number_format($statistics['total_files'], 0, '.', ' ') ?></div>
				<div class="devtech-stat-label"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_FILES') ?></div>
				<div class="devtech-stat-date">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_AVG_FILES') ?>:
					<?= $statistics['total'] > 0 ? round($statistics['total_files'] / $statistics['total'], 1) : 0 ?>
				</div>
			</div>

			<div class="devtech-stat-card">
				<div class="devtech-stat-number"><?= count($statistics['by_days']) ?></div>
				<div class="devtech-stat-label"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_DAYS_WITH_SPAM') ?></div>
				<div class="devtech-stat-date">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_LAST_30_DAYS') ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Настройки -->
	<div class="devtech-settings-bar">
		<table style="width: 100%;">
			<tr>
				<td style="width: 200px;">
					<strong><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_DAYS_TO_KEEP') ?>:</strong>
				</td>
				<td>
					<input type="number"
					       id="global_days_to_keep"
					       value="<?= $daysToKeepDefault ?>"
					       style="width: 80px;">
					<span class="devtech-stat-date"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_DAYS_HINT') ?></span>
				</td>
				<td style="width: 200px;">
					<strong><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_BATCH_LIMIT') ?>:</strong>
				</td>
				<td>
					<input type="number"
					       id="global_batch_limit"
					       value="<?= $batchLimitDefault ?>"
					       style="width: 80px;">
					<span class="devtech-stat-date"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_BATCH_HINT') ?></span>
				</td>
			</tr>
			<tr>
				<td colspan="4" style="padding-top: 10px;">
					<input type="checkbox"
					       id="global_dry_run"
						<?= $dryRunDefault ? 'checked' : '' ?>>
					<label for="global_dry_run"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_DRY_RUN_HINT') ?></label>
				</td>
			</tr>
		</table>
	</div>

	<!-- Кнопки действий -->
	<div class="devtech-stats-block">
		<div class="devtech-stats-title">
			<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_ACTIONS_TITLE') ?>
		</div>

		<div class="devtech-action-buttons">
			<!-- Удаление файлов -->
			<div class="devtech-action-card">
				<div class="devtech-action-icon">🗑️</div>
				<div class="devtech-action-title"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CLEAN_FILES') ?></div>
				<div class="devtech-action-desc">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CLEAN_FILES_DESC') ?><br>
					<strong id="files_to_delete"><?= number_format($statistics['total_files'], 0, '.', ' ') ?></strong>
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_FILES_TO_DELETE') ?>
				</div>
				<button type="button"
				        class="devtech-action-btn devtech-action-btn-files"
				        data-action="clean_files"
				        onclick="DevTechClearIm.ajaxClean(this, 'clean_files')">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_DELETE_FILES') ?>
				</button>
			</div>

			<!-- Удаление сообщений -->
			<div class="devtech-action-card">
				<div class="devtech-action-icon">💬</div>
				<div class="devtech-action-title"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CLEAN_MESSAGES') ?></div>
				<div class="devtech-action-desc">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CLEAN_MESSAGES_DESC') ?><br>
					<strong id="messages_to_delete"><?= number_format($statistics['total_messages'], 0, '.', ' ') ?></strong>
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_MESSAGES_TO_DELETE') ?>
				</div>
				<button type="button"
				        class="devtech-action-btn devtech-action-btn-messages"
				        data-action="clean_messages"
				        onclick="DevTechClearIm.ajaxClean(this, 'clean_messages')">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_DELETE_MESSAGES') ?>
				</button>
			</div>

			<!-- Удаление чатов -->
			<div class="devtech-action-card">
				<div class="devtech-action-icon">💬🗑️</div>
				<div class="devtech-action-title"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CLEAN_CHATS') ?></div>
				<div class="devtech-action-desc">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CLEAN_CHATS_DESC') ?><br>
					<strong id="chats_to_delete"><?= number_format($statistics['total'], 0, '.', ' ') ?></strong>
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CHATS_TO_DELETE') ?>
				</div>
				<button type="button"
				        class="devtech-action-btn devtech-action-btn-chats"
				        data-action="clean_chats"
				        onclick="DevTechClearIm.ajaxClean(this, 'clean_chats')">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_DELETE_CHATS') ?>
				</button>
			</div>

			<!-- Полная очистка -->
			<div class="devtech-action-card">
				<div class="devtech-action-icon">⚠️</div>
				<div class="devtech-action-title"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_FULL_CLEAN') ?></div>
				<div class="devtech-action-desc">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_FULL_CLEAN_DESC') ?><br>
					<strong id="full_to_delete"><?= number_format($statistics['total'], 0, '.', ' ') ?></strong>
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_CHATS_TO_DELETE') ?>
				</div>
				<button type="button"
				        class="devtech-action-btn devtech-action-btn-full"
				        data-action="full_clean"
				        onclick="DevTechClearIm.ajaxClean(this, 'full_clean')">
					<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_FULL_DELETE') ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Статистика по дням -->
	<?php if (!empty($statistics['by_days'])): ?>
		<div class="devtech-stats-block">
			<div class="devtech-stats-title">
				<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_BY_DAYS') ?>
			</div>
			<table class="devtech-table">
				<thead>
				<tr>
					<th><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_DATE') ?></th>
					<th><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_COUNT') ?></th>
					<th><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_PERCENT') ?></th>
					<th><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_TIMESPAN') ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				$maxCount = !empty($statistics['by_days']) ? max(array_column($statistics['by_days'], 'count')) : 0;
				foreach ($statistics['by_days'] as $date => $data):
					$percent = $maxCount > 0 ? round($data['count'] / $maxCount * 100) : 0;
					?>
					<tr>
						<td><?= date('d.m.Y', strtotime($date)) ?></td>
						<td>
							<strong><?= $data['count'] ?></strong>
							<?php if ($data['count'] > 10): ?>
								<span class="devtech-badge devtech-badge-danger"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_HIGH') ?></span>
							<?php elseif ($data['count'] > 5): ?>
								<span class="devtech-badge devtech-badge-warning"><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_STATS_MEDIUM') ?></span>
							<?php endif; ?>
						</td>
						<td>
							<div class="devtech-progress-bar">
								<div class="devtech-progress-fill" style="width: <?= $percent ?>%;">
									<?= $percent ?>%
								</div>
							</div>
						</td>
						<td class="devtech-stat-date">
							<?= date('H:i', strtotime($data['first'])) ?> - <?= date('H:i', strtotime($data['last'])) ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<!-- Предупреждение или успех -->
	<?php if ($statistics['total'] > 0): ?>
		<div class="devtech-warning">
			<strong><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_WARNING_TITLE') ?></strong><br>
			<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_WARNING_TEXT', ['#COUNT#' => $statistics['total']]) ?>
		</div>
	<?php else: ?>
		<div class="devtech-warning success">
			<strong><?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_SUCCESS_TITLE') ?></strong><br>
			<?= \Bitrix\Main\Localization\Loc::getMessage('DEVTC_CLEARIM_SUCCESS_TEXT') ?>
		</div>
	<?php endif; ?>
</div>

<script>
  // Передаем PHP переменные в JavaScript
  window.DevTechClearImConfig = {
    bitrixSessid: '<?= bitrix_sessid() ?>',
    moduleId: '<?= $moduleId ?>'
  };
</script>

<?php
// Подключаем JS после определения конфигурации
$jsFile = '/local/modules/devtech.clearim/admin_templates/clearim/script.js';
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $jsFile)) {
	$APPLICATION->AddHeadScript($jsFile);
}
?>
