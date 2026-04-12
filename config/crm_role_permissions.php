<?php

return [
    'default_permissions' => [],
    'roles' => [
        'warehouse_manager' => [
            'vouchers.view',
            'vouchers.create',
            'stocktake.view',
            'preinvoice.warehouse.review',
        ],
        'finance_user' => [
            'invoices.view',
            'invoices.payments.create',
            'account-statements.view',
        ],
        'admin' => ['*'],
    ],
];

