<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PlayerLayout extends Component
{
    public function __construct(public string $title = '')
    {
    }

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('layouts.player');
    }
}
