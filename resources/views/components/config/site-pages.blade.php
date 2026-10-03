<h2 class="mb-4 card-header">Site Pages</h2>
<p>Create pages such as About, Features or Get in touch. Published pages marked “Show in header navigation” appear in the homepage and site-page menus, including mobile navigation. Existing footer pages remain under Footer Pages.</p>
@if(session('site_pages_success'))<div class="alert alert-success">{{ session('site_pages_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@foreach(\App\Support\SitePages::all() as $editPage)
<details class="border rounded p-3 mb-3" @if(old('id') === $editPage['id']) open @endif>
<summary>{{ $editPage['title'] }} — {{ $editPage['published'] ? 'Published' : 'Draft' }}</summary>
<div class="mt-3">
@if($editPage['published'])<a href="{{ route('publicSitePage', ['slug' => $editPage['slug']]) }}" target="_blank" rel="noopener" class="d-block mb-3">View page</a>@endif
@include('components.config.site-page-form')
<form action="{{ route('deleteSitePage') }}" method="POST" onsubmit="return confirm('Delete this site page? Its URL will stop working.');">@csrf<input type="hidden" name="id" value="{{ $editPage['id'] }}"><button class="btn btn-outline-danger" type="submit">Delete page</button></form>
</div>
</details>
@endforeach
<details class="border rounded p-3" @if(!old('id')) open @endif><summary>Create a page</summary><div class="mt-3">@include('components.config.site-page-form', ['editPage' => []])</div></details>
