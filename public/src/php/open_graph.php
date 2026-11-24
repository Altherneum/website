<?php

// Balises Open Graph (OG), Twitter Cards, favicons et données structurées du site
const OPEN_GRAPH_SITE_NAME = 'doc.altherneum.fr';
const OPEN_GRAPH_SITE_URL = 'https://doc.altherneum.fr';
const OPEN_GRAPH_DEFAULT_IMAGE = '/src/assets/gif/small.gif';
const OPEN_GRAPH_TWITTER_IMAGE = '/src/assets/image/og-image.png';
const OPEN_GRAPH_LOCALE = 'fr_FR';
const OPEN_GRAPH_LANGUAGE = 'fr-FR';
const OPEN_GRAPH_TWITTER_SITE = '@Altherneum';
const OPEN_GRAPH_TWITTER_CREATOR = '@Altherneum';
const OPEN_GRAPH_THEME_COLOR = '#1e1e1e';
const OPEN_GRAPH_FAVICON_PNG = '/favicon-32x32.png';
const OPEN_GRAPH_FAVICON_SVG = '/favicon.svg';
const OPEN_GRAPH_APPLE_TOUCH_ICON = '/apple-touch-icon.png';
const OPEN_GRAPH_MANIFEST = '/site.webmanifest';

function escapeOpenGraphText(string $text): string
{
    return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
}

function buildOpenGraphRoute(string $route): string
{
    $cleanRoute = trim($route, '/');
    if ($cleanRoute === '') {
        return '';
    }

    return '/' . $cleanRoute;
}

function buildOpenGraphUrl(string $route): string
{
    $path = buildOpenGraphRoute($route);
    if ($path === '') {
        return OPEN_GRAPH_SITE_URL . '/';
    }

    return OPEN_GRAPH_SITE_URL . $path;
}

function buildOpenGraphImageUrl(string $image): string
{
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $image;
    }

    return OPEN_GRAPH_SITE_URL . '/' . ltrim($image, '/');
}

function getOpenGraphImageDetails(string $image): array
{
    static $cache = [];

    $details = [
        'width' => 0,
        'height' => 0,
        'type' => '',
    ];

    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $details;
    }

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $filePath = $documentRoot . '/' . ltrim($image, '/');
    if (isset($cache[$filePath])) {
        return $cache[$filePath];
    }

    if (is_file($filePath)) {
        $size = @getimagesize($filePath);
        if (is_array($size)) {
            $details['width'] = (int) $size[0];
            $details['height'] = (int) $size[1];
            if (isset($size['mime'])) {
                $details['type'] = (string) $size['mime'];
            }
        }
    }

    $cache[$filePath] = $details;

    return $details;
}

function getOpenGraphTextLength(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text, 'UTF-8');
    }

    $length = preg_match_all('/./us', $text);
    if ($length === false) {
        return strlen($text);
    }

    return $length;
}

function buildOpenGraphDescription(string $description): string
{
    $descriptionLength = getOpenGraphTextLength($description);
    if ($descriptionLength >= 120) {
        return $description;
    }

    $suffixes = [
        'Découvrez également les cours, outils, jeux et documentations techniques proposés gratuitement sur le site officiel d\'Altherneum.fr.',
        'Retrouvez les cours, outils, jeux et documentations techniques proposés gratuitement sur Altherneum.fr.',
        'Retrouvez les cours, outils et documentations techniques proposés par Altherneum.fr.',
        'Découvrez plus de cours, outils et jeux sur Altherneum.fr.',
        'Plus de contenus sur Altherneum.fr.',
    ];

    $separator = '';
    if (!preg_match('/[.!?]$/u', $description)) {
        $separator = ' | ';
    }

    foreach ($suffixes as $suffix) {
        $candidate = $description . $separator . $suffix;
        $candidateLength = getOpenGraphTextLength($candidate);
        if ($candidateLength >= 120 && $candidateLength <= 160) {
            return $candidate;
        }
    }

    return $description;
}

