                  @if(config('footer.DISPLAY_FOOTER') === true)
                    @if(config('footer.DISPLAY_FOOTER_HOME') === true)<li class="list-inline-item"><a class="list-inline-item" href="@if(config('footer.HOME_FOOTER_LINK') === "" ){{ url('') }}@else{{ config('footer.HOME_FOOTER_LINK') }}@endif">{{footer('Home')}}</a></li>@endif
                    @if(config('footer.DISPLAY_FOOTER_TERMS') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Terms')) }}">{{footer('Terms')}}</a></li>@endif
                    @if(config('footer.DISPLAY_FOOTER_PRIVACY') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Privacy')) }}">{{footer('Privacy')}}</a></li>@endif
                    @if(config('footer.DISPLAY_FOOTER_CONTACT') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Contact')) }}">{{footer('Contact')}}</a></li>@endif
                  @endif
