@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-4">
<div class="row"><div class="col-12">
@include('studio.photo-gallery')
<a class="btn btn-outline-primary" href="{{ url('/'.Auth::user()->littlelink_name) }}" target="_blank" rel="noopener">View my public profile</a>
</div></div>
</div>
@endsection
