<?php

namespace App\Http\Livewire;

use Livewire\Component;

class DirectorList extends Component
{
    public $directors = [];

    public function render()
    {
        return view('livewire.director-list');
    }

    public function addDirector($director)
    {
        $this->directors[] = $director;
    }

    public function removeDirector($index)
    {
        unset($this->directors[$index]);
        $this->directors = array_values($this->directors);
    }
}