import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'happy-dom',
    globals: true,
    setupFiles: './tests/setup.ts',
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      exclude: [
        'node_modules/',
        'tests/',
        '**/*.d.ts',
        '**/*.config.*',
        '**/mockData',
        'resources/js/bootstrap.js',
        'public/**',
        'vendor/**',
        'storage/**',
        'bootstrap/**',
        'database/**',
      ],
      include: [
        'resources/js/**/*.{js,jsx,ts,tsx}',
        'app/**/*.php'
      ],
      all: true,
      lines: 80,
      functions: 80,
      branches: 80,
      statements: 80
    },
    include: [
      'resources/js/**/*.{test,spec}.{js,jsx,ts,tsx}',
      'tests/js/**/*.{test,spec}.{js,jsx,ts,tsx}'
    ],
    exclude: ['node_modules', 'dist', '.idea', '.git', '.cache'],
    reporters: ['default', 'html'],
    outputFile: {
      html: './coverage/vitest/index.html'
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      '@components': path.resolve(__dirname, './resources/js/components'),
      '@utils': path.resolve(__dirname, './resources/js/utils'),
      '@services': path.resolve(__dirname, './resources/js/services'),
      '@hooks': path.resolve(__dirname, './resources/js/hooks'),
      '@contexts': path.resolve(__dirname, './resources/js/contexts'),
      '@pages': path.resolve(__dirname, './resources/js/Pages'),
      '@lib': path.resolve(__dirname, './resources/js/lib')
    }
  }
})