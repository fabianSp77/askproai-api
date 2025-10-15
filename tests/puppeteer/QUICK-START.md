# Quick Start - CRM Customer History E2E Test

## 1. Setup (One-time)

```bash
# Install Puppeteer
npm install puppeteer

# Set admin password
export ADMIN_PASSWORD=your_password_here

# Verify setup
./tests/puppeteer/verify-test-setup.sh
```

## 2. Run Test

```bash
# Standard run (headless)
./tests/run-customer-history-test.sh

# With visible browser (debugging)
./tests/run-customer-history-test.sh --no-headless
```

## 3. View Results

```bash
# Check exit code
echo $?  # 0 = success, 1 = failure

# View screenshots
ls -lh screenshots/

# View specific screenshot
open screenshots/customer-461-appointments.png
```

## 4. Troubleshooting

```bash
# Verify test data exists
php artisan tinker --execute="echo \App\Models\Customer::find(461);"

# Check appointments
php artisan tinker --execute="echo \App\Models\Appointment::whereIn('id', [672, 673])->get();"

# Test admin login manually
curl -I https://api.askproai.de/admin/login
```

## Common Issues

### Login Failed
```bash
# Reset admin password
php artisan tinker --execute="\$user = \App\Models\User::where('email', 'fabian@askproai.de')->first(); \$user->password = bcrypt('newpass'); \$user->save();"
```

### Puppeteer Not Found
```bash
npm install puppeteer --force
```

### ARM64 Chrome Issues
```bash
export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser
```

## Environment Variables

```bash
# Minimal setup
export ADMIN_PASSWORD=your_password

# Full configuration
export APP_URL=https://api.askproai.de
export ADMIN_EMAIL=fabian@askproai.de
export ADMIN_PASSWORD=your_password
export HEADLESS=true
```

## Test Verification Checklist

- [ ] Node.js installed (`node --version`)
- [ ] Puppeteer installed (`ls node_modules/puppeteer`)
- [ ] Admin password set (`echo $ADMIN_PASSWORD`)
- [ ] Customer #461 exists in database
- [ ] Appointments #672, #673 exist
- [ ] Admin panel accessible (`curl -I https://api.askproai.de/admin/login`)

## File Locations

```
/var/www/api-gateway/
├── tests/
│   ├── puppeteer/
│   │   ├── crm-customer-history-e2e.cjs      ← Test script
│   │   ├── README-CUSTOMER-HISTORY.md        ← Full documentation
│   │   ├── QUICK-START.md                    ← This file
│   │   └── verify-test-setup.sh              ← Setup checker
│   └── run-customer-history-test.sh          ← Test runner
└── screenshots/
    ├── customer-461-detail.png               ← Success screenshots
    ├── customer-461-appointments.png
    └── error-*.png                           ← Error screenshots
```

## Quick Commands

```bash
# Full workflow
npm install puppeteer
export ADMIN_PASSWORD=your_password
./tests/puppeteer/verify-test-setup.sh
./tests/run-customer-history-test.sh

# Debug mode
HEADLESS=false ./tests/run-customer-history-test.sh --no-headless

# Clean screenshots
rm -rf screenshots/error-*.png

# Re-run after fixes
./tests/run-customer-history-test.sh
```
