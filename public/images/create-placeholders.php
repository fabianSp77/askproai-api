<?php

// User avatar placeholders
$users = [
    'jese-leos' => ['initials' => 'JL', 'bg' => '#3B82F6'],
    'bonnie-green' => ['initials' => 'BG', 'bg' => '#10B981'],
    'joseph-mcfall' => ['initials' => 'JM', 'bg' => '#F59E0B'],
    'neil-sims' => ['initials' => 'NS', 'bg' => '#8B5CF6'],
    'lana-byrd' => ['initials' => 'LB', 'bg' => '#EC4899'],
    'thomas-lean' => ['initials' => 'TL', 'bg' => '#06B6D4'],
    'roberta-casas' => ['initials' => 'RC', 'bg' => '#F97316'],
    'robert-brown' => ['initials' => 'RB', 'bg' => '#84CC16'],
    'michael-gough' => ['initials' => 'MG', 'bg' => '#6366F1'],
    'karen-nelson' => ['initials' => 'KN', 'bg' => '#A855F7'],
    'helene-engels' => ['initials' => 'HE', 'bg' => '#14B8A6'],
    'sofia-mcguire' => ['initials' => 'SM', 'bg' => '#F43F5E']
];

// Create user avatars
foreach ($users as $name => $data) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
    <rect width="100" height="100" rx="50" fill="{$data['bg']}" />
    <text x="50" y="50" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial, sans-serif" font-size="36" font-weight="bold">{$data['initials']}</text>
</svg>
SVG;
    file_put_contents("/var/www/api-gateway/public/images/users/{$name}.png.svg", $svg);
    // Create symlink to make it work as .png
    @symlink("{$name}.png.svg", "/var/www/api-gateway/public/images/users/{$name}.png");
}

// Create product placeholders
for ($i = 1; $i <= 5; $i++) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
    <rect width="400" height="300" fill="#E5E7EB" />
    <rect x="150" y="100" width="100" height="100" fill="#9CA3AF" rx="8" />
    <text x="200" y="230" text-anchor="middle" fill="#6B7280" font-family="Arial, sans-serif" font-size="18">Product $i</text>
</svg>
SVG;
    file_put_contents("/var/www/api-gateway/public/images/feed/product-{$i}.jpg.svg", $svg);
    @symlink("product-{$i}.jpg.svg", "/var/www/api-gateway/public/images/feed/product-{$i}.jpg");
}

// Create authentication SVGs
$authSvgs = [
    'sign-in' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#F3F4F6"/>
        <rect x="150" y="100" width="200" height="200" rx="100" fill="#3B82F6" opacity="0.1"/>
        <path d="M250 150 L300 200 L250 250 L250 225 L200 225 L200 175 L250 175 Z" fill="#3B82F6"/>
        <text x="250" y="330" text-anchor="middle" fill="#6B7280" font-family="Arial, sans-serif" font-size="16">Sign In</text>
    </svg>',
    
    'sign-in-dark' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#1F2937"/>
        <rect x="150" y="100" width="200" height="200" rx="100" fill="#60A5FA" opacity="0.2"/>
        <path d="M250 150 L300 200 L250 250 L250 225 L200 225 L200 175 L250 175 Z" fill="#60A5FA"/>
        <text x="250" y="330" text-anchor="middle" fill="#9CA3AF" font-family="Arial, sans-serif" font-size="16">Sign In</text>
    </svg>',
    
    'lock-password' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#F3F4F6"/>
        <rect x="200" y="150" width="100" height="100" rx="8" fill="#3B82F6" opacity="0.1"/>
        <path d="M250 170 C235 170 225 180 225 195 L225 205 L220 205 L220 235 L280 235 L280 205 L275 205 L275 195 C275 180 265 170 250 170 Z M250 180 C260 180 265 185 265 195 L265 205 L235 205 L235 195 C235 185 240 180 250 180 Z" fill="#3B82F6"/>
        <circle cx="250" cy="220" r="5" fill="#3B82F6"/>
        <text x="250" y="330" text-anchor="middle" fill="#6B7280" font-family="Arial, sans-serif" font-size="16">Password Lock</text>
    </svg>',
    
    'lock-password-dark' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#1F2937"/>
        <rect x="200" y="150" width="100" height="100" rx="8" fill="#60A5FA" opacity="0.2"/>
        <path d="M250 170 C235 170 225 180 225 195 L225 205 L220 205 L220 235 L280 235 L280 205 L275 205 L275 195 C275 180 265 170 250 170 Z M250 180 C260 180 265 185 265 195 L265 205 L235 205 L235 195 C235 185 240 180 250 180 Z" fill="#60A5FA"/>
        <circle cx="250" cy="220" r="5" fill="#60A5FA"/>
        <text x="250" y="330" text-anchor="middle" fill="#9CA3AF" font-family="Arial, sans-serif" font-size="16">Password Lock</text>
    </svg>'
];

foreach ($authSvgs as $name => $svg) {
    file_put_contents("/var/www/api-gateway/public/images/{$name}.svg", $svg);
}

// Create customer logos
$customers = [
    'stripe' => ['text' => 'STRIPE', 'bg' => '#635BFF'],
    'spotify' => ['text' => 'SPOTIFY', 'bg' => '#1DB954'],
    'tesla' => ['text' => 'TESLA', 'bg' => '#CC0000'],
    'twitch' => ['text' => 'TWITCH', 'bg' => '#9146FF'],
    'intel' => ['text' => 'INTEL', 'bg' => '#0071C5'],
    'shell' => ['text' => 'SHELL', 'bg' => '#DD1D21'],
    'netflix' => ['text' => 'NETFLIX', 'bg' => '#E50914'],
    'nestle' => ['text' => 'NESTLE', 'bg' => '#000000'],
    'fedex' => ['text' => 'FEDEX', 'bg' => '#4D148C'],
    'disney' => ['text' => 'DISNEY', 'bg' => '#006E99'],
    'bmw' => ['text' => 'BMW', 'bg' => '#0066CC'],
    'coca-cola' => ['text' => 'COCA-COLA', 'bg' => '#F40009']
];

foreach ($customers as $name => $data) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" viewBox="0 0 200 60">
    <rect width="200" height="60" fill="{$data['bg']}" rx="8" />
    <text x="100" y="35" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">{$data['text']}</text>
</svg>
SVG;
    file_put_contents("/var/www/api-gateway/public/images/customers/{$name}.svg", $svg);
}

echo "✅ Created placeholders for:\n";
echo "- " . count($users) . " user avatars\n";
echo "- 5 product images\n";
echo "- " . count($authSvgs) . " authentication SVGs\n";
echo "- " . count($customers) . " customer logos\n";
echo "\nTotal: " . (count($users) + 5 + count($authSvgs) + count($customers)) . " placeholder images created!\n";