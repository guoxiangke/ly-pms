<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Outl1ne\NovaInlineTextField\InlineText;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Audio;


use App;
use Laravel\Nova\Fields\File;
use Illuminate\Support\Facades\Auth;

class LyItem extends Resource
{   
    /**
     * The resource label
     * 
     */

    public static function label()
    {
        return __('Ly Episodes');
    }

     /**
      * Singular resource label
      */

    public static function singularLabel()
    {
       return __('Ly Episode');
    }

    // public static $group = 'Items 列表';
    public static $priority = 2;
    public static $perPageOptions = [50,100];
    public static $perPageViaRelationship = 50;
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\LyItem>
     */
    public static $model = \App\Models\LyItem::class;

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
        'description',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $model = $this;

        $fileFeild = [
            File::make('音频勘误', 'mp3')
                ->disk('public')
                ->path('ly/corrections')
                ->storeAs(fn() => $this->alias .'v'.date('His'). '.mp3')
                ->help('紧急情况下修正 音频错误时，上传新的mp3 ')
                ->acceptedTypes('.mp3')
                ->disableDownload()
        ];
        return array_merge( [
            Text::make(__('Episode Alias'), 'alias')
                ->sortable()
                ->rules('required', 'max:12')
                ->hideFromIndex(),
            // ID::make()->sortable(),
        ] , $fileFeild,
        [
            // ly_meta
            // obersive Mp3: 一更新，后台便去处理
            Boolean::make('', function(){
                return !$this->is_future;
            })->onlyOnIndex(),
            Text::make(__('Episode Title'), fn()=> $this->ly_meta->name . "-" . $this->play_at->format("ymd"))->onlyOnIndex(),

            BelongsTo::make(__('Episode Title'), 'ly_meta', 'App\Nova\LyMeta')->hideFromIndex(),//->filterable(),
            Text::make(__('Episode Duration'), 'playtime_string')->sortable(),

            InlineText::make(__('Episode Description'), 'description')->displayUsing(function($description) {
                    return Str::limit($description, 32);
                })->onlyOnIndex(),
            Text::make(__('Episode Description'), 'description')
                ->rules('required', 'max:255')->displayUsing(function($description) {
                    return Str::limit($description, 32);
                })->hideFromIndex(),
            Date::make(__('Start Publishing Date'), 'play_at')->hideFromIndex(),
            
            // TODO: 不要跳转，不要统计, aws直链
            App::isLocal() ? Audio::make('Mp3', fn() => $this->mp3?:$this->novaPath)->disableDownload()->onlyOnDetail() : Audio::make('Mp3', fn() => $this->mp3?$this->path:$this->novaPath)->disableDownload()->onlyOnDetail(),
        ]);

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
