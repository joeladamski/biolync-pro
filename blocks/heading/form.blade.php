<label for="block-heading" class="form-label">{{ __('messages.Heading Text:') }}</label>
<textarea id="block-heading" name="title" class="form-control" rows="3" data-rich-text data-rich-text-inline data-rich-text-label="Heading block">{{ old('title', $title ?? '') }}</textarea>
<p class="text-muted mt-2">Format a short heading with emphasis, color or a link.</p>
