<?php

namespace App\Http\Livewire;

use Livewire\Component;

class SearchableDropdown extends Component
{
    public $options = [
        'ESI',
        'PF',
        'Payroll',
        'Labour Law',
    ];
    
    public $selected = [];
    public $search = '';


    public function save(){
        
    }

    public function render()
    {
        $filteredOptions = collect($this->options)
            ->filter(fn($option) => str_contains(strtolower($option), strtolower($this->search)));
        
        return view('livewire.searchable-dropdown', [
            'filteredOptions' => $filteredOptions,
        ]);
    }

    public function toggleOption($option)
    {
        if (($key = array_search($option, $this->selected)) !== false) {
            unset($this->selected[$key]);
        } else {
            $this->selected[] = $option;
        }
    }

    public function removeOption($option)
    {
        if (($key = array_search($option, $this->selected)) !== false) {
            unset($this->selected[$key]);
        }
    }
}