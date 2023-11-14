<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\VaporImage;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\FormData;
use App\Models\LtsMeta;
use Spatie\TagsField\Tags;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App;

class LyMeta extends Resource
{
    // public static function label() { return '良友'; }
    // public static $priority = 1;
    public static $group = 'Metadata';
    public static $perPageOptions = [300];
    
    // https://trungpv1601.github.io/2020/04/14/Laravel-Nova-Setting-a-default-sort-order-support-multi-columns/
    /**
     * Default Sort Columns variable
     *
     * @var array
     */
    public static $defaultSort = [
        'id' => 'asc',
    ];
    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        if (static::$defaultSort && empty($request->get('orderBy'))) {
            $query->getQuery()->orders = [];
            foreach (static::$defaultSort as $field => $order) {
                $query->orderBy($field, $order);
            }
        }
        return $query;
    }
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\LyMeta>
     */
    public static $model = \App\Models\LyMeta::class;

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
        $image = App::isLocal() ? Image::class : VaporImage::class;

        // $ltsPlaylistMeta = [
        //     'ltsnp' => '启航',
        //     'ltsdp1' => '本科1',
        //     'ltsdp2' => '本科2',
        //     'ltshdp1' => '进深1',
        //     'ltshdp2' => '进深2',
        // ];
        // $ltsTags = [
        //     "启航课程" => "ltsnp",
        //     "本科文凭课程" => "ltsdp",
        //     "进深文凭课程" => "ltshdp",
        //     "专辑课程" => "ltsnop",
        // ];
        $isLts = in_array($this->code, ['maltsnp','maltsdp1','maltsdp2','maltshdp1','maltshdp2']);
        $tags = [];
        // by 阮老师
            // 良院專輯在廣播節目表上按日期插在啟航（大部分）及本科良院內
            // 這個只是廣播時間連續兩個半小時节目
        switch ($this->code) {
            case 'maltsnp':
                $tags[] = '启航课程';
                $tags[] = '专辑课程';
                break;
            case 'maltsdp1':
            case 'maltsdp2':
                $tags[] = '本科文凭课程';
                $tags[] = '专辑课程';
                break;
            case 'maltshdp1':
            case 'maltshdp2':
                $tags[] = '进深文凭课程';
                break;
            
            default:
                // code...
                break;
        }
        $currentOptions = LtsMeta::withAnyTags($tags, 'lts')->pluck('name','id');
        $model = $this;

        $meta_fields = config('pms.lyMeta.extraFields.text');
        $addMetaFields = [];
        foreach ($meta_fields as $filed) {
            $addMetaFields[] = Text::make($filed['field_desc'], $filed['field'])
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $model->setMeta($attribute, $request->input($attribute));
                })
                ->withMeta(["value" => $model->getMeta($filed['field'])])
                ->placeholder($filed['placeholder']);
        }

        $defaultFields = [
            ID::make()->sortable(),
            Text::make('avatar', function () {
                return "<img width='100px' src='{$this->cover}' />";
            })->asHtml(),

            Text::make('Program Title','name')
                ->sortable()
                ->hideFromIndex(),

            Text::make('Program Title', 'name')
                ->rules('required', 'max:255')->displayUsing(function($name) {
                    return Str::limit($name, 32);
                })->onlyOnIndex(),
            Text::make('Program Alias','code')
                ->placeholder('节目网络用代号')
                ->sortable()
                ->rules('required', 'max:12'),
                // ->withMeta(['placeholder' => 'Add categories...']),
                // ->canBeDeselected(),
                // ->limit($maxNumberOfTags),
            BelongsToMany::make('Announcers')->allowDuplicateRelations(),

            Tags::make('Program Category Title')
                ->type('ly')
                ->single(),
            Date::make('Program Start Date','begin_at')->sortable(),
            Date::make('Program End Date','end_at')->sortable(),
            Date::make('unpublished_at')->sortable(),
            Textarea::make('Program Brief Description','description')->hideFromIndex(),
            Textarea::make('remark')->hideFromIndex(),

            $image::make('avatar')
                ->path('ly/programs')
                ->storeAs(function (Request $request) {
                    return $this->code . '.jpg';
                })
                ->acceptedTypes('.jpg')->onlyOnForms(),

            Text::make('Weekly Broadcast Date','rrule_by_day')
                ->sortable()
                ->placeholder('每周播出日期')
                ->rules('required', 'max:255'),

            Textarea::make('Program Full Description', 'description_detail')
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $model->setMeta($attribute, $request->input($attribute));
                })
                ->withMeta(["value" => $model->getMeta('description_detail')])
                ->placeholder('Program Full Description')
                ->hideFromIndex(),

            Tags::make('Program Language')
                ->type('program-language')
                ->single(),
            Tags::make('Program Format')
                ->type('program-format')
                ->single(),
            Tags::make('Program Nature')
                ->type('program-nature')
                ->single(),
            Tags::make('Target Audience')
                ->type('target-audience')
                ->single(),
            Tags::make('Production Centre')
                ->type('production-centre')
                ->single(),
            Tags::make('Sponsor Producer')
                ->type('sponsor-producer')
                ->single(),

        ];

        // 动态添加LTS的Meta
        if($isLts) {
            $ltsFields = [
                // 起先播放 first_play_lts
                // 起先播放日期 first_play_lts
                //current_lts fist_play_at then 1-24...
                Select::make('lts_first_play')
                    ->options($currentOptions)
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $model->setMeta('lts_first_play', $request->input($attribute));
                    })
                    ->default(function ($request) {
                        return $this->getMeta('lts_first_play');
                    })->onlyOnForms(),

                Text::make('lts_first_play', function () use($isLts) {
                        if(!$isLts) return '!lts';
                        $lts_first_play = $this->getMeta('lts_first_play');
                        $ltsMeta = LtsMeta::find($lts_first_play);
                        if($ltsMeta) return "<a class='link-default' target='_blank' href='/nova/resources/lts-metas/{$ltsMeta->id}'>{$ltsMeta->name}</a>";
                        return '-';
                    })
                    ->asHtml()
                    ->onlyOnDetail(),
                Date::make('lts_first_play_at')
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $isDirty2 = $this->getMeta('lts_first_play_at') != $request->input('lts_first_play_at');
                        if($isDirty2) {
                            $model->setMeta('lts_first_play_at', $request->input($attribute));

                                $lts_first_play_at = $request->input($attribute);
                                $lts_first_play = $request->input('lts_first_play');
                                $lts_first_play_index = $request->input('lts_first_play_index');

                                $schedule = explode(",", $model->rrule_by_day);
                                $count = 0;
                                $ymd = $lts_first_play_at . " 00:00:00";
                                $ltsMeta = LtsMeta::find($lts_first_play);
                                foreach ($ltsMeta->lts_items as $key => $ltsItem) {
                                    // 从第N个节目开始更新
                                    if ($key + 1 >= $lts_first_play_index) {
                                        $dt = Carbon::createFromFormat('Y-m-d H:i:s', $ymd);
                                        
                                        $playAt = $dt->addDays($count);
                                        
                                        while (!in_array(Str::upper($playAt->minDayName), $schedule)) {
                                            $playAt->addDay();
                                            $count++; // 跳过周日后
                                        }

                                        $ltsItem->update([
                                            'play_at' => $playAt
                                        ]);

                                        $count++;
                                    }
                                }
                        }
                    })
                    ->default(function ($request) use($model) {
                        return $model->getMeta('lts_first_play_at')??1;
                    })->hideFromIndex(),
                Number::make('lts_first_play_index')
                    ->dependsOn(['code'],
                        function ($field) use($isLts) {
                            if (!$isLts) {
                                $field->hide();
                            }
                        }
                    )
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $model->setMeta('lts_first_play_index', $request->input($attribute));
                    })
                    // https://github.com/laravel/nova-issues/issues/58#issuecomment-419754713
                    ->withMeta(["value" => (int)$model->getMeta('lts_first_play_index')??1])
                    ->min(1)->max(30)->step(1)
                    ->hideFromIndex(),
            ];
        }else{
            // if(!$isLts) 
            // 动态添加 HasMany lyitem, 原因： 良院的lyMeta没有这些。
            array_push($defaultFields, HasMany::make('ly_items', 'ly_items_with_future'));
        }
        
        return !$isLts?array_merge($defaultFields, $addMetaFields):array_merge($defaultFields,$ltsFields, $addMetaFields);
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
