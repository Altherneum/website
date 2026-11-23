<?php

const SITEMAP_BASE_URL = 'https://doc.altherneum.fr';
const SITEMAP_EXCLUDED_ROUTES = [
    '404',
    'discord/callback',
];

function collectSitemapEntries(string $pagesDirectory, string $homePageFile): array
{
    $entries = [];
    if (is_file($homePageFile)) {
        $entries[''] = filemtime($homePageFile);
    }

    $directoryIterator = new RecursiveDirectoryIterator($pagesDirectory, FilesystemIterator::SKIP_DOTS);
    $fileIterator = new RecursiveIteratorIterator($directoryIterator);
    foreach ($fileIterator as $file) {
        if (!$file->isFile() || $file->getFilename() !== 'index.php') {
            continue;
        }

        $relativeDirectory = str_replace('\\', '/', substr($file->getPath(), strlen($pagesDirectory)));
        $route = trim($relativeDirectory, '/');
        if ($route === '' || in_array($route, SITEMAP_EXCLUDED_ROUTES, true)) {
            continue;
        }

        $entries[$route] = $file->getMTime();
    }

    ksort($entries);

    return $entries;
}

function renderSitemap(string $pagesDirectory, string $homePageFile): string
{
    $entries = collectSitemapEntries($pagesDirectory, $homePageFile);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    foreach ($entries as $route => $lastModified) {
        $url = SITEMAP_BASE_URL . '/';
        if ($route !== '') {
            $url .= $route;
        }

        $xml .= '    <url>' . PHP_EOL;
        $xml .= '        <loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>' . PHP_EOL;
        $xml .= '        <lastmod>' . date('Y-m-d', $lastModified) . '</lastmod>' . PHP_EOL;
        $xml .= '    </url>' . PHP_EOL;
    }
    $xml .= '</urlset>' . PHP_EOL;

    return $xml;
}
