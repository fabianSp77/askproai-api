<?php

namespace App\Support;

/**
 * TooltipBuilder - Helper class for creating beautiful, structured HTML tooltips
 *
 * Features:
 * - Automatic XSS protection (htmlspecialchars)
 * - Dark mode support (Tailwind dark: classes)
 * - Mobile-responsive
 * - Consistent styling
 *
 * Usage:
 * ```php
 * $tooltip = TooltipBuilder::make()
 *     ->section('Title', 'Content')
 *     ->build();
 * ```
 */
class TooltipBuilder
{
    private array $sections = [];

    public static function make(): self
    {
        return new self();
    }

    /**
     * Add a section to the tooltip
     *
     * @param string $title Section header
     * @param string $content Section content (can be HTML from other methods)
     * @param string|null $icon Optional emoji or icon
     * @return self
     */
    public function section(string $title, string $content, ?string $icon = null): self
    {
        $this->sections[] = [
            'title' => $title,
            'content' => $content,
            'icon' => $icon,
        ];
        return $this;
    }

    /**
     * Create a colored badge
     *
     * @param string $label Badge text
     * @param string $color One of: success, error, warning, info, gray
     * @return string HTML badge
     */
    public function badge(string $label, string $color = 'gray'): string
    {
        $colors = [
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'error' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        ];

        $colorClass = $colors[$color] ?? $colors['gray'];

        return sprintf(
            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium %s">%s</span>',
            $colorClass,
            htmlspecialchars($label)
        );
    }

    /**
     * Create a bulleted list
     *
     * @param array $items List items (will be escaped)
     * @return string HTML list
     */
    public function list(array $items): string
    {
        $html = '<ul class="space-y-1 text-sm">';
        foreach ($items as $item) {
            $html .= sprintf(
                '<li class="flex items-start"><span class="text-gray-400 dark:text-gray-500 mr-2">•</span><span class="text-gray-700 dark:text-gray-300">%s</span></li>',
                htmlspecialchars($item)
            );
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Create a key-value pair display
     *
     * @param string $key Label text
     * @param string $value Value text
     * @param bool $monospace Use monospace font for value (e.g., IDs)
     * @return string HTML key-value pair
     */
    public function keyValue(string $key, string $value, bool $monospace = false): string
    {
        $valueClass = $monospace
            ? 'font-mono text-gray-900 dark:text-gray-100'
            : 'text-gray-900 dark:text-gray-100';

        return sprintf(
            '<div class="flex justify-between gap-3 text-sm"><span class="text-gray-600 dark:text-gray-400">%s</span><span class="%s">%s</span></div>',
            htmlspecialchars($key),
            $valueClass,
            htmlspecialchars($value)
        );
    }

    /**
     * Create a horizontal divider
     *
     * @return string HTML divider
     */
    public function divider(): string
    {
        return '<hr class="border-gray-200 dark:border-gray-700 my-2">';
    }

    /**
     * Create a progress bar
     *
     * @param int $percentage Percentage value (0-100)
     * @param string $color One of: success, error, warning, info, gray
     * @return string HTML progress bar
     */
    public function progressBar(int $percentage, string $color = 'info'): string
    {
        $percentage = max(0, min(100, $percentage)); // Clamp to 0-100

        $colors = [
            'success' => 'bg-green-500 dark:bg-green-600',
            'error' => 'bg-red-500 dark:bg-red-600',
            'warning' => 'bg-yellow-400 dark:bg-yellow-500',
            'info' => 'bg-blue-500 dark:bg-blue-600',
            'gray' => 'bg-gray-400 dark:bg-gray-500',
        ];

        $colorClass = $colors[$color] ?? $colors['info'];

        return sprintf(
            '<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2"><div class="%s h-2 rounded-full transition-all duration-300" style="width: %d%%"></div></div>',
            $colorClass,
            $percentage
        );
    }

    /**
     * Build the final HTML tooltip
     *
     * @return string Complete HTML tooltip
     */
    public function build(): string
    {
        if (empty($this->sections)) {
            return '<div class="p-3 text-sm text-gray-500 dark:text-gray-400">Keine Informationen verfügbar</div>';
        }

        $html = '<div class="p-3 space-y-3 max-w-md">';

        foreach ($this->sections as $index => $section) {
            // Add divider between sections (except before first section)
            if ($index > 0) {
                $html .= '<div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>';
            }

            $html .= '<div class="space-y-1.5">';

            // Section header
            if (!empty($section['title'])) {
                $html .= '<div class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">';
                if ($section['icon']) {
                    $html .= '<span>' . $section['icon'] . '</span>';
                }
                $html .= htmlspecialchars($section['title']);
                $html .= '</div>';
            }

            // Section content (already HTML from methods like list(), badge(), etc.)
            $html .= '<div class="text-sm text-gray-700 dark:text-gray-300">' . $section['content'] . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Create a simple tooltip with just text (no sections)
     *
     * @param string $text Plain text content
     * @param string|null $icon Optional emoji or icon
     * @return string HTML tooltip
     */
    public static function simple(string $text, ?string $icon = null): string
    {
        $iconHtml = $icon ? '<span class="mr-2">' . $icon . '</span>' : '';
        return sprintf(
            '<div class="p-3 text-sm text-gray-700 dark:text-gray-300">%s%s</div>',
            $iconHtml,
            htmlspecialchars($text)
        );
    }
}
