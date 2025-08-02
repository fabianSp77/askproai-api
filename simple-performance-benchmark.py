#!/usr/bin/env python3

"""
Business Portal Performance Benchmark
Simple Python-based performance testing for login and dashboard
"""

import requests
import time
import statistics
import json
import os
from urllib.parse import urljoin
import re

class PerformanceBenchmark:
    def __init__(self, base_url="https://api.askproai.de", iterations=10):
        self.base_url = base_url
        self.iterations = iterations
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Performance-Benchmark/1.0',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        })
        
        self.credentials = {
            'email': os.getenv('TEST_EMAIL', 'demo@askproai.de'),
            'password': os.getenv('TEST_PASSWORD', 'password')
        }
        
        self.results = {
            'login_page': [],
            'login_submit': [],
            'dashboard': [],
            'api_stats': [],
            'api_calls': [],
            'api_appointments': []
        }

    def get_csrf_token(self, html_content):
        """Extract CSRF token from HTML"""
        match = re.search(r'_token[^>]*value="([^"]*)"', html_content)
        return match.group(1) if match else None

    def measure_request(self, method, url, data=None, description=""):
        """Measure HTTP request performance"""
        try:
            start_time = time.time()
            
            if method.upper() == 'GET':
                response = self.session.get(url, timeout=30)
            elif method.upper() == 'POST':
                response = self.session.post(url, data=data, timeout=30)
            
            end_time = time.time()
            duration = (end_time - start_time) * 1000  # Convert to milliseconds
            
            return {
                'duration': duration,
                'status_code': response.status_code,
                'success': response.status_code < 400,
                'size': len(response.content),
                'url': url,
                'description': description
            }
            
        except Exception as e:
            return {
                'duration': 0,
                'status_code': 0,
                'success': False,
                'error': str(e),
                'url': url,
                'description': description
            }

    def test_login_performance(self):
        """Test login page load and form submission performance"""
        print("🔐 Testing Login Performance...")
        
        for i in range(self.iterations):
            print(f"  Iteration {i+1}/{self.iterations}: ", end="", flush=True)
            
            # Start fresh session for each iteration
            self.session.cookies.clear()
            
            # 1. Test login page load
            login_url = urljoin(self.base_url, '/business/login')
            page_result = self.measure_request('GET', login_url, description="Login Page")
            
            if page_result['success']:
                self.results['login_page'].append(page_result['duration'])
                print(f"page: {page_result['duration']:.0f}ms ", end="")
                
                # Extract CSRF token
                page_response = self.session.get(login_url)
                csrf_token = self.get_csrf_token(page_response.text)
                
                if csrf_token:
                    # 2. Test login form submission 
                    login_data = {
                        '_token': csrf_token,
                        'email': self.credentials['email'],
                        'password': self.credentials['password']
                    }
                    
                    submit_result = self.measure_request('POST', login_url, data=login_data, description="Login Submit")
                    
                    if submit_result['success'] or submit_result['status_code'] == 302:  # 302 is redirect after successful login
                        self.results['login_submit'].append(submit_result['duration'])
                        print(f"submit: {submit_result['duration']:.0f}ms ✓")
                    else:
                        print(f"✗ Login failed (HTTP {submit_result['status_code']})")
                else:
                    print("✗ CSRF token not found")
            else:
                print(f"✗ Page load failed (HTTP {page_result['status_code']})")

    def test_dashboard_performance(self):
        """Test dashboard loading performance"""
        print("\n📊 Testing Dashboard Performance...")
        
        for i in range(self.iterations):
            print(f"  Iteration {i+1}/{self.iterations}: ", end="", flush=True)
            
            # Start fresh session and login
            self.session.cookies.clear()
            
            # Quick login
            login_url = urljoin(self.base_url, '/business/login')
            page_response = self.session.get(login_url)
            csrf_token = self.get_csrf_token(page_response.text)
            
            if csrf_token:
                login_data = {
                    '_token': csrf_token,
                    'email': self.credentials['email'],
                    'password': self.credentials['password']
                }
                self.session.post(login_url, data=login_data)
                
                # Test dashboard load
                dashboard_url = urljoin(self.base_url, '/business/dashboard')
                dashboard_result = self.measure_request('GET', dashboard_url, description="Dashboard")
                
                if dashboard_result['success']:
                    self.results['dashboard'].append(dashboard_result['duration'])
                    print(f"dashboard: {dashboard_result['duration']:.0f}ms ({dashboard_result['size']} bytes) ✓")
                else:
                    print(f"✗ Dashboard failed (HTTP {dashboard_result['status_code']})")
            else:
                print("✗ Login failed")

    def test_api_performance(self):
        """Test API endpoint performance"""
        print("\n🌐 Testing API Performance...")
        
        # Login once for all API tests
        self.session.cookies.clear()
        login_url = urljoin(self.base_url, '/business/login')
        page_response = self.session.get(login_url)
        csrf_token = self.get_csrf_token(page_response.text)
        
        if not csrf_token:
            print("✗ Could not login for API tests")
            return
            
        login_data = {
            '_token': csrf_token,
            'email': self.credentials['email'],
            'password': self.credentials['password']
        }
        login_response = self.session.post(login_url, data=login_data)
        
        if login_response.status_code not in [200, 302]:
            print("✗ Login failed for API tests")
            return
        
        # Test API endpoints
        api_endpoints = [
            ('/business/api/dashboard/stats', 'Stats API', 'api_stats'),
            ('/business/api/dashboard/recent-calls', 'Recent Calls API', 'api_calls'),
            ('/business/api/dashboard/upcoming-appointments', 'Appointments API', 'api_appointments')
        ]
        
        for endpoint, name, result_key in api_endpoints:
            print(f"  Testing {name}...")
            api_url = urljoin(self.base_url, endpoint)
            
            api_results = []
            for i in range(self.iterations):
                result = self.measure_request('GET', api_url, description=name)
                
                if result['success']:
                    api_results.append(result['duration'])
                    print(".", end="", flush=True)
                else:
                    print("x", end="", flush=True)
            
            print(f" ({len(api_results)}/{self.iterations} successful)")
            self.results[result_key] = api_results

    def calculate_statistics(self, data):
        """Calculate performance statistics"""
        if not data:
            return None
            
        return {
            'count': len(data),
            'avg': statistics.mean(data),
            'min': min(data),
            'max': max(data),
            'median': statistics.median(data),
            'p95': data[int(len(sorted(data)) * 0.95)] if len(data) > 1 else data[0],
            'p99': data[int(len(sorted(data)) * 0.99)] if len(data) > 1 else data[0]
        }

    def get_performance_rating(self, avg_time, thresholds):
        """Get performance rating based on average time"""
        if avg_time <= thresholds['excellent']:
            return "🟢 EXCELLENT"
        elif avg_time <= thresholds['good']:
            return "🟡 GOOD"
        else:
            return "🔴 NEEDS IMPROVEMENT"

    def generate_report(self):
        """Generate comprehensive performance report"""
        print("\n📈 PERFORMANCE BENCHMARK RESULTS")
        print("==================================")
        
        # Login Performance
        login_page_stats = self.calculate_statistics(self.results['login_page'])
        login_submit_stats = self.calculate_statistics(self.results['login_submit'])
        
        if login_page_stats:
            print(f"\n🔐 LOGIN PERFORMANCE:")
            print(f"  Login Page Load:")
            print(f"    Average: {login_page_stats['avg']:.0f}ms")
            print(f"    Min: {login_page_stats['min']:.0f}ms, Max: {login_page_stats['max']:.0f}ms")
            print(f"    95th Percentile: {login_page_stats['p95']:.0f}ms")
            print(f"    Success Rate: {login_page_stats['count']}/{self.iterations} ({(login_page_stats['count']/self.iterations*100):.0f}%)")
            print(f"    Rating: {self.get_performance_rating(login_page_stats['avg'], {'excellent': 1000, 'good': 2000})}")
        
        if login_submit_stats:
            print(f"  Login Form Submission:")
            print(f"    Average: {login_submit_stats['avg']:.0f}ms")
            print(f"    Min: {login_submit_stats['min']:.0f}ms, Max: {login_submit_stats['max']:.0f}ms")
            print(f"    95th Percentile: {login_submit_stats['p95']:.0f}ms")
            print(f"    Success Rate: {login_submit_stats['count']}/{self.iterations} ({(login_submit_stats['count']/self.iterations*100):.0f}%)")
        
        # Dashboard Performance
        dashboard_stats = self.calculate_statistics(self.results['dashboard'])
        
        if dashboard_stats:
            print(f"\n📊 DASHBOARD PERFORMANCE:")
            print(f"  Dashboard Load Time:")
            print(f"    Average: {dashboard_stats['avg']:.0f}ms")
            print(f"    Min: {dashboard_stats['min']:.0f}ms, Max: {dashboard_stats['max']:.0f}ms")
            print(f"    95th Percentile: {dashboard_stats['p95']:.0f}ms")
            print(f"    Success Rate: {dashboard_stats['count']}/{self.iterations} ({(dashboard_stats['count']/self.iterations*100):.0f}%)")
            print(f"    Rating: {self.get_performance_rating(dashboard_stats['avg'], {'excellent': 1500, 'good': 3000})}")
        
        # API Performance
        print(f"\n🌐 API PERFORMANCE:")
        
        api_endpoints = [
            ('api_stats', 'Stats API'),
            ('api_calls', 'Recent Calls API'),
            ('api_appointments', 'Appointments API')
        ]
        
        for result_key, name in api_endpoints:
            api_stats = self.calculate_statistics(self.results[result_key])
            
            if api_stats:
                print(f"  {name}:")
                print(f"    Average: {api_stats['avg']:.0f}ms")
                print(f"    Min: {api_stats['min']:.0f}ms, Max: {api_stats['max']:.0f}ms")
                print(f"    95th Percentile: {api_stats['p95']:.0f}ms")
                print(f"    Success Rate: {api_stats['count']}/{self.iterations} ({(api_stats['count']/self.iterations*100):.0f}%)")
                print(f"    Rating: {self.get_performance_rating(api_stats['avg'], {'excellent': 200, 'good': 500})}")
        
        # Industry Comparison
        print(f"\n🏭 INDUSTRY STANDARDS COMPARISON:")
        print(f"  Login < 1s: EXCELLENT | < 2s: GOOD | > 2s: POOR")
        print(f"  Dashboard < 1.5s: EXCELLENT | < 3s: GOOD | > 3s: POOR")
        print(f"  API < 200ms: EXCELLENT | < 500ms: GOOD | > 500ms: POOR")
        
        # Recommendations
        print(f"\n💡 PERFORMANCE RECOMMENDATIONS:")
        recommendations = []
        
        if login_page_stats and login_page_stats['avg'] > 2000:
            recommendations.append("🔴 LOGIN: Optimize login page loading - consider CDN, compression, and asset optimization")
        
        if login_submit_stats and login_submit_stats['avg'] > 1000:
            recommendations.append("🔴 LOGIN: Optimize authentication processing - check database queries and session handling")
        
        if dashboard_stats and dashboard_stats['avg'] > 3000:
            recommendations.append("🔴 DASHBOARD: Implement lazy loading and code splitting for React components")
        elif dashboard_stats and dashboard_stats['avg'] > 1500:
            recommendations.append("🟡 DASHBOARD: Consider optimizing initial bundle size and resource loading")
        
        for result_key, name in api_endpoints:
            api_stats = self.calculate_statistics(self.results[result_key])
            if api_stats and api_stats['avg'] > 500:
                recommendations.append(f"🔴 API: Optimize {name} - implement caching or database query optimization")
        
        if not recommendations:
            print("  🎉 No critical performance issues detected!")
        else:
            for rec in recommendations:
                print(f"  {rec}")
        
        # Save detailed results
        timestamp = time.strftime("%Y-%m-%d_%H-%M-%S")
        report_file = f"performance-report-{timestamp}.json"
        
        detailed_report = {
            'metadata': {
                'timestamp': time.strftime("%Y-%m-%d %H:%M:%S UTC", time.gmtime()),
                'base_url': self.base_url,
                'iterations': self.iterations,
                'test_user': self.credentials['email']
            },
            'statistics': {
                'login_page': login_page_stats,
                'login_submit': login_submit_stats,
                'dashboard': dashboard_stats,
                'api_stats': self.calculate_statistics(self.results['api_stats']),
                'api_calls': self.calculate_statistics(self.results['api_calls']),
                'api_appointments': self.calculate_statistics(self.results['api_appointments'])
            },
            'raw_data': self.results,
            'recommendations': recommendations
        }
        
        with open(report_file, 'w') as f:
            json.dump(detailed_report, f, indent=2)
        
        print(f"\n📄 Detailed report saved to: {report_file}")

    def run(self):
        """Run the complete benchmark suite"""
        print("🚀 Business Portal Performance Benchmark")
        print("==========================================")
        print(f"Base URL: {self.base_url}")
        print(f"Iterations: {self.iterations}")
        print(f"Test User: {self.credentials['email']}")
        print()
        
        try:
            # Run all tests
            self.test_login_performance()
            self.test_dashboard_performance()
            self.test_api_performance()
            
            # Generate comprehensive report
            self.generate_report()
            
            print("\n✅ Performance benchmark completed!")
            
        except KeyboardInterrupt:
            print("\n❌ Benchmark interrupted by user")
        except Exception as e:
            print(f"\n❌ Benchmark failed: {e}")


if __name__ == "__main__":
    import sys
    
    # Parse command line arguments
    base_url = os.getenv('BASE_URL', 'https://api.askproai.de')
    iterations = int(os.getenv('ITERATIONS', '10'))
    
    if len(sys.argv) > 1:
        base_url = sys.argv[1]
    if len(sys.argv) > 2:
        iterations = int(sys.argv[2])
    
    benchmark = PerformanceBenchmark(base_url, iterations)
    benchmark.run()