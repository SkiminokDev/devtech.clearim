<?php
/**
 * Файл конфигурации проекта devtech.clearim
 * 
 * Содержит основные параметры и настройки для разработки модуля.
 * Используется для генерации кода, документации и других задач.
 */

return [
    // Основная информация о проекте
    'project' => [
        'name' => 'devtech.clearim',
        'description' => 'Модуль очистки спам-чатов в OpenLines для Битрикс',
        'version' => '1.0.0',
        'author' => 'DevTech',
        'author_uri' => 'https://devtech.ru',
        'bitrix_compatible' => '20.0.0',
    ],

    // Структура модуля
    'structure' => [
        'module_id' => 'devtech.clearim',
        'namespace_prefix' => 'ClearIm',
        'directories' => [
            'lib' => 'lib/',
            'admin' => 'admin/',
            'classes' => 'classes/',
            'install' => 'install/',
            'lang' => 'lang/',
            'admin_templates' => 'admin_templates/',
            'assets' => 'assets/',
        ],
    ],

    // Автозагрузка классов
    'autoload' => [
        'prefix' => 'ClearIm\\',
        'base_dir' => 'lib/',
        'classes' => [
            'ClearIm\\Cleaner\\AbstractProcessor' => 'lib/Cleaner/AbstractProcessor.php',
            'ClearIm\\Cleaner\\ChatProcessor' => 'lib/Cleaner/ChatProcessor.php',
            'ClearIm\\Cleaner\\MessageProcessor' => 'lib/Cleaner/MessageProcessor.php',
            'ClearIm\\Cleaner\\MessageParamProcessor' => 'lib/Cleaner/MessageParamProcessor.php',
            'ClearIm\\Cleaner\\RelationProcessor' => 'lib/Cleaner/RelationProcessor.php',
            'ClearIm\\Cleaner\\SessionProcessor' => 'lib/Cleaner/SessionProcessor.php',
            'ClearIm\\Cleaner\\LinkFileProcessor' => 'lib/Cleaner/LinkFileProcessor.php',
            'ClearIm\\Cleaner\\OpenLinesCleaner' => 'lib/Cleaner/OpenLinesCleaner.php',
            'ClearIm\\Agent\\CleanerAgent' => 'lib/Agent/CleanerAgent.php',
            'ClearIm\\Command\\CleanCommand' => 'lib/Command/CleanCommand.php',
            'ClearIm\\Event\\Handlers' => 'lib/Event/Handlers.php',
        ],
    ],

    // Зависимости модуля
    'dependencies' => [
        'required_modules' => [
            'main' => '20.0.0',
            'im' => null,
            'imopenlines' => null,
        ],
    ],

    // Настройки по умолчанию
    'default_options' => [
        'days_to_keep' => '30',
        'batch_limit' => '50',
        'enable_agent' => 'Y',
        'agent_time' => '86400',
        'dry_run_default' => 'N',
        'log_enabled' => 'Y',
        'log_path' => '/upload/devtech_clearim/logs/',
        'backup_enabled' => 'Y',
        'backup_path' => '/upload/devtech_clearim/backup/',
    ],

    // Таблицы базы данных
    'database' => [
        'tables' => [
            'b_im_message' => [
                'processor' => 'MessageProcessor',
                'key_field' => 'ID',
                'link_field' => 'CHAT_ID',
            ],
            'b_im_message_param' => [
                'processor' => 'MessageParamProcessor',
                'key_field' => 'ID',
                'link_field' => 'MESSAGE_ID',
            ],
            'b_im_link_file' => [
                'processor' => 'LinkFileProcessor',
                'key_field' => 'ID',
                'link_field' => 'CHAT_ID',
            ],
            'b_im_relation' => [
                'processor' => 'RelationProcessor',
                'key_field' => 'ID',
                'link_field' => 'CHAT_ID',
            ],
            'b_imopenlines_session' => [
                'processor' => 'SessionProcessor',
                'key_field' => 'ID',
                'link_field' => 'CHAT_ID',
            ],
            'b_im_chat' => [
                'processor' => 'ChatProcessor',
                'key_field' => 'ID',
                'link_field' => null,
            ],
        ],
    ],

    // Классы процессоров
    'processors' => [
        'AbstractProcessor' => [
            'file' => 'lib/Cleaner/AbstractProcessor.php',
            'type' => 'abstract',
            'methods' => ['process', 'getTableClass', 'getFilePrefix', 'getLinkField'],
        ],
        'ChatProcessor' => [
            'file' => 'lib/Cleaner/ChatProcessor.php',
            'type' => 'concrete',
            'extends' => 'AbstractProcessor',
        ],
        'MessageProcessor' => [
            'file' => 'lib/Cleaner/MessageProcessor.php',
            'type' => 'concrete',
            'extends' => 'AbstractProcessor',
        ],
        'MessageParamProcessor' => [
            'file' => 'lib/Cleaner/MessageParamProcessor.php',
            'type' => 'concrete',
            'extends' => 'AbstractProcessor',
        ],
        'RelationProcessor' => [
            'file' => 'lib/Cleaner/RelationProcessor.php',
            'type' => 'concrete',
            'extends' => 'AbstractProcessor',
        ],
        'SessionProcessor' => [
            'file' => 'lib/Cleaner/SessionProcessor.php',
            'type' => 'concrete',
            'extends' => 'AbstractProcessor',
        ],
        'LinkFileProcessor' => [
            'file' => 'lib/Cleaner/LinkFileProcessor.php',
            'type' => 'concrete',
            'extends' => 'AbstractProcessor',
        ],
    ],

    // Административные страницы
    'admin_pages' => [
        'clearim_cleaner' => [
            'file' => 'admin/clearim_cleaner.php',
            'title' => 'Очистка спам-чатов',
            'parent_menu' => 'global_menu_services',
        ],
    ],

    // События
    'events' => [
        // Закомментированные события
        // 'main.OnAfterEpilog' => '\\ClearIm\\Event\\Handlers::onPageStart',
    ],

    // Агенты
    'agents' => [
        'run' => [
            'method' => '\\ClearIm\\Agent\\CleanerAgent::run()',
            'interval' => 86400,
            'enabled_option' => 'enable_agent',
        ],
    ],

    // Языковые файлы
    'language' => [
        'default_lang' => 'ru',
        'supported_langs' => ['ru', 'en'],
        'files' => [
            'install/index.php',
            'options.php',
            'admin/clearim_cleaner.php',
            'admin/menu.php',
        ],
    ],

    // Пути к файлам шаблонов
    'templates' => [
        'admin' => [
            'clearim' => [
                'template' => 'admin_templates/clearim/template.php',
                'css' => 'admin_templates/clearim/style.css',
                'js' => 'admin_templates/clearim/script.js',
            ],
        ],
    ],

    // Права доступа
    'access_rights' => [
        'reference' => [
            'W' => 'Полный доступ',
            'R' => 'Только чтение',
        ],
        'default_group' => 'admin',
    ],

    // Кодировки и стандарты
    'standards' => [
        'php_version' => '7.4+',
        'coding_standard' => 'PSR-12',
        'encoding' => 'UTF-8',
        'line_ending' => 'LF',
    ],
];
