#!/usr/bin/env node
/**
 * Bundle Size Analysis Script
 * Analyzes the Vite build output for performance metrics
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const BUILD_DIR = './public/build';
const MANIFEST_PATH = path.join(BUILD_DIR, 'manifest.json');

// Color codes for terminal output
const colors = {
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    magenta: '\x1b[35m',
    cyan: '\x1b[36m',
    white: '\x1b[37m',
    reset: '\x1b[0m',
    bold: '\x1b[1m'
};

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getGzipSize(filePath) {
    try {
        const gzipOutput = execSync(`gzip -c "${filePath}" | wc -c`, { encoding: 'utf8' });
        return parseInt(gzipOutput.trim());
    } catch (error) {
        return 0;
    }
}

function analyzeBundle() {
    console.log(`${colors.bold}${colors.blue}📊 Bundle Analysis Report${colors.reset}\n`);
    
    if (!fs.existsSync(MANIFEST_PATH)) {
        console.log(`${colors.red}❌ Build manifest not found. Run 'npm run build' first.${colors.reset}`);
        return;
    }

    const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'));
    const assets = [];
    let totalSize = 0;
    let totalGzipSize = 0;

    // Analyze each asset
    for (const [key, asset] of Object.entries(manifest)) {
        const assetPath = path.join(BUILD_DIR, asset.file);
        
        if (fs.existsSync(assetPath)) {
            const stats = fs.statSync(assetPath);
            const size = stats.size;
            const gzipSize = getGzipSize(assetPath);
            
            assets.push({
                name: key,
                file: asset.file,
                size,
                gzipSize,
                type: path.extname(asset.file).toLowerCase()
            });
            
            totalSize += size;
            totalGzipSize += gzipSize;
        }
    }

    // Sort by size (descending)
    assets.sort((a, b) => b.size - a.size);

    // Performance thresholds
    const thresholds = {
        js: { warning: 250 * 1024, critical: 500 * 1024 }, // 250KB/500KB
        css: { warning: 50 * 1024, critical: 100 * 1024 }  // 50KB/100KB
    };

    // Display results
    console.log(`${colors.bold}📦 Asset Overview${colors.reset}`);
    console.log(`Total Assets: ${assets.length}`);
    console.log(`Total Size: ${formatBytes(totalSize)} (${formatBytes(totalGzipSize)} gzipped)\n`);

    // Group by type
    const byType = {
        js: assets.filter(a => a.type === '.js'),
        css: assets.filter(a => a.type === '.css'),
        other: assets.filter(a => !['.js', '.css'].includes(a.type))
    };

    console.log(`${colors.bold}📊 Size Breakdown by Type${colors.reset}`);
    
    for (const [type, typeAssets] of Object.entries(byType)) {
        if (typeAssets.length === 0) continue;
        
        const typeSize = typeAssets.reduce((sum, asset) => sum + asset.size, 0);
        const typeGzipSize = typeAssets.reduce((sum, asset) => sum + asset.gzipSize, 0);
        
        console.log(`${type.toUpperCase()}: ${formatBytes(typeSize)} (${formatBytes(typeGzipSize)} gzipped) - ${typeAssets.length} files`);
    }

    console.log(`\n${colors.bold}🏆 Top 10 Largest Assets${colors.reset}`);
    console.log(''.padEnd(60, '-'));
    console.log(`${'Asset'.padEnd(40)} ${'Size'.padEnd(10)} ${'Gzipped'.padEnd(10)}`);
    console.log(''.padEnd(60, '-'));

    assets.slice(0, 10).forEach(asset => {
        let color = colors.green;
        const threshold = thresholds[asset.type.slice(1)];
        
        if (threshold) {
            if (asset.gzipSize > threshold.critical) {
                color = colors.red;
            } else if (asset.gzipSize > threshold.warning) {
                color = colors.yellow;
            }
        }

        const name = asset.name.length > 38 ? asset.name.slice(0, 35) + '...' : asset.name;
        console.log(`${color}${name.padEnd(40)} ${formatBytes(asset.size).padEnd(10)} ${formatBytes(asset.gzipSize)}${colors.reset}`);
    });

    // Performance recommendations
    console.log(`\n${colors.bold}💡 Performance Recommendations${colors.reset}`);
    
    const largeJS = byType.js.filter(asset => asset.gzipSize > thresholds.js.warning);
    const largeCSS = byType.css.filter(asset => asset.gzipSize > thresholds.css.warning);
    
    if (largeJS.length > 0) {
        console.log(`${colors.yellow}⚠️  Large JavaScript bundles detected:${colors.reset}`);
        largeJS.forEach(asset => {
            console.log(`   • ${asset.name}: ${formatBytes(asset.gzipSize)} gzipped`);
        });
        console.log('   Consider code splitting or lazy loading.');
    }
    
    if (largeCSS.length > 0) {
        console.log(`${colors.yellow}⚠️  Large CSS bundles detected:${colors.reset}`);
        largeCSS.forEach(asset => {
            console.log(`   • ${asset.name}: ${formatBytes(asset.gzipSize)} gzipped`);
        });
        console.log('   Consider CSS code splitting or removing unused styles.');
    }

    // Bundle analysis
    const criticalJS = byType.js.filter(asset => 
        asset.name.includes('app') || 
        asset.name.includes('critical') || 
        asset.name.includes('login')
    );
    
    const criticalSize = criticalJS.reduce((sum, asset) => sum + asset.gzipSize, 0);
    
    console.log(`\n${colors.bold}🚀 Critical Path Analysis${colors.reset}`);
    console.log(`Critical JavaScript size: ${formatBytes(criticalSize)} gzipped`);
    
    if (criticalSize < 100 * 1024) {
        console.log(`${colors.green}✅ Critical path size is excellent (< 100KB)${colors.reset}`);
    } else if (criticalSize < 200 * 1024) {
        console.log(`${colors.yellow}⚠️  Critical path size is acceptable (< 200KB)${colors.reset}`);
    } else {
        console.log(`${colors.red}❌ Critical path size is too large (> 200KB)${colors.reset}`);
    }

    // Vendor chunk analysis
    const vendorChunks = byType.js.filter(asset => asset.name.includes('vendor') || asset.file.includes('vendor'));
    if (vendorChunks.length > 0) {
        console.log(`\n${colors.bold}📚 Vendor Chunks${colors.reset}`);
        const vendorSize = vendorChunks.reduce((sum, asset) => sum + asset.gzipSize, 0);
        console.log(`Total vendor size: ${formatBytes(vendorSize)} gzipped`);
        
        vendorChunks.forEach(chunk => {
            console.log(`   • ${chunk.name}: ${formatBytes(chunk.gzipSize)} gzipped`);
        });
    }

    // Performance score
    let score = 100;
    
    if (criticalSize > 200 * 1024) score -= 20;
    else if (criticalSize > 100 * 1024) score -= 10;
    
    if (largeJS.length > 2) score -= 15;
    else if (largeJS.length > 0) score -= 10;
    
    if (largeCSS.length > 1) score -= 10;
    else if (largeCSS.length > 0) score -= 5;
    
    if (totalGzipSize > 1024 * 1024) score -= 15; // 1MB total
    else if (totalGzipSize > 512 * 1024) score -= 10; // 512KB total

    console.log(`\n${colors.bold}🎯 Performance Score${colors.reset}`);
    
    let scoreColor = colors.green;
    if (score < 70) scoreColor = colors.red;
    else if (score < 85) scoreColor = colors.yellow;
    
    console.log(`${scoreColor}${score}/100${colors.reset}`);
    
    if (score >= 90) {
        console.log(`${colors.green}🌟 Excellent! Your bundle is well optimized.${colors.reset}`);
    } else if (score >= 80) {
        console.log(`${colors.yellow}👍 Good, but there's room for improvement.${colors.reset}`);
    } else {
        console.log(`${colors.red}⚠️  Consider optimizing your bundles for better performance.${colors.reset}`);
    }

    console.log(`\n${colors.bold}📈 Performance Metrics Comparison${colors.reset}`);
    console.log('Industry Standards:');
    console.log('• Critical JS: < 100KB gzipped ✨ Excellent, < 200KB ✅ Good');
    console.log('• Total JS: < 500KB gzipped ✨ Excellent, < 1MB ✅ Acceptable');
    console.log('• Total CSS: < 100KB gzipped ✨ Excellent, < 200KB ✅ Good');
    
    const totalJS = byType.js.reduce((sum, asset) => sum + asset.gzipSize, 0);
    const totalCSS = byType.css.reduce((sum, asset) => sum + asset.gzipSize, 0);
    
    console.log(`\nYour metrics:`);
    console.log(`• Critical JS: ${formatBytes(criticalSize)} ${criticalSize < 100*1024 ? '✨' : criticalSize < 200*1024 ? '✅' : '⚠️'}`);
    console.log(`• Total JS: ${formatBytes(totalJS)} ${totalJS < 500*1024 ? '✨' : totalJS < 1024*1024 ? '✅' : '⚠️'}`);
    console.log(`• Total CSS: ${formatBytes(totalCSS)} ${totalCSS < 100*1024 ? '✨' : totalCSS < 200*1024 ? '✅' : '⚠️'}`);
}

// Run analysis
analyzeBundle();