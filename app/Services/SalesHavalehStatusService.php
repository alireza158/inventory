<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;

class SalesHavalehStatusService
{
    public const PENDING_WAREHOUSE_APPROVAL = 'pending_warehouse_approval';
    public const COLLECTING = 'collecting';
    public const CHECKING_DISCREPANCY = 'checking_discrepancy';
    public const FINAL_CHECK = 'final_check';
    public const PACKING = 'packing';
    public const SHIPPED = 'shipped';
    public const NOT_SHIPPED = 'not_shipped';

    public function all(): array
    {
        return [
            self::PENDING_WAREHOUSE_APPROVAL,
            self::COLLECTING,
            self::CHECKING_DISCREPANCY,
            self::FINAL_CHECK,
            self::PACKING,
            self::SHIPPED,
            self::NOT_SHIPPED,
        ];
    }

    public function labels(): array
    {
        return [
            self::PENDING_WAREHOUSE_APPROVAL => 'در انتظار تایید انبار',
            self::COLLECTING => 'در حال جمع‌آوری',
            self::CHECKING_DISCREPANCY => 'در حال مغایرت و بررسی',
            self::FINAL_CHECK => 'در حال چک نهایی',
            self::PACKING => 'در حال بسته‌بندی',
            self::SHIPPED => 'ارسال شده',
            self::NOT_SHIPPED => 'ارسال نشده',
        ];
    }

    public function editableStatusesForRegularUsers(): array
    {
        return [
            self::PENDING_WAREHOUSE_APPROVAL,
            self::COLLECTING,
            self::CHECKING_DISCREPANCY,
            self::FINAL_CHECK,
        ];
    }

    public function isEditable(Invoice $invoice, ?User $user): bool
    {
        if ($invoice->status === self::SHIPPED) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return in_array((string) $invoice->status, $this->editableStatusesForRegularUsers(), true);
    }

    public function assertValidTransition(Invoice $invoice, string $newStatus, ?User $user): void
    {
        if (!in_array($newStatus, $this->all(), true)) {
            abort(422, 'وضعیت انتخاب‌شده معتبر نیست.');
        }

        $current = (string) $invoice->status;

        if ($current === $newStatus) {
            return;
        }

        if ($this->isAdmin($user)) {
            return;
        }

        $allowedNext = [
            self::PENDING_WAREHOUSE_APPROVAL => [self::COLLECTING],
            self::COLLECTING => [self::CHECKING_DISCREPANCY],
            self::CHECKING_DISCREPANCY => [self::FINAL_CHECK],
            self::FINAL_CHECK => [self::PACKING],
            self::PACKING => [self::SHIPPED, self::NOT_SHIPPED],
            self::SHIPPED => [],
            self::NOT_SHIPPED => [],
        ];

        if (!in_array($newStatus, $allowedNext[$current] ?? [], true)) {
            abort(422, 'تغییر وضعیت به این مرحله مجاز نیست.');
        }
    }

    public function allowedTransitions(Invoice $invoice, ?User $user): array
    {
        if ($this->isAdmin($user)) {
            return $this->all();
        }

        $allowedNext = [
            self::PENDING_WAREHOUSE_APPROVAL => [self::PENDING_WAREHOUSE_APPROVAL, self::COLLECTING],
            self::COLLECTING => [self::COLLECTING, self::CHECKING_DISCREPANCY],
            self::CHECKING_DISCREPANCY => [self::CHECKING_DISCREPANCY, self::FINAL_CHECK],
            self::FINAL_CHECK => [self::FINAL_CHECK, self::PACKING],
            self::PACKING => [self::PACKING, self::SHIPPED, self::NOT_SHIPPED],
            self::SHIPPED => [self::SHIPPED],
            self::NOT_SHIPPED => [self::NOT_SHIPPED],
        ];

        return $allowedNext[(string) $invoice->status] ?? [(string) $invoice->status];
    }

    private function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $hasRole = method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole(['admin', 'super-admin'])
            : false;

        $hasPermission = method_exists($user, 'can')
            ? $user->can('sales-havaleh.status.skip')
            : false;

        return $hasRole || $hasPermission;
    }
}
