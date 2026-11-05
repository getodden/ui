<?php

declare(strict_types=1);

use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Odden\Ui\Tests\Support\ConfirmingComponent;

it('holds the pending confirmation and opens the dialog', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToRemove', 7)
        ->assertSet('confirming', true)
        ->assertSet('confirmation.title', 'Remove it?')
        ->assertSet('confirmation.confirmLabel', 'Remove')
        ->assertSet('confirmation.variant', 'danger')
        ->assertSee('Gone for good.')
        ->assertSee('Remove');
});

it('runs the confirmed protected action with its arguments and clears the state', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToRemove', 7)
        ->assertSet('removed', [])
        ->call('runConfirmation')
        ->assertSet('removed', [7])
        ->assertSet('confirmation', [])
        ->assertSet('confirming', false);
});

it('does nothing when there is nothing to confirm', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('runConfirmation')
        ->assertSet('removed', []);
});

it('cancels without running the action', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToRemove', 7)
        ->call('cancelConfirmation')
        ->assertSet('confirmation', [])
        ->assertSet('confirming', false)
        ->assertSet('removed', []);
});

it('cancels when the dialog is closed in the browser', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToRemove', 7)
        ->set('confirming', false)
        ->assertSet('confirmation', [])
        ->call('runConfirmation')
        ->assertSet('removed', []);
});

it('cannot reach the protected action without confirming', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('remove', 7);
})->throws(MethodNotFoundException::class);

it('cannot be asked to confirm from the browser', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('confirm', 'remove', [999], 'Remove it?', 'Gone for good.', 'Remove');
})->throws(MethodNotFoundException::class);

it('refuses to confirm a lifecycle method', function (string $action): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToDoSomethingElse', $action);
})->throws(InvalidArgumentException::class, 'cannot be confirmed')->with([
    'mount', 'boot', 'booted', 'hydrate', 'hydrateExtras', 'dehydrate', 'dehydrateExtras',
    'updating', 'updatingExtras', 'updated', 'updatedExtras', 'updatedConfirming', 'render', 'rendering', 'rendered', 'exception',
    'mountWithConfirmation', 'bootWithConfirmation', 'bootedWithConfirmation',
]);

it('rejects a tampered action from the browser', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToRemove', 7)
        ->set('confirmation.action', 'askToDoSomethingElse');
})->throws(CannotUpdateLockedPropertyException::class);

it('rejects replacing the whole pending confirmation from the browser', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->set('confirmation', ['action' => 'remove', 'arguments' => [1], 'title' => '', 'message' => '', 'confirmLabel' => '', 'variant' => 'danger']);
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses an action that is not a method on the component', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToDoSomethingElse', 'doesNotExist');
})->throws(InvalidArgumentException::class);

it('refuses to confirm a framework method', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToDoSomethingElse', 'dispatch');
})->throws(InvalidArgumentException::class);

it('refuses to confirm the confirmation methods themselves', function (): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToDoSomethingElse', 'runConfirmation');
})->throws(InvalidArgumentException::class);

it('confirms an ordinary method whose name only starts like a lifecycle hook', function (string $action): void {
    Livewire::test(ConfirmingComponent::class)
        ->call('askToDoSomethingElse', $action)
        ->assertSet('confirming', true)
        ->call('runConfirmation')
        ->assertSet('removed', [$action]);
})->with(['bootstrapWorkspace', 'mountVolume', 'renderPdf', 'exceptional']);
