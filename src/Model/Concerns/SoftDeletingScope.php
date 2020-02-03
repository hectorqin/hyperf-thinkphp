<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Z\HyperfThinkphp\Model\Concerns;

use Hyperf\Database\Model\Scope;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;

class SoftDeletingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = ['Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    /**
     * Apply the scope to a given Model query builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     * @param \Hyperf\Database\Model\Model $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $column = $model->getQualifiedDeletedAtColumn();
        $defaultValue = $model->getDeletedAtColumnDefaultValue();
        if (is_null($defaultValue)) {
            $builder->whereNull($column);
        } else {
            $builder->where($column, '=', $defaultValue);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);

            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedAtColumn();
        }

        return $builder->getModel()->getDeletedAtColumn();
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();
            $model = $builder->getModel();

            return $builder->update([$model->getDeletedAtColumn() => $model->getDeletedAtColumnDefaultValue()]);
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addWithTrashed(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder, $withTrashed = true) {
            if (! $withTrashed) {
                return $builder->withoutTrashed();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addWithoutTrashed(Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $column = $model->getQualifiedDeletedAtColumn();
            $defaultValue = $model->getDeletedAtColumnDefaultValue();
            if (is_null($defaultValue)) {
                $builder->whereNull($column);
            } else {
                $builder->where($column, '=', $defaultValue);
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $column = $model->getQualifiedDeletedAtColumn();
            $defaultValue = $model->getDeletedAtColumnDefaultValue();
            if (is_null($defaultValue)) {
                $builder->whereNotNull($column);
            } else {
                $builder->where($column, '<>', $defaultValue);
            }

            return $builder->withoutGlobalScope($this);
        });
    }
}
