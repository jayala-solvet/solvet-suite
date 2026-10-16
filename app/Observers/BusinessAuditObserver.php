<?php

namespace App\Observers;

use App\Modules\Access\Services\AccessService;
use Illuminate\Database\Eloquent\Model;

class BusinessAuditObserver
{
    public function created(Model $record): void
    {
        $this->record($record, 'created');
    }

    public function updated(Model $record): void
    {
        $this->record($record, 'updated');
    }

    private function record(Model $record, string $action): void
    {
        AccessService::audit(auth()->user(), strtolower(class_basename($record)).'.'.$action, strtolower(class_basename($record)), $record->id, ['fields' => array_values(array_diff(array_keys($record->getChanges()), ['created_at', 'updated_at']))]);
    }
}
