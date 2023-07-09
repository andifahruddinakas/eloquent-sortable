<?php

namespace Akas\EloquentSortable\Test;

use Illuminate\Database\Eloquent\Model;
use Akas\EloquentSortable\Sortable;
use Akas\EloquentSortable\SortableTrait;

class Dummy extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'dummies';
    protected $guarded = [];
    public $timestamps = false;
}
