<div class="fadein biolync-text-block">{!! \App\Support\RichText::render($link->title, preg_match('/<[a-z]/i', $link->title ?? '') ? 'html' : 'plain') !!}</div>
