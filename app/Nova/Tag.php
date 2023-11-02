<?php
namespace App\Nova;

use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Http\Requests\NovaRequest;
// use Spatie\Tags\Tag as TagModel;
use App\Models\Tag as TagModel;

class Tag extends Resource
{
    public static function label() { return 'Category'; }
	public static $perPageOptions = [100,500];

    public static $model = TagModel::class;

    public static $title = 'name';

    public static $search = [
        'name',
        'slug',
    ];

    public function fields(NovaRequest $request)
    {
        $tag = $this;
        return [
            Text::make('Name')->sortable(),
            Slug::make('Slug')->from('name')->sortable(),
            Text::make('Weight','order_column')->sortable()->hideFromIndex(),
            Text::make('Desc', 'description')
                ->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
                    $model->setMeta($attribute, $request->input($attribute));
                })
                ->withMeta(["value" => $tag->getMeta('description')])
                ->placeholder('Your description goes here!'),
            Text::make('Type')->showOnIndex(),//->filterable(),
        ];
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
        	new Filters\TagType,
    	];
    }

}