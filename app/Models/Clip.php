<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Clip extends Model implements Sortable
{
    use HasFactory;
    use SoftDeletes;
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
        'sort_on_has_many' => true,
    ];

    public function buildSortQuery()
    {
        return static::query()->where('album_id', $this->album_id);
    }
    // if(App::isProduction()) use Searchable;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function lyItems(): MorphToMany
    {
        return $this->morphedByMany(LyItem::class, 'clipable');
    }

    public function ltsItems(): MorphToMany
    {
        return $this->morphedByMany(LtsItem::class, 'clipable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }
}
