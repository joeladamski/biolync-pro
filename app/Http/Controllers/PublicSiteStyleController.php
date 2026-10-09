<?php

namespace App\Http\Controllers;

use App\Support\PublicSiteStyle;
use Illuminate\Http\Request;

class PublicSiteStyleController extends Controller
{
    public function save(Request $request)
    {
        $hex = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        $data = $request->validate([
            'scheme' => 'required|in:auto,dark,light',
            'primary' => $hex,
            'accent' => $hex,
            'background' => $hex,
            'surface' => $hex,
            'text_light' => $hex,
            'text_dark' => $hex,
            'heading_font' => 'required|in:system,serif,modern,clean',
            'body_font' => 'required|in:system,serif,modern,clean',
            'button_radius' => 'required|integer|min:0|max:40',
            'card_radius' => 'required|integer|min:0|max:40',
            'content_width' => 'required|integer|min:720|max:1600',
        ]);

        PublicSiteStyle::save($data);

        return redirect(url('/admin/config') . '#site-styles')->with('public_site_styles_saved', true);
    }
}
