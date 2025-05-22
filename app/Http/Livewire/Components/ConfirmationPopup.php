<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ConfirmationPopup extends Component
{
    public $showPopup = false;
    public $button1Text;
    public $button2Text;

    public $message;

    public function togglePopup()
    {
        $this->showPopup = !$this->showPopup;
    }

    public function mount()
    {
        $this->message = 'Are you sure you want to delete this item?';
        $this->button1Text = 'Button 1';
        $this->button2Text = 'Button 2';
    }

    public function render()
    {
        return view(view: 'livewire.components.confirmation-popup');
    }
}