function renderOpenGraph(string $title, string $route, string $description, string $image = OPEN_GRAPH_DEFAULT_IMAGE): string
{
    $openGraphTitle = OPEN_GRAPH_SITE_NAME . buildOpenGraphRoute($route);
    $url = buildOpenGraphUrl($route);
    $imageUrl = buildOpenGraphImageUrl($image);
    $imageDetails = getOpenGraphImageDetails($image);
    $metaDescription = buildOpenGraphDescription($description);

    $openGraphTags = [
        'og:title' => $openGraphTitle,
        'og:type' => 'website',
        'og:image' => $imageUrl,
    ];
    if ($imageDetails['type'] !== '') {
        $openGraphTags['og:image:type'] = $imageDetails['type'];
    }
    if ($imageDetails['width'] > 0) {
        $openGraphTags['og:image:width'] = (string) $imageDetails['width'];
    }
    if ($imageDetails['height'] > 0) {
        $openGraphTags['og:image:height'] = (string) $imageDetails['height'];
    }
    $openGraphTags['og:image:alt'] = 'logo';
    $openGraphTags['og:url'] = $url;
    $openGraphTags['og:locale'] = OPEN_GRAPH_LOCALE;
    $openGraphTags['og:site_name'] = OPEN_GRAPH_SITE_NAME;
    $openGraphTags['og:description'] = $metaDescription;

    $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:site' => OPEN_GRAPH_TWITTER_SITE,
        'twitter:creator' => OPEN_GRAPH_TWITTER_CREATOR,
        'twitter:title' => $title,
        'twitter:description' => $metaDescription,
        'twitter:image' => buildOpenGraphImageUrl(OPEN_GRAPH_TWITTER_IMAGE),
        'twitter:image:alt' => 'Altherneum.fr',
    ];

    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $title,
        'description' => $metaDescription,
        'url' => $url,
        'inLanguage' => OPEN_GRAPH_LANGUAGE,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => OPEN_GRAPH_SITE_NAME,
            'url' => OPEN_GRAPH_SITE_URL . '/',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Altherneum.fr',
            'url' => OPEN_GRAPH_SITE_URL . '/',
        ],
        'primaryImageOfPage' => [
            '@type' => 'ImageObject',
            'url' => $imageUrl,
        ],
    ];

    $structuredDataJson = json_encode(
        $structuredData,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
    );

    $html = '<head>' . PHP_EOL;
    $html .= '    <meta charset="UTF-8">' . PHP_EOL;
    $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1">' . PHP_EOL;
    $html .= '    <title>' . escapeOpenGraphText($title) . '</title>' . PHP_EOL;
    $html .= '    <meta name="description" content="' . escapeOpenGraphText($metaDescription) . '">' . PHP_EOL;
    $html .= '    <link rel="canonical" href="' . escapeOpenGraphText($url) . '">' . PHP_EOL;
    $html .= '    <meta name="theme-color" content="' . escapeOpenGraphText(OPEN_GRAPH_THEME_COLOR) . '">' . PHP_EOL;
    $html .= '    <link rel="icon" type="image/png" sizes="32x32" href="' . escapeOpenGraphText(OPEN_GRAPH_FAVICON_PNG) . '">' . PHP_EOL;
    $html .= '    <link rel="icon" type="image/svg+xml" href="' . escapeOpenGraphText(OPEN_GRAPH_FAVICON_SVG) . '">' . PHP_EOL;
    $html .= '    <link rel="apple-touch-icon" sizes="180x180" href="' . escapeOpenGraphText(OPEN_GRAPH_APPLE_TOUCH_ICON) . '">' . PHP_EOL;
    $html .= '    <link rel="manifest" href="' . escapeOpenGraphText(OPEN_GRAPH_MANIFEST) . '">' . PHP_EOL;

    foreach ($openGraphTags as $property => $content) {
        $html .= '    <meta property="' . escapeOpenGraphText($property) . '" content="' . escapeOpenGraphText($content) . '" />' . PHP_EOL;
    }
    foreach ($twitterTags as $name => $content) {
        $html .= '    <meta name="' . escapeOpenGraphText($name) . '" content="' . escapeOpenGraphText($content) . '" />' . PHP_EOL;
    }

    $html .= '    <script type="application/ld+json">' . $structuredDataJson . '</script>' . PHP_EOL;
    $html .= '</head>' . PHP_EOL;

    return $html;
}
