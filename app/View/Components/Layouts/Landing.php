<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;

class Landing extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
    ) {}

    public function render()
    {
        return view('layouts.landing');
    }
}
