<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Audio;

use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\VaporFile;

class LtsItem extends Resource
{
    /**
     * The resource label
     * 
     */

    public static function label()
    {
        return __('Lts Episodes');
    }

     /**
      * Singular resource label
      */

    public static function singularLabel()
    {
       return __('Lts Episode');
    }

    // public static $group = 'Items 列表';
    public static $priority = 1;
    public static $perPageOptions = [5,10,25,30,50,100];
    public static $perPageViaRelationship = 50;
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\LtsItem>
     */
    public static $model = \App\Models\LtsItem::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'alias',
        'description'
    ];

    /**
     * @param NovaRequest $request
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        $query = $query->with('lts_meta');

        return $query;
    }
    
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
            Text::make(__('Episode Title'), fn()=>$this->episodeTitle)->exceptOnForms(),
            Text::make(__('Episode Alias'),'alias')
                ->sortable()
                ->rules('required', 'max:12'),
            // BelongsTo::make(__('Episode Title'), 'lts_meta', 'App\Nova\LtsMeta')->searchable(),
            Text::make(__('Episode Description'),'description')
                ->hideFromIndex(),
            Text::make(__('Episode Description'),'description')
                ->rules('required', 'max:255')->displayUsing(function($description) {
                    return Str::limit($description, 32);
                })->onlyOnIndex(),
            Date::make(__('Start Publishing Date'),'play_at')->sortable(),
            Audio::make('Mp3', fn() => $this->novaMp3Path)->disableDownload(),
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
