
<div class="form-check mt-1">
  <input class="form-check-input parent" type="checkbox"  value="{{ $category->term_taxonomy_id }}"
    name="category_ids[]" id="category{{ $category->term->term_id }}"
    @if(isset($post)) {{  $post->taxonomies->contains('term_id', $category->term->term_id) ? 'checked' : '' }} @endif>
  <label class="form-check-label" for="category{{ $category->term->term_id }}">
    {{ $category->term->name }}
  </label>
</div>
@if ($category->children->isNotEmpty())
<div class="child-checkboxes">
  @foreach ($category->children as $child)
    @include('pages.category.partials.category-checkbox', ['category' => $child,'post' => isset($post)?$post:null ])
  @endforeach
</div>
@endif
