<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Outl1ne\NovaInlineTextField\InlineText;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasManyThrough;
use Laravel\Nova\Fields\Select;
use Spatie\TagsField\Tags;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LyMeta extends Resource
{   
    /**
     * The resource label
     * 
     */

    public static function label()
    {
        return __('LY Metas');
    }

     /**
      * Singular resource label
      */

    public static function singularLabel()
    {
       return __('LY Meta');
    }

    // public static function label() { return '良友'; }
    public static $priority = 1;
    // public static $group = 'Metadata';
    public static $perPageOptions = [5,10,25,30,50,100];
    
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
        $query->with('tags');
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
        'name',
        'code',
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
        // $image = \App::isLocal() ? Image::class : VaporImage::class;

        $isLts = $this->isLts;
        $model = $this;

        $meta_fields = config('pms.lyMeta.extraFields.text');
        $addMetaFields = [];
        foreach ($meta_fields as $filed) {
            $addMetaFields[] = Text::make(__($filed['field_desc']), $filed['field'])
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $model->setMeta($attribute, $request->input($attribute));
                })
                ->withMeta(["value" => $model->getMeta($filed['field'])])
                ->hideFromIndex()
                ->placeholder($filed['placeholder']);
        }

        $defaultFields = [
            ID::make()->sortable(),

            Text::make(__('Program Title'),'name')
                ->sortable()
                ->hideFromIndex(),
            Text::make(__('Program Title'), 'name')
                ->rules('required', 'max:255')->displayUsing(function($name) {
                    return Str::limit($name, 32);
                })->onlyOnIndex(),
            Text::make(__('Cover'), function () {
                return "<img width='40px' src='{$this->cover}' />";
            })->asHtml(),
            Image::make(__('Cover'),'avatar')
                ->disk('s3')
                ->path('ly/image/cover')
                ->storeAs(function (Request $request) {
                    return $this->code . '.jpg';
                })
                ->acceptedTypes(['.jpg','.png'])->onlyOnForms(),
            Text::make(__('Program Alias'),'code')
                ->sortable()
                ->rules('required', 'max:12'),
            Tags::make(__('Program Category Title'))
                ->type('ly')
                ->single(),
            Tags::make(__('Program Language'))
                ->type('program-language')
                ->hideFromIndex()
                ->placeholder('空格查看并选择'),
            Text::make(__('Program Brief Description'),'description')->hideFromIndex(),
            Trix::make(__('Program Full Description'), 'description_detail')
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $model->setMeta($attribute, $request->input($attribute));
                })
                ->withMeta(["value" => $model->getMeta('description_detail')])
                ->hideFromIndex(),
            Tags::make(__('Program Nature'))
                ->type('program-nature')
                ->single(),
            Tags::make(__('Target Audience'))
                ->type('target-audience')
                ->hideFromIndex()
                ->placeholder('空格查看并选择'),
            Tags::make(__('Program Format'))
                ->type('program-format')
                ->hideFromIndex()
                ->placeholder('空格查看并选择'),
            Text::make(__('Weekly Broadcast Date'),'rrule_by_day')
                ->rules('required', 'max:20')
                ->hideFromIndex(),
            Date::make(__('Program Start Date'),'begin_at')->sortable()->hideFromIndex(),
            Date::make(__('Program End Date'),'end_at')->sortable()->help('节目最后一集的日期'),
            Date::make(__('Playlist Unpublish Date'),'unpublished_at')->sortable()->help('播放列表最后一天出街的日期'),
            Text::make(__('Publish Duration'),'counts_max_list')->placeholder('播放列表最多显示天数，31-255')->sortable()->hideFromIndex(),
            Tags::make(__('Production Centre'))
                ->type('production-centre')
                ->hideFromIndex()
                ->single(),
            Tags::make(__('Sponsor'))
                ->type('sponsor')
                ->hideFromIndex()
                ->single(),
            Textarea::make(__('Remark'),'remark')->hideFromIndex(),
            BelongsToMany::make(__('Announcers'), 'announcers', Announcer::class)->allowDuplicateRelations(),
            HasManyThrough::make(__('LTS Episodes'), 'ltsItems', LtsItem::class)->showOnDetail(),
        ];

        // 动态添加LTS的Meta
        if($isLts) {
            $tags = \App\Models\LyMeta::getLtsTags($this->code);
            $local = app()->getLocale();
            if($local != 'en') app()->setLocale('en');
            // tag only has en translation, so if cn, the $currentOptions = []
            $currentOptions = \App\Models\LtsMeta::withAnyTags($tags, 'lts')->pluck('name','id')
                ->toArray();
            app()->setLocale($local); // rollback local
            $ltsFields = [
                // 起先播放 first_play_lts
                // 起先播放日期 first_play_lts
                //current_lts fist_play_at then 1-24...
                Select::make(__('Assign Subject'), 'lts_first_play')
                    ->options($currentOptions)
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $isDirty = $this->getMeta('lts_first_play') != $request->input('lts_first_play');
                        if($isDirty) {
                            $model->setMeta('lts_first_play', $request->input($attribute));
                            $this->updateLtsItems($request,$model);
                        }
                    })
                    ->default(function ($request) {
                        return $this->getMeta('lts_first_play');
                    })->onlyOnForms(),

                Text::make(__('Assign Subject'), 'lts_first_play', function () use($isLts) {
                        if(!$isLts) return '!lts';
                        $lts_first_play = $this->getMeta('lts_first_play');
                        $ltsMeta = \App\Models\LtsMeta::find($lts_first_play);
                        if($ltsMeta) return "<a class='link-default' target='_blank' href='/admin/resources/lts-metas/{$ltsMeta->id}'>{$ltsMeta->name}</a>";
                        return '-';
                    })
                    ->asHtml()
                    ->onlyOnDetail(),
                Date::make(__('Assign Start Publishing Date'), 'lts_first_play_at')
                    ->help('请更改时间')
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $isDirty = $this->getMeta('lts_first_play_at') != $request->input('lts_first_play_at');
                        if($isDirty) {
                            $model->setMeta('lts_first_play_at', $request->input($attribute));
                            $this->updateLtsItems($request,$model);
                        }
                    })
                    ->default(function ($request) use($model) {
                        return $model->getMeta('lts_first_play_at')??now();
                    })->hideFromIndex(),
                Number::make(__('Assign Start Episode Number'),'lts_first_play_index')
                    ->dependsOn(['code'],
                        function ($field) use($isLts) {
                            if (!$isLts) {
                                $field->hide();
                            }
                        }
                    )
                    ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                        $isDirty = $this->getMeta('lts_first_play_index') != $request->input('lts_first_play_index');
                        if($isDirty) {
                            $model->setMeta('lts_first_play_index', $request->input($attribute));
                            $this->updateLtsItems($request,$model);
                        }
                    })
                    // https://github.com/laravel/nova-issues/issues/58#issuecomment-419754713
                    ->withMeta(["value" => (int)$model->getMeta('lts_first_play_index')??1])
                    ->min(0)->max(30)->step(1)
                    ->hideFromIndex(),
            ];
        }else{
            // if(!$isLts) 
            // 动态添加 HasMany lyitem, 原因： 良院的lyMeta没有这些。
            array_push($defaultFields, HasMany::make(__('LY Episodes'), 'ly_items_with_future', LyItem::class));
        }
        
        return !$isLts?array_merge($defaultFields, $addMetaFields):array_merge($defaultFields,$ltsFields, $addMetaFields);
    }

    private function updateLtsItems($request,$model)
    {
        // code...
        $lts_first_play_at = $request->input('lts_first_play_at');
        $lts_first_play = $request->input('lts_first_play');
        $lts_first_play_index = $request->input('lts_first_play_index');

        if(!$lts_first_play||!$lts_first_play||!$lts_first_play_index) {
            Log::error(__CLASS__, [__LINE__,$lts_first_play,$lts_first_play,$lts_first_play_index]);
            return;
        }

        $schedule = explode(",", $model->rrule_by_day);
        $count = 0;
        $ymd = $lts_first_play_at . " 00:00:00";
        $dt = Carbon::createFromFormat('Y-m-d H:i:s', $ymd);
        $ltsMeta = \App\Models\LtsMeta::find($lts_first_play);
        $ltsMeta->update(['ly_meta_id' => $model->id]);
        foreach ($ltsMeta->lts_items_asc as $key => $ltsItem) {
            // 从第N个节目开始更新
            if ($key + 1 >= $lts_first_play_index) {
                $playAt = $dt->copy()->addDays($count);
                                                        
                while (!in_array(Str::upper($playAt->locale('en')->minDayName), $schedule)) {
                    $playAt->addDay();
                    $count++; // 跳过周日后
                }

                $ltsItem->update([
                    'play_at' => $playAt
                ]);
                Log::info(__CLASS__,[__LINE__, $playAt, Str::upper($playAt->locale('en')->minDayName), $ltsItem->alias, $ltsMeta->name]);

                $count++;
            }
        }
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
        return [
            // https://laracasts.com/discuss/channels/nova/filter-by-a-non-null-value-in-laravel-nova?page=1&replyId=923948
            // new Filters\LyMetaActive,
        ];
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
