<?php

declare(strict_types=1);

use Livewire\Livewire;
use Odden\Ui\Tests\Support\Html;
use Odden\Ui\Tests\Support\ToastingComponent;

it('dispatches a success toast to the browser', function (): void {
    Livewire::test(ToastingComponent::class)
        ->call('inform')
        ->assertDispatched('ui-toast', message: 'Saved', variant: 'success');
});

it('dispatches a toast with the requested variant', function (): void {
    Livewire::test(ToastingComponent::class)
        ->call('fail')
        ->assertDispatched('ui-toast', message: 'Could not save', variant: 'danger');
});

it('flashes toasts for the page after a redirect', function (): void {
    Livewire::test(ToastingComponent::class)
        ->call('saveAndLeave')
        ->assertRedirect('/elsewhere');

    expect(session('ui.toasts'))->toBe([
        ['message' => 'Saved', 'variant' => 'info'],
        ['message' => 'And more', 'variant' => 'success'],
    ]);

    $region = Html::render('<x-ui::toasts />')->querySelector('[aria-live]');

    expect($region->getAttribute('x-data'))->toContain('And more');
});
