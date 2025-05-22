<div>

    @if($showPopup)
    <div class="modal fade show" style="display: block;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Popup Title</h5>
                    <button wire:click="togglePopup" type="button" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <p>{{$message}}</p>
                </div>
                <div class="modal-footer">
                    <button wire:click="togglePopup" class="btn btn-secondary">{{ $button1Text }}</button>
                    <button wire:click="togglePopup" class="btn btn-primary">{{ $button2Text }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
