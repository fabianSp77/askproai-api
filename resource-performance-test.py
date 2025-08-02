#!/usr/bin/env python3

"""
Resource Loading Performance Test
Tests static resources, bundle sizes, and loading patterns
"""

import requests
import time
import re
from urllib.parse import urljoin, urlparse
import os

class ResourcePerformanceTest:
    def __init__(self, base_url="https://api.askproai.de"):
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Resource-Performance-Test/1.0',
            'Accept': '*/*'
        })
        
        self.results = {
            'html': [],
            'css': [],
            'js': [],
            'images': [],
            'fonts': [],
            'other': []
        }

    def login_and_get_dashboard(self):
        """Login and get dashboard HTML to extract resource URLs"""
        # Login first
        login_url = urljoin(self.base_url, '/business/login')
        page_response = self.session.get(login_url)
        
        # Extract CSRF token
        csrf_match = re.search(r'_token[^>]*value="([^"]*)"', page_response.text)
        if not csrf_match:
            raise Exception("Could not find CSRF token")
        
        csrf_token = csrf_match.group(1)
        
        # Login
        login_data = {
            '_token': csrf_token,
            'email': 'demo@askproai.de',
            'password': 'password'
        }
        self.session.post(login_url, data=login_data)
        
        # Get dashboard
        dashboard_url = urljoin(self.base_url, '/business/dashboard')
        dashboard_response = self.session.get(dashboard_url)
        
        return dashboard_response.text

    def extract_resources_from_html(self, html_content):
        """Extract resource URLs from HTML"""
        resources = []
        
        # CSS files
        css_matches = re.findall(r'<link[^>]*href="([^"]*\.css[^"]*)"', html_content)
        for url in css_matches:
            resources.append(('css', url))
        
        # JavaScript files
        js_matches = re.findall(r'<script[^>]*src="([^"]*\.js[^"]*)"', html_content)
        for url in js_matches:
            resources.append(('js', url))
        
        # Images
        img_matches = re.findall(r'<img[^>]*src="([^"]*)"', html_content)
        for url in img_matches:
            if any(ext in url.lower() for ext in ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp']):
                resources.append(('image', url))
        
        # Background images from CSS (basic detection)
        bg_matches = re.findall(r'background-image:\s*url\(["\']?([^"\')\s]+)', html_content)
        for url in bg_matches:
            resources.append(('image', url))
        
        return resources

    def test_resource(self, resource_type, url):
        """Test loading performance of a single resource"""
        try:
            # Handle relative URLs
            if url.startswith('//'):
                full_url = 'https:' + url
            elif url.startswith('/'):
                full_url = urljoin(self.base_url, url)
            elif not url.startswith('http'):
                full_url = urljoin(self.base_url, url)
            else:
                full_url = url
            
            start_time = time.time()
            response = self.session.get(full_url, timeout=10)
            end_time = time.time()
            
            duration = (end_time - start_time) * 1000  # Convert to milliseconds
            size = len(response.content)
            
            return {
                'url': url,
                'full_url': full_url,
                'type': resource_type,
                'duration': duration,
                'size': size,
                'status_code': response.status_code,
                'success': response.status_code == 200,
                'content_type': response.headers.get('content-type', ''),
                'cache_control': response.headers.get('cache-control', ''),
                'etag': response.headers.get('etag', ''),
                'gzip': 'gzip' in response.headers.get('content-encoding', '')
            }
            
        except Exception as e:
            return {
                'url': url,
                'type': resource_type,
                'success': False,
                'error': str(e),
                'duration': 0,
                'size': 0
            }

    def run_test(self):
        """Run complete resource performance test"""
        print("🔍 Resource Performance Test")
        print("============================")
        print(f"Base URL: {self.base_url}")
        print()
        
        try:
            # Get dashboard HTML and extract resources
            print("📄 Getting dashboard HTML and extracting resources...")
            html_content = self.login_and_get_dashboard()
            resources = self.extract_resources_from_html(html_content)
            
            print(f"Found {len(resources)} resources to test")
            print()
            
            # Test each resource
            for i, (resource_type, url) in enumerate(resources):
                print(f"Testing {i+1}/{len(resources)}: {resource_type} - {url[:50]}{'...' if len(url) > 50 else ''}")
                
                result = self.test_resource(resource_type, url)
                
                if result['success']:
                    print(f"  ✅ {result['duration']:.0f}ms, {result['size']} bytes")
                    self.results[resource_type].append(result)
                else:
                    print(f"  ❌ Failed: {result.get('error', 'Unknown error')}")
            
            # Generate report
            self.generate_report()
            
        except Exception as e:
            print(f"❌ Test failed: {e}")

    def generate_report(self):
        """Generate resource performance report"""
        print("\n📊 RESOURCE PERFORMANCE REPORT")
        print("===============================")
        
        total_resources = 0
        total_size = 0
        total_time = 0
        
        for resource_type, resources in self.results.items():
            if not resources:
                continue
                
            count = len(resources)
            total_size_type = sum(r['size'] for r in resources)
            total_time_type = sum(r['duration'] for r in resources)
            avg_time = total_time_type / count if count > 0 else 0
            avg_size = total_size_type / count if count > 0 else 0
            
            total_resources += count
            total_size += total_size_type
            total_time += total_time_type
            
            print(f"\n📦 {resource_type.upper()} RESOURCES:")
            print(f"  Count: {count}")
            print(f"  Total Size: {self.format_bytes(total_size_type)}")
            print(f"  Average Size: {self.format_bytes(avg_size)}")
            print(f"  Average Load Time: {avg_time:.0f}ms")
            
            # Check for optimization opportunities
            if resource_type == 'js' and avg_size > 100000:  # > 100KB
                print(f"  💡 Consider code splitting - average JS bundle is large")
            
            if resource_type == 'css' and avg_size > 50000:  # > 50KB
                print(f"  💡 Consider CSS optimization - average CSS file is large")
            
            if resource_type == 'image' and avg_size > 500000:  # > 500KB
                print(f"  💡 Consider image optimization - average image is large")
            
            # Show largest resources
            if resources:
                largest = max(resources, key=lambda x: x['size'])
                print(f"  Largest: {self.format_bytes(largest['size'])} - {largest['url'][:50]}{'...' if len(largest['url']) > 50 else ''}")
        
        print(f"\n📈 OVERALL SUMMARY:")
        print(f"  Total Resources: {total_resources}")
        print(f"  Total Size: {self.format_bytes(total_size)}")
        print(f"  Total Load Time: {total_time:.0f}ms")
        print(f"  Average per Resource: {total_time/total_resources:.0f}ms" if total_resources > 0 else "  No resources loaded")
        
        # Performance rating
        if total_size < 1000000:  # < 1MB
            print(f"  Bundle Size Rating: 🟢 EXCELLENT (< 1MB)")
        elif total_size < 2000000:  # < 2MB
            print(f"  Bundle Size Rating: 🟡 GOOD (< 2MB)")
        else:
            print(f"  Bundle Size Rating: 🔴 NEEDS OPTIMIZATION (> 2MB)")
        
        # Check caching
        cached_resources = sum(1 for resources in self.results.values() for r in resources if r.get('cache_control'))
        cache_percentage = (cached_resources / total_resources * 100) if total_resources > 0 else 0
        
        print(f"\n🗄️ CACHING ANALYSIS:")
        print(f"  Resources with Cache Headers: {cached_resources}/{total_resources} ({cache_percentage:.0f}%)")
        
        if cache_percentage < 80:
            print(f"  💡 Consider adding cache headers to more resources")
        else:
            print(f"  ✅ Good caching strategy implemented")
        
        # Check compression
        compressed_resources = sum(1 for resources in self.results.values() for r in resources if r.get('gzip'))
        compression_percentage = (compressed_resources / total_resources * 100) if total_resources > 0 else 0
        
        print(f"\n🗜️ COMPRESSION ANALYSIS:")
        print(f"  Compressed Resources: {compressed_resources}/{total_resources} ({compression_percentage:.0f}%)")
        
        if compression_percentage < 80:
            print(f"  💡 Enable gzip/brotli compression for more resources")
        else:
            print(f"  ✅ Good compression strategy implemented")

    def format_bytes(self, bytes_value):
        """Format bytes in human readable format"""
        if bytes_value == 0:
            return "0 B"
        
        for unit in ['B', 'KB', 'MB', 'GB']:
            if bytes_value < 1024:
                return f"{bytes_value:.1f} {unit}"
            bytes_value /= 1024
        
        return f"{bytes_value:.1f} TB"


if __name__ == "__main__":
    import sys
    
    base_url = sys.argv[1] if len(sys.argv) > 1 else "https://api.askproai.de"
    
    test = ResourcePerformanceTest(base_url)
    test.run_test()