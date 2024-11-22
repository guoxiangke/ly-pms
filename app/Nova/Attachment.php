<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\File;

class Attachment extends Resource
{
    public static $displayInNavigation = false;
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Attachment>
     */
    public static $model = \App\Models\Attachment::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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
            Text::make('name')->onlyOnIndex(),
            File::make(__('Attachment'), 'path')
                ->acceptedTypes(['.pdf','.doc','.docx'])
                ->store(function (Request $request, $model) {
                    return [
                        'name' => $request->path->getClientOriginalName(),
                        'path' => $request->path->store('ly/attachment', 's3'),
                        'mime_type' => $request->path->getMimeType(),
                    ];
                }),
            Text::make('description'),
            BelongsTo::make('user')->default(\Auth::user()->id)->withoutTrashed()->withMeta(['extraAttributes' => ['readonly' => true]]),

            MorphToMany::make(__('LY Episodes'), 'lyItems', LyItem::class)->hideFromDetail(fn () => $this->lyItems->isEmpty()),
            MorphToMany::make(__('LTS Episodes'), 'ltsItems', LtsItem::class)->hideFromDetail(fn () => $this->ltsItems->isEmpty()),
            MorphToMany::make(__('Contents'), 'content', Content::class)->hideFromDetail(fn () => $this->content->isEmpty()),

            // MorphMany::make(__('Contents'), 'contents', Content::class),
            
            // MorphToMany::make('Content'),
            // MorphMany::make('Content'),//$attachment->contents()->attach($content->id);
        ];
    }

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
