@php($styles = \App\Support\PublicSiteStyle::settings())
<h2 class="mb-3 card-header">Public Site Styles</h2>
<p>Global design tokens for the landing page and other PinkKiss.Love public surfaces. Profile themes can still add their own presentation layer.</p>
@if(session('public_site_styles_saved'))<div class="alert alert-success">Public site styles saved.</div>@endif
<form method="POST" action="{{ route('savePublicSiteStyles') }}">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <label class="form-label" for="pk-scheme">Color scheme</label>
            <select id="pk-scheme" class="form-control mb-3" name="scheme">
                @foreach(['auto' => 'Auto', 'dark' => 'Dark', 'light' => 'Light'] as $value => $label)
                <option value="{{ $value }}" @selected(old('scheme', $styles['scheme']) === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <div class="row">
                @foreach([
                    'primary' => 'Primary / Deep Purple',
                    'accent' => 'Accent / Pink',
                    'background' => 'Background',
                    'surface' => 'Surface / Card',
                    'text_light' => 'Light Text',
                    'text_dark' => 'Dark Text'
                ] as $key => $label)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="pk-{{ $key }}">{{ $label }}</label>
                    <div class="input-group">
                        <input class="form-control form-control-color" type="color" id="pk-{{ $key }}-picker" value="{{ old($key, $styles[$key]) }}" aria-label="{{ $label }}">
                        <input class="form-control pk-color-text" id="pk-{{ $key }}" name="{{ $key }}" value="{{ old($key, $styles[$key]) }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" required>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="pk-heading-font">Heading font</label>
                    <select id="pk-heading-font" class="form-control" name="heading_font">
                        @foreach(['system'=>'System','serif'=>'Editorial Serif','modern'=>'Modern','clean'=>'Clean Sans'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('heading_font', $styles['heading_font']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="pk-body-font">Body font</label>
                    <select id="pk-body-font" class="form-control" name="body_font">
                        @foreach(['system'=>'System','serif'=>'Editorial Serif','modern'=>'Modern','clean'=>'Clean Sans'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('body_font', $styles['body_font']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="pk-button-radius">Button radius</label>
                    <input id="pk-button-radius" class="form-control" type="number" min="0" max="40" name="button_radius" value="{{ old('button_radius', $styles['button_radius']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="pk-card-radius">Card radius</label>
                    <input id="pk-card-radius" class="form-control" type="number" min="0" max="40" name="card_radius" value="{{ old('card_radius', $styles['card_radius']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="pk-content-width">Content width</label>
                    <input id="pk-content-width" class="form-control" type="number" min="720" max="1600" name="content_width" value="{{ old('content_width', $styles['content_width']) }}">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Save public styles</button>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div id="pk-style-preview" class="p-4" style="min-height:260px;border-radius:18px;background:{{ $styles['background'] }};color:{{ $styles['text_light'] }}">
                <div style="background:{{ $styles['surface'] }};color:{{ $styles['text_dark'] }};border-radius:{{ (int)$styles['card_radius'] }}px;padding:22px">
                    <div style="font-size:1.5rem;font-weight:700">PinkKiss.Love</div>
                    <p class="mb-3">Public style preview</p>
                    <button type="button" class="btn" style="background:{{ $styles['accent'] }};color:#000;border-radius:{{ (int)$styles['button_radius'] }}px">Featured CTA</button>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id$="-picker"]').forEach(picker => {
        const text = document.getElementById(picker.id.replace('-picker', ''));
        if (!text) return;
        picker.addEventListener('input', () => { text.value = picker.value.toUpperCase(); });
        text.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
        });
    });
});
</script>
