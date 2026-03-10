<?php

namespace App\Services;

use App\Models\CommissionPeriod;
use App\Models\CommissionResult;
use App\Models\CommissionTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionCalculatorService
{
    public function calculateForPeriod(int $periodId): array
    {
        $period = CommissionPeriod::query()->findOrFail($periodId);

        $targets = CommissionTarget::query()
            ->where('commission_period_id', $period->id)
            ->get();

        $sales = $this->aggregateSales($period);
        $now = now();

        $rows = [];
        foreach ($targets as $target) {
            $key = $this->buildSalesKey($target->user_id, $target->category_id);
            $actual = $sales->get($key, ['sold_amount' => 0, 'sold_qty' => 0]);

            $achievement = $this->calculateAchievementPercent(
                (int) $actual['sold_amount'],
                (int) $target->target_amount
            );

            $commissionAmount = $this->calculateCommissionAmount(
                soldAmount: (int) $actual['sold_amount'],
                achievementPercent: $achievement,
                minPercentToActivate: (float) $target->min_percent_to_activate,
                commissionType: $target->commission_type,
                commissionValue: (float) $target->commission_value,
            );

            $rows[] = [
                'commission_period_id' => $period->id,
                'user_id' => $target->user_id,
                'category_id' => $target->category_id,
                'sold_amount' => (int) $actual['sold_amount'],
                'sold_qty' => (int) $actual['sold_qty'],
                'target_amount' => (int) $target->target_amount,
                'target_qty' => $target->target_qty,
                'achievement_percent' => $achievement,
                'commission_type' => $target->commission_type,
                'commission_value' => (float) $target->commission_value,
                'commission_amount' => $commissionAmount,
                'calculated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($period, $rows) {
            CommissionResult::query()->where('commission_period_id', $period->id)->delete();
            if ($rows !== []) {
                CommissionResult::query()->insert($rows);
            }
        });

        return [
            'period_id' => $period->id,
            'targets_count' => count($rows),
            'total_commission' => array_sum(array_column($rows, 'commission_amount')),
            'calculated_at' => $now,
        ];
    }

    private function aggregateSales(CommissionPeriod $period): Collection
    {
        $from = Carbon::parse($period->start_date)->startOfDay();
        $to = Carbon::parse($period->end_date)->endOfDay();

        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('preinvoice_orders', 'preinvoice_orders.id', '=', 'invoices.preinvoice_order_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->whereNotNull('preinvoice_orders.created_by')
            ->whereNotNull('products.category_id')
            ->whereBetween('invoices.created_at', [$from, $to])
            ->where('invoices.status', '!=', 'canceled')
            ->groupBy('preinvoice_orders.created_by', 'products.category_id')
            ->selectRaw('preinvoice_orders.created_by as user_id, products.category_id, SUM(invoice_items.line_total) as sold_amount, SUM(invoice_items.quantity) as sold_qty')
            ->get();

        return $rows->mapWithKeys(function ($row) {
            return [
                $this->buildSalesKey((int) $row->user_id, (int) $row->category_id) => [
                    'sold_amount' => (int) $row->sold_amount,
                    'sold_qty' => (int) $row->sold_qty,
                ],
            ];
        });
    }

    private function calculateAchievementPercent(int $soldAmount, int $targetAmount): float
    {
        if ($targetAmount <= 0) {
            return 0;
        }

        return round(($soldAmount / $targetAmount) * 100, 2);
    }

    private function calculateCommissionAmount(
        int $soldAmount,
        float $achievementPercent,
        float $minPercentToActivate,
        string $commissionType,
        float $commissionValue,
    ): int {
        if ($achievementPercent < $minPercentToActivate) {
            return 0;
        }

        return match ($commissionType) {
            'percent' => (int) round(($soldAmount * $commissionValue) / 100),
            'fixed' => (int) round($commissionValue),
            // TODO: phase-2 - support step, score-based and product-specific commission rules.
            default => 0,
        };
    }

    private function buildSalesKey(int $userId, int $categoryId): string
    {
        return $userId . ':' . $categoryId;
    }
}
