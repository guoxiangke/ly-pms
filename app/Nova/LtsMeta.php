<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\BelongsTo;

use Spatie\TagsField\Tags;

class LtsMeta extends Resource
{
    // public static function label() { return '良院'; }
    public static $priority = 2;
    public static $group = 'Metadata';
    public static $perPageOptions = [200,400];
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\LyMeta>
     */
    public static $model = \App\Models\LtsMeta::class;

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
        // https://docs.vapor.build/resources/storage.html
        // https://nova.laravel.com/docs/4.0/resources/fields.html#vapor-image-field
        $image = app()->environment() == 'local' ? Image::class : VaporImage::class;
        
        return [
            // ID::make()->sortable(),
            Text::make('index')
                ->sortable(),
            Text::make('avatar', function () {
                return "<img width='100px' src='{$this->cover}' />";
            })->asHtml(),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('code')
                ->sortable()
                ->rules('required', 'max:12'),
            Text::make('count')
                ->sortable(),
            Tags::make('Category', 'Tags')
                ->type('lts')
                ->single(),
            Text::make('author')
                ->sortable(),
            Date::make('stop_at')->sortable(),
            Image::make('avatar')
                ->path('ly/lts')
                ->storeAs(function (Request $request) {
                    return $this->code . '.jpg';
                })
                ->acceptedTypes('.jpg')
                ->disableDownload()
                ->onlyOnForms(),
            Textarea::make('description')->hideFromIndex(),
            Textarea::make('remark')->hideFromIndex(),
            // Text::make('fields')->hideFromIndex()->rules('nullable', 'max:255'),

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
