<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
// use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Number;
use Illuminate\Database\Eloquent\Builder;

use Spatie\TagsField\Tags;

class LtsMeta extends Resource
{   
    /**
     * The resource label
     * 
     */

    public static function label()
    {
        return __('LTS Metas');
    }

     /**
      * Singular resource label
      */

    public static function singularLabel()
    {
       return __('LTS Meta');
    }

    public static $priority = 3;
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
        return $query;
    }
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
        'name',
        'code',
        'name_en',
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
        $model =  $this;
        $meta_fields = config('pms.ltsMeta.extraFields.text');
        $addMetaFields = [];
        if($model->id && $meta_fields)
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
            Text::make(__('LTS Subject Title'),'name')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make(__('LTS Subject English Title'),'name_en')
                ->sortable()
                ->hideFromIndex(),
            Text::make(__('LTS Subject Prefix'),'code')
                ->sortable()
                ->rules('required', 'max:12'),
            // BelongsTo::make(__('LTS Program Title'), 'ly_meta', 'App\Nova\LyMeta')
            //     ->relatableQueryUsing(function (NovaRequest $request, Builder $query) {
            //         $query->where('code', 'like', '%lts%');
            //     })->withoutTrashed(),
            Text::make(__('Number of Episode'),'count')->sortable()->required(),
            Number::make(__('Assign Start Episode Number'),'first_play_id')
                ->min(0)->max(99)->step(1)
                ->default(1)
                ->hideFromIndex(),
                
            Textarea::make(__('LTS Subject Description'),'description')
                ->hideFromIndex(),
            Date::make(__('Production Date'),'made_at')->sortable()->onlyOnForms(),
            Text::make(__('Production Date'), function () {
                return $this->made_at?$this->made_at->format("Y/m"):'—';
            })->asHtml()->hideWhenUpdating(),

            Tags::make(__('Production Centre'))
                ->type('production-centre')
                ->hideFromIndex()
                ->single(),
            Text::make(__('Announcer'),'author')
                ->hideFromIndex()
                ->sortable(),
            Date::make(__('Premiere Date'),'begin_at')->hideFromIndex()->sortable(),
            Textarea::make(__("Remark"), 'remark')->hideFromIndex(),
            Tags::make(__('LTS Program Category'),'Tags')
                ->type('lts')
                ->single()
                ->hideFromIndex(),
            HasMany::make(__("LTS Episodes"), 'lts_items', LtsItem::class),
        ];
        return array_merge($defaultFields, $addMetaFields);
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
