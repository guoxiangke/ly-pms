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
use Laravel\Nova\Fields\HasMany;

use Spatie\TagsField\Tags;
// use MichielKempen\NovaOrderField\Orderable;
// use MichielKempen\NovaOrderField\OrderField;
use App;

class LtsMeta extends Resource
{
    // use Orderable;
    // public static $defaultOrderField = 'weight';
    // public static function label() { return '良院'; }
    public static $priority = 2;
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
        $image = App::isLocal() ? Image::class : VaporImage::class;
        $model =  $this;
        $meta_fields = config('pms.ltsMeta.extraFields.text');
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
            Text::make('index')
                ->sortable()
                ->placeholder('微信编码')
                ->hideFromIndex(),
            Text::make('avatar', function () {
                return "<img width='100px' src='{$this->cover}' />";
            })->asHtml(),
            BelongsTo::make('lyMeta', 'ly_meta', 'App\Nova\LyMeta'),
            Text::make('LTS Program Title','name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('LTS Program Alias','code')
                ->placeholder('良院课程代号')
                ->sortable()
                ->rules('required', 'max:12'),
            Text::make('Number of Episode','count')
                ->placeholder('节数')
                ->sortable(),
            Tags::make('Category', 'Tags')
                ->type('lts')
                ->single(),
            Text::make('Announcer','author')
                ->sortable(),
            Date::make('Start Publishing Date','begin_at')->sortable(),
            Date::make('Finish Publishing Date','stop_at')->sortable(),
            Date::make('Production Date','made_at')->sortable(),//制作日期
            Image::make('avatar')
                ->path('ly/lts')
                ->storeAs(function (Request $request) {
                    return $this->code . '.jpg';
                })
                ->acceptedTypes('.jpg')
                ->disableDownload()
                ->onlyOnForms(),
            Textarea::make('LTS Program Description','description')
                ->placeholder('良院课程说明')
                ->hideFromIndex(),
            Textarea::make('remark')->hideFromIndex(),
            HasMany::make('lts_items','lts_items'),
            // Production Centre
            Tags::make('Production Centre')
                ->type('production-centre')
                ->single(),
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
