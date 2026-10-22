<div class="container">
	<div class="footer fadein" style="margin:5% 0px 35px 0px;">
	@if(config('footer.DISPLAY_FOOTER') === true)
		@if(config('footer.DISPLAY_FOOTER_HOME') === true)<a class="footer-hover spacing" href="@if(config('footer.HOME_FOOTER_LINK') === "" ){{ url('') }}@else{{ config('footer.HOME_FOOTER_LINK') }}@endif">{{footer('Home')}}</a>@endif
		@if(config('footer.DISPLAY_FOOTER_TERMS') === true)<a class="footer-hover spacing" href="{{ url('') }}/pages/{{ strtolower(footer('Terms')) }}">{{footer('Terms')}}</a>@endif
		@if(config('footer.DISPLAY_FOOTER_PRIVACY') === true)<a class="footer-hover spacing" href="{{ url('') }}/pages/{{ strtolower(footer('Privacy')) }}">{{footer('Privacy')}}</a>@endif
		@if(config('footer.DISPLAY_FOOTER_CONTACT') === true)<a class="footer-hover spacing" href="{{ url('') }}/pages/{{ strtolower(footer('Contact')) }}">{{footer('Contact')}}</a>@endif
	@endif
	</div>

	@if(config('footer.DISPLAY_CREDIT') === true)
	{{-- Removed class spacing --}}
	<div style="vertical-align:middle;display:inline-flex;flex-direction:column;align-items:center;gap:8px;padding-bottom:50px;" class="credit-hover fadein">
		<a style="text-decoration:none;" href="{{ config('branding.url') }}" title="{{__('messages.Learn more about LinkStack')}}">
			<span style="display:inline-flex;align-items:center;padding:10px 18px;border-radius:999px;background:#16121d;color:#fff;font-weight:700;letter-spacing:.02em;box-shadow:0 8px 24px rgba(0,0,0,.12);">Powered by {{ config('branding.name') }}</span>
		</a>
		<a style="font-size:12px;text-decoration:none;opacity:.72;" href="{{ config('branding.source_url') }}" target="_blank" rel="noopener noreferrer">Source</a>
	</div>
	@endif
	</div>
