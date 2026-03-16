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

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make(__('Clip Title'), 'title')
                ->rules('required')
                ->sortable(),

            Number::make(__('Begin At (seconds)'), 'begin_at')
                ->default(0)
                ->min(0)
                ->step(1)
                ->help(__('Start time in seconds. 0 means from the beginning.')),

            Number::make(__('Length (seconds)'), 'length')
                ->default(0)
                ->min(0)
                ->step(1)
                ->help(__('Duration in seconds. 0 means full audio.')),

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
}
