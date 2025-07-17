/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  transpilePackages: ['@askproai/ui', '@askproai/auth', '@askproai/api-client'],
  images: {
    domains: ['api.askproai.de'],
  },
  experimental: {
    optimizePackageImports: ['@askproai/ui', 'lucide-react'],
  },
}