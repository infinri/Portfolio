#!/usr/bin/env node
/**
 * Asset Build System
 * Minifies and bundles CSS/JS for production
 */

const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');
const { minify: minifyJS } = require('terser');

// Configuration
const config = {
    sourceDir: path.join(__dirname, 'app'),
    baseViewDir: path.join(__dirname, 'app/base/view'),
    distDir: path.join(__dirname, 'pub/assets/dist'),
    publicAssetsDir: path.join(__dirname, 'pub/assets'),
    cssFiles: [
        // Base CSS (non-critical - critical.css is inlined separately)
        'base/css/reset.css',
        'base/css/variables.css',
        'base/css/theme.css',        // Customer theme overrides
        'base/css/base.css',
        // Frontend CSS
        'frontend/css/theme.css'
    ],
    criticalCss: 'base/css/critical.css', // Inlined separately
    jsFiles: [
        // Base JS
        'base/js/base.js',
        // Frontend JS
        'frontend/js/theme.js'
    ],
    modules: [
        'home',
        'about',
        'services',
        'contact',
        'error',
        'head',
        'footer',
        'legal'
    ]
};

// Ensure dist directory exists
function ensureDir(dir) {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
}

// Read file with error handling
function readFile(filePath) {
    try {
        return fs.readFileSync(filePath, 'utf8');
    } catch (error) {
        console.warn(`⚠️  Warning: Could not read ${filePath}`);
        return '';
    }
}

// Minify CSS
async function minifyCSS(files, outputName) {
    console.log(`🎨 Minifying CSS: ${outputName}`);
    
    let combinedCSS = '';
    
    for (const file of files) {
        // Base/frontend assets are in app/base/view/, module assets are in app/modules/
        let filePath;
        if (file.startsWith('base/') || file.startsWith('frontend/')) {
            filePath = path.join(config.baseViewDir, file);
        } else {
            filePath = path.join(config.sourceDir, file);
        }
        
        const content = readFile(filePath);
        if (content) {
            combinedCSS += `\n/* ${file} */\n${content}\n`;
            console.log(`  ✓ ${file}`);
        }
    }
    
    const originalSize = Buffer.byteLength(combinedCSS, 'utf8');
    
    const output = new CleanCSS({
        level: 2,
        compatibility: 'ie11'
    }).minify(combinedCSS);
    
    if (output.errors.length > 0) {
        console.error('❌ CSS Errors:', output.errors);
        return false;
    }
    
    const outputPath = path.join(config.distDir, outputName);
    ensureDir(path.dirname(outputPath));
    fs.writeFileSync(outputPath, output.styles);
    
    const minifiedSize = Buffer.byteLength(output.styles, 'utf8');
    const savings = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
    
    console.log(`  📦 ${(originalSize / 1024).toFixed(1)}KB → ${(minifiedSize / 1024).toFixed(1)}KB (${savings}% smaller)`);
    return true;
}

// Minify JavaScript
async function minifyJavaScript(files, outputName) {
    console.log(`⚡ Minifying JS: ${outputName}`);
    
    let combinedJS = '';
    
    for (const file of files) {
        // Base/frontend assets are in app/base/view/, module assets are in app/modules/
        let filePath;
        if (file.startsWith('base/') || file.startsWith('frontend/')) {
            filePath = path.join(config.baseViewDir, file);
        } else {
            filePath = path.join(config.sourceDir, file);
        }
        
        const content = readFile(filePath);
        if (content) {
            combinedJS += `\n/* ${file} */\n${content}\n`;
            console.log(`  ✓ ${file}`);
        }
    }
    
    const originalSize = Buffer.byteLength(combinedJS, 'utf8');
    
    try {
        const result = await minifyJS(combinedJS, {
            compress: {
                dead_code: true,
                drop_console: true,
                drop_debugger: true,
                conditionals: true,
                evaluate: true,
                booleans: true,
                loops: true,
                unused: true,
                hoist_funs: true,
                keep_fargs: false,
                hoist_vars: false,
                if_return: true,
                join_vars: true,
                side_effects: true,
                warnings: false
            },
            mangle: true,
            format: {
                comments: false
            }
        });
        
        const outputPath = path.join(config.distDir, outputName);
        ensureDir(path.dirname(outputPath));
        fs.writeFileSync(outputPath, result.code);
        
        const minifiedSize = Buffer.byteLength(result.code, 'utf8');
        const savings = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
        
        console.log(`  📦 ${(originalSize / 1024).toFixed(1)}KB → ${(minifiedSize / 1024).toFixed(1)}KB (${savings}% smaller)`);
        return true;
    } catch (error) {
        console.error('❌ JS Error:', error.message);
        return false;
    }
}

// Clean dist directory
function cleanDist() {
    if (fs.existsSync(config.distDir)) {
        fs.rmSync(config.distDir, { recursive: true, force: true });
    }
    ensureDir(config.distDir);
}

// Minify critical CSS separately
async function minifyCriticalCSS() {
    console.log('🎨 Minifying Critical CSS (inlined separately)');
    
    const criticalPath = path.join(config.baseViewDir, config.criticalCss);
    const content = readFile(criticalPath);
    
    if (!content) {
        console.warn('⚠️  Warning: Critical CSS not found');
        return false;
    }
    
    const originalSize = Buffer.byteLength(content, 'utf8');
    
    const output = new CleanCSS({
        level: 2,
        compatibility: 'ie11'
    }).minify(content);
    
    if (output.errors.length > 0) {
        console.error('❌ Critical CSS Errors:', output.errors);
        return false;
    }
    
    const outputPath = path.join(config.distDir, 'critical.min.css');
    fs.writeFileSync(outputPath, output.styles);
    
    const minifiedSize = Buffer.byteLength(output.styles, 'utf8');
    const savings = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
    
    console.log(`  ✓ ${config.criticalCss}`);
    console.log(`  📦 ${(originalSize / 1024).toFixed(1)}KB → ${(minifiedSize / 1024).toFixed(1)}KB (${savings}% smaller)\n`);
    return true;
}

// Main build process
async function build() {
    console.log('🚀 Starting Production Bundle Build\n');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    const startTime = Date.now();
    
    // Clean and create dist directory
    cleanDist();
    
    console.log('\n📦 Building Production Bundles\n');
    
    // Build critical CSS separately (this gets inlined)
    await minifyCriticalCSS();
    
    // Build all-in-one CSS bundle (base + all modules, NO critical.css)
    const allCssFiles = [...config.cssFiles];
    for (const module of config.modules) {
        const cssFilename = module === 'head' ? 'header' : module;
        allCssFiles.push(`modules/${module}/view/frontend/css/${cssFilename}.css`);
    }
    await minifyCSS(allCssFiles, 'all.min.css');
    
    // Build all-in-one JS bundle
    const allJsFiles = [...config.jsFiles];
    for (const module of config.modules) {
        const jsFilename = module === 'head' ? 'header' : module;
        const jsPath = `modules/${module}/view/frontend/js/${jsFilename}.js`;
        const jsFilePath = path.join(config.sourceDir, jsPath);
        if (fs.existsSync(jsFilePath)) {
            allJsFiles.push(jsPath);
        }
    }
    await minifyJavaScript(allJsFiles, 'all.min.js');
    
    const duration = ((Date.now() - startTime) / 1000).toFixed(2);
    
    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`✅ Build Complete in ${duration}s\n`);
    console.log('📁 Production Bundles:');
    console.log('  • pub/assets/dist/critical.min.css (inlined for instant LCP)');
    console.log('  • pub/assets/dist/all.min.css (loaded async)');
    console.log('  • pub/assets/dist/all.min.js (deferred)');
    console.log('\n🎯 Ready for deployment - no Node.js needed on server!\n');
}

// Run build
build().catch(error => {
    console.error('❌ Build failed:', error);
    process.exit(1);
});
