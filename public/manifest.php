<?php
// This endpoint is independent of authentication and database availability.
header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'id' => '/pikvero/',
    'name' => 'Pikvero',
    'short_name' => 'Pikvero',
    'description' => 'Discover courts, book a game, and join Open Play.',
    'start_url' => '/pikvero/public/customer/dashboard.php',
    'scope' => '/pikvero/',
    'display' => 'standalone',
    'background_color' => '#f8faf8',
    'theme_color' => '#003d2d',
    'icons' => [
        ['src'=>'/pikvero/assets/images/pwa/icon-192.png', 'sizes'=>'192x192', 'type'=>'image/png', 'purpose'=>'any'],
        ['src'=>'/pikvero/assets/images/pwa/icon-512.png', 'sizes'=>'512x512', 'type'=>'image/png', 'purpose'=>'any'],
    ],
], JSON_UNESCAPED_SLASHES);
