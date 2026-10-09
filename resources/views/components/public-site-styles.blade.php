@php($pkStyles = \App\Support\PublicSiteStyle::settings())
<style id="pk-public-site-styles">
:root {
    --pk-primary: {{ $pkStyles['primary'] }};
    --pk-accent: {{ $pkStyles['accent'] }};
    --pk-bg: {{ $pkStyles['background'] }};
    --pk-surface: {{ $pkStyles['surface'] }};
    --pk-text-light: {{ $pkStyles['text_light'] }};
    --pk-text-dark: {{ $pkStyles['text_dark'] }};
    --pk-button-radius: {{ (int) $pkStyles['button_radius'] }}px;
    --pk-card-radius: {{ (int) $pkStyles['card_radius'] }}px;
    --pk-content-width: {{ (int) $pkStyles['content_width'] }}px;
    --pk-heading-font: {!! \App\Support\PublicSiteStyle::fontStack($pkStyles['heading_font']) !!};
    --pk-body-font: {!! \App\Support\PublicSiteStyle::fontStack($pkStyles['body_font']) !!};
}
.pk-public-surface { font-family: var(--pk-body-font); }
.pk-public-surface h1,
.pk-public-surface h2,
.pk-public-surface h3,
.pk-public-surface h4 { font-family: var(--pk-heading-font); }
.pk-public-surface .btn-primary { background-color: var(--pk-accent); border-color: var(--pk-accent); border-radius: var(--pk-button-radius); }
.pk-public-surface .card { border-radius: var(--pk-card-radius); }
</style>
