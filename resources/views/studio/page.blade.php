@extends('layouts.sidebar')

@section('content')
<style>
.biolync-page-editor .profile-preview{width:112px;height:112px;object-fit:cover;border-radius:50%;background:#fff;border:4px solid rgba(255,255,255,.12)}
.biolync-page-editor .profile-section{max-width:920px}
.biolync-page-editor .locked-handle{display:flex;align-items:center;gap:8px}
.biolync-page-editor .locked-handle .form-control{background:rgba(127,127,127,.08)}
.biolync-page-editor .form-group{margin-bottom:24px}
.biolync-page-editor .ck-editor__editable[role="textbox"]{min-height:200px}
</style>

<div class="container-fluid content-inner py-4 biolync-page-editor">
  <div class="row">
    <div class="col-lg-12">
      <div class="card rounded">
        <div class="card-body">
          <section class="profile-section text-gray-400">
            <h2 class="mb-4 card-header"><i class="bi bi-person"></i> {{ __('messages.My Profile') }}</h2>

            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if($errors->any())
              <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @foreach($pages as $page)
            <form action="{{ route('editPage') }}" enctype="multipart/form-data" method="post">
              @csrf

              <div class="form-group">
                <label class="form-label fw-bold">Profile Picture</label>
                <div class="mb-3">
                  @if(file_exists(base_path(findAvatar(Auth::id()))))
                    <img src="{{ url(findAvatar(Auth::id())) }}" class="profile-preview" width="112" height="112" alt="Current profile picture">
                  @elseif(file_exists(base_path('assets/linkstack/images/').findFile('avatar')))
                    <img src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" class="profile-preview" width="112" height="112" alt="Default profile picture">
                  @else
                    <img src="{{ asset('assets/linkstack/images/logo.svg') }}" class="profile-preview" width="112" height="112" alt="Default profile picture">
                  @endif
                </div>
                <label class="form-label" for="profile-picture-upload">Upload / change profile picture</label>
                <input id="profile-picture-upload" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" name="image" class="form-control">
                <div class="form-text">JPEG, PNG or WebP, up to 2MB.</div>
                @if(file_exists(base_path(findAvatar(Auth::id()))))
                  <a class="btn btn-sm btn-outline-danger mt-2" href="{{ route('delProfilePicture') }}"><i class="bi bi-trash"></i> Remove custom picture</a>
                @endif
              </div>

              <button type="submit" class="btn btn-primary mb-4">Save profile picture</button>
            </form>

            @include('studio.profile-header')

            <form action="{{ route('editPage') }}" enctype="multipart/form-data" method="post">
              @csrf
              <div class="form-group">
                <label class="form-label">Profile Handle</label>
                <div class="locked-handle">
                  <span class="input-group-text">@</span>
                  <input class="form-control" value="{{ $page->littlelink_name }}" readonly aria-readonly="true">
                  <span title="Only a PinkKiss.Love administrator can change your handle">🔒</span>
                </div>
                <div class="form-text">Managed by PinkKiss.Love. Contact an administrator if your handle needs to change.</div>
                <div class="form-text">{{ url('') }}/@{{ $page->littlelink_name }}</div>
              </div>

              <div class="form-group">
                <label class="form-label" for="display-name">{{ __('messages.Display name') }}</label>
                <input id="display-name" type="text" class="form-control" name="name" value="{{ old('name', $page->name) }}" maxlength="255" required>
              </div>

              <div class="form-group">
                <label class="form-label" for="page-description">{{ __('messages.Page Description') }}</label>
                <textarea id="page-description" class="form-control @if(env('ALLOW_USER_HTML') === true) ckeditor @endif" name="pageDescription" rows="4">{{ old('pageDescription', $page->littlelink_description ?? '') }}</textarea>
              </div>

              <div class="form-group">
                <h5>{{ __('messages.Show share button') }}</h5>
                <p class="text-muted">{{ __('messages.disablesharebutton') }}</p>
                <div class="form-check form-switch">
                  <input type="hidden" name="sharebtn" value="off">
                  <input name="sharebtn" class="form-check-input" type="checkbox" id="sharebtn" value="on" @checked(\App\Models\UserData::getData(Auth::id(), 'disable-sharebtn') != 'true')>
                  <label class="form-check-label" for="sharebtn">{{ __('messages.Enable') }}</label>
                </div>
              </div>

              <button type="submit" class="btn btn-primary">Save profile</button>
            </form>
            @endforeach

            @include('studio.creator-experience')
          </section>
        </div>
      </div>
    </div>
  </div>
</div>

@if(env('ALLOW_USER_HTML') === true)
<script src="{{ asset('assets/external-dependencies/ckeditor.js') }}"></script>
<script>
ClassicEditor.create(document.querySelector('.ckeditor'), {
  toolbar: {items:['heading','|','bold','italic','underline','link','bulletedList','numberedList','|','undo','redo'],shouldNotGroupWhenFull:false},
  link:{addTargetToExternalLinks:true,defaultProtocol:'https://'}
}).catch(error => console.error(error));
</script>
@endif
@endsection
