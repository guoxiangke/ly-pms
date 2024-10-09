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


use Laravel\Nova\Fields\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use getID3;

class LyItem extends Resource
{   
    /**
     * The resource label
     * 
     */

    public static function label()
    {
        return __('LY Episodes');
    }

     /**
      * Singular resource label
      */

    public static function singularLabel()
    {
       return __('LY Episode');
    }

    // public static $group = 'Items 列表';
    public static $priority = 2;
    public static $perPageOptions = [5,10,25,30,50,100];
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
        'alias',
        'description',
    ];

    /**
     * @param NovaRequest $request
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        $query = $query->with('ly_meta');

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
        $model = $this;
        $fileFeild = [
            File::make(__('Replace Audio'), 'mp3')
                ->help('如音频错误，请在此上传新的mp3档')
                ->acceptedTypes('.mp3')
                // ->storeOriginalName($model->alias .'v'.date('His'). '.mp3')
                ->disableDownload()
                ->store(function (Request $request, $model) {
                    $getID3 = new getID3;
                    $thisFileInfo = $getID3->analyze($request->mp3->getPathname());
                    return [
                        'mp3' => $request->mp3->store('ly/corrections/'.$model->alias .'v'.date('His'), 's3'),
                        'playtime_string' => $thisFileInfo['playtime_string'],
                    ];
                })
                 ->storeAs(function (Request $request, $model) {
                    return $model->alias .'v'.date('His'). '.mp3';
                }),
        ];
        return array_merge([ID::make()->sortable()],$fileFeild,
        [
            Text::make(__('Episode Title'), fn()=> $this->episodeTitle)->exceptOnForms(),
            Text::make(__('Episode Alias'), 'alias')
                ->sortable()
                ->rules('required', 'max:12')->readonly(),
            BelongsTo::make(__('Program Title'), 'ly_meta', 'App\Nova\LyMeta')->onlyOnForms()->readonly(),
            InlineText::make(__('Episode Description'), 'description')->onlyOnIndex(),
            Text::make(__('Episode Duration'), 'playtime_string')->sortable()->exceptOnForms(),
            Text::make(__('Episode Description'), 'description')
                ->rules('required', 'max:255')->hideFromIndex(),
            Date::make(__('Start Publishing Date'), 'play_at'),
            
            // TODO: 不要跳转，不要统计, aws直链
            Audio::make('Mp3', fn() => $this->novaMp3Path)->disableDownload()->onlyOnDetail(),
            Text::make('', fn() => '<a target="_blank" href="'.$this->path.'" dusk="ComputedField-download-link" tabindex="0" class="cursor-pointer text-gray-500 inline-flex items-center"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" class="inline-block mr-2" role="presentation" view-box="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg><span class="class mt-1">'.__('Download').'</span></a>')->asHtml()->onlyOnDetail(),
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
