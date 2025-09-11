<?php

// Additional missing SVGs
$additionalSvgs = [
    'girl-and-computer' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#F3F4F6"/>
        <circle cx="250" cy="150" r="50" fill="#FEF3C7"/>
        <path d="M230 140 Q250 120 270 140" stroke="#374151" stroke-width="2" fill="none"/>
        <circle cx="235" cy="140" r="3" fill="#374151"/>
        <circle cx="265" cy="140" r="3" fill="#374151"/>
        <rect x="175" y="200" width="150" height="100" rx="8" fill="#3B82F6" opacity="0.2"/>
        <rect x="190" y="215" width="120" height="70" rx="4" fill="#1F2937"/>
        <rect x="225" y="300" width="50" height="30" fill="#6B7280"/>
        <text x="250" y="360" text-anchor="middle" fill="#6B7280" font-family="Arial, sans-serif" font-size="14">Forgot Password</text>
    </svg>',
    
    'girl-and-computer-dark' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#1F2937"/>
        <circle cx="250" cy="150" r="50" fill="#FCD34D"/>
        <path d="M230 140 Q250 120 270 140" stroke="#F3F4F6" stroke-width="2" fill="none"/>
        <circle cx="235" cy="140" r="3" fill="#F3F4F6"/>
        <circle cx="265" cy="140" r="3" fill="#F3F4F6"/>
        <rect x="175" y="200" width="150" height="100" rx="8" fill="#60A5FA" opacity="0.3"/>
        <rect x="190" y="215" width="120" height="70" rx="4" fill="#374151"/>
        <rect x="225" y="300" width="50" height="30" fill="#9CA3AF"/>
        <text x="250" y="360" text-anchor="middle" fill="#9CA3AF" font-family="Arial, sans-serif" font-size="14">Forgot Password</text>
    </svg>',
    
    'communication' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#F3F4F6"/>
        <circle cx="200" cy="200" r="40" fill="#3B82F6" opacity="0.2"/>
        <circle cx="300" cy="200" r="40" fill="#10B981" opacity="0.2"/>
        <path d="M200 200 L300 200" stroke="#6B7280" stroke-width="2" stroke-dasharray="5,5"/>
        <rect x="180" y="180" width="40" height="40" rx="4" fill="#3B82F6"/>
        <rect x="280" y="180" width="40" height="40" rx="4" fill="#10B981"/>
        <text x="250" y="280" text-anchor="middle" fill="#6B7280" font-family="Arial, sans-serif" font-size="16">Video Meeting</text>
    </svg>',
    
    'communication-dark' => '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="400" viewBox="0 0 500 400">
        <rect width="500" height="400" fill="#1F2937"/>
        <circle cx="200" cy="200" r="40" fill="#60A5FA" opacity="0.3"/>
        <circle cx="300" cy="200" r="40" fill="#34D399" opacity="0.3"/>
        <path d="M200 200 L300 200" stroke="#9CA3AF" stroke-width="2" stroke-dasharray="5,5"/>
        <rect x="180" y="180" width="40" height="40" rx="4" fill="#60A5FA"/>
        <rect x="280" y="180" width="40" height="40" rx="4" fill="#34D399"/>
        <text x="250" y="280" text-anchor="middle" fill="#9CA3AF" font-family="Arial, sans-serif" font-size="16">Video Meeting</text>
    </svg>'
];

foreach ($additionalSvgs as $name => $svg) {
    file_put_contents("/var/www/api-gateway/public/images/{$name}.svg", $svg);
    echo "Created: {$name}.svg\n";
}

// Set proper permissions
shell_exec('chown -R www-data:www-data /var/www/api-gateway/public/images/');

echo "\n✅ Created " . count($additionalSvgs) . " additional SVG placeholders!\n";