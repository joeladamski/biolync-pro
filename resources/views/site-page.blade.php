<!doctype html>
@include('layouts.lang')
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $sitePage['title'] }} — {{ config('app.name') }}</title>
<script src="{{ asset('assets/js/detect-dark-mode.js') }}"></script>
@include('layouts.fonts')
<link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/dark.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/customizer.min.css') }}">
<style>.site-page-content {max-width:900px;margin:0 auto;padding:32px 20px;min-height:70vh;overflow-wrap:anywhere;}.site-page-body {white-space:pre-line;line-height:1.7;}.site-page-content h1 {scroll-margin-top:100px;}.site-page-footer {padding:24px;text-align:center;}</style>
  @include('components.public-site-styles')
</head>
<body class="pk-public-surface">
@include('components.public-header')
<main class="site-page-content">
<h1>{{ $sitePage['title'] }}</h1>
<div class="site-page-body">{{ $sitePage['body'] }}</div>
</main>
<footer class="site-page-footer"><a href="{{ url('/') }}">Home</a> · {{ config('app.name') }}</footer>
<script src="{{ asset('assets/js/core/libs.min.js') }}"></script>
</body>
</html>
