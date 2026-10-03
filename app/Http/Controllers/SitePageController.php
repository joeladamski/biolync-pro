<?php

namespace App\Http\Controllers;

use App\Support\SitePages;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SitePageController extends Controller
{
    public function show(string $slug)
    {
        foreach (SitePages::all() as $page) {
            if ($page['slug'] === $slug && $page['published']) return view('site-page', ['sitePage' => $page]);
        }
        abort(404);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|string|regex:/^[a-f0-9]{16}$/',
            'title' => 'required|string|max:100',
            'nav_label' => 'nullable|string|max:50',
            'slug' => 'required|string|max:60|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'body' => 'required|string|max:20000',
            'published' => 'required|boolean',
            'show_in_header' => 'required|boolean',
            'order' => 'required|integer|min:0|max:99',
        ]);
        $reserved = array_map('strtolower', [footer('Terms'), footer('Privacy'), footer('Contact'), 'terms', 'privacy', 'contact']);
        if (in_array($data['slug'], $reserved, true)) throw ValidationException::withMessages(['slug' => 'This address is reserved for an existing footer page.']);
        SitePages::update(function ($pages) use ($data) {
            $existing = null;
            foreach ($pages as $index => $page) {
                if (!empty($data['id']) && $page['id'] === $data['id']) $existing = $index;
                if ($page['slug'] === $data['slug'] && $page['id'] !== ($data['id'] ?? null)) throw ValidationException::withMessages(['slug' => 'Another page already uses this address.']);
            }
            if (!empty($data['id']) && $existing === null) abort(404);
            if ($existing === null && count($pages) >= 20) throw ValidationException::withMessages(['title' => 'You can create up to 20 site pages.']);
            $page = [
                'id' => $data['id'] ?? bin2hex(random_bytes(8)),
                'title' => $data['title'], 'nav_label' => $data['nav_label'] ?? '',
                'slug' => $data['slug'], 'body' => $data['body'],
                'published' => (bool) $data['published'], 'show_in_header' => (bool) $data['show_in_header'],
                'order' => (int) $data['order'],
            ];
            if ($existing === null) $pages[] = $page; else $pages[$existing] = $page;
            return $pages;
        });
        return redirect(url('/admin/config') . '#site-pages')->with('site_pages_success', 'Site page saved.');
    }

    public function delete(Request $request)
    {
        $data = $request->validate(['id' => 'required|string|regex:/^[a-f0-9]{16}$/']);
        SitePages::update(function ($pages) use ($data) {
            $remaining = array_values(array_filter($pages, fn ($page) => $page['id'] !== $data['id']));
            if (count($remaining) === count($pages)) abort(404);
            return $remaining;
        });
        return redirect(url('/admin/config') . '#site-pages')->with('site_pages_success', 'Site page deleted.');
    }
}
