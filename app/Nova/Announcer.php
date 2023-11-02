<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
// use Laravel\Nova\Fields\Trix;
// use Laravel\Nova\Fields\Textarea;
use Advoor\NovaEditorJs\NovaEditorJsField;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Http\Requests\NovaRequest;


class Announcer extends Resource
{
    public static $perPageOptions = [200,400];
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Announcer>
     */
    public static $model = \App\Models\Announcer::class;

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
            BelongsTo::make('user'),
            Image::make('avatar')
                // ->disk('s3')
                ->path('ly/announcers')
                ->storeAs(function (Request $request) {
                    return $this->id . '.jpg';
                    // return sha1($request->attachment->getClientOriginalName());
                })
                ->acceptedTypes('.jpg')
                ->disableDownload(),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),
            Date::make('birthday'),
            
            BelongsToMany::make('LyMetas'),
            NovaEditorJsField::make('description')->hideFromIndex(),

            Date::make('begin_at')->sortable(),
            Date::make('stop_at')->sortable(),
            
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
