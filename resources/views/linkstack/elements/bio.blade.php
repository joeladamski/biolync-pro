        <!-- Short Bio -->
        <style>.description-parent * {margin-bottom: 1em;}.description-parent {padding-bottom: 30px;}</style>
        <div class="fadein description-parent dynamic-contrast" style="text-align:center">{!! \App\Support\RichText::render($info->littlelink_description, preg_match('/<[a-z]/i', $info->littlelink_description ?? '') ? 'html' : 'plain', true) !!}</div>
