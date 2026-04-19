<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Badge;
use Illuminate\Support\Str;

class Album extends Resource
{
    public static function label()
    {
        return __('Albums');
    }

    public static function singularLabel()
    {
        return __('Album');
    }

    public static $priority = 6;

    public static $perPageOptions = [10, 25, 50];

    public static $model = \App\Models\Album::class;

    public static $title = 'name';

    public static $search = [
        'id',
        'name',
        'description',
    ];

    public static $defaultSort = [
        'id' => 'desc',
    ];

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

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make(__('Album Name'), 'name')
                ->sortable()
                ->rules('required', 'max:255'),

            Textarea::make(__('Album Description'), 'description')
                ->hideFromIndex(),

            Image::make(__('Cover'), 'avatar')
                ->disk('s3')
                ->path('ly/image/album')
                ->storeAs(function (Request $request) {
                    return $this->id . '_' . time() . '.jpg';
                })
                ->acceptedTypes(['.jpg', '.png'])
                ->hideFromIndex(),

            Text::make(__('Cover'), function () {
                if ($this->avatar) {
                    $url = $this->avatar;
                    return "<img width='40px' src='{$url}' />";
                }
                return '-';
            })->asHtml()->onlyOnIndex(),

            DateTime::make(__('Published At'), 'published_at')
                ->sortable()
                ->nullable()
                ->help(__('留空表示未发布')),

            Badge::make(__('Status'), function () {
                return $this->published_at ? 'published' : 'draft';
            })->map([
                    'draft' => 'info',
                    'published' => 'success',
                ])
                ->labels([
                    'draft' => __('未发布'),
                    'published' => __('已发布'),
                ])
                ->onlyOnIndex(),

            MorphTo::make(__('Target Program'), 'target')
                ->types([
                    LyMeta::class,
                ])
                ->searchable()
                ->nullable()
                ->hideFromIndex()
                ->help(__('RRule 模式：必须与 RRule 同时填写。Manual 模式：必须留空。')),

            Textarea::make(__('RRule'), 'rrule')
                ->help(__('RFC 5545 recurrence rule，必须与 Target Program 同时填写。Manual 专辑请留空。e.g.: FREQ=WEEKLY;BYDAY=FR;DTSTART=20220101T000000Z;UNTIL=20221231T235959Z'))
                ->hideFromIndex(),

            Text::make(__('Mode'), function () {
                if ($this->is_rrule_mode) {
                    $count = $this->resolveRruleDates()->count();
                    return "RRule ({$count} " . __('episodes') . ")";
                }
                $count = $this->clips()->count();
                return __('Manual') . " ({$count} clips)";
            })->exceptOnForms(),

            HasMany::make(__('Clips'), 'clips', Clip::class),

            Textarea::make(__('Remark'), 'remark')
                ->hideFromIndex(),
        ];
    }

    public function cards(NovaRequest $request)
    {
        return [];
    }

    public function filters(NovaRequest $request)
    {
        return [];
    }

    public function lenses(NovaRequest $request)
    {
        return [];
    }

    public function actions(NovaRequest $request)
    {
        return [];
    }
}
