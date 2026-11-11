<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait CascadesSoftDeletes
{
    public static function bootCascadesSoftDeletes(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                foreach ($model->cascadeSoftDeletes ?? [] as $relationName) {
                    if (method_exists($model, $relationName)) {
                        $children = $model->$relationName()->withTrashed()->get();
                        foreach ($children as $child) {
                            $child->forceDelete();
                        }
                    }
                }
            }
        });

        static::deleted(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $deletedAt = $model->{$model->getDeletedAtColumn()};
            $deletedBy = $model->deleted_by;

            foreach ($model->cascadeSoftDeletes ?? [] as $relationName) {
                if (!method_exists($model, $relationName)) {
                    continue;
                }

                $children = $model->$relationName()->withoutTrashed()->get();
                foreach ($children as $child) {
                    if (method_exists($child, 'applyTrashSlug')) {
                        $child->applyTrashSlug();
                    }

                    $child->{$child->getDeletedAtColumn()} = $deletedAt;
                    $child->deleted_by = $deletedBy;
                    $child->saveQuietly();
                }
            }
        });

        static::restoring(function (Model $model): void {
            $parentDeletedAt = $model->{$model->getDeletedAtColumn()};
            if (!$parentDeletedAt) {
                return;
            }

            foreach ($model->cascadeSoftDeletes ?? [] as $relationName) {
                if (!method_exists($model, $relationName)) {
                    continue;
                }

                $relation = $model->$relationName();
                $related = $relation->getRelated();
                $deletedAtCol = $related->getDeletedAtColumn();

                $children = $relation->onlyTrashed()
                    ->where($deletedAtCol, $parentDeletedAt)
                    ->get();

                foreach ($children as $child) {
                    if (method_exists($child, 'restoreSlugOrFail')) {
                        $child->restoreSlugOrFail();
                    }

                    $child->deleted_by = null;
                    $child->saveQuietly();
                    $child->restore();
                }
            }
        });
    }

    public function restoreCascade(?string $deletedAt = null): void
    {
        $deletedAt = $deletedAt ?? $this->{$this->getDeletedAtColumn()} ?? $this->getOriginal($this->getDeletedAtColumn());
        if (!$deletedAt) {
            return;
        }

        foreach ($this->cascadeSoftDeletes ?? [] as $relationName) {
            if (!method_exists($this, $relationName)) {
                continue;
            }

            $relation = $this->$relationName();
            $related = $relation->getRelated();
            $deletedAtCol = $related->getDeletedAtColumn();

            $children = $relation->onlyTrashed()
                ->where($deletedAtCol, $deletedAt)
                ->get();

            foreach ($children as $child) {
                if (method_exists($child, 'restoreSlugOrFail')) {
                    $child->restoreSlugOrFail();
                }

                $child->deleted_by = null;
                $child->saveQuietly();
                $child->restore();
            }
        }
    }
}
