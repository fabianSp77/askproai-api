#!/usr/bin/env node

/**
 * UX Analysis Script - Comprehensive Admin Panel Testing
 * Using Puppeteer (ARM64 compatible)
 *
 * Tests all three new resources with detailed UX evaluation:
 * - PolicyConfigurationResource
 * - NotificationConfigurationResource
 * - AppointmentModificationResource
 */

const puppeteer = require('puppeteer');
const fs = require('fs').promises;
const path = require('path');

const ADMIN_URL = process.env.ADMIN_URL || 'https://api.askproai.de/admin';
const ADMIN_EMAIL = 'admin@askproai.de';
const ADMIN_PASSWORD = 'testpassword123';
const SCREENSHOT_DIR = path.join(__dirname, '../storage/ux-analysis-screenshots');

const uxIssues = [];
let screenshotCounter = 0;

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function ensureScreenshotDir() {
    try {
        await fs.mkdir(SCREENSHOT_DIR, { recursive: true });
        console.log(`✅ Screenshot directory: ${SCREENSHOT_DIR}`);
    } catch (err) {
        console.error(`❌ Failed to create screenshot dir: ${err.message}`);
    }
}

function generateScreenshotName(resource, pageType, description = '') {
    screenshotCounter++;
    const timestamp = String(screenshotCounter).padStart(3, '0');
    const desc = description ? `-${description}` : '';
    return `${resource}-${pageType}${desc}-${timestamp}.png`;
}

function recordUxIssue(screenshot, problem, severity, userImpact, evidence) {
    uxIssues.push({
        screenshot,
        problem,
        severity,
        userImpact,
        evidence,
        timestamp: new Date().toISOString()
    });
}

async function captureAndAnalyze(page, resource, pageType, description = '') {
    const filename = generateScreenshotName(resource, pageType, description);
    const filepath = path.join(SCREENSHOT_DIR, filename);
    await page.screenshot({ path: filepath, fullPage: true });
    console.log(`   📸 Screenshot: ${filename}`);
    return filename;
}

async function analyzeFormField(page, selector, fieldName, resource, screenshot) {
    try {
        const field = await page.$(selector);
        if (!field) return null;

        const analysis = await page.evaluate((sel, name) => {
            const element = document.querySelector(sel);
            if (!element) return null;

            const container = element.closest('.form-group, .field-group, div[class*="field"]');
            const label = container?.querySelector('label');
            const helpText = container?.querySelector('.help-text, .hint, small, .description');
            const placeholder = element.getAttribute('placeholder');
            const required = element.hasAttribute('required') || element.getAttribute('aria-required') === 'true';

            return {
                hasLabel: !!label,
                labelText: label?.textContent?.trim() || '',
                hasHelpText: !!helpText,
                helpText: helpText?.textContent?.trim() || '',
                placeholder: placeholder || '',
                required: required,
                type: element.type || element.tagName.toLowerCase()
            };
        }, selector, fieldName);

        // UX Issue Detection
        if (!analysis.hasLabel || !analysis.labelText) {
            recordUxIssue(
                screenshot,
                `Missing label for "${fieldName}" field`,
                'HIGH',
                'User cannot understand field purpose without label',
                `Field ${selector} has no visible label`
            );
        }

        if (analysis.required && !analysis.hasHelpText && !analysis.placeholder) {
            recordUxIssue(
                screenshot,
                `Required field "${fieldName}" lacks guidance`,
                'MEDIUM',
                'User may enter invalid data without help text or placeholder',
                `Required field ${selector} has no help text or placeholder`
            );
        }

        return analysis;
    } catch (err) {
        console.log(`   ⚠️  Field analysis failed for ${fieldName}: ${err.message}`);
        return null;
    }
}

