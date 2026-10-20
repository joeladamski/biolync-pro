<label for="block-text" class="form-label">{{ __('messages.Text to display') }}</label>
<textarea id="block-text" class="form-control" name="text" rows="8" data-rich-text data-rich-text-label="Text block">{{ old('text', $title ?? '') }}</textarea>
