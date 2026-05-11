// local/modules/devtech.clearim/admin_templates/clearim/script.js

var DevTechClearIm = (function() {
  'use strict';

  // Конфигурация
  var config = window.DevTechClearImConfig || {};

  // Сообщения для подтверждения
  var confirmMessages = {
    'clean_files': 'Вы действительно хотите удалить ВСЕ файлы из спам-чатов?\n\nЭто действие нельзя отменить!',
    'clean_messages': 'Вы действительно хотите удалить ВСЕ сообщения из спам-чатов?\n\nЭто действие нельзя отменить!',
    'clean_chats': 'Вы действительно хотите удалить ВСЕ спам-чаты?\n\nБудут удалены: чаты, связи, сессии. Сообщения и файлы останутся.',
    'full_clean': 'ВНИМАНИЕ! Вы собираетесь выполнить ПОЛНУЮ очистку!\n\nБудут удалены: чаты, сообщения, файлы, связи, сессии.\n\nЭто действие нельзя отменить!'
  };

  // Вспомогательные функции
  function getGlobalSettings() {
    var daysInput = document.getElementById('global_days_to_keep');
    var batchInput = document.getElementById('global_batch_limit');
    var dryRunInput = document.getElementById('global_dry_run');

    return {
      daysToKeep: daysInput ? daysInput.value : 30,
      batchLimit: batchInput ? batchInput.value : 50,
      dryRun: dryRunInput ? dryRunInput.checked : false
    };
  }

  function showNotification(message, type) {
    // Удаляем старые уведомления
    var oldNotifications = document.querySelectorAll('.devtech-notification');
    oldNotifications.forEach(function(el) { el.remove(); });

    var notification = document.createElement('div');
    notification.className = 'devtech-notification devtech-notification-' + type;
    notification.innerHTML = message;
    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            border-radius: 5px;
            z-index: 10002;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            max-width: 400px;
            animation: slideIn 0.3s ease;
        `;

    document.body.appendChild(notification);

    setTimeout(function() {
      if (notification && notification.remove) {
        notification.remove();
      }
    }, 5000);
  }

  function showLoader(btnElement) {
    // Сохраняем оригинальное состояние
    btnElement._originalText = btnElement.innerHTML;
    btnElement._originalDisabled = btnElement.disabled;

    // Меняем кнопку
    btnElement.innerHTML = '⏳ Обработка...';
    btnElement.disabled = true;

    // Создаем глобальный loader
    var loader = document.createElement('div');
    loader.id = 'devtech-global-loader';
    loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

    loader.innerHTML = `
            <div style="background: white; padding: 30px 40px; border-radius: 8px; text-align: center;">
                <div style="width: 40px; height: 40px; margin: 0 auto 15px; border: 4px solid #f3f3f3; border-top: 4px solid #2fc6f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div>Выполняется очистка...</div>
                <div style="font-size: 12px; color: #666; margin-top: 8px;">Пожалуйста, подождите</div>
            </div>
        `;

    document.body.appendChild(loader);

    return loader;
  }

  function hideLoader(loader, btnElement) {
    if (loader && loader.remove) {
      loader.remove();
    }

    if (btnElement && btnElement._originalText) {
      btnElement.innerHTML = btnElement._originalText;
      btnElement.disabled = btnElement._originalDisabled || false;
    }
  }

  // Основная функция AJAX очистки
  function ajaxClean(btnElement, actionType) {
    // Получаем настройки
    var settings = getGlobalSettings();
    var dryRun = settings.dryRun;

    // Подтверждение действия
    var confirmMsg = confirmMessages[actionType];
    if (dryRun) {
      confirmMsg += '\n\n⚠️ Режим ТЕСТА (dry-run): реального удаления НЕ будет.';
    }

    if (!confirm(confirmMsg)) {
      return false;
    }

    // Показываем индикатор загрузки
    var loader = showLoader(btnElement);

    // Формируем данные для отправки
    var formData = new FormData();
    formData.append('action_type', actionType);
    formData.append('days_to_keep', settings.daysToKeep);
    formData.append('batch_limit', settings.batchLimit);
    formData.append('dry_run', dryRun ? 'Y' : 'N');
    formData.append('sessid', config.bitrixSessid);
    formData.append('ajax', 'Y');

    // Логирование в консоль для отладки
    console.log('📤 Отправка AJAX-запроса:', {
      action: actionType,
      settings: settings,
      url: window.location.href
    });

    // Отправляем запрос
    fetch(window.location.href, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json, text/plain, */*'
      },
      body: formData
    })
      .then(function(response) {
        console.log('📥 Получен ответ, статус:', response.status);
        console.log('📥 Content-Type:', response.headers.get('content-type'));

        if (!response.ok) {
          throw new Error('HTTP ошибка: ' + response.status + ' ' + response.statusText);
        }

        // Проверяем что пришел JSON
        var contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          throw new Error('Сервер вернул не JSON. Content-Type: ' + contentType);
        }

        return response.json();
      })
      .then(function(data) {
        console.log('✅ Ответ сервера (JSON):', data);

        if (data.success) {
          var detailsText = '';
          if (data.details) {
            detailsText = '\n\n📊 Результат:\n';
            for (var key in data.details) {
              if (data.details.hasOwnProperty(key) && typeof data.details[key] !== 'object') {
                detailsText += '• ' + key + ': ' + data.details[key] + '\n';
              }
            }
          }

          showNotification('✅ ' + data.message, 'success');
          alert('✅ ' + data.message + detailsText);

          // Перезагружаем страницу
          setTimeout(function() {
            location.reload();
          }, 1500);
        } else {
          showNotification('❌ Ошибка: ' + data.message, 'error');
          alert('❌ Ошибка: ' + data.message);
          hideLoader(loader, btnElement);
        }
      })
      .catch(function(error) {
        console.error('❌ Ошибка запроса:', error);
        console.error('❌ Stack trace:', error.stack);

        showNotification('❌ Ошибка соединения: ' + error.message, 'error');
        alert('❌ Ошибка при выполнении запроса:\n' + error.message + '\n\nПроверьте консоль для деталей');
        hideLoader(loader, btnElement);
      });

    return false;
  }

  // Инициализация
  function init() {
    console.log('DevTech ClearIm: Модуль инициализирован');
    console.log('DevTech ClearIm: Конфигурация:', config);

    // Добавляем стили анимации если их нет
    if (!document.querySelector('#devtech-animations')) {
      var style = document.createElement('style');
      style.id = 'devtech-animations';
      style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
      document.head.appendChild(style);
    }

    // Синхронизация настроек
    var daysInput = document.getElementById('global_days_to_keep');
    var batchInput = document.getElementById('global_batch_limit');

    if (daysInput) {
      daysInput.addEventListener('change', function() {
        console.log('Изменен days_to_keep:', this.value);
      });
    }

    if (batchInput) {
      batchInput.addEventListener('change', function() {
        console.log('Изменен batch_limit:', this.value);
      });
    }
  }

  // Публичное API
  return {
    init: init,
    ajaxClean: ajaxClean
  };
})();

// Инициализация при загрузке документа
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    DevTechClearIm.init();
  });
} else {
  DevTechClearIm.init();
}