<?php

namespace App\Console\Commands;

use App\Services\InventoryAuditService;
use Illuminate\Console\Command;

class InventoryAuditCommand extends Command
{
    protected $signature = 'inventory:audit {--dry-run : Required safety flag; the audit never mutates data} {--json : Emit JSON output} {--product=} {--variant=} {--preinvoice=} {--invoice=}';
    protected $description = 'Dry-run inventory, reservation, document workflow, ordering, and numbering audit.';

    public function handle(InventoryAuditService $audit): int
    {
        if (! $this->option('dry-run')) {
            $this->error('This audit is read-only and must be run with --dry-run.');
            return self::FAILURE;
        }

        $report = $audit->run([
            'product' => $this->option('product'),
            'variant' => $this->option('variant'),
            'preinvoice' => $this->option('preinvoice'),
            'invoice' => $this->option('invoice'),
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->info('Inventory audit dry-run report');
        $this->line('Generated at: '.$report['generated_at']);
        $this->line('Data changed: no');
        $this->table(['severity','count'], collect($report['summary'])->map(fn($v,$k)=>[$k,$v])->values()->all());

        foreach ($report['sections'] as $name => $section) {
            $this->newLine();
            $this->warn(str_replace('_', ' ', strtoupper($name)));
            $this->line($section['description']);
            if (! empty($section['rows'])) $this->table(array_keys((array) $section['rows'][0]), array_map(fn($r)=>(array)$r, array_slice($section['rows'], 0, 50)));
            foreach ($section['problems'] as $problem) $this->line("[{$problem['severity']}] {$problem['code']}: {$problem['message']} ".json_encode($problem['context'], JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
