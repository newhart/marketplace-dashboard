<div x-data="{ open: false }" class="flex flex-col">
    <div @click="open = !open"
        class="flex items-center gap-2 cursor-pointer group select-none transition-colors duration-200 hover:text-primary-600 dark:hover:text-primary-400">
        @if($getRecord()->children->count() > 0)
            <div class="transition-transform duration-200" :class="{ 'rotate-90': open }">
                <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400 group-hover:text-primary-500" />
            </div>
        @else
            <div class="w-4 h-4"></div> <!-- Spacer for alignment -->
        @endif

        <span class="font-bold text-sm" :class="{ 'text-primary-600 dark:text-primary-400': open }">
            {{ $getRecord()->name }}
        </span>

        @if($getRecord()->children->count() > 0)
            <span
                class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full group-hover:bg-primary-50 dark:group-hover:bg-primary-900/20 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                {{ $getRecord()->children->count() }}
            </span>
        @endif
    </div>

    @if($getRecord()->children->count() > 0)
        <div x-show="open" x-collapse x-cloak>
            <ul class="py-2 pl-4 mt-2 ml-2 space-y-1 border-l-2 border-gray-100 dark:border-gray-800">
                @foreach($getRecord()->children as $child)
                    <li
                        class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 pl-4 py-1 hover:text-gray-900 dark:hover:text-gray-200 transition-colors">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                        <span>{{ $child->name }}</span>
                        <span class="text-xs text-gray-400">({{ $child->products_count }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>