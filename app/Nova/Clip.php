<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphToMany;
use AlmirHodzic\NovaSortable5\Sortable;

class Clip extends Resource
{
    public static function label()
    {
        return __('Clips');
    }

    public static function singularLabel()
    {
        return __('Clip');
    }

    public static $priority = 7;

    public static $perPageOptions = [10, 25, 50];

    public static $model = \App\Models\Clip::class;

    public static $title = 'title';

    public static $search = [
        'id',
        'title',
    ];

    public static $perPageViaRelationship = 25;

    public static function indexQuery(NovaRequest $request, $query)
    {
        if (empty($request->get('orderBy'))) {
            $query->getQuery()->orders = [];
            $query->orderBy('sort_order', 'asc');
        }
        return $query;
    }

    public function fields(NovaRequest $request)
    {
        $viaRelationship = $request->viaRelationship();

        return [
            Sortable::make('Order', 'sort_order')
                ->showOnIndex($viaRelationship),

            ID::make()->sortable()
                ->showOnIndex(! $viaRelationship),

            Text::make(__('Clip Title'), 'title')
                ->rules('required')
                ->sortable(),

            Text::make(__('Begin At'), 'begin_at')
                ->resolveUsing(fn ($value) => $this->secondsToTime($value))
                ->fillUsing(function (NovaRequest $request, $model, $attribute) {
                    $model->{$attribute} = $this->timeToSeconds($request->input($attribute));
                })
                ->default('00:00')
                ->help(__('Format: MM:SS or HH:MM:SS. 00:00 means from the beginning.')),

            Text::make(__('Length'), 'length')
                ->displayUsing(fn ($value) => $this->secondsToTime((int) $this->begin_at + (int) $value))
                ->default(0)
                ->help(__('Seconds. 0 means full audio.')),

            Textarea::make(__('AI Summary'), 'ars_summary')
                ->hideFromIndex()
                ->nullable(),

            BelongsTo::make(__('Album'), 'album', Album::class)
                ->nullable()
                ->searchable()
                ->hideFromIndex(),

            BelongsTo::make(__('User'), 'user', User::class)
                ->default(function ($request) {
                    return $request->user()->id;
                })
                ->hideFromIndex(),

            MorphToMany::make(__('LY Episodes'), 'lyItems', LyItem::class)
                ->searchable(),
        ];
    }

    public function cards(NovaRequest $request)
    {
        return [];
    }

    public function filters(NovaRequest $request)
    {
        return [];
    }

    public function lenses(NovaRequest $request)
    {
        return [];
    }

    public function actions(NovaRequest $request)
    {
        return [];
    }

    protected function secondsToTime(?int $seconds): string
    {
        $seconds = $seconds ?? 0;
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%02d:%02d', $m, $s);
    }

    protected function timeToSeconds(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        // Already numeric (seconds)
        if (is_numeric($time)) {
            return (int) $time;
        }

        $parts = array_map('intval', explode(':', $time));

        return match (count($parts)) {
            3 => $parts[0] * 3600 + $parts[1] * 60 + $parts[2],
            2 => $parts[0] * 60 + $parts[1],
            default => (int) $time,
        };
    }
}
