@extends('layouts.installing')

@push('installer-body')
<div class="container">
    <div class="logo-container fadein">
        <img class="logo-img" src="{{ asset('assets/linkstack/images/logo.svg') }}" alt="Logo">
    </div>
    <h1>{{ __('messages.Setup LinkStack') }}</h1>
    <p class="inst-txt">{{ __('messages.Configure your page') }}</p>

    <form id="owner-finalize-form" action="{{ route('setupOwnerOptions') }}" enctype="multipart/form-data" method="post">
        <div class="form-group col-lg-8">
            <div class="input-group">
                <label>{{ __('messages.Enable registration:') }}</label>
                <select style="max-width:300px" class="form-control" name="register">
                    <option value="Yes">{{ __('messages.Yes') }}</option>
                    <option value="No">{{ __('messages.No') }}</option>
                </select>

                <label>{{ __('messages.Enable email verification:') }}</label>
                <select style="max-width:300px" class="form-control" name="verify">
                    <option value="Yes">{{ __('messages.Yes') }}</option>
                    <option value="No">{{ __('messages.No') }}</option>
                </select>

                <label>{{ __('messages.Set your page as Home Page') }}</label>
                <select style="max-width:300px" class="form-control" name="page">
                    <option value="No">{{ __('messages.No') }}</option>
                    <option value="Yes">{{ __('messages.Yes') }}</option>
                </select>

                <label>{{ __('messages.App Name:') }}</label>
                <input style="max-width:275px;" class="form-control" value="{{ config('app.name') }}" name="app" type="text" required>
            </div>
        </div>

        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button type="submit" class="mt-3 ml-3 btn btn-info">{{ __('messages.Finish setup') }}</button>
    </form>
</div>
@endpush
