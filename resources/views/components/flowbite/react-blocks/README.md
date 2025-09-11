# Flowbite React to Alpine.js Conversion Guide

This directory contains Alpine.js-powered Blade components converted from Flowbite React blocks. Each component maintains the same functionality and styling while leveraging Alpine.js for state management instead of React hooks.

## 📁 Directory Structure

```
flowbite/react-blocks/
├── application-ui/
│   ├── advanced-user-management-table.blade.php
│   └── ...
├── marketing-ui/
│   ├── login-form-with-description.blade.php
│   ├── pricing-table-toggle.blade.php
│   └── ...
├── ecommerce-ui/
│   └── ...
├── publisher-ui/
│   └── ...
└── README.md (this file)
```

## 🔄 Conversion Patterns

### React → Alpine.js State Management

#### Basic State
**React (useState):**
```jsx
const [isOpen, setIsOpen] = useState(false);
```

**Alpine.js:**
```html
<div x-data="{ isOpen: false }">
```

#### Complex State Objects
**React:**
```jsx
const [formData, setFormData] = useState({
    email: '',
    password: '',
    remember: false
});
```

**Alpine.js:**
```html
<div x-data="{
    formData: {
        email: '',
        password: '',
        remember: false
    }
}">
```

#### Computed Values
**React:**
```jsx
const filteredItems = useMemo(() => 
    items.filter(item => item.name.includes(search)), [items, search]
);
```

**Alpine.js:**
```html
<div x-data="{
    items: [],
    search: '',
    get filteredItems() {
        return this.items.filter(item => 
            item.name.includes(this.search)
        );
    }
}">
```

### Event Handling

#### Click Events
**React:**
```jsx
<button onClick={() => setCount(count + 1)}>
```

**Alpine.js:**
```html
<button @click="count++">
```

#### Form Submission
**React:**
```jsx
<form onSubmit={handleSubmit}>
```

**Alpine.js:**
```html
<form @submit.prevent="handleSubmit()">
```

#### Input Binding
**React:**
```jsx
<input 
    value={email} 
    onChange={(e) => setEmail(e.target.value)}
/>
```

**Alpine.js:**
```html
<input x-model="email" />
```

### Conditional Rendering

#### Show/Hide Elements
**React:**
```jsx
{isVisible && <div>Content</div>}
```

**Alpine.js:**
```html
<div x-show="isVisible">Content</div>
```

#### Dynamic Classes
**React:**
```jsx
<div className={`base-class ${isActive ? 'active' : 'inactive'}`}>
```

**Alpine.js:**
```html
<div class="base-class" :class="isActive ? 'active' : 'inactive'">
```

### List Rendering

#### Mapping Arrays
**React:**
```jsx
{items.map(item => (
    <div key={item.id}>{item.name}</div>
))}
```

**Alpine.js:**
```html
<template x-for="item in items" :key="item.id">
    <div x-text="item.name"></div>
</template>
```

## 🎛️ Component State Management Patterns

### 1. Simple Toggle Component
```html
<div x-data="{ isOpen: false }">
    <button @click="isOpen = !isOpen">Toggle</button>
    <div x-show="isOpen" x-transition>Content</div>
</div>
```

### 2. Form Component with Validation
```html
<form x-data="{
    formData: { name: '', email: '' },
    errors: {},
    isSubmitting: false,
    
    validateField(field) {
        this.errors[field] = '';
        if (!this.formData[field]) {
            this.errors[field] = 'This field is required';
        }
    },
    
    async submitForm() {
        this.isSubmitting = true;
        try {
            // API call
            await fetch('/api/submit', {
                method: 'POST',
                body: JSON.stringify(this.formData)
            });
        } catch (error) {
            console.error('Submission failed:', error);
        } finally {
            this.isSubmitting = false;
        }
    }
}">
```

### 3. Data Table with Sorting and Filtering
```html
<div x-data="{
    data: [],
    searchQuery: '',
    sortColumn: 'name',
    sortDirection: 'asc',
    selectedItems: [],
    
    get filteredData() {
        return this.data.filter(item => 
            item.name.toLowerCase().includes(this.searchQuery.toLowerCase())
        );
    },
    
    get sortedData() {
        return this.filteredData.sort((a, b) => {
            let aVal = a[this.sortColumn];
            let bVal = b[this.sortColumn];
            
            if (this.sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            }
            return aVal < bVal ? 1 : -1;
        });
    },
    
    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
    }
}">
```

### 4. Modal Component
```html
<div x-data="{ 
    isOpen: false,
    
    open() {
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
    },
    
    close() {
        this.isOpen = false;
        document.body.style.overflow = 'auto';
    }
}"
@keydown.escape.window="close()">

    <button @click="open()">Open Modal</button>
    
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="close()"></div>
        <div class="relative bg-white rounded-lg">
            <!-- Modal content -->
        </div>
    </div>
</div>
```

### 5. Tabs Component
```html
<div x-data="{ 
    activeTab: 'tab1',
    
    switchTab(tab) {
        this.activeTab = tab;
    }
}">
    <div class="border-b border-gray-200">
        <nav class="flex space-x-8">
            <button @click="switchTab('tab1')"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'tab1' }"
                    class="py-2 px-1 border-b-2 font-medium text-sm">
                Tab 1
            </button>
            <button @click="switchTab('tab2')"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'tab2' }"
                    class="py-2 px-1 border-b-2 font-medium text-sm">
                Tab 2
            </button>
        </nav>
    </div>
    
    <div x-show="activeTab === 'tab1'" x-transition>Tab 1 Content</div>
    <div x-show="activeTab === 'tab2'" x-transition>Tab 2 Content</div>
</div>
```

## 🎨 Styling and Animations

### Transitions
Alpine.js provides built-in transition directives:

```html
<!-- Fade transition -->
<div x-show="isVisible" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">

<!-- Scale transition -->
<div x-show="isVisible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">

<!-- Slide transition -->
<div x-show="isVisible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="transform -translate-x-full"
     x-transition:enter-end="transform translate-x-0">
```

### Dynamic Classes
```html
<div :class="{
    'bg-green-100 text-green-800': status === 'success',
    'bg-red-100 text-red-800': status === 'error',
    'bg-yellow-100 text-yellow-800': status === 'warning'
}">
```

## 📦 Usage Examples

### Using Login Form Component
```html
<x-flowbite.react-blocks.marketing-ui.login-form-with-description 
    :title="'Welcome Back to ' . config('app.name')"
    :site-url="route('home')"
    :logo-url="asset('images/logo.svg')"
    :site-name="config('app.name')"
    :hero-title="'Join thousands of developers'"
    :hero-description="'Build amazing applications with our platform.'"
    :customer-count="'25k+'"
    :avatars="[
        asset('images/avatars/user1.jpg'),
        asset('images/avatars/user2.jpg'),
        asset('images/avatars/user3.jpg')
    ]"
/>
```

### Using Pricing Table Component
```html
<x-flowbite.react-blocks.marketing-ui.pricing-table-toggle 
    :title="'Choose Your Plan'"
    :description="'Select the perfect plan for your needs'"
    :plans="[
        [
            'name' => 'Basic',
            'monthly_price' => 29,
            'yearly_price' => 24,
            'description' => 'Perfect for getting started',
            'features' => ['5 Projects', '10GB Storage', 'Basic Support'],
            'button_text' => 'Get Started'
        ],
        // More plans...
    ]"
/>
```

### Using Data Table Component
```html
<x-flowbite.react-blocks.application-ui.advanced-user-management-table 
    :users="$users"
    :total-users="$totalUsers"
    :total-projects="$totalProjects"
    :current-page="$currentPage"
    :per-page="20"
/>
```

## 🔧 Best Practices

### 1. Initialize Data Properly
```html
<!-- Good: Initialize with sensible defaults -->
<div x-data="{
    items: [],
    loading: false,
    error: null
}" x-init="loadItems()">

<!-- Bad: Uninitialized properties -->
<div x-data="{}">
```

### 2. Use Computed Properties for Complex Logic
```html
<!-- Good: Computed property -->
<div x-data="{
    items: [],
    searchTerm: '',
    get filteredItems() {
        return this.items.filter(item => 
            item.name.includes(this.searchTerm)
        );
    }
}">

<!-- Bad: Inline computation -->
<template x-for="item in items.filter(i => i.name.includes(searchTerm))">
```

### 3. Handle Loading States
```html
<div x-data="{
    isLoading: false,
    data: null,
    
    async fetchData() {
        this.isLoading = true;
        try {
            const response = await fetch('/api/data');
            this.data = await response.json();
        } catch (error) {
            console.error('Failed to fetch data:', error);
        } finally {
            this.isLoading = false;
        }
    }
}">
    <div x-show="isLoading">Loading...</div>
    <div x-show="!isLoading && data">
        <!-- Content -->
    </div>
</div>
```

### 4. Clean Up Side Effects
```html
<div x-data="{
    interval: null,
    
    startTimer() {
        this.interval = setInterval(() => {
            // Timer logic
        }, 1000);
    },
    
    stopTimer() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
}"
x-init="startTimer()"
x-on:unmount="stopTimer()">
```

## 🎯 Component Library Integration

### Laravel Blade Components
All converted components can be used as Laravel Blade components:

```php
// In a Blade view
<x-flowbite.react-blocks.marketing-ui.pricing-table-toggle />

// With custom data
<x-flowbite.react-blocks.application-ui.advanced-user-management-table 
    :users="$users" 
/>
```

### Integration with Laravel Livewire
Alpine.js components work seamlessly with Livewire:

```html
<div wire:ignore>
    <x-flowbite.react-blocks.marketing-ui.login-form-with-description />
</div>
```

## 📝 Notes

- All components maintain responsive design from the original Flowbite React blocks
- Alpine.js provides similar functionality to React hooks but with simpler syntax
- Components are fully accessible and include proper ARIA attributes
- Styling uses Tailwind CSS classes compatible with the original designs
- Components support both light and dark modes through Tailwind's dark mode classes

## 🔗 Resources

- [Alpine.js Documentation](https://alpinejs.dev/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Flowbite Components](https://flowbite.com/blocks/)
- [Laravel Blade Components](https://laravel.com/docs/blade#components)