@props(['confirmation' => []])

@php
    $isDanger = ($confirmation['variant'] ?? null) === 'danger';
@endphp

<x-ui::modal wire:model.live="confirming" size="md" :heading="$confirmation['title'] ?? __('ui::ui.confirm')" :description="$confirmation['message'] ?? ''" {{ $attributes }}>
    <x-slot:footer>
        <x-ui::button variant="secondary" wire:click="cancelConfirmation" :data-autofocus="$isDanger ? true : null">{{ __('ui::ui.cancel') }}</x-ui::button>
        <x-ui::button :variant="$confirmation['variant'] ?? 'primary'" wire:click="runConfirmation" :data-autofocus="$isDanger ? null : true">{{ $confirmation['confirmLabel'] ?? __('ui::ui.confirm') }}</x-ui::button>
    </x-slot:footer>
</x-ui::modal>
