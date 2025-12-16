<ul class="py-2 pl-4 mt-2 space-y-1 bg-gray-50 dark:bg-gray-900/50 rounded-lg border-l-2 border-primary-500"
    wire:key="category-subs-{{ $category->id }}" wire:transition.slide.down>
    @forelse($category->children as $child)
        <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>{{ $child->name }}</span>
            <span class="text-xs text-gray-400">({{ $child->products_count }} produits)</span>
        </li>
    @empty
        <li class="text-sm italic text-gray-400 pl-6">
            Aucune sous-catégorie
        </li>
    @endforelse
</ul>