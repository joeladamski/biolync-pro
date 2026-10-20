<?php

/**
 * Handles the logic for "heading" link type.
 * 
 * @param \Illuminate\Http\Request $request The incoming request.
 * @param mixed $linkType The link type information.
 * @return array The prepared link data.
 */
function handleLinkType($request, $linkType) {

    $rules = [
        'title' => [
            'required',
            'string',
            'max:5000',
        ],
    ];

    // Prepare the link data
    $linkData = [
        'title' => \App\Support\RichText::inline((string)$request->title, true),
        'button_id' => "42",
    ];

    return ['rules' => $rules, 'linkData' => $linkData];
}