<ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-700 dark:text-gray-300">
    @foreach ($entries as $entry)
        <li class="inline-flex items-center gap-1.5">
            <span aria-hidden="true" class="size-2.5 shrink-0 rounded-sm" style="background-color: {{ $entry['paint'] }}"></span>
            <span>{{ $entry['text'] }}</span>
        </li>
    @endforeach
</ul>
