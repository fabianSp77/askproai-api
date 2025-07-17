#!/usr/bin/env node

const newman = require('newman');
const path = require('path');
const fs = require('fs');

// Configuration
const collectionPath = path.join(__dirname, 'askproai-api-collection.json');
const environmentPath = path.join(__dirname, 'environments', process.env.NODE_ENV === 'ci' ? 'ci.json' : 'local.json');
const resultsDir = path.join(__dirname, 'results');

// Ensure results directory exists
if (!fs.existsSync(resultsDir)) {
    fs.mkdirSync(resultsDir, { recursive: true });
}

// Run options
const options = {
    collection: require(collectionPath),
    environment: require(environmentPath),
    reporters: ['cli', 'json', 'html'],
    reporter: {
        json: {
            export: path.join(resultsDir, `api-test-results-${Date.now()}.json`)
        },
        html: {
            export: path.join(resultsDir, `api-test-results-${Date.now()}.html`)
        }
    },
    insecure: true, // Skip SSL verification for local testing
    timeout: 10000, // 10 second timeout
    timeoutRequest: 5000, // 5 second request timeout
    bail: false, // Don't stop on first failure
    color: true,
    suppressExitCode: false
};

// Custom error handling
process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
    process.exit(1);
});

// Run the collection
console.log('🚀 Starting API tests...');
console.log(`📁 Collection: ${collectionPath}`);
console.log(`🌍 Environment: ${environmentPath}`);
console.log('');

newman.run(options, (err, summary) => {
    if (err) {
        console.error('❌ Collection run failed:', err);
        process.exit(1);
    }

    // Display summary
    console.log('\n📊 Test Summary:');
    console.log(`Total Requests: ${summary.run.stats.requests.total}`);
    console.log(`Failed Requests: ${summary.run.stats.requests.failed}`);
    console.log(`Total Assertions: ${summary.run.stats.assertions.total}`);
    console.log(`Failed Assertions: ${summary.run.stats.assertions.failed}`);
    console.log(`Average Response Time: ${Math.round(summary.run.timings.responseAverage)}ms`);
    
    // Check for failures
    if (summary.run.failures.length > 0) {
        console.error('\n❌ Test Failures:');
        summary.run.failures.forEach((failure, index) => {
            console.error(`\n${index + 1}. ${failure.source.name || 'Unknown'} - ${failure.error.test || 'Unknown test'}`);
            console.error(`   Error: ${failure.error.message}`);
            if (failure.error.stack) {
                console.error(`   Stack: ${failure.error.stack.split('\\n')[0]}`);
            }
        });
        process.exit(1);
    } else {
        console.log('\n✅ All tests passed!');
        console.log(`📄 Results saved to: ${resultsDir}`);
    }
});