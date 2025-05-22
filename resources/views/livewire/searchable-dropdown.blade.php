<div >
    <div class="" x-data="{open: false}">
        <input
            type="text"
            @click="open = !open" 
            @click.away="open = false"
            wire:model="search"
            placeholder="Search options..."
            wire:keydown.enter="toggleOption($event.target.value)"
            class="w-full p-2 border radius-4 rounded"
        >
        <div x-show="open"  class="position-absolute z-10 w-25 mt-1 bg-white border rounded shadow-lg max-h-60 overflow-y-auto">
            
            @foreach ($filteredOptions as $option)
                <div
                    class="p-2 cursor-pointer hover:bg-gray-100"
                    wire:click="toggleOption('{{ $option }}')"
                >
                   {{ $option }}
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-4">
        <div class="d-flex flex-wrap border p-2 rounded gap-2 bg-gray-400 w-25 h-auto" style="min-height:60px; height: auto;">
            @foreach ($selected as $option)
                <div class=" items-center px-2 py-1 bg-primary text-white text-bold rounded text-sm overflow-y-auto" style="width:fit-content; height:fit-content; max-height:fit-content; "> 
                    <span >{{ $option }}</span>
                    <a
                        wire:click="removeOption('{{ $option }}')"
                        class="ml-2 text-white text-bold cursor-pointer" 
                    >
                        &times;
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>