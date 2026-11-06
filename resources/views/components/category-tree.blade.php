@props([
    'nodes',
    'precheckedCategoryIds' => [],
    'parentHasCheckedChild' => null,
    'level' => 0
])

@foreach ($nodes as $node)
    {{-- Node cha/con/cháu… --}}
    <div class="form-check mt-1 ms-{{ $level * 4 }}">
        <input class="form-check-input"
            type="checkbox"
            id="category{{ $node->term->term_id }}"
            name="category_ids[]"
            value="{{ $node->term->term_id }}"
            @checked(
                in_array((int)$node->term->term_id, $precheckedCategoryIds, true)
                || ($parentHasCheckedChild && $parentHasCheckedChild($node))
            )
        >
        <label class="form-check-label" for="category{{ $node->term->term_id }}">
            {{ $node->term->name }}
        </label>
    </div>

    {{-- Nếu có con thì in đệ quy --}}
    @if ($node->children && $node->children->isNotEmpty())
        <x-category-tree 
            :nodes="$node->children"
            :precheckedCategoryIds="$precheckedCategoryIds"
            :parentHasCheckedChild="$parentHasCheckedChild"
            :level="$level + 1"
        />
    @endif
@endforeach
