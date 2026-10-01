<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

final class SafeHtml
{
    private const TAGS = [
        'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'figcaption', 'figure',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'ol', 'p', 'pre',
        'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'base', 'embed', 'form', 'iframe', 'input', 'link', 'math', 'meta',
        'object', 'script', 'select', 'style', 'svg', 'textarea',
    ];

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            if (! $document->loadHTML('<?xml encoding="UTF-8"><div id="safe-html-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET)) {
                return e($html);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $document->getElementById('safe-html-root');

        if (! $root) {
            return e($html);
        }

        foreach (iterator_to_array($root->childNodes) as $child) {
            self::sanitize($child);
        }

        $result = '';

        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private static function sanitize(DOMNode $node): void
    {
        if ($node instanceof DOMComment) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            self::sanitize($child);
        }

        if (! in_array($tag, self::TAGS, true)) {
            while ($node->firstChild) {
                $node->parentNode?->insertBefore($node->firstChild, $node);
            }

            $node->parentNode?->removeChild($node);

            return;
        }

        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (! self::allowedAttribute($tag, $name, $value)) {
                $node->removeAttributeNode($attribute);
            }
        }

        if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function allowedAttribute(string $tag, string $name, string $value): bool
    {
        if ($name === 'class') {
            return (bool) preg_match('/^[A-Za-z0-9 _-]{1,255}$/', $value);
        }

        if ($tag === 'a') {
            return match ($name) {
                'href' => self::safeUrl($value),
                'title' => true,
                'target' => in_array($value, ['_blank', '_self'], true),
                'rel' => (bool) preg_match('/^[a-z -]{1,100}$/i', $value),
                default => false,
            };
        }

        if ($tag === 'img') {
            return match ($name) {
                'src' => self::safeUrl($value, image: true),
                'alt', 'title' => true,
                'width', 'height' => (bool) preg_match('/^[0-9]{1,5}$/', $value),
                'loading' => in_array($value, ['lazy', 'eager'], true),
                default => false,
            };
        }

        return in_array($tag, ['td', 'th'], true)
            && in_array($name, ['colspan', 'rowspan'], true)
            && (bool) preg_match('/^[1-9][0-9]?$/', $value);
    }

    private static function safeUrl(string $url, bool $image = false): bool
    {
        if (str_contains($url, '\\') || preg_match('/[[:cntrl:]]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        if (! $image && preg_match('/^#[A-Za-z0-9_-]+$/', $url)) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (in_array($scheme, ['http', 'https'], true)) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        return ! $image && match ($scheme) {
            'mailto' => filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) !== false,
            'tel' => (bool) preg_match('/^tel:[+0-9 ()-]+$/i', $url),
            default => false,
        };
    }
}
