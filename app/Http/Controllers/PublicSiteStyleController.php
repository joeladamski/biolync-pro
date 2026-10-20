<?php

namespace App\Http\Controllers;

use App\Support\PublicSiteStyle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'surface_opacity' => 'sometimes|integer|min:0|max:100',
            'background_image_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'remove_background_image' => 'nullable|boolean',
            'text_light' => $hex,
            'text_dark' => $hex,
            'heading_font' => ['required', Rule::in(array_keys(PublicSiteStyle::fontOptions()))],
            'body_font' => ['required', Rule::in(array_keys(PublicSiteStyle::fontOptions()))],
            'button_radius' => 'required|integer|min:0|max:40',
            'card_radius' => 'required|integer|min:0|max:40',
            'content_width' => 'required|integer|min:720|max:1600',
        ]);

        PublicSiteStyle::withLock(function () use ($request, $data) {
            $current = PublicSiteStyle::settings();
            $data['surface_opacity'] = $data['surface_opacity'] ?? $current['surface_opacity'];
            $data['background_image'] = $current['background_image'];
            if ($request->hasFile('background_image_upload')) {
                $upload = $request->file('background_image_upload');
                $extension = $upload->extension() === 'jpeg' ? 'jpg' : $upload->extension();
                $logical = 'assets/img/background-img/0_landing'.bin2hex(random_bytes(16)).'.'.$extension;
                $directory = \App\Support\RuntimeMedia::path('assets/img/background-img');
                $upload->move($directory, basename($logical));
                $data['background_image'] = $logical;
            } elseif ($request->boolean('remove_background_image')) {
                $data['background_image'] = null;
            }
            try {
                PublicSiteStyle::save($data);
            } catch (\Throwable $error) {
                if ($data['background_image'] !== $current['background_image']) {
                    $newPath = PublicSiteStyle::backgroundPath($data['background_image']);
                    if ($newPath && is_file($newPath)) unlink($newPath);
                }
                throw $error;
            }
            if ($data['background_image'] !== $current['background_image']) {
                $oldPath = PublicSiteStyle::backgroundPath($current['background_image']);
                if ($oldPath && is_file($oldPath)) unlink($oldPath);
            }
        });

        return redirect(url('/admin/config') . '#site-styles')->with('public_site_styles_saved', true);
    }

    public function reset()
    {
        PublicSiteStyle::reset();

        return redirect(url('/admin/config') . '#site-styles')->with('public_site_styles_reset', true);
    }
}
