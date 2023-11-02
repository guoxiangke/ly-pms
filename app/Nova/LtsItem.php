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

use App;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\VaporFile;

class LtsItem extends Resource
{
    public static $group = 'Items';
    public static $priority = 1;
    public static $perPageOptions = [50,100];
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
        $file = App::isLocal() ? File::class : VaporFile::class;
        return [
            ID::make()->sortable(),
            $file::make('音频勘误', 'mp3')
                ->disk('public')
                ->path('lts/corrections')
                ->storeAs(function (Request $request){
                    // 记录谁上传的，上传的时间
                    $fileNameParts = [
                        $this->alias,
                        Auth::id(),
                        now()->format('Ymd_H:i:s'),
                        $request->mp3->getSize(),
                        $request->mp3->getClientOriginalName(),
                    ];
                    return implode('-', $fileNameParts);
                })
                ->help('紧急情况下修正 音频错误时，上传新的mp3')
                ->acceptedTypes('.mp3')
                ->disableDownload(),
            BelongsTo::make('ltsMeta', 'lts_meta', 'App\Nova\LtsMeta'),
            Text::make('alias')
                ->sortable()
                ->rules('required', 'max:12'),

            Text::make('description')
                ->sortable()
                ->hideFromIndex(),
            Text::make('description')
                ->rules('required', 'max:255')->displayUsing(function($description) {
                    return Str::limit($description, 32);
                })->onlyOnIndex(),
            Text::make('alias')
                ->sortable()
                ->rules('required', 'max:12'),
            Date::make('play_at')->sortable(),
            Audio::make('Mp3', function(){
                return $this->path;
            })->disableDownload(),
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
