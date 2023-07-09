<?php

namespace Akas\EloquentSortable;

use ArrayAccess;
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
     */
    public function getOrderColumnName(): string;

    /**
     * Determine if the order column should be set when saving a new model instance.
     */
    public function shouldSortWhenCreating(): bool;

    /**
     * Get the highest order number among the records.
     */
    public function getHighestOrderNumber(): int;

    /**
     * Get the lowest order number among the records.
     */
    public function getLowestOrderNumber(): int;

    /**
     * Build the query for sorting.
     */
    public function buildSortQuery(): Builder;

    /**
     * Provide an ordered scope.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc');

    /**
     * Set a new order for the records.
     *
     * @param array|ArrayAccess $ids
     *
     * @throws InvalidArgumentException
     */
    public static function setNewOrder($ids, int $startOrder = 1, ?string $primaryKeyColumn = null): void;

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
     */
    public function decrementOrderAfterDelete(): void;

    /**
     * Reorder the remaining records after a delete operation.
     */
    public function reorderRemaining(): void;

    /**
     * Move the current model to a specific position.
     *
     * @return $this
     */
    public function moveToPosition(int $position): self;
}
