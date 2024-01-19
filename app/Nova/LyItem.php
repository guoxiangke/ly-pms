<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Audio;
use Ebess\AdvancedNovaMediaLibrary\Fields\Files;

use App;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\VaporFile;
// use DmitryBubyakin\NovaMedialibraryField\Fields\Medialibrary;
use Illuminate\Support\Facades\Auth;

class LyItem extends Resource
{
    public static $group = 'Items';
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
        $model = $this;
        $file = App::isLocal() ? File::class : VaporFile::class;
        $reflection = new \ReflectionClass($file);
        $className = $reflection->getName();

        $fileFeild = App::isLocal() ? [
            File::make('音频勘误', 'mp3')
                ->disk('public')
                ->path('ly/corrections')
                ->storeAs(fn() => $this->alias . '.mp3')
                // ->storeAs(function (Request $request){
                //     // 记录谁上传的，上传的时间
                //     $fileNameParts = [
                //         $this->alias,
                //         Auth::id(),
                //         now()->format('Ymd_H:i:s'),
                //         $request->mp3->getSize(),
                //         $request->mp3->getClientOriginalName(),
                //     ];
                //     return implode('-', $fileNameParts);
                // })
                ->help('紧急情况下修正 音频错误时，上传新的mp3 '.$className)
                ->acceptedTypes('.mp3')
                ->disableDownload()] : [
            VaporFile::make('音频勘误', 'mp3')
                ->path('ly/corrections')
                ->storeAs(fn() => $this->alias . '.mp3')
                ->help('紧急情况下修正 音频错误时，上传新的mp3 '.$className)
                ->acceptedTypes('.mp3')
                ->disableDownload()];
        return array_merge( [
            ID::make()->sortable(),
        ] , $fileFeild,
        [
            // obersive Mp3: 一更新，后台便去处理
            BelongsTo::make('LyMeta', 'ly_meta', 'App\Nova\LyMeta'),

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
            
            Boolean::make('Active',function(){
                return !$this->is_future;
            })->showOnIndex(),
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
