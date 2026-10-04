@extends('layouts.installing')

@push('installer-body')
<div class="container">
    <div class="logo-container fadein">
        <img class="logo-img" src="{{ asset('assets/linkstack/images/logo.svg') }}" alt="Logo">
    </div>
    <h1>{{ __('messages.Setup LinkStack') }}</h1>
    <p class="inst-txt">{{ __('messages.Create an admin account') }}</p>

    <form id="owner-bootstrap-form" action="{{ route('createAdmin') }}" enctype="multipart/form-data" method="post">
        <div class="form-group col-lg-8">
            <label>{{ __('messages.Admin email:') }}</label>
            <input style="max-width:275px;" class="form-control" placeholder="admin@admin.com" name="email" type="email" required>

            <label>{{ __('messages.Admin password:') }}</label>
            <input style="max-width:275px;" class="form-control" placeholder="12345678" name="password" type="password" required>

            <label>{{ __('messages.Handle:') }}</label>
            <div class="input-group">
                <div class="input-group-prepend"><div class="input-group-text">@</div></div>
                <input style="max-width:237px; padding-left:50px;" class="form-control" name="handle" type="text" required>
            </div>

            <label>{{ __('messages.Name:') }}</label>
            <input style="max-width:275px;" class="form-control" name="name" type="text" required>
        </div>

        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button type="submit" class="mt-3 ml-3 btn btn-info">{{ __('messages.Next') }}</button>
    </form>
</div>
@endpush
