<?php

namespace Akas\EloquentSortable;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

interface Sortable
{
    /**
     * Modify the order column value.
     */
    public function setHighestOrderNumber(): void;

    /**
     * Get the name of the order column.
     *
     * @return string
     */
    public function getOrderColumnName(): string;

    /**
     * Determine if the order column should be set when saving a new model instance.
     *
     * @return bool
     */
    public function shouldSortWhenCreating(): bool;

    /**
     * Get the highest order number among the records.
     *
     * @return int
     */
    public function getHighestOrderNumber(): int;

    /**
     * Get the lowest order number among the records.
     *
     * @return int
     */
    public function getLowestOrderNumber(): int;

    /**
     * Build the query for sorting.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildSortQuery(): Builder;

    /**
     * Provide an ordered scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc');

    /**
     * Set a new order for the records.
     *
     * @param array|\ArrayAccess $ids
     * @param int $startOrder
     * @param string|null $primaryKeyColumn
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public static function setNewOrder($ids, int $startOrder = 1, string $primaryKeyColumn = null): void;

    /**
     * Move the current model before the specified model.
     *
     * @param \Akas\EloquentSortable\Sortable $model
     *
     * @return $this
     */
    public function moveBefore(Sortable $model);

    /**
     * Move the current model after the specified model.
     *
     * @param \Akas\EloquentSortable\Sortable $model
     *
     * @return $this
     */
    public function moveAfter(Sortable $model);

    /**
     * Decrement the order of remaining records after a delete operation.
     *
     * @return void
     */
    public function decrementOrderAfterDelete(): void;

    /**
     * Reorder the remaining records after a delete operation.
     *
     * @return void
     */
    public function reorderRemaining(): void;

    /**
     * Move the current model to a specific position.
     *
     * @param int $position
     *
     * @return $this
     */
    public function moveToPosition(int $position): self;
}
