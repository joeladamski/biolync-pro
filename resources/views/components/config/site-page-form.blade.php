@include('components.admin-rich-text')
<form action="{{ route('saveSitePage') }}" method="POST" class="mb-3">
@csrf
@if(!empty($editPage['id']))<input type="hidden" name="id" value="{{ $editPage['id'] }}">@endif
@php
$prefix = $editPage['id'] ?? 'new';
$submitted = session()->hasOldInput() && old('id', '') === ($editPage['id'] ?? '');
$value = fn ($key, $default = '') => $submitted ? old($key, $default) : ($editPage[$key] ?? $default);
@endphp
<label for="page-title-{{ $prefix }}" class="form-label">Page title</label>
<input id="page-title-{{ $prefix }}" name="title" class="form-control mb-3" maxlength="100" value="{{ $value('title') }}" required>
<label for="page-nav-{{ $prefix }}" class="form-label">Navigation label (optional)</label>
<input id="page-nav-{{ $prefix }}" name="nav_label" class="form-control mb-3" maxlength="50" value="{{ $value('nav_label') }}">
<label for="page-slug-{{ $prefix }}" class="form-label">Page address — /pages/</label>
<input id="page-slug-{{ $prefix }}" name="slug" class="form-control mb-3" maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="about" value="{{ $value('slug') }}" required>
<label for="page-body-{{ $prefix }}" class="form-label">Page content</label>
<textarea id="page-body-{{ $prefix }}" name="body" data-rich-text data-rich-text-label="Page content" class="form-control mb-3" rows="8" maxlength="20000" required>{{ \App\Support\RichText::render($value('body'), $submitted ? old('body_format', 'text') : ($editPage['body_format'] ?? 'text')) }}</textarea>
<input type="hidden" name="body_format" value="html">
<p class="small">Use the toolbar to format your page, or Source to edit HTML. Changing the address changes the page URL.</p>
<label for="page-order-{{ $prefix }}" class="form-label">Navigation order</label>
<input id="page-order-{{ $prefix }}" name="order" class="form-control mb-3" type="number" min="0" max="99" value="{{ $value('order', 0) }}" required>
<input type="hidden" name="published" value="0"><label class="d-block mb-2"><input type="checkbox" name="published" value="1" @if($value('published', false)) checked @endif> Published</label>
<input type="hidden" name="show_in_header" value="0"><label class="d-block mb-3"><input type="checkbox" name="show_in_header" value="1" @if($value('show_in_header', true)) checked @endif> Show in header navigation</label>
<button class="btn btn-primary" type="submit">Save page</button>
</form>
