<?php

return [
    'links' => [
        'preinvoice.create' => ['roles' => ['admin', 'sales_user']],
        'customers.index' => ['roles' => ['admin', 'sales_user']],
        'preinvoice.draft.index' => ['roles' => ['admin', 'finance_user']],
        'account-statements.index' => ['roles' => ['admin', 'finance_user']],
        'invoices.index' => ['roles' => ['admin', 'finance_user']],
        'shipping-methods.index' => ['roles' => ['admin']],
        'users.index' => ['roles' => ['admin']],
        'activity-logs.index' => ['roles' => ['admin']],
    ],
];

