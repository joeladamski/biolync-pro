@php($pkStyles = \App\Support\PublicSiteStyle::settings())
<style id="pk-public-site-styles">\n#loading { display:none!important; }
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
    color-scheme: {{ $pkStyles['scheme'] === 'auto' ? 'light dark' : $pkStyles['scheme'] }};
}

.pk-public-surface { font-family: var(--pk-body-font); }
.pk-public-surface h1,
.pk-public-surface h2,
.pk-public-surface h3,
.pk-public-surface h4 { font-family: var(--pk-heading-font); }
.pk-public-surface .btn-primary {
    background-color: var(--pk-accent)!important;
    border-color: var(--pk-accent)!important;
    border-radius: var(--pk-button-radius)!important;
    color: var(--pk-text-dark)!important;
}
.pk-public-surface .btn-secondary {
    background-color: var(--pk-primary)!important;
    border-color: var(--pk-primary)!important;
    border-radius: var(--pk-button-radius)!important;
    color: var(--pk-text-light)!important;
}
.pk-public-surface .card { border-radius: var(--pk-card-radius); }

/* Landing page consumes the public tokens directly. */
.pk-public-home,
.pk-public-home .wrapper,
.pk-public-home .login-content,
.pk-public-home .home-layout {
    background: var(--pk-bg)!important;
}
.pk-public-home .home-copy {
    background: var(--pk-bg)!important;
    color: var(--pk-text-light)!important;
}
.pk-public-home .home-preview {
    background: var(--pk-primary)!important;
    color: var(--pk-text-light)!important;
}
.pk-public-home .home-copy h1,
.pk-public-home .home-copy h2,
.pk-public-home .home-copy h3,
.pk-public-home .home-copy h4,
.pk-public-home .home-copy p,
.pk-public-home .home-copy .lead,
.pk-public-home .home-preview-copy,
.pk-public-home .home-preview-copy h1,
.pk-public-home .home-preview-copy h2,
.pk-public-home .home-preview-copy h3,
.pk-public-home .home-preview-copy p {
    color: var(--pk-text-light)!important;
}
.pk-public-home .home-nav {
    background: var(--pk-primary)!important;
    color: var(--pk-text-light)!important;
}
.pk-public-home .home-nav .logo-title,
.pk-public-home .home-nav .nav-link {
    color: var(--pk-text-light)!important;
}
.pk-public-home .home-footer {
    background: var(--pk-primary)!important;
    color: var(--pk-text-light)!important;
}
.pk-public-home .home-footer a {
    color: var(--pk-text-light)!important;
}

/* Standalone public pages use the same global palette without touching profile themes. */
.pk-public-page {
    background: var(--pk-bg)!important;
    color: var(--pk-text-light)!important;
    min-height: 100vh;
}
.pk-public-page .iq-navbar,
.pk-public-page .footer {
    background: var(--pk-primary)!important;
}
.pk-public-page .iq-navbar .logo-title,
.pk-public-page .iq-navbar .nav-link,
.pk-public-page .footer,
.pk-public-page .footer a {
    color: var(--pk-text-light)!important;
}
.pk-public-page .card,
.pk-public-page .content-inner {
    border-radius: var(--pk-card-radius);
}
</style>