async function login(page) {
    console.log(`\n🔐 Logging in as ${ADMIN_EMAIL}...`);

    await page.goto(`${ADMIN_URL}/login`, { waitUntil: 'networkidle2', timeout: 30000 });
    await captureAndAnalyze(page, 'login', 'page', 'initial');

    // Wait for form to be ready
    await page.waitForSelector('input[type="email"]', { timeout: 10000 });

    // Fill login form
    await page.type('input[type="email"]', ADMIN_EMAIL);
    await page.type('input[type="password"]', ADMIN_PASSWORD);

    await captureAndAnalyze(page, 'login', 'page', 'filled');

    // Click login button and wait for navigation with multiple strategies
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => {}),
        page.click('button[type="submit"]')
    ]);

    // Wait a bit for potential redirects
    await delay(3000);

    await captureAndAnalyze(page, 'login', 'success');

    const url = page.url();
    console.log(`   Current URL after login: ${url}`);

    if (url.includes('/admin') && !url.includes('/login')) {
        console.log('✅ Login successful');
        return true;
    } else {
        console.log('❌ Login failed - still on login page or unexpected URL');
        return false;
    }
}

async function evaluatePageIntuition(page, resource, pageType) {
    const evaluation = await page.evaluate(() => {
        const issues = [];

        // Check for obvious UI problems
        const unlabeledInputs = document.querySelectorAll('input:not([aria-label]):not([aria-labelledby])');
        const parentHasLabel = (input) => {
            const container = input.closest('.form-group, .field-group, div[class*="field"]');
            return container?.querySelector('label') !== null;
        };

        unlabeledInputs.forEach(input => {
            if (!parentHasLabel(input) && input.type !== 'hidden') {
                issues.push(`Unlabeled input: ${input.name || input.id || 'unknown'}`);
            }
        });

        // Check for validation feedback elements
        const hasValidationMessages = document.querySelector('.error, .invalid-feedback, [class*="error"]') !== null;

        // Check for help/hint elements
        const helpElements = document.querySelectorAll('.help-text, .hint, small, .description');

        // Count form fields
        const formFields = document.querySelectorAll('input, textarea, select').length;

        return {
            unlabeledIssues: issues,
            hasValidationUI: hasValidationMessages,
            helpElementCount: helpElements.length,
            formFieldCount: formFields,
            pageTitle: document.title,
            hasHeading: document.querySelector('h1, h2') !== null
        };
    });

    // Calculate intuition score
    let score = 10;
    if (evaluation.unlabeledIssues.length > 0) score -= evaluation.unlabeledIssues.length * 2;
    if (!evaluation.hasHeading) score -= 1;
    if (evaluation.formFieldCount > 0 && evaluation.helpElementCount === 0) score -= 3;
    if (!evaluation.hasValidationUI) score -= 2;
    score = Math.max(1, score);

    console.log(`   📊 Intuition Score: ${score}/10`);
    console.log(`   📝 Page Title: ${evaluation.pageTitle}`);
    console.log(`   🏷️  Form Fields: ${evaluation.formFieldCount}`);
    console.log(`   💡 Help Elements: ${evaluation.helpElementCount}`);

    if (evaluation.unlabeledIssues.length > 0) {
        console.log(`   ⚠️  Unlabeled fields: ${evaluation.unlabeledIssues.length}`);
    }

    return {
        score,
        ...evaluation
    };
}

