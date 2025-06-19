<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\MorphMany;
use Illuminate\Support\Facades\Storage;

class Content extends Resource
{
    public static $displayInNavigation = true;
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Content>
     */
    public static $model = \App\Models\Content::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'title',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('title')->required(),
            Textarea::make('summary')->hideFromIndex()->alwaysShow(),
            Markdown::make('body')->hideFromIndex(),
            BelongsTo::make('user')->default(\Auth::user()->id)->withoutTrashed()->withMeta(['extraAttributes' => ['readonly' => true]])->onlyOnForms(),
            MorphMany::make('Attachments')->readonly(),
            MorphToMany::make(__('LY Episodes'), 'lyItems', LyItem::class)->hideFromDetail(fn () => $this->lyItems->isEmpty()),
            MorphToMany::make(__('LTS Episodes'), 'ltsItems', LtsItem::class)->hideFromDetail(fn () => $this->ltsItems->isEmpty()),
            // MorphMany::make(__('Attachment'), 'attachments', Attachment::class),
        ];
    }
    // https://github.com/spatie/laravel-medialibrary/issues/3729
    // https://github.com/laravel/nova-issues/issues/2334#issuecomment-709422756

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
