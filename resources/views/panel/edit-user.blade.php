@extends('layouts.sidebar')

@section('content')

<div class="conatiner-fluid content-inner mt-n5 py-0">
  <div class="row">   


      <div class="col-lg-12">
          <div class="card   rounded">
             <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">  
  
                      <section class="text-gray-400">
                        <h2 class="mb-4 card-header"><i class="bi bi-person"> {{__('messages.Edit User')}}</i></h2>
                          <div class="card-body p-0 p-md-3">
                  
                        @if($errors->any())
                          <div class="alert alert-danger" role="alert">
                            <strong>User was not saved. Please correct the following:</strong>
                            <ul class="mb-0">
                              @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                              @endforeach
                            </ul>
                          </div>
                        @endif
                        @foreach($user as $user)
                        <form action="{{ route('editUser', $user->id) }}" enctype="multipart/form-data" method="post">
                          @csrf
                              <div class="form-group col-lg-8">
                              <label>{{__('messages.Name')}}</label>
                              <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}">
                            </div>
                            <div class="form-group col-lg-8">
                              <label>{{__('messages.Email')}}</label>
                              <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}">
                            </div>
                            <div class="form-group col-lg-8">
                              <label>{{__('messages.Password')}}</label>
                              <input type="password" class="form-control" name="password" placeholder="Leave empty for no change">
                            </div>
                            
                            <div class="form-group col-lg-8">
                              <label>{{__('messages.Logo')}}</label>
                              <div class="mb-3">
                                <input type="file" class="form-control form-control-lg" name="image">
                            </div>
                            </div>
                            
                            <div class="form-group col-lg-8">
                              @if(file_exists(base_path(findAvatar($user->id))))
                              <img src="{{ url(findAvatar($user->id)) }}" class="bd-placeholder-img img-thumbnail" width="100" height="100" draggable="false">
                              @else
                              <img src="{{ asset('assets/linkstack/images/logo.svg') }}" class="bd-placeholder-img img-thumbnail" width="100" height="100" draggable="false">
                              @endif
                              @if(file_exists(base_path(findAvatar($user->id))))<br><a title="Remove icon" class="hvr-grow p-1 text-danger" style="padding-left:5px;" href="?delete"><i class="bi bi-trash-fill"></i> {{__('messages.Delete')}}</a>@endif
                              @if($_SERVER['QUERY_STRING'] === 'delete' and File::exists(base_path(findAvatar($user->id))))@php File::delete(base_path(findAvatar($user->id))); header("Location: ".url()->current()); die(); @endphp @endif
                          </div><br>
                            
                            <div class="form-group col-lg-8">
                              <label>{{__('messages.Custom background')}}</label>
                              <div class="mb-3">
                                <input type="file" class="form-control form-control-lg" name="background">
                            </div>
                            </div>
                            <div class="form-group col-lg-8">
                                @if(!file_exists(base_path('assets/img/background-img/'.findBackground($user->id))))<p><i>{{__('messages.No image selected')}}</i></p>@endif
                                <img style="width:95%;max-width:400px;argin-left:1rem!important;border-radius:5px;" src="@if(file_exists(base_path('assets/img/background-img/'.findBackground($user->id)))){{url('assets/img/background-img/'.findBackground($user->id))}}@else{{url('/assets/linkstack/images/themes/no-preview.png')}}@endif">
                                @if(file_exists(base_path('assets/img/background-img/'.findBackground($user->id))))<br><a title="Remove icon" class="hvr-grow p-1 text-danger" style="padding-left:5px;" href="?deleteB"><i class="bi bi-trash-fill"></i> {{__('messages.Delete')}}</a>@endif
                                @if($_SERVER['QUERY_STRING'] === 'deleteB' and File::exists(base_path('assets/img/background-img/'.findBackground($user->id))))@php File::delete(base_path('assets/img/background-img/'.findBackground($user->id))); header("Location: ".url()->current()); die(); @endphp @endif
                                <br>
                            </div><br>

                            <label>{{__('messages.Select theme')}}</label>
                              <div class="form-group col-lg-8">
                                  <select id="theme-select" style="margin-bottom: 40px;" class="form-control" name="theme" data-base-url="{{ url('') }}/@<?= Auth::user()->littlelink_name ?>">
                                      <?php
                                          $selectedTheme = old('theme', $user->theme);
                                          if ($handle = opendir('themes')) {
                                              while (false !== ($entry = readdir($handle))) {
                                                  if ($entry != "." && $entry != "..") {
                                                      if(file_exists(base_path('themes') . '/' . $entry . '/readme.md')){
                                                          $text = file_get_contents(base_path('themes') . '/' . $entry . '/readme.md');
                                                          $pattern = '/Theme Name:.*/';
                                                          preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                                                          if(sizeof($matches) > 0) {
                                                              $themeName = substr($matches[0][0],12);
                                                          }
                                                      }
                                                      if($selectedTheme != $entry and isset($themeName)){
                                                          echo '<option value="'.$entry.'" data-image="'.url('themes/'.$entry.'/screenshot.png').'">'.$themeName.'</option>';
                                                      }
                                                  }
                                              }
                                          }
                              
                                          if($selectedTheme != "default" and $selectedTheme != ""){
                                              if(file_exists(base_path('themes') . '/' . $selectedTheme . '/readme.md')){
                                                  $text = file_get_contents(base_path('themes') . '/' . $selectedTheme . '/readme.md');
                                                  $pattern = '/Theme Name:.*/';
                                                  preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                                                  $themeName = substr($matches[0][0],12);
                                              }
                                              echo '<option value="'.$selectedTheme.'" data-image="'.url('themes/'.$selectedTheme.'/screenshot.png').'" selected>'.$themeName.'</option>';
                                          }
                              
                                          echo '<option value="default" data-image="'.url('themes/default/screenshot.png').'"';
                                          if($selectedTheme == "default" or $selectedTheme == ""){
                                              echo ' selected';
                                          }
                                          echo '>Default</option>';
                                      ?>
                                  </select>
                              </div>
                            
                            <div class="form-group col-lg-8">
                              <label>{{__('messages.Page URL')}}</label>
                              <div class="input-group">
                            <div class="input-group-prepend">
                            <div class="input-group-text">{{ url('') }}/@</div>
                            </div>
                            <input type="text" class="form-control" name="littlelink_name" value="{{ old('littlelink_name', $user->littlelink_name) }}">
                          </div>
                        </div>
                            
                            <div class="form-group col-lg-8">
                              <label> {{__('messages.Page description')}}</label>
                              <textarea class="form-control" name="littlelink_description" rows="3">{{ old('littlelink_description', $user->littlelink_description) }}</textarea>
                            </div>
                            <div class="form-group col-lg-8">
                              <label for="exampleFormControlSelect1">{{__('messages.Role')}}</label>
                              <select class="form-control" name="role">
                                <option value="user" @selected(old('role', $user->role) === 'user')>user</option>
                                <option value="vip" @selected(old('role', $user->role) === 'vip')>vip</option>
                                <option value="admin" @selected(old('role', $user->role) === 'admin')>admin</option>
                              </select>
                            </div>
                            @php
                              $adminVip = \App\Models\UserData::getData($user->id, 'vip_profile');
                              $adminVip = is_array($adminVip) ? $adminVip : [];
                            @endphp
                            <fieldset class="border rounded p-3 mb-4 col-lg-8">
                              <legend class="float-none w-auto px-2 fs-5">Platform-controlled profile status</legend>

                              <div class="form-check form-switch mb-3">
                                <input type="hidden" name="show_checkmark" value="0">
                                <input class="form-check-input" type="checkbox" id="show-checkmark" name="show_checkmark" value="1" @checked(old('show_checkmark', \App\Models\UserData::getData($user->id, 'checkmark') == true))>
                                <label class="form-check-label" for="show-checkmark">Show checkmark</label>
                              </div>

                              <div class="form-check form-switch mb-3">
                                <input type="hidden" name="links_new_tab" value="0">
                                <input class="form-check-input" type="checkbox" id="links-new-tab" name="links_new_tab" value="1" @checked(old('links_new_tab', \App\Models\UserData::getData($user->id, 'links-new-tab') == true))>
                                <label class="form-check-label" for="links-new-tab">Open profile links in a new tab</label>
                              </div>

                              <div class="form-check form-switch mb-3">
                                <input type="hidden" name="vip_badge_enabled" value="0">
                                <input class="form-check-input" type="checkbox" id="vip-badge-enabled" name="vip_badge_enabled" value="1" @checked(old('vip_badge_enabled', $adminVip['enabled'] ?? ($user->role === 'vip')))>
                                <label class="form-check-label" for="vip-badge-enabled">Enable PinkKiss VIP icon and recognition panel</label>
                              </div>
                              <p class="form-text">Only administrators control this status. VIP is a PinkKiss.Love platform designation, not identity verification.</p>

                              <label class="form-label" for="vip-headline">Personal VIP headline</label>
                              <input class="form-control mb-3" id="vip-headline" name="vip_headline" maxlength="220" value="{{ old('vip_headline', $adminVip['headline'] ?? '') }}" placeholder="{{ old('name', $user->name) }} has earned a place in the PinkKiss.Love VIP community.">

                              <label class="form-label" for="vip-message">Personal VIP message</label>
                              <textarea class="form-control mb-3" id="vip-message" name="vip_message" maxlength="800" rows="4">{{ old('vip_message', $adminVip['message'] ?? '') }}</textarea>

                              <label class="form-label" for="vip-cta-label">VIP panel CTA label</label>
                              <input class="form-control mb-3" id="vip-cta-label" name="vip_cta_label" maxlength="80" value="{{ old('vip_cta_label', $adminVip['cta_label'] ?? '') }}">

                              <label class="form-label" for="vip-cta-url">VIP panel CTA URL</label>
                              <input class="form-control mb-3" id="vip-cta-url" type="url" name="vip_cta_url" value="{{ old('vip_cta_url', $adminVip['cta_url'] ?? '') }}" placeholder="https://">

                              @if(!empty($adminVip['granted_at']))
                                @php($days = \Illuminate\Support\Carbon::parse($adminVip['granted_at'])->startOfDay()->diffInDays(now()->startOfDay()))
                                <div class="alert alert-secondary mb-0">VIP for {{ number_format($days) }} {{ \Illuminate\Support\Str::plural('day', $days) }} and counting. 💋</div>
                              @endif
                            </fieldset>
                            @endforeach
                            <button type="submit" class="mt-3 ml-3 btn btn-primary">{{__('messages.Save')}}</button>
                          </form>
                          @include('components.config.profile-ai-discovery')
                          @include('components.config.profile-seo-discovery')
                  
                            </div>
                  </section>
  
                    </div>
                </div>
             </div>
          </div>
       </div>


    </div>
  </div>

@endsection
