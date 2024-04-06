<?php

namespace App\Nova\Dashboards;

use Laravel\Nova\Cards\Help;
use Laravel\Nova\Dashboards\Main as Dashboard;
// use Xiangkeguo\NovaEmbedCard\NovaEmbedCard;
use Elipzis\NovaEmbedCard\EmbedCard;
class Main extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            (new EmbedCard())->url('https://www.youtube.com/embed/WhWc3b3KhnY'),
            // new NovaEmbedCard,
            (new EmbedCard())
                ->header('Spring')
                ->footer('A Blender Open Movie')
                ->code('<iframe width="100%" height="100%" src="https://www.youtube.com/embed/WhWc3b3KhnY" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'),
        ];
        // return [(new NovaEmbedCard())->url('https://www.youtube.com/embed/WhWc3b3KhnY')];
        return [
            new Help,
            // new Pluse;
            new NovaEmbedCard,
            // (new NovaEmbedCard())->url('https://www.youtube.com/embed/WhWc3b3KhnY'),
            // (new EmbedCard())->header('Spring')->footer('A Blender Open Movie')->code('<iframe width="560" height="315" src="https://www.youtube.com/embed/WhWc3b3KhnY" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'),
        ];
    }

    /**
     * Get the displayable name of the dashboard.
     *
     * @return string
     */
    public function name()
    {
        return __('Insights Status');
    }
}
