<style>
.home-nav {position:sticky!important;top:0;z-index:1030;}
.home-nav .navbar-collapse {max-height:70vh;max-height:70svh;overflow-y:auto;}
</style>
        <!--Nav Start-->
        <nav class="nav navbar navbar-expand-lg navbar-light iq-navbar home-nav">
          <div class="container-fluid navbar-inner">
            <a href="{{ url('/') }}" class="navbar-brand">
                
                <!--Logo start-->
                <div class="logo-main">
                  @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
                  <div class="logo-normal">
                    <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;">
                </div>
                <div class="logo-mini">
                  <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;">
                </div>
                  @else
                  <div class="logo-normal">
                    <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/logo.svg') }}" width="30px" height="30px">
                </div>
                <div class="logo-mini">
                  <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/logo.svg') }}" width="30px" height="30px">
                </div>
                  @endif
                  </div>
                <!--logo End-->
                
                
                <h4 class="logo-title">{{env('APP_NAME')}}</h4>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon">
                  <span class="mt-2 navbar-toggler-bar bar1"></span>
                  <span class="navbar-toggler-bar bar2"></span>
                  <span class="navbar-toggler-bar bar3"></span>
                </span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
              <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
                @foreach(\App\Support\SitePages::navigation() as $navPage)
                <li class="me-0 me-xl-2"><a class="nav-link" href="{{ route('publicSitePage', ['slug' => $navPage['slug']]) }}" @if(request()->is('pages/'.$navPage['slug'])) aria-current="page" @endif>{{ $navPage['nav_label'] ?: $navPage['title'] }}</a></li>
                @endforeach
                @if (Route::has('login'))
                @auth
                <li class="me-0 me-xl-2">
                  <a class="btn btn-primary btn-sm d-flex gap-2 align-items-center" aria-current="page" href="{{ url('dashboard') }}">
                    {{__('messages.Dashboard')}}
                  </a>
                </li>
            @else
                @if (Route::has('login'))
                <li class="me-0 me-xl-2">
                  <a class="btn btn-primary btn-sm d-flex gap-2 align-items-center" aria-current="page" href="{{ route('login') }}">
                    {{__('messages.Log in')}}
                  </a>
                </li>
                @endif
            
                @if ((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))
                <li class="me-0 me-xl-2">
                  <a class="btn btn-secondary btn-sm d-flex gap-2 align-items-center" aria-current="page" href="{{ route('register') }}">
                    {{__('messages.Register')}}
                  </a>
                </li>
                @endif
            @endauth        
                  @endif
              </ul>
            </div>
          </div>
        </nav>
        <!--Nav End-->
