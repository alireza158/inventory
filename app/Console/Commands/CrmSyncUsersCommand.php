<?php

namespace App\Console\Commands;

use App\Services\CrmUserService;
use Illuminate\Console\Command;

class CrmSyncUsersCommand extends Command
{
    protected $signature = 'crm:sync-users';

    protected $description = 'Sync users and roles from CRM';

    public function handle(CrmUserService $crmUserService): int
    {
        $result = $crmUserService->syncUsers();

        if (!empty($result['error'])) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'CRM users synced successfully. synced=%d deactivated=%d',
            $result['synced_count'],
            $result['deactivated_count'] ?? 0
        ));

        return self::SUCCESS;
    }
}

