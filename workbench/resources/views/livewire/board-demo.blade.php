<section aria-labelledby="board-demo-heading">
    <h2 id="board-demo-heading">Board</h2>

    <x-ui::board id="fruit-board" label="Fruit by colour" :columns="$labels">
        @foreach ($columns as $key => $column)
            <x-ui::board.column wire:key="board-column-{{ $key }}" :column="$key" :label="$column['label']" :count="$column['count']" :total="$column['total']" :has-more="$column['slice']->hasMore">
                @foreach ($column['slice']->records as $fruit)
                    <x-ui::board.card wire:key="board-card-{{ $fruit->id }}" :id="$fruit->id" :label="$fruit->name">{{ $fruit->name }}</x-ui::board.card>
                @endforeach
            </x-ui::board.column>
        @endforeach
    </x-ui::board>
</section>
