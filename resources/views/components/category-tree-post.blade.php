@props([
    'nodes',
    'selectedCatIds' => collect(),
    'level' => 0
])

@foreach ($nodes as $node)
    <div class="form-check mt-1 category-node level-{{ $level }}">
        <input  
            class="form-check-input category-checkbox"
            type="checkbox"
            id="category{{ $node->term_taxonomy_id }}"
            name="category_ids[]"
            value="{{ $node->term_taxonomy_id }}"
            {{ $selectedCatIds->contains($node->term_taxonomy_id) ? 'checked' : '' }}
        >
        <label class="form-check-label" for="category{{ $node->term_taxonomy_id }}">
            {{ $node->term->name }} ({{ $node->count }})
        </label>
    </div>

    @if ($node->children->isNotEmpty())
        <div class="child-checkboxes ms-4">
            <x-category-tree-post 
                :nodes="$node->children"
                :selectedCatIds="$selectedCatIds"
                :level="$level + 1"
            />
        </div>
    @endif
@endforeach
