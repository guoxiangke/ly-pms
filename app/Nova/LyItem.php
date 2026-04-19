<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Outl1ne\NovaInlineTextField\InlineText;
    use Laravel\Nova\Fields\BelongsTo;
    use Laravel\Nova\Fields\MorphToMany;
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
        'program_station_code',
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
                ->help(__('If the audio is wrong, please upload a new mp3 file here'))
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
            Text::make('', function () {
    return $this->contents()->count() > 0 ? '<span><svg class="share cursor-pointer h-5 w-5 flex-none text-sky-500  hover:text-sky-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 16 2 C 14.743378 2 13.85942 2.8933056 13.416016 4 L 11 4 L 10 4 L 5 4 L 5 29 L 27 29 L 27 4 L 22 4 L 21 4 L 18.583984 4 C 18.14058 2.8933056 17.256622 2 16 2 z M 16 4 C 16.56503 4 17 4.4349698 17 5 L 17 6 L 18 6 L 20 6 L 20 8 L 12 8 L 12 6 L 15 6 L 15 5 C 15 4.4349698 15.43497 4 16 4 z M 7 6 L 10 6 L 10 10 L 22 10 L 22 6 L 25 6 L 25 27 L 7 27 L 7 6 z M 12 14 L 12 16 L 15 16 L 15 23 L 17 23 L 17 16 L 20 16 L 20 14 L 12 14 z"></path></svg>
                      </svg></span>' : '-';
})->asHtml()->onlyOnIndex(),
            Text::make('', function () {
                $url = '/admin/resources/clips/new?viaResource=ly-items&viaResourceId=' . $this->id . '&viaRelationship=clips';
                return '<a href="' . $url . '" class="cursor-pointer text-sky-500 hover:text-sky-600 inline-flex items-center" title="' . __('Create Clip') . '">'
                    . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/></svg>'
                    . '<span class="ml-1">' . __('Create Clip') . '</span>'
                    . '</a>';
            })->asHtml()->onlyOnIndex(),
            Text::make(__('Episode Alias'), 'alias')
                ->sortable()
                ->rules('required', 'max:12')->readonly(),
            Text::make(__('Program Station Code'), 'program_station_code')
                ->sortable()
                ->rules('max:12'),
            BelongsTo::make(__('Program Title'), 'ly_meta', 'App\Nova\LyMeta')->onlyOnForms()->readonly(),
            InlineText::make(__('Episode Description'), 'description')->onlyOnIndex(),
            Text::make(__('Episode Duration'), 'playtime_string')->sortable()->exceptOnForms(),
            Text::make(__('Episode Description'), 'description')
                ->rules('required', 'max:255')->hideFromIndex()->placeholder(''),
            Date::make(__('Start Publishing Date'), 'play_at'),
            
            // TODO: 不要跳转，不要统计, aws直链
            Audio::make('Mp3', fn() => $this->novaMp3Path)->disableDownload()->onlyOnDetail(),
            Text::make('', fn() => '<a target="_blank" href="'.$this->path.'" dusk="ComputedField-download-link" tabindex="0" class="cursor-pointer text-gray-500 inline-flex items-center"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" class="inline-block mr-2" role="presentation" view-box="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg><span class="class mt-1">'.__('Download').'</span></a>')->asHtml()->onlyOnDetail(),
            MorphToMany::make('Contents')->searchable(),
            MorphToMany::make(__('Clips'), 'clips', \App\Nova\Clip::class),
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
