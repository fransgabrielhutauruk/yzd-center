<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            if (Auth::check() && empty($model->created_by)) {
                $user = Auth::user();
                $model->created_by = $user->username ?? $user->email ?? (string) $user->id;
            }
        });

        static::updating(function (Model $model): void {
            if (Auth::check()) {
                $user = Auth::user();
                $model->updated_by = $user->username ?? $user->email ?? (string) $user->id;
            }
        });

        static::deleting(function (Model $model): void {
            if (Auth::check() && in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $user = Auth::user();
                $model->deleted_by = $user->username ?? $user->email ?? (string) $user->id;
                $model->saveQuietly();
            }
        });
    }
}
