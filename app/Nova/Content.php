<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphToMany;
use Illuminate\Support\Facades\Storage;

class Content extends Resource
{
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
    public static $title = 'id';

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
            Text::make('title')->required(),
            Textarea::make('summary')->hideFromIndex()->alwaysShow(),
            Markdown::make('body')->hideFromIndex(),
            BelongsTo::make('user')->default(\Auth::user()->id)->withoutTrashed(),
            File::make(__('Attachment'), 'attachment')
                ->acceptedTypes('.pdf')
                ->store(function (Request $request, $model) {
                    $s3filePath = $request->attachment->store('/ly/contents/', 's3');
                    $model
                        ->addMediaFromDisk($s3filePath, 's3')
                        ->toMediaCollection();
                    return [
                        'attachment' => $s3filePath
                    ];
                })->onlyOnForms(),
            MorphToMany::make(__('LY Episodes'), 'lyItems', LyItem::class),
            MorphToMany::make(__('LTS Episodes'), 'ltsItems', LtsItem::class),

        ];
    }
    // https://github.com/laravel/nova-issues/issues/2334#issuecomment-709422756
    protected static function fillFields(NovaRequest $request, $model, $fields)
        {
            $fillFields = parent::fillFields($request, $model, $fields);

            // first element should be model object
            $modelObject = $fillFields[0];

            // remove all attributes do not have relevant columns in model table
            unset($modelObject->attachment);

            return $fillFields;
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
