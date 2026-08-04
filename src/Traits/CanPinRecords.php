<?php

namespace Arm092\LivewireDatatables\Traits;

use Arm092\LivewireDatatables\Action;

/**
 * Use this trait to enable the functionality to pin specific records to the
 * top of the table.
 *
 * Ensure to;
 *
 * 1) have at least one Checkbox Column in your table
 * 2) have session storage activated (it is by default)
 * 3) to enable the mass bulk action dropdown as described in:
 *
 * @link https://github.com/arm092/livewire-datatables#mass-bulk-action
 */
trait CanPinRecords
{
    public array $pinnedRecords = [];

    public string $sessionKeyPostfix = '_pinned_records';

    public function buildActions(): array
    {
        return array_merge(parent::buildActions() ?? [], [
            Action::value('pin')
                ->label(__('Pin selected Records'))
                ->callback(function ($mode, $items) {
                    $this->pinnedRecords = array_merge($this->pinnedRecords, $items);
                    $this->selected = $this->pinnedRecords;

                    session()->put($this->sessionKey(), $this->pinnedRecords);
                }),

            Action::value('unpin')
                ->label(__('Unpin selected Records'))
                ->callback(function ($mode, $items) {
                    $this->pinnedRecords = array_diff($this->pinnedRecords, $items);
                    $this->selected = $this->pinnedRecords;

                    session()->put($this->sessionKey(), $this->pinnedRecords);
                }),
        ]);
    }

    public function resetTable(): void
    {
        parent::resetTable();
        $this->pinnedRecords = [];
    }

    protected function initialisePinnedRecords()
    {
        if (session()->has($this->sessionKey())) {
            $this->pinnedRecords = session()->get($this->sessionKey());
        }

        $this->selected = $this->pinnedRecords;
    }

    /**
     * This function should be called after every filter method to ensure pinned records appear
     * in every possible filter combination.
     * Ensures to have at least _one other_ where applied to the current query build
     * to apply this orWhere() on top of that.
     */
    protected function applyPinnedRecords(): self
    {
        if (isset($this->pinnedRecords) && $this->pinnedRecords && $this->query->getQuery()->wheres) {
            $pinnedRecords = $this->recordIdentifiers($this->pinnedRecords);
            $model = $this->builder()->getModel();
            $allowedPinnedRecords = $this->builder()
                ->whereKey($pinnedRecords)
                ->pluck($model->getQualifiedKeyName())
                ->all();

            if ($allowedPinnedRecords) {
                $this->query->orWhereIn($model->getQualifiedKeyName(), $allowedPinnedRecords);
            }
        }

        return $this;
    }

    private function sessionKey(): string
    {
        return $this->sessionStorageKey() . $this->sessionKeyPostfix;
    }
}
