<?php

// Balises Open Graph (OG) communes à toutes les pages du site
const OPEN_GRAPH_SITE_NAME = 'doc.altherneum.fr';
const OPEN_GRAPH_SITE_URL = 'https://doc.altherneum.fr';
const OPEN_GRAPH_DEFAULT_IMAGE = '/src/assets/gif/small.gif';
const OPEN_GRAPH_LOCALE = 'fr_FR';
const OPEN_GRAPH_PROFILE_USERNAME = 'Altherneum.fr';

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

function buildOpenGraphImageUrl(string $image): string
{
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $image;
    }

    return OPEN_GRAPH_SITE_URL . '/' . ltrim($image, '/');
}

function renderOpenGraph(string $title, string $route, string $description, string $image = OPEN_GRAPH_DEFAULT_IMAGE): string
{
    $path = buildOpenGraphRoute($route);
    $openGraphTitle = OPEN_GRAPH_SITE_NAME . $path;
    $url = OPEN_GRAPH_SITE_URL . $path;
    $imageUrl = buildOpenGraphImageUrl($image);

    $tags = [
        'og:determiner' => '📰',
        'og:title' => $openGraphTitle,
        'og:type' => 'siteweb',
        'og:image' => $imageUrl,
        'og:image:type' => 'image',
        'og:image:alt' => 'logo',
        'og:url' => $url,
        'og:locale' => OPEN_GRAPH_LOCALE,
        'og:site_name' => OPEN_GRAPH_SITE_NAME,
        'profile:username' => OPEN_GRAPH_PROFILE_USERNAME,
        'og:description' => $description,
    ];

    $html = '<head>' . PHP_EOL;
    $html .= '    <meta charset="UTF-8">' . PHP_EOL;
    $html .= '    <title>' . escapeOpenGraphText($title) . '</title>' . PHP_EOL;
    $html .= '    <meta name="description" content="' . escapeOpenGraphText($description) . '">' . PHP_EOL;
    foreach ($tags as $property => $content) {
        $html .= '    <meta property="' . escapeOpenGraphText($property) . '" content="' . escapeOpenGraphText($content) . '" />' . PHP_EOL;
    }

    $twitterTags = [
        'twitter:card' => 'summary',
        'twitter:title' => $title,
        'twitter:description' => $description,
        'twitter:image' => $imageUrl,
    ];
    foreach ($twitterTags as $name => $content) {
        $html .= '    <meta name="' . escapeOpenGraphText($name) . '" content="' . escapeOpenGraphText($content) . '" />' . PHP_EOL;
    }
    $html .= '</head>' . PHP_EOL;

    return $html;
}
