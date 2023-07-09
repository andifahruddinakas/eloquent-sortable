<?php

namespace Akas\EloquentSortable;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

trait SortableTrait
{
    public function __construct()
    {
        parent::__construct();
        get_instance()->load->config('eloquent-sortable');
    }

    public static function bootSortableTrait()
    {
        parent::boot();

        static::creating(static function ($model) {
            if ($model instanceof Sortable && $model->shouldSortWhenCreating()) {
                $model->setHighestOrderNumber();
            }
        });

        static::deleted(static function ($model) {
            if ($model instanceof Sortable) {
                $model->decrementOrderAfterDelete();
                $model->reorderRemaining();
            }
        });

        static::forceDeleted(static function ($model) {
            if ($model instanceof Sortable) {
                $model->reorderRemaining();
            }
        });
    }

    public function getOrderColumnName(): string
    {
        return config_item('order_column_name') ?? 'order_column';
    }

    public function shouldSortWhenCreating(): bool
    {
        return config_item('sort_when_creating') ?? true;
    }

    public function setHighestOrderNumber(): void
    {
        $orderColumnName = $this->getOrderColumnName();

        $this->{$orderColumnName} = $this->getHighestOrderNumber() + 1;
    }

    public function getHighestOrderNumber(): int
    {
        return (int) $this->buildSortQuery()->max($this->getOrderColumnName());
    }

    public function getLowestOrderNumber(): int
    {
        return (int) $this->buildSortQuery()->min($this->getOrderColumnName());
    }

    public function buildSortQuery(): Builder
    {
        return static::query();
    }

    public function scopeOrdered(Builder $query, string $direction = 'asc')
    {
        return $query->orderBy($this->getOrderColumnName(), $direction);
    }

    public static function setNewOrder($ids, int $startOrder = 1, ?string $primaryKeyColumn = null): void
    {
        if (! is_array($ids) && ! $ids instanceof ArrayAccess) {
            throw new InvalidArgumentException('You must pass an array or ArrayAccess object to setNewOrder');
        }

        $model = new static();

        $orderColumnName = $model->getOrderColumnName();

        if (null === $primaryKeyColumn) {
            $primaryKeyColumn = $model->getKeyName();
        }

        foreach ($ids as $id) {
            static::withoutGlobalScope(SoftDeletingScope::class)
                ->where($primaryKeyColumn, $id)
                ->update([$orderColumnName => $startOrder++]);
        }
    }

    public static function setNewOrderByCustomColumn(string $primaryKeyColumn, $ids, int $startOrder = 1)
    {
        self::setNewOrder($ids, $startOrder, $primaryKeyColumn);
    }

    public function moveOrderUp(): self
    {
        $orderColumnName = $this->getOrderColumnName();

        $swapWithModel = $this->buildSortQuery()
            ->where($orderColumnName, '<', $this->{$orderColumnName})
            ->ordered('desc')
            ->first();

        if (! $swapWithModel) {
            return $this;
        }

        return $this->swapOrderWithModel($swapWithModel);
    }

    public function moveOrderDown(): self
    {
        $orderColumnName = $this->getOrderColumnName();

        $swapWithModel = $this->buildSortQuery()
            ->where($orderColumnName, '>', $this->{$orderColumnName})
            ->ordered()
            ->first();

        if (! $swapWithModel) {
            return $this;
        }

        return $this->swapOrderWithModel($swapWithModel);
    }

    public function swapOrderWithModel(Sortable $otherModel): self
    {
        $orderColumnName = $this->getOrderColumnName();

        $oldOrderOfOtherModel = $otherModel->{$orderColumnName};

        $otherModel->{$orderColumnName} = $this->{$orderColumnName};
        $otherModel->save();

        $this->{$orderColumnName} = $oldOrderOfOtherModel;
        $this->save();

        return $this;
    }

    public static function swapOrder(Sortable $model, Sortable $otherModel): void
    {
        $model->swapOrderWithModel($otherModel);
    }

    public function moveToEnd(): self
    {
        $maxOrder = $this->getHighestOrderNumber();

        $orderColumnName = $this->getOrderColumnName();

        if ($this->{$orderColumnName} === $maxOrder) {
            return $this;
        }

        $oldOrder = $this->{$orderColumnName};

        $this->{$orderColumnName} = $maxOrder;
        $this->save();

        $this->buildSortQuery()
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->where($orderColumnName, '>', $oldOrder)
            ->decrement($orderColumnName);

        return $this;
    }

    public function moveToStart(): self
    {
        $firstModel = $this->buildSortQuery()
            ->ordered()
            ->first();

        if ($firstModel->getKey() === $this->getKey()) {
            return $this;
        }

        $orderColumnName = $this->getOrderColumnName();

        $this->{$orderColumnName} = $firstModel->{$orderColumnName};
        $this->save();

        $this->buildSortQuery()
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->increment($orderColumnName);

        return $this;
    }

    public function isFirstInOrder(): bool
    {
        $orderColumnName = $this->getOrderColumnName();

        return (int) $this->{$orderColumnName} === $this->getLowestOrderNumber();
    }

    public function isLastInOrder(): bool
    {
        $orderColumnName = $this->getOrderColumnName();

        return (int) $this->{$orderColumnName} === $this->getHighestOrderNumber();
    }

    public function moveBefore(Sortable $model): self
    {
        $orderColumnName = $this->getOrderColumnName();

        if ($model->{$orderColumnName} === $this->{$orderColumnName}) {
            return $this;
        }

        $this->buildSortQuery()
            ->where($orderColumnName, '>=', $model->{$orderColumnName})
            ->where($orderColumnName, '<', $this->{$orderColumnName})
            ->increment($orderColumnName);

        $this->{$orderColumnName} = $model->{$orderColumnName};
        $this->save();

        return $this;
    }

    public function moveAfter(Sortable $model): self
    {
        $orderColumnName = $this->getOrderColumnName();

        if ($model->{$orderColumnName} === $this->{$orderColumnName}) {
            return $this;
        }

        $this->buildSortQuery()
            ->where($orderColumnName, '>', $model->{$orderColumnName})
            ->where($orderColumnName, '<=', $this->{$orderColumnName})
            ->decrement($orderColumnName);

        $this->{$orderColumnName} = $model->{$orderColumnName};
        $this->save();

        return $this;
    }

    public function decrementOrderAfterDelete(): void
    {
        $orderColumnName = $this->getOrderColumnName();

        $this->buildSortQuery()
            ->where($orderColumnName, '>', $this->{$orderColumnName})
            ->decrement($orderColumnName);
    }

    public function reorderRemaining(): void
    {
        $orderColumnName = $this->getOrderColumnName();

        $remainingModels = $this->buildSortQuery()
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->get();

        foreach ($remainingModels as $index => $model) {
            $model->{$orderColumnName} = $index + 1;
            $model->save();
        }
    }

    public function moveToPosition(int $position): self
    {
        $orderColumnName = $this->getOrderColumnName();

        $currentPosition = $this->{$orderColumnName};

        if ($position === $currentPosition) {
            return $this;
        }

        $query = $this->buildSortQuery();

        if ($position < $currentPosition) {
            $query->where($orderColumnName, '>=', $position)
                ->where($orderColumnName, '<', $currentPosition)
                ->increment($orderColumnName);
        } else {
            $query->where($orderColumnName, '>', $currentPosition)
                ->where($orderColumnName, '<=', $position)
                ->decrement($orderColumnName);
        }

        $this->{$orderColumnName} = $position;
        $this->save();

        return $this;
    }
}
