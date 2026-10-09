@php($styles = \App\Support\PublicSiteStyle::settings())
<h2 class="mb-3 card-header">Public Site Styles</h2>
<p>Global design tokens for the landing page and public PinkKiss.Love pages. Profile themes keep their own presentation layer.</p>
@if(session('public_site_styles_saved'))<div class="alert alert-success">Public site styles saved.</div>@endif
@if(session('public_site_styles_reset'))<div class="alert alert-success">Public site styles reset to PinkKiss defaults.</div>@endif

<form method="POST" action="{{ route('savePublicSiteStyles') }}" id="pk-public-style-form">
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
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Save public styles</button>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div id="pk-style-preview" class="p-4" style="min-height:260px;border-radius:18px;background:{{ $styles['background'] }};color:{{ $styles['text_light'] }}">
                <div id="pk-style-preview-card" style="background:{{ $styles['surface'] }};color:{{ $styles['text_dark'] }};border-radius:{{ (int)$styles['card_radius'] }}px;padding:22px">
                    <div id="pk-style-preview-title" style="font-size:1.5rem;font-weight:700">PinkKiss.Love</div>
                    <p class="mb-3">Public style preview</p>
                    <button id="pk-style-preview-button" type="button" class="btn" style="background:{{ $styles['accent'] }};color:{{ $styles['text_dark'] }};border-radius:{{ (int)$styles['button_radius'] }}px">Featured CTA</button>
                </div>
            </div>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('resetPublicSiteStyles') }}" class="mt-3" onsubmit="return confirm('Reset all public site styles to the PinkKiss.Love defaults?');">
    @csrf
    <button class="btn btn-outline-danger" type="submit">Reset to PinkKiss defaults</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('pk-public-style-form');
    if (!form) return;

    const preview = document.getElementById('pk-style-preview');
    const previewCard = document.getElementById('pk-style-preview-card');
    const previewButton = document.getElementById('pk-style-preview-button');

    const fontMap = {
        system: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        serif: 'Georgia, "Times New Roman", serif',
        modern: '"Trebuchet MS", Arial, sans-serif',
        clean: 'Arial, Helvetica, sans-serif'
    };

    const syncPreview = () => {
        const val = id => document.getElementById(id)?.value;
        preview.style.background = val('pk-background');
        preview.style.color = val('pk-text_light');
        previewCard.style.background = val('pk-surface');
        previewCard.style.color = val('pk-text_dark');
        previewCard.style.borderRadius = (val('pk-card-radius') || 18) + 'px';
        previewButton.style.background = val('pk-accent');
        previewButton.style.color = val('pk-text_dark');
        previewButton.style.borderRadius = (val('pk-button-radius') || 14) + 'px';
        previewCard.style.fontFamily = fontMap[val('pk-body-font')] || fontMap.system;
        document.getElementById('pk-style-preview-title').style.fontFamily = fontMap[val('pk-heading-font')] || fontMap.system;
    };

    form.querySelectorAll('[id$="-picker"]').forEach(picker => {
        const text = document.getElementById(picker.id.replace('-picker', ''));
        if (!text) return;
        picker.addEventListener('input', () => {
            text.value = picker.value.toUpperCase();
            syncPreview();
        });
        text.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
            syncPreview();
        });
    });

    form.querySelectorAll('select,input[type="number"]').forEach(el => {
        el.addEventListener('input', syncPreview);
        el.addEventListener('change', syncPreview);
    });

    syncPreview();
});
</script>
