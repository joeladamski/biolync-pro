<?php

namespace App\Support;

/** Public editorial HTML. Never render editor input without this allowlist. */
class RichText
{
    public static function render(?string $value, string $format = 'html'): string
    {
        if ($format !== 'html') return nl2br(e($value ?? ''));
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><!doctype html><html><body>'.($value ?? '').'</body></html>', LIBXML_NONET);
            $output = new \DOMDocument('1.0', 'UTF-8');
            $root = $output->createElement('div');
            $output->appendChild($root);
            $body = $document->getElementsByTagName('body')->item(0);
            if ($body) foreach ($body->childNodes as $child) self::copy($child, $root, $output);
            $html = '';
            foreach ($root->childNodes as $child) $html .= $output->saveHTML($child);
            return $html;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function copy(\DOMNode $node, \DOMNode $parent, \DOMDocument $output): void
    {
        if ($node instanceof \DOMText) {
            $parent->appendChild($output->createTextNode($node->textContent));
            return;
        }
        if (!$node instanceof \DOMElement) return;
        $tag = strtolower($node->tagName);
        if ($tag === 'h1') $tag = 'h2';
        if (in_array($tag, ['script','style','iframe','object','embed','svg','math','template','form','input','button','textarea','select','link','meta','base'], true)) return;
        $allowed = ['p','div','span','br','h2','h3','h4','h5','h6','strong','b','em','i','u','s','strike','sub','sup','ul','ol','li','blockquote','pre','code','hr','a','img','table','thead','tbody','tfoot','tr','th','td','caption'];
        $target = $parent;
        if (in_array($tag, $allowed, true)) {
            $target = $output->createElement($tag);
            $parent->appendChild($target);
            if ($tag === 'a' && self::safeUrl($node->getAttribute('href'))) {
                $target->setAttribute('href', trim($node->getAttribute('href')));
                if ($node->getAttribute('target') === '_blank') {
                    $target->setAttribute('target', '_blank');
                    $target->setAttribute('rel', 'noopener noreferrer');
                }
            }
            if ($tag === 'img') {
                if (!self::safeUrl($node->getAttribute('src'), true)) {
                    $parent->removeChild($target);
                    return;
                }
                $target->setAttribute('src', trim($node->getAttribute('src')));
                $target->setAttribute('alt', $node->getAttribute('alt'));
                $target->setAttribute('loading', 'lazy');
            }
            foreach (['title','colspan','rowspan','width','height','start'] as $attribute) {
                if (!$node->hasAttribute($attribute)) continue;
                $value = $node->getAttribute($attribute);
                if ($attribute === 'title' || preg_match('/^[1-9][0-9]{0,3}$/D', $value)) $target->setAttribute($attribute, $value);
            }
            $styles = [];
            foreach (explode(';', $node->getAttribute('style')) as $style) {
                $pair = explode(':', $style, 2);
                if (count($pair) !== 2) continue;
                [$property, $value] = array_map('trim', $pair);
                $property = strtolower($property);
                if (in_array($property, ['color','background-color'], true) && preg_match('/^(#[a-f0-9]{3,8}|[a-z]+|rgba?\([0-9.,%\s]+\))$/iD', $value)) $styles[] = "$property:$value";
                if ($property === 'text-align' && in_array($value, ['left','center','right','justify'], true)) $styles[] = "$property:$value";
                if ($property === 'margin-left' && preg_match('/^[0-9]{1,3}(px|em)$/D', $value)) $styles[] = "$property:$value";
            }
            if ($styles) $target->setAttribute('style', implode(';', $styles));
        }
        foreach ($node->childNodes as $child) self::copy($child, $target, $output);
    }

    private static function safeUrl(string $value, bool $image = false): bool
    {
        $value = trim($value);
        if ($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) return false;
        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) return true;
        if (!$image && str_starts_with($value, '#')) return true;
        $schemes = $image ? ['http','https'] : ['http','https','mailto','tel'];
        return in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), $schemes, true);
    }
}
