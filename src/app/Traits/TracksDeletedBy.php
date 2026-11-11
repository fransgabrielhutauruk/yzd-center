<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

trait TracksDeletedBy
{
    public static function bootTracksDeletedBy(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            if (!in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                return;
            }

            if (Auth::check() && empty($model->deleted_by)) {
                $user = Auth::user();
                $model->deleted_by = $user->username ?? $user->email ?? (string) $user->id;
                $model->saveQuietly();
            }
        });

        static::restoring(function (Model $model): void {
            $model->deleted_by = null;
        });
    }
}