async function testPolicyConfigurationResource(page) {
    console.log('\n\n═══════════════════════════════════════════════════════');
    console.log('⚙️  TESTING: PolicyConfiguration Resource');
    console.log('═══════════════════════════════════════════════════════\n');

    const resource = 'policy-config';
    const baseUrl = `${ADMIN_URL}/policy-configurations`;

    // Test 1: List View
    console.log('\n📋 Test 1: List View');
    try {
        await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await delay(2000);
    } catch (err) {
        console.log(`   ⚠️  Navigation warning: ${err.message}`);
    }
    const listScreenshot = await captureAndAnalyze(page, resource, 'list');
    const listEval = await evaluatePageIntuition(page, resource, 'list');

    // Check for table/list elements
    const hasTable = await page.$('table') !== null;
    const hasCreateButton = await page.evaluate(() => {
        return !!document.querySelector('a[href*="create"], button');
    });
    console.log(`   📊 Has table: ${hasTable ? '✅' : '❌'}`);
    console.log(`   ➕ Has create button: ${hasCreateButton ? '✅' : '❌'}`);

    if (!hasTable && !hasCreateButton) {
        recordUxIssue(
            listScreenshot,
            'List view lacks table and create button',
            'CRITICAL',
            'User cannot view or create policy configurations',
            'No table or create button found on list page'
        );
    }

    // Test 2: Create Form
    console.log('\n📝 Test 2: Create Form');
    try {
        await page.goto(`${baseUrl}/create`, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await delay(2000);
        const createEmptyScreenshot = await captureAndAnalyze(page, resource, 'create-form', 'empty');
        const createEval = await evaluatePageIntuition(page, resource, 'create-form');

        // Analyze critical fields
        await analyzeFormField(page, 'input[name="name"]', 'name', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'select[name="policy_type"]', 'policy_type', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'textarea[name="description"]', 'description', resource, createEmptyScreenshot);

        // CRITICAL: KeyValue field analysis
        const keyValueField = await page.$('input[name="key_value"], textarea[name="key_value"], [name="key_value"]');
        if (keyValueField) {
            console.log('\n   🔍 CRITICAL: Analyzing KeyValue field...');
            const kvAnalysis = await analyzeFormField(page, '[name="key_value"]', 'key_value', resource, createEmptyScreenshot);

            if (!kvAnalysis?.hasHelpText) {
                recordUxIssue(
                    createEmptyScreenshot,
                    'KeyValue field has NO explanation of allowed keys/values',
                    'CRITICAL',
                    'User cannot use feature without documentation. Must guess keys like "hours_before", "max_cancellations_per_month" and their formats',
                    'KeyValue field lacks help text, examples, or validation hints'
                );
            }

            if (!kvAnalysis?.placeholder) {
                recordUxIssue(
                    createEmptyScreenshot,
                    'KeyValue field lacks placeholder example',
                    'HIGH',
                    'User has no visual example of expected format (JSON, key=value, etc)',
                    'No placeholder attribute on KeyValue field'
                );
            }
        } else {
            console.log('   ⚠️  KeyValue field not found on create form');
        }

        // Fill form with test data
        console.log('\n   ✏️  Filling form with test data...');
        await page.type('input[name="name"]', 'UX Test Policy');

        const policyTypeSelect = await page.$('select[name="policy_type"]');
        if (policyTypeSelect) {
            await page.select('select[name="policy_type"]', 'cancellation');
        }

        const descField = await page.$('textarea[name="description"]');
        if (descField) {
            await page.type('textarea[name="description"]', 'Test policy for UX analysis');
        }

        const createFilledScreenshot = await captureAndAnalyze(page, resource, 'create-form', 'filled');

        // Try to submit (but don't actually save)
        const submitButton = await page.$('button[type="submit"]');
        if (submitButton) {
            console.log('   📸 Capturing form before submit...');
            // Don't actually submit - we're just testing UX
        }

    } catch (err) {
        console.log(`   ❌ Create form test failed: ${err.message}`);
        recordUxIssue(
            'policy-config-create-error',
            'Create form not accessible',
            'CRITICAL',
            'User cannot create new policy configurations',
            err.message
        );
    }

    // Test 3: Edit Form (if records exist)
    console.log('\n✏️  Test 3: Edit Form');
    try {
        // Try to find first record
        try {
            await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await delay(2000);
        } catch (err) {
            console.log(`   ⚠️  Navigation warning: ${err.message}`);
        }

        const editUrl = await page.evaluate(() => {
            return document.querySelector('a[href*="/edit"]')?.href;
        });

        if (editUrl) {
            await page.goto(editUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await delay(2000);
            const editScreenshot = await captureAndAnalyze(page, resource, 'edit-form', 'loaded');
            const editEval = await evaluatePageIntuition(page, resource, 'edit-form');
        } else {
            console.log('   ℹ️  No existing records to edit');
        }
    } catch (err) {
        console.log(`   ⚠️  Edit form test skipped: ${err.message}`);
    }

    return {
        resource: 'PolicyConfiguration',
        listScore: listEval.score,
        createScore: createEval?.score || 0,
        testsCompleted: 2
    };
}

async function testNotificationConfigurationResource(page) {
    console.log('\n\n═══════════════════════════════════════════════════════');
    console.log('📧 TESTING: NotificationConfiguration Resource');
    console.log('═══════════════════════════════════════════════════════\n');

    const resource = 'notification-config';
    const baseUrl = `${ADMIN_URL}/notification-configurations`;

    // Test 1: List View
    console.log('\n📋 Test 1: List View');
    try {
        await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await delay(2000);
    } catch (err) {
        console.log(`   ⚠️  Navigation warning: ${err.message}`);
    }
    const listScreenshot = await captureAndAnalyze(page, resource, 'list');
    const listEval = await evaluatePageIntuition(page, resource, 'list');

    // Test 2: Create Form
    console.log('\n📝 Test 2: Create Form');
    try {
        await page.goto(`${baseUrl}/create`, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await delay(2000);
        const createEmptyScreenshot = await captureAndAnalyze(page, resource, 'create-form', 'empty');
        const createEval = await evaluatePageIntuition(page, resource, 'create-form');

        // Analyze form fields
        await analyzeFormField(page, 'input[name="event_type"]', 'event_type', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'select[name="channel"]', 'channel', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'textarea[name="template"]', 'template', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'input[name="enabled"]', 'enabled', resource, createEmptyScreenshot);

        // Fill test data
        const nameField = await page.$('input[name="event_type"]');
        if (nameField) {
            await page.type('input[name="event_type"]', 'appointment_created');
        }

        const createFilledScreenshot = await captureAndAnalyze(page, resource, 'create-form', 'filled');

    } catch (err) {
        console.log(`   ❌ Create form test failed: ${err.message}`);
        recordUxIssue(
            'notification-config-create-error',
            'Create form not accessible',
            'HIGH',
            'User cannot create notification configurations',
            err.message
        );
    }

    // Test 3: Edit Form
    console.log('\n✏️  Test 3: Edit Form');
    try {
        await page.goto(baseUrl, { waitUntil: 'networkidle2' });
        const editLink = await page.$('a[href*="/edit"]');
        if (editLink) {
            await editLink.click();
            await page.waitForNavigation({ waitUntil: 'networkidle2' });
            const editScreenshot = await captureAndAnalyze(page, resource, 'edit-form', 'loaded');
            const editEval = await evaluatePageIntuition(page, resource, 'edit-form');
        } else {
            console.log('   ℹ️  No existing records to edit');
        }
    } catch (err) {
        console.log(`   ⚠️  Edit form test skipped: ${err.message}`);
    }

    return {
        resource: 'NotificationConfiguration',
        listScore: listEval.score,
        createScore: createEval?.score || 0,
        testsCompleted: 2
    };
}

async function testAppointmentModificationResource(page) {
    console.log('\n\n═══════════════════════════════════════════════════════');
    console.log('📝 TESTING: AppointmentModification Resource');
    console.log('═══════════════════════════════════════════════════════\n');

    const resource = 'appointment-mod';
    const baseUrl = `${ADMIN_URL}/appointment-modifications`;

    // Test 1: List View
    console.log('\n📋 Test 1: List View');
    try {
        await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await delay(2000);
    } catch (err) {
        console.log(`   ⚠️  Navigation warning: ${err.message}`);
    }
    const listScreenshot = await captureAndAnalyze(page, resource, 'list');
    const listEval = await evaluatePageIntuition(page, resource, 'list');

    // Test 2: Create Form
    console.log('\n📝 Test 2: Create Form');
    try {
        await page.goto(`${baseUrl}/create`, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await delay(2000);
        const createEmptyScreenshot = await captureAndAnalyze(page, resource, 'create-form', 'empty');
        const createEval = await evaluatePageIntuition(page, resource, 'create-form');

        // Analyze form fields
        await analyzeFormField(page, 'select[name="appointment_id"]', 'appointment_id', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'select[name="modification_type"]', 'modification_type', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'textarea[name="reason"]', 'reason', resource, createEmptyScreenshot);
        await analyzeFormField(page, 'input[name="modified_by"]', 'modified_by', resource, createEmptyScreenshot);

        const createFilledScreenshot = await captureAndAnalyze(page, resource, 'create-form', 'partial');

    } catch (err) {
        console.log(`   ❌ Create form test failed: ${err.message}`);
        recordUxIssue(
            'appointment-mod-create-error',
            'Create form not accessible',
            'HIGH',
            'User cannot create appointment modifications',
            err.message
        );
    }

    // Test 3: View Page
    console.log('\n👁️  Test 3: View Page');
    try {
        await page.goto(baseUrl, { waitUntil: 'networkidle2' });
        const viewLink = await page.$('a[href*="/view"], a[title*="View"]');
        if (viewLink) {
            await viewLink.click();
            await page.waitForNavigation({ waitUntil: 'networkidle2' });
            const viewScreenshot = await captureAndAnalyze(page, resource, 'view-page', 'loaded');
        } else {
            console.log('   ℹ️  No records to view');
        }
    } catch (err) {
        console.log(`   ⚠️  View page test skipped: ${err.message}`);
    }

    return {
        resource: 'AppointmentModification',
        listScore: listEval.score,
        createScore: createEval?.score || 0,
        testsCompleted: 2
    };
}

async function generateUxReport() {
    console.log('\n\n═══════════════════════════════════════════════════════');
    console.log('📊 GENERATING UX ANALYSIS REPORT');
    console.log('═══════════════════════════════════════════════════════\n');

    // Sort issues by severity
    const criticalIssues = uxIssues.filter(i => i.severity === 'CRITICAL');
    const highIssues = uxIssues.filter(i => i.severity === 'HIGH');
    const mediumIssues = uxIssues.filter(i => i.severity === 'MEDIUM');
    const lowIssues = uxIssues.filter(i => i.severity === 'LOW');

    const report = {
        metadata: {
            timestamp: new Date().toISOString(),
            adminUrl: ADMIN_URL,
            totalScreenshots: screenshotCounter,
            totalIssues: uxIssues.length,
            criticalCount: criticalIssues.length,
            highCount: highIssues.length,
            mediumCount: mediumIssues.length,
            lowCount: lowIssues.length
        },
        top10CriticalIssues: [...criticalIssues, ...highIssues].slice(0, 10),
        allIssues: uxIssues,
        screenshotList: Array.from({ length: screenshotCounter }, (_, i) => {
            const num = String(i + 1).padStart(3, '0');
            return `Screenshot ${num}: Check storage/ux-analysis-screenshots/`;
        })
    };

    // Save JSON report
    const jsonPath = path.join(SCREENSHOT_DIR, 'ux-analysis-results.json');
    await fs.writeFile(jsonPath, JSON.stringify(report, null, 2));

    // Generate markdown report
    let markdown = `# UX Analysis Report - Admin Panel

**Generated:** ${new Date().toISOString()}
**Admin URL:** ${ADMIN_URL}
**Total Screenshots:** ${screenshotCounter}
**Total UX Issues:** ${uxIssues.length}

## Summary

| Severity | Count |
|----------|-------|
| 🔴 CRITICAL | ${criticalIssues.length} |
| 🟠 HIGH | ${highIssues.length} |
| 🟡 MEDIUM | ${mediumIssues.length} |
| 🟢 LOW | ${lowIssues.length} |

## Top 10 Critical UX Problems

`;

    [...criticalIssues, ...highIssues].slice(0, 10).forEach((issue, idx) => {
        markdown += `### ${idx + 1}. ${issue.problem}

**Screenshot:** \`${issue.screenshot}\`
**Severity:** ${issue.severity}

**User Impact:**
${issue.userImpact}

**Evidence:**
${issue.evidence}

---

`;
    });

    markdown += `## All UX Issues by Resource\n\n`;

    ['policy-config', 'notification-config', 'appointment-mod'].forEach(resource => {
        const resourceIssues = uxIssues.filter(i => i.screenshot.startsWith(resource));
        if (resourceIssues.length > 0) {
            markdown += `### ${resource.toUpperCase()}\n\n`;
            resourceIssues.forEach((issue, idx) => {
                markdown += `${idx + 1}. **[${issue.severity}]** ${issue.problem}\n`;
                markdown += `   - Screenshot: \`${issue.screenshot}\`\n`;
                markdown += `   - Impact: ${issue.userImpact}\n\n`;
            });
        }
    });

    markdown += `## Screenshot List\n\n`;
    markdown += `Total screenshots captured: **${screenshotCounter}**\n\n`;
    markdown += `All screenshots saved to: \`/var/www/api-gateway/storage/ux-analysis-screenshots/\`\n\n`;
    markdown += `### Screenshot Naming Convention\n\n`;
    markdown += `- Format: \`{resource}-{page-type}-{description}-{counter}.png\`\n`;
    markdown += `- Example: \`policy-config-create-form-empty-004.png\`\n\n`;

    const mdPath = path.join(SCREENSHOT_DIR, 'UX_ANALYSIS.md');
    await fs.writeFile(mdPath, markdown);

    console.log(`✅ JSON Report: ${jsonPath}`);
    console.log(`✅ Markdown Report: ${mdPath}`);
    console.log(`\n📊 Statistics:`);
    console.log(`   Total Issues: ${uxIssues.length}`);
    console.log(`   Critical: ${criticalIssues.length}`);
    console.log(`   High: ${highIssues.length}`);
    console.log(`   Medium: ${mediumIssues.length}`);
    console.log(`   Low: ${lowIssues.length}`);

    return report;
}

async function main() {
    console.log('🚀 Starting Comprehensive UX Analysis...');
    console.log(`   Admin URL: ${ADMIN_URL}`);
    console.log(`   Login: ${ADMIN_EMAIL}`);
    console.log(`   Chromium: /usr/bin/chromium`);

    await ensureScreenshotDir();

    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/chromium',
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1920,1080'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    const results = {
        timestamp: new Date().toISOString(),
        resources: []
    };

    try {
        // Login
        const loginSuccess = await login(page);
        if (!loginSuccess) {
            throw new Error('Login failed - cannot continue UX analysis');
        }

        // Test all three resources
        results.resources.push(await testPolicyConfigurationResource(page));
        results.resources.push(await testNotificationConfigurationResource(page));
        results.resources.push(await testAppointmentModificationResource(page));

        // Generate comprehensive UX report
        const report = await generateUxReport();
        results.report = report;

    } catch (err) {
        console.error(`\n❌ UX Analysis failed: ${err.message}`);
        console.error(err.stack);
        results.error = err.message;
    } finally {
        await browser.close();
    }

    console.log('\n\n═══════════════════════════════════════════════════════');
    console.log('✅ UX ANALYSIS COMPLETE');
    console.log('═══════════════════════════════════════════════════════');
    console.log(`\n📸 Total Screenshots: ${screenshotCounter}`);
    console.log(`📝 Total UX Issues: ${uxIssues.length}`);
    console.log(`📂 Output Directory: ${SCREENSHOT_DIR}`);

    process.exit(results.error ? 1 : 0);
}

main().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
