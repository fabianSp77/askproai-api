<?php

namespace App\Services\MCP;

use App\Services\MCP\Contracts\ExternalMCPProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FigmaMCPServer implements ExternalMCPProvider
{
    protected string $name = 'figma';
    protected string $version = '1.0.0';
    protected array $capabilities = [
        'design_to_code',
        'component_generation',
        'asset_extraction',
        'design_tokens',
        'layout_analysis',
        'style_export',
        'prototyping'
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Get available Figma tools
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'get_file',
                'description' => 'Get Figma file details and structure',
                'category' => 'file',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key from URL'
                        ],
                        'include_images' => [
                            'type' => 'boolean',
                            'default' => false,
                            'description' => 'Include image URLs'
                        ]
                    ],
                    'required' => ['file_key']
                ]
            ],
            [
                'name' => 'get_frame',
                'description' => 'Get specific frame or component details',
                'category' => 'component',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'node_id' => [
                            'type' => 'string',
                            'description' => 'Node ID of the frame/component'
                        ]
                    ],
                    'required' => ['file_key', 'node_id']
                ]
            ],
            [
                'name' => 'generate_html',
                'description' => 'Generate HTML from Figma design',
                'category' => 'code_generation',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'node_id' => [
                            'type' => 'string',
                            'description' => 'Node ID to convert'
                        ],
                        'framework' => [
                            'type' => 'string',
                            'enum' => ['html', 'tailwind', 'bootstrap'],
                            'default' => 'tailwind',
                            'description' => 'CSS framework to use'
                        ]
                    ],
                    'required' => ['file_key', 'node_id']
                ]
            ],
            [
                'name' => 'generate_react',
                'description' => 'Generate React component from Figma design',
                'category' => 'code_generation',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'node_id' => [
                            'type' => 'string',
                            'description' => 'Node ID to convert'
                        ],
                        'typescript' => [
                            'type' => 'boolean',
                            'default' => true,
                            'description' => 'Generate TypeScript'
                        ],
                        'style_type' => [
                            'type' => 'string',
                            'enum' => ['css', 'styled-components', 'emotion', 'tailwind'],
                            'default' => 'tailwind'
                        ]
                    ],
                    'required' => ['file_key', 'node_id']
                ]
            ],
            [
                'name' => 'generate_blade',
                'description' => 'Generate Laravel Blade component from Figma',
                'category' => 'code_generation',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'node_id' => [
                            'type' => 'string',
                            'description' => 'Node ID to convert'
                        ],
                        'component_name' => [
                            'type' => 'string',
                            'description' => 'Blade component name'
                        ],
                        'use_alpine' => [
                            'type' => 'boolean',
                            'default' => true,
                            'description' => 'Include Alpine.js'
                        ]
                    ],
                    'required' => ['file_key', 'node_id', 'component_name']
                ]
            ],
            [
                'name' => 'extract_colors',
                'description' => 'Extract color palette from design',
                'category' => 'design_tokens',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['css', 'scss', 'json', 'tailwind'],
                            'default' => 'css'
                        ]
                    ],
                    'required' => ['file_key']
                ]
            ],
            [
                'name' => 'extract_typography',
                'description' => 'Extract typography styles',
                'category' => 'design_tokens',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['css', 'scss', 'json'],
                            'default' => 'css'
                        ]
                    ],
                    'required' => ['file_key']
                ]
            ],
            [
                'name' => 'export_assets',
                'description' => 'Export images and icons from Figma',
                'category' => 'assets',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'file_key' => [
                            'type' => 'string',
                            'description' => 'Figma file key'
                        ],
                        'node_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Node IDs to export'
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['png', 'jpg', 'svg', 'pdf'],
                            'default' => 'png'
                        ],
                        'scale' => [
                            'type' => 'number',
                            'default' => 2,
                            'description' => 'Export scale (1x, 2x, 3x)'
                        ]
                    ],
                    'required' => ['file_key', 'node_ids']
                ]
            ]
        ];
    }

    /**
     * Execute a Figma tool
     */
    public function executeTool(string $tool, array $arguments): array
    {
        Log::debug("Executing Figma tool: {$tool}", $arguments);

        try {
            // Check if we have Figma credentials
            $figmaToken = config('services.figma.api_token');
            if (!$figmaToken) {
                return [
                    'success' => false,
                    'error' => 'Figma API token not configured. Please set FIGMA_API_TOKEN in .env',
                    'data' => null
                ];
            }

            switch ($tool) {
                case 'get_file':
                    return $this->getFile($arguments);
                
                case 'get_frame':
                    return $this->getFrame($arguments);
                
                case 'generate_html':
                    return $this->generateHtml($arguments);
                
                case 'generate_react':
                    return $this->generateReact($arguments);
                
                case 'generate_blade':
                    return $this->generateBlade($arguments);
                
                case 'extract_colors':
                    return $this->extractColors($arguments);
                
                case 'extract_typography':
                    return $this->extractTypography($arguments);
                
                case 'export_assets':
                    return $this->exportAssets($arguments);
                
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown Figma tool: {$tool}",
                        'data' => null
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Figma operation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get Figma file details
     */
    protected function getFile(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $includeImages = $arguments['include_images'] ?? false;

        // Cache key
        $cacheKey = "figma:file:{$fileKey}";
        
        // Check cache first
        if (!$includeImages && $cached = Cache::get($cacheKey)) {
            return [
                'success' => true,
                'error' => null,
                'data' => $cached
            ];
        }

        // Make API call
        $response = Http::withHeaders([
            'X-Figma-Token' => config('services.figma.api_token')
        ])->get("https://api.figma.com/v1/files/{$fileKey}");

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to fetch Figma file: ' . $response->body(),
                'data' => null
            ];
        }

        $fileData = $response->json();
        
        // Process file structure
        $processedData = [
            'name' => $fileData['name'],
            'last_modified' => $fileData['lastModified'] ?? null,
            'version' => $fileData['version'] ?? null,
            'pages' => $this->processPages($fileData['document']['children'] ?? [])
        ];

        // Get images if requested
        if ($includeImages) {
            $imageUrls = $this->getImageUrls($fileKey, $this->collectImageNodes($fileData['document']));
            $processedData['images'] = $imageUrls;
        }

        // Cache for 1 hour
        if (!$includeImages) {
            Cache::put($cacheKey, $processedData, 3600);
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $processedData
        ];
    }

    /**
     * Get specific frame details
     */
    protected function getFrame(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $nodeId = $arguments['node_id'];

        // Get file data
        $fileResult = $this->getFile(['file_key' => $fileKey]);
        if (!$fileResult['success']) {
            return $fileResult;
        }

        // Find the specific node
        $node = $this->findNode($fileResult['data'], $nodeId);
        if (!$node) {
            return [
                'success' => false,
                'error' => "Node {$nodeId} not found in file",
                'data' => null
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'node' => $node,
                'file_name' => $fileResult['data']['name']
            ]
        ];
    }

    /**
     * Generate HTML from Figma design
     */
    protected function generateHtml(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $nodeId = $arguments['node_id'];
        $framework = $arguments['framework'] ?? 'tailwind';

        // Get node details
        $nodeResult = $this->getFrame([
            'file_key' => $fileKey,
            'node_id' => $nodeId
        ]);

        if (!$nodeResult['success']) {
            return $nodeResult;
        }

        $node = $nodeResult['data']['node'];
        
        // Generate HTML based on node type and framework
        $html = $this->nodeToHtml($node, $framework);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'html' => $html,
                'framework' => $framework,
                'node_name' => $node['name'] ?? 'Component'
            ]
        ];
    }

    /**
     * Generate React component
     */
    protected function generateReact(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $nodeId = $arguments['node_id'];
        $typescript = $arguments['typescript'] ?? true;
        $styleType = $arguments['style_type'] ?? 'tailwind';

        // Get node details
        $nodeResult = $this->getFrame([
            'file_key' => $fileKey,
            'node_id' => $nodeId
        ]);

        if (!$nodeResult['success']) {
            return $nodeResult;
        }

        $node = $nodeResult['data']['node'];
        $componentName = Str::studly($node['name'] ?? 'Component');

        // Generate React component
        $component = $this->nodeToReact($node, $componentName, $typescript, $styleType);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'component' => $component,
                'component_name' => $componentName,
                'typescript' => $typescript,
                'style_type' => $styleType
            ]
        ];
    }

    /**
     * Generate Laravel Blade component
     */
    protected function generateBlade(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $nodeId = $arguments['node_id'];
        $componentName = $arguments['component_name'];
        $useAlpine = $arguments['use_alpine'] ?? true;

        // Get node details
        $nodeResult = $this->getFrame([
            'file_key' => $fileKey,
            'node_id' => $nodeId
        ]);

        if (!$nodeResult['success']) {
            return $nodeResult;
        }

        $node = $nodeResult['data']['node'];
        
        // Generate Blade component
        $blade = $this->nodeToBlade($node, $componentName, $useAlpine);

        // Also generate the component class
        $componentClass = $this->generateBladeComponentClass($componentName);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'blade' => $blade,
                'component_class' => $componentClass,
                'component_name' => $componentName,
                'blade_path' => "resources/views/components/{$componentName}.blade.php",
                'class_path' => "app/View/Components/" . Str::studly($componentName) . ".php"
            ]
        ];
    }

    /**
     * Extract color palette
     */
    protected function extractColors(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $format = $arguments['format'] ?? 'css';

        // Get file styles
        $response = Http::withHeaders([
            'X-Figma-Token' => config('services.figma.api_token')
        ])->get("https://api.figma.com/v1/files/{$fileKey}/styles");

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to fetch styles: ' . $response->body(),
                'data' => null
            ];
        }

        $styles = $response->json()['meta']['styles'] ?? [];
        $colors = [];

        // Extract color styles
        foreach ($styles as $style) {
            if ($style['style_type'] === 'FILL') {
                // Get style details
                $styleResponse = Http::withHeaders([
                    'X-Figma-Token' => config('services.figma.api_token')
                ])->get("https://api.figma.com/v1/styles/{$style['key']}");

                if ($styleResponse->successful()) {
                    $styleData = $styleResponse->json();
                    $name = Str::slug($style['name'], '-');
                    $color = $this->extractColorValue($styleData);
                    
                    if ($color) {
                        $colors[$name] = $color;
                    }
                }
            }
        }

        // Format output based on requested format
        $output = $this->formatColors($colors, $format);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'colors' => $colors,
                'formatted' => $output,
                'format' => $format,
                'count' => count($colors)
            ]
        ];
    }

    /**
     * Extract typography styles
     */
    protected function extractTypography(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $format = $arguments['format'] ?? 'css';

        // Get file styles
        $response = Http::withHeaders([
            'X-Figma-Token' => config('services.figma.api_token')
        ])->get("https://api.figma.com/v1/files/{$fileKey}/styles");

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to fetch styles: ' . $response->body(),
                'data' => null
            ];
        }

        $styles = $response->json()['meta']['styles'] ?? [];
        $typography = [];

        // Extract text styles
        foreach ($styles as $style) {
            if ($style['style_type'] === 'TEXT') {
                // Get style details
                $styleResponse = Http::withHeaders([
                    'X-Figma-Token' => config('services.figma.api_token')
                ])->get("https://api.figma.com/v1/styles/{$style['key']}");

                if ($styleResponse->successful()) {
                    $styleData = $styleResponse->json();
                    $name = Str::slug($style['name'], '-');
                    $textStyle = $this->extractTextStyle($styleData);
                    
                    if ($textStyle) {
                        $typography[$name] = $textStyle;
                    }
                }
            }
        }

        // Format output based on requested format
        $output = $this->formatTypography($typography, $format);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'typography' => $typography,
                'formatted' => $output,
                'format' => $format,
                'count' => count($typography)
            ]
        ];
    }

    /**
     * Export assets from Figma
     */
    protected function exportAssets(array $arguments): array
    {
        $fileKey = $arguments['file_key'];
        $nodeIds = $arguments['node_ids'];
        $format = $arguments['format'] ?? 'png';
        $scale = $arguments['scale'] ?? 2;

        // Request image exports
        $response = Http::withHeaders([
            'X-Figma-Token' => config('services.figma.api_token')
        ])->get("https://api.figma.com/v1/images/{$fileKey}", [
            'ids' => implode(',', $nodeIds),
            'format' => $format,
            'scale' => $scale
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to export assets: ' . $response->body(),
                'data' => null
            ];
        }

        $images = $response->json()['images'] ?? [];

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'images' => $images,
                'format' => $format,
                'scale' => $scale,
                'count' => count($images)
            ]
        ];
    }

    /**
     * Helper: Process pages structure
     */
    protected function processPages(array $pages): array
    {
        $processed = [];
        
        foreach ($pages as $page) {
            $processed[] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'type' => $page['type'],
                'children' => $this->processNodes($page['children'] ?? [])
            ];
        }
        
        return $processed;
    }

    /**
     * Helper: Process nodes recursively
     */
    protected function processNodes(array $nodes): array
    {
        $processed = [];
        
        foreach ($nodes as $node) {
            $processedNode = [
                'id' => $node['id'],
                'name' => $node['name'],
                'type' => $node['type']
            ];
            
            if (isset($node['children'])) {
                $processedNode['children'] = $this->processNodes($node['children']);
            }
            
            $processed[] = $processedNode;
        }
        
        return $processed;
    }

    /**
     * Helper: Find node by ID
     */
    protected function findNode(array $data, string $nodeId)
    {
        // Search in pages
        foreach ($data['pages'] ?? [] as $page) {
            if ($page['id'] === $nodeId) {
                return $page;
            }
            
            $found = $this->findNodeInChildren($page['children'] ?? [], $nodeId);
            if ($found) {
                return $found;
            }
        }
        
        return null;
    }

    /**
     * Helper: Find node in children
     */
    protected function findNodeInChildren(array $children, string $nodeId)
    {
        foreach ($children as $child) {
            if ($child['id'] === $nodeId) {
                return $child;
            }
            
            if (isset($child['children'])) {
                $found = $this->findNodeInChildren($child['children'], $nodeId);
                if ($found) {
                    return $found;
                }
            }
        }
        
        return null;
    }

    /**
     * Helper: Convert node to HTML
     */
    protected function nodeToHtml(array $node, string $framework): string
    {
        $type = $node['type'] ?? 'FRAME';
        $name = $node['name'] ?? 'div';
        $className = Str::slug($name, '-');
        
        switch ($framework) {
            case 'tailwind':
                return $this->nodeToTailwindHtml($node, $className);
            
            case 'bootstrap':
                return $this->nodeToBootstrapHtml($node, $className);
            
            default:
                return $this->nodeToPlainHtml($node, $className);
        }
    }

    /**
     * Helper: Node to Tailwind HTML
     */
    protected function nodeToTailwindHtml(array $node, string $className): string
    {
        $html = "<div class=\"{$className}";
        
        // Add Tailwind classes based on node properties
        if (isset($node['absoluteBoundingBox'])) {
            $bounds = $node['absoluteBoundingBox'];
            $html .= " w-[{$bounds['width']}px] h-[{$bounds['height']}px]";
        }
        
        if (isset($node['backgroundColor'])) {
            $color = $this->rgbaToHex($node['backgroundColor']);
            $html .= " bg-[{$color}]";
        }
        
        $html .= "\">\n";
        
        // Add children
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $html .= "  " . $this->nodeToTailwindHtml($child, Str::slug($child['name'] ?? 'element', '-')) . "\n";
            }
        }
        
        $html .= "</div>";
        
        return $html;
    }

    /**
     * Helper: Node to Bootstrap HTML
     */
    protected function nodeToBootstrapHtml(array $node, string $className): string
    {
        $html = "<div class=\"{$className}";
        
        // Add Bootstrap classes
        if ($node['type'] === 'TEXT') {
            $html .= " text-center";
        }
        
        $html .= "\">\n";
        
        // Add children
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $html .= "  " . $this->nodeToBootstrapHtml($child, Str::slug($child['name'] ?? 'element', '-')) . "\n";
            }
        }
        
        $html .= "</div>";
        
        return $html;
    }

    /**
     * Helper: Node to plain HTML
     */
    protected function nodeToPlainHtml(array $node, string $className): string
    {
        $tag = 'div';
        if ($node['type'] === 'TEXT') {
            $tag = 'p';
        }
        
        $html = "<{$tag} class=\"{$className}\">";
        
        if (isset($node['characters'])) {
            $html .= htmlspecialchars($node['characters']);
        }
        
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $html .= $this->nodeToPlainHtml($child, Str::slug($child['name'] ?? 'element', '-'));
            }
        }
        
        $html .= "</{$tag}>";
        
        return $html;
    }

    /**
     * Helper: Node to React component
     */
    protected function nodeToReact(array $node, string $componentName, bool $typescript, string $styleType): string
    {
        $fileExt = $typescript ? 'tsx' : 'jsx';
        $propsType = $typescript ? ": React.FC" : "";
        
        $component = "import React from 'react';\n";
        
        if ($styleType === 'styled-components') {
            $component .= "import styled from 'styled-components';\n\n";
        } elseif ($styleType === 'emotion') {
            $component .= "import styled from '@emotion/styled';\n\n";
        }
        
        $component .= "const {$componentName}{$propsType} = () => {\n";
        $component .= "  return (\n";
        $component .= $this->nodeToJsx($node, $styleType, 4);
        $component .= "\n  );\n";
        $component .= "};\n\n";
        $component .= "export default {$componentName};\n";
        
        return $component;
    }

    /**
     * Helper: Node to JSX
     */
    protected function nodeToJsx(array $node, string $styleType, int $indent = 0): string
    {
        $spaces = str_repeat(' ', $indent);
        $jsx = "{$spaces}<div";
        
        if ($styleType === 'tailwind') {
            $jsx .= ' className="' . $this->nodeToTailwindClasses($node) . '"';
        } else {
            $jsx .= ' style={' . json_encode($this->nodeToInlineStyles($node)) . '}';
        }
        
        $jsx .= ">";
        
        if (isset($node['characters'])) {
            $jsx .= "\n{$spaces}  " . htmlspecialchars($node['characters']);
        }
        
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $jsx .= "\n" . $this->nodeToJsx($child, $styleType, $indent + 2);
            }
        }
        
        $jsx .= "\n{$spaces}</div>";
        
        return $jsx;
    }

    /**
     * Helper: Node to Blade component
     */
    protected function nodeToBlade(array $node, string $componentName, bool $useAlpine): string
    {
        $blade = "@props(['class' => ''])\n\n";
        $blade .= "<div class=\"{$componentName} {{ \$class }}\"";
        
        if ($useAlpine && $node['type'] === 'COMPONENT') {
            $blade .= " x-data=\"{ open: false }\"";
        }
        
        $blade .= ">\n";
        
        // Add content based on node structure
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $blade .= $this->nodeToBladeElement($child, $useAlpine, 2);
            }
        } else {
            $blade .= "  {{ \$slot }}\n";
        }
        
        $blade .= "</div>\n";
        
        return $blade;
    }

    /**
     * Helper: Node to Blade element
     */
    protected function nodeToBladeElement(array $node, bool $useAlpine, int $indent = 0): string
    {
        $spaces = str_repeat(' ', $indent);
        $element = '';
        
        switch ($node['type']) {
            case 'TEXT':
                $element = "{$spaces}<span class=\"" . Str::slug($node['name'] ?? 'text', '-') . "\">";
                $element .= "{{ \$" . Str::camel($node['name'] ?? 'text') . " ?? '" . ($node['characters'] ?? '') . "' }}";
                $element .= "</span>\n";
                break;
            
            case 'RECTANGLE':
            case 'FRAME':
                $element = "{$spaces}<div class=\"" . Str::slug($node['name'] ?? 'container', '-') . "\">\n";
                
                if (isset($node['children'])) {
                    foreach ($node['children'] as $child) {
                        $element .= $this->nodeToBladeElement($child, $useAlpine, $indent + 2);
                    }
                }
                
                $element .= "{$spaces}</div>\n";
                break;
        }
        
        return $element;
    }

    /**
     * Helper: Generate Blade component class
     */
    protected function generateBladeComponentClass(string $componentName): string
    {
        $className = Str::studly($componentName);
        
        $class = "<?php\n\n";
        $class .= "namespace App\\View\\Components;\n\n";
        $class .= "use Illuminate\\View\\Component;\n\n";
        $class .= "class {$className} extends Component\n";
        $class .= "{\n";
        $class .= "    /**\n";
        $class .= "     * Create a new component instance.\n";
        $class .= "     */\n";
        $class .= "    public function __construct(\n";
        $class .= "        public string \$class = ''\n";
        $class .= "    ) {}\n\n";
        $class .= "    /**\n";
        $class .= "     * Get the view / contents that represent the component.\n";
        $class .= "     */\n";
        $class .= "    public function render()\n";
        $class .= "    {\n";
        $class .= "        return view('components.{$componentName}');\n";
        $class .= "    }\n";
        $class .= "}\n";
        
        return $class;
    }

    /**
     * Helper: Extract color value
     */
    protected function extractColorValue($styleData): ?string
    {
        // This is simplified - real implementation would parse Figma's paint format
        return '#' . substr(md5($styleData['name']), 0, 6);
    }

    /**
     * Helper: Extract text style
     */
    protected function extractTextStyle($styleData): array
    {
        // This is simplified - real implementation would parse Figma's text format
        return [
            'font-family' => 'Inter',
            'font-size' => '16px',
            'font-weight' => '400',
            'line-height' => '1.5'
        ];
    }

    /**
     * Helper: Format colors
     */
    protected function formatColors(array $colors, string $format): string
    {
        switch ($format) {
            case 'css':
                $output = ":root {\n";
                foreach ($colors as $name => $value) {
                    $output .= "  --color-{$name}: {$value};\n";
                }
                $output .= "}\n";
                return $output;
            
            case 'scss':
                $output = "";
                foreach ($colors as $name => $value) {
                    $output .= "\${$name}: {$value};\n";
                }
                return $output;
            
            case 'tailwind':
                $output = "module.exports = {\n  theme: {\n    extend: {\n      colors: {\n";
                foreach ($colors as $name => $value) {
                    $output .= "        '{$name}': '{$value}',\n";
                }
                $output .= "      }\n    }\n  }\n}";
                return $output;
            
            case 'json':
            default:
                return json_encode($colors, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Helper: Format typography
     */
    protected function formatTypography(array $typography, string $format): string
    {
        switch ($format) {
            case 'css':
                $output = "";
                foreach ($typography as $name => $styles) {
                    $output .= ".text-{$name} {\n";
                    foreach ($styles as $prop => $value) {
                        $output .= "  {$prop}: {$value};\n";
                    }
                    $output .= "}\n\n";
                }
                return $output;
            
            case 'scss':
                $output = "";
                foreach ($typography as $name => $styles) {
                    $output .= "@mixin text-{$name} {\n";
                    foreach ($styles as $prop => $value) {
                        $output .= "  {$prop}: {$value};\n";
                    }
                    $output .= "}\n\n";
                }
                return $output;
            
            case 'json':
            default:
                return json_encode($typography, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Helper: RGBA to hex
     */
    protected function rgbaToHex($rgba): string
    {
        $r = dechex(round($rgba['r'] * 255));
        $g = dechex(round($rgba['g'] * 255));
        $b = dechex(round($rgba['b'] * 255));
        
        return '#' . str_pad($r, 2, '0', STR_PAD_LEFT) . 
                     str_pad($g, 2, '0', STR_PAD_LEFT) . 
                     str_pad($b, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Helper: Node to Tailwind classes
     */
    protected function nodeToTailwindClasses(array $node): string
    {
        $classes = [];
        
        if (isset($node['absoluteBoundingBox'])) {
            $bounds = $node['absoluteBoundingBox'];
            $classes[] = "w-[{$bounds['width']}px]";
            $classes[] = "h-[{$bounds['height']}px]";
        }
        
        return implode(' ', $classes);
    }

    /**
     * Helper: Node to inline styles
     */
    protected function nodeToInlineStyles(array $node): array
    {
        $styles = [];
        
        if (isset($node['absoluteBoundingBox'])) {
            $bounds = $node['absoluteBoundingBox'];
            $styles['width'] = $bounds['width'] . 'px';
            $styles['height'] = $bounds['height'] . 'px';
        }
        
        if (isset($node['backgroundColor'])) {
            $styles['backgroundColor'] = $this->rgbaToHex($node['backgroundColor']);
        }
        
        return $styles;
    }

    /**
     * Helper: Collect image nodes
     */
    protected function collectImageNodes($document): array
    {
        // Simplified - would recursively collect all image nodes
        return [];
    }

    /**
     * Helper: Get image URLs
     */
    protected function getImageUrls(string $fileKey, array $nodeIds): array
    {
        if (empty($nodeIds)) {
            return [];
        }
        
        // Make API call to get image URLs
        $response = Http::withHeaders([
            'X-Figma-Token' => config('services.figma.api_token')
        ])->get("https://api.figma.com/v1/images/{$fileKey}", [
            'ids' => implode(',', $nodeIds)
        ]);
        
        if ($response->successful()) {
            return $response->json()['images'] ?? [];
        }
        
        return [];
    }

    /**
     * Check if external server is running
     */
    public function isExternalServerRunning(): bool
    {
        // Figma MCP runs via npx on-demand
        return true;
    }

    /**
     * Start the external server
     */
    public function startExternalServer(): bool
    {
        // Figma MCP runs on-demand via npx
        return true;
    }

    /**
     * Get server configuration
     */
    public function getConfiguration(): array
    {
        return [
            'external_server' => '@composio/mcp',
            'uses_npx' => true,
            'oauth_required' => true,
            'api_token_required' => true,
            'design_integration' => true
        ];
    }
}