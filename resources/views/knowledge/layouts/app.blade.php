<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Knowledge Base') - {{ config('app.name', 'AskProAI') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        {!! $knowledgeEnhancerCSS ?? '' !!}
        
        /* Base styles */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f9fafb;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
        }
        
        .nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .nav a:hover {
            color: #111827;
        }
        
        /* Search */
        .search-box {
            display: flex;
            align-items: center;
            background: #f3f4f6;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            min-width: 300px;
        }
        
        .search-box input {
            background: none;
            border: none;
            outline: none;
            flex: 1;
            font-size: 0.875rem;
        }
        
        /* Main layout */
        .main-layout {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .sidebar {
            width: 280px;
            flex-shrink: 0;
        }
        
        .main-content {
            flex: 1;
            min-width: 0;
        }
        
        /* Sidebar */
        .sidebar-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .category-list li {
            margin-bottom: 0.5rem;
        }
        
        .category-list a {
            color: #4b5563;
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        
        .category-list a:hover {
            background: #f3f4f6;
            color: #111827;
        }
        
        .category-count {
            font-size: 0.75rem;
            color: #9ca3af;
            background: #f3f4f6;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
        }
        
        /* Content */
        .content-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        /* Tags */
        .tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #e0e7ff;
            color: #4338ca;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .tag:hover {
            background: #c7d2fe;
        }
        
        /* Footer */
        .footer {
            margin-top: 4rem;
            padding: 2rem 0;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .nav {
                display: none;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="{{ route('portal.knowledge.index') }}" class="logo">
                    📚 Knowledge Base
                </a>
                
                <nav class="nav">
                    <form action="{{ route('portal.knowledge.search') }}" method="GET" class="search-box">
                        <input type="search" name="q" placeholder="Search documentation..." value="{{ request('q') }}">
                        <button type="submit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </form>
                    
                    @auth
                        <a href="{{ route('portal.knowledge.notebooks.index') }}">My Notebooks</a>
                    @endauth
                    
                    <a href="{{ route('admin') }}">Admin</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="main-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                @section('sidebar')
                    <!-- Categories -->
                    @if(isset($globalCategories) && $globalCategories->count() > 0)
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Categories</h3>
                            <ul class="category-list">
                                @foreach($globalCategories as $category)
                                    <li>
                                        <a href="{{ route('portal.knowledge.category', $category->slug) }}">
                                            <span>{{ $category->name }}</span>
                                            <span class="category-count">{{ $category->documents_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <!-- Popular Tags -->
                    @if(isset($popularTags) && $popularTags->count() > 0)
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Popular Tags</h3>
                            <div>
                                @foreach($popularTags as $tag)
                                    <a href="{{ route('portal.knowledge.tag', $tag->slug) }}" class="tag">
                                        {{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @show
            </aside>
            
            <!-- Main Content -->
            <main class="main-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'AskProAI') }}. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Mermaid for diagrams -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    
    <!-- Knowledge Base JavaScript -->
    <script>
        {!! $knowledgeEnhancerJS ?? '' !!}
    </script>
    
    @stack('scripts')
</body>
</html>