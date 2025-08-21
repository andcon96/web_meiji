<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0775,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'permission' => 0775,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'access_menu_log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/access_menu_log.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'salesOrder' => [
            'driver' => 'daily',
            'path' => storage_path('logs/salesOrder.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'purchaseRequisition' => [
            'driver' => 'daily',
            'path' => storage_path('logs/purchaseRequisition.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'PRApproval' => [
            'driver' => 'daily',
            'path' => storage_path('logs/prApproval.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'itemTransfer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/itemTransfer.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'itemTransferConfirm' => [
            'driver' => 'daily',
            'path' => storage_path('logs/itemTransferConfirm.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'convertToPR' => [
            'driver' => 'daily',
            'path' => storage_path('logs/convertToPR.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'stockRequest' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stockRequest.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'packageRequest' => [
            'driver' => 'daily',
            'path' => storage_path('logs/packageRequest.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'stockRequestApproval' => [
            'driver' => 'daily',
            'path' => storage_path('logs/stockRequestApproval.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'packagingRequestApproval' => [
            'driver' => 'daily',
            'path' => storage_path('logs/packagingRequestApproval.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'buildPOFromPR' => [
            'driver' => 'daily',
            'path' => storage_path('logs/buildPOFromPR.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'schedulePOMonthly' => [
            'driver' => 'daily',
            'path' => storage_path('logs/scheduledPOMonthly.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'scheduleSOMonthly' => [
            'driver' => 'daily',
            'path' => storage_path('logs/scheduledSOMonthly.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'editPOMonthly' => [
            'driver' => 'daily',
            'path' => storage_path('logs/editPOMonthly.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],

        'customlog' => [
            'driver' => 'daily',
            'path' => storage_path('logs/apiQxtend.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'POToSOAuto' => [
            'driver' => 'daily',
            'path' => storage_path('logs/POToSOAuto.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'ShopifySO' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ShopifySO.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'SummaryPendingInvoice' => [
            'driver' => 'daily',
            'path' => storage_path('logs/SummaryPendingInvoice.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'ERBLog' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ERBLog.log'),
            'level' => 'info',
            'days' => '0',
            'permission' => 0775,
        ],
        'laborFeedbackLog' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laborFeedback.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'getSoldToLog' => [
            'driver' => 'daily',
            'path' => storage_path('logs/getSoldToLog.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'shipmentSchedule' => [
            'driver' => 'daily',
            'path' => storage_path('logs/shipmentSchedule.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'packingReplenishment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/packingReplenishment.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ],
        'confirmShipment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/confirmShipment.log'),
            'level' => 'info',
            'days' => '30',
            'permission' => 0775,
        ]
    ]
];
