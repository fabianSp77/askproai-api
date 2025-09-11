# 🎨 Flowbite Pro Integration Guide for AskProAI

## 📁 Installation Steps

### Step 1: Upload Flowbite Pro Files
Please upload the Flowbite Pro files from your Google Drive to the following locations:

```bash
# Main Flowbite Pro files
/var/www/api-gateway/resources/flowbite-pro/

# Structure should be:
├── components/     # Pro UI components
├── layouts/        # Layout templates
├── pages/          # Page templates
├── widgets/        # Dashboard widgets
├── js/            # JavaScript files
└── css/           # CSS files
```

### Step 2: Package Configuration

```bash
# Update package.json to include Flowbite Pro
npm install flowbite --save

# If you have the Pro NPM package:
# npm install @flowbite-pro/flowbite --save
```

### Step 3: Tailwind Configuration Update

```javascript
// tailwind.config.js
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/flowbite-pro/**/*.{html,js}', // Add Flowbite Pro
        './node_modules/flowbite/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // AskProAI brand colors
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
        },
    },
    plugins: [
        require('flowbite/plugin')({
            charts: true,
            forms: true,
            tooltips: true,
        }),
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
```

### Step 4: Vite Configuration

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/flowbite-pro/js/flowbite-pro.js', // Add this
                'resources/flowbite-pro/css/flowbite-pro.css', // Add this
            ],
            refresh: true,
        }),
    ],
});
```

### Step 5: Blade Components Integration

Create wrapper components for Flowbite Pro in Filament:

```php
// app/View/Components/FlowbitePro/DataTable.php
namespace App\View\Components\FlowbitePro;

use Illuminate\View\Component;

class DataTable extends Component
{
    public function __construct(
        public array $headers = [],
        public array $data = [],
        public bool $sortable = true,
        public bool $searchable = true
    ) {}

    public function render()
    {
        return view('flowbite-pro.components.data-table');
    }
}
```

### Step 6: Filament Integration

```php
// app/Filament/Widgets/FlowbiteProChart.php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FlowbiteProChart extends Widget
{
    protected static string $view = 'filament.widgets.flowbite-pro-chart';
    
    protected function getViewData(): array
    {
        return [
            'chartData' => $this->getChartData(),
        ];
    }
}
```

## 🎯 Pro Components to Integrate

### Priority 1: Enhanced Tables
- Advanced filtering
- Column sorting
- Export functionality
- Bulk actions

### Priority 2: Analytics Dashboard
- Revenue charts
- User analytics
- Performance metrics
- Real-time updates

### Priority 3: Enhanced Forms
- Multi-step forms
- File upload with preview
- Advanced validation
- Auto-save functionality

### Priority 4: Marketing Components
- Landing pages
- Pricing tables
- Testimonials
- CTAs

## 🔧 Custom Integration Points

### For Retell.AI Dashboard:
```blade
<!-- resources/views/admin/retell-dashboard.blade.php -->
@extends('flowbite-pro.layouts.dashboard')

@section('content')
    <x-flowbite-pro.stats-card 
        title="Total Calls"
        value="{{ $totalCalls }}"
        trend="+12%"
        icon="phone"
    />
    
    <x-flowbite-pro.chart 
        type="line"
        :data="$callsPerDay"
        title="Call Volume"
    />
@endsection
```

### For Cal.com Integration:
```blade
<!-- resources/views/admin/calendar-view.blade.php -->
@extends('flowbite-pro.layouts.app')

@section('content')
    <x-flowbite-pro.calendar 
        :events="$appointments"
        :staff="$staff"
        view="month"
    />
@endsection
```

## 📊 Performance Optimization

### Asset Loading Strategy:
```javascript
// Lazy load Pro components
import('flowbite-pro/components/charts').then(module => {
    window.FlowbiteCharts = module.default;
});

// Conditional loading based on page
if (document.querySelector('[data-flowbite-datatable]')) {
    import('flowbite-pro/components/datatable');
}
```

## 🎨 Theme Customization

### Dark Mode Support:
```css
/* resources/css/flowbite-overrides.css */
.dark .flowbite-card {
    @apply bg-gray-800 border-gray-700;
}

.dark .flowbite-table {
    @apply bg-gray-900 text-gray-100;
}
```

## 🚀 Deployment Checklist

- [ ] Upload Flowbite Pro files
- [ ] Update package.json
- [ ] Configure Tailwind
- [ ] Update Vite config
- [ ] Build assets: `npm run build`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Test components in staging
- [ ] Document custom components
- [ ] Train team on new components

## 📝 License Information

Ensure you have a valid Flowbite Pro license for:
- Development environments: ✓
- Staging environments: ✓
- Production environments: ✓
- Client projects: Check license terms

## 🔗 Useful Resources

- [Flowbite Pro Documentation](https://flowbite.com/pro/docs/)
- [Figma Design Files](https://flowbite.com/figma/)
- [Component Examples](https://flowbite.com/blocks/)
- [Support](https://flowbite.com/support/)

---
Generated: September 2025