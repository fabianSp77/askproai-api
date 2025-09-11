# 📤 How to Upload Flowbite Pro Files from Google Drive

## Option 1: Direct Server Upload (Recommended)

### Step 1: Download from Google Drive
1. Go to your Google Drive link: https://drive.google.com/drive/folders/1MEpv9w12cpdC_upis9VRomEXF47T1KEe
2. Download the entire folder as ZIP

### Step 2: Upload to Server
```bash
# Using SCP from your local machine:
scp flowbite-pro.zip root@api.askproai.de:/var/www/api-gateway/resources/

# Or using rsync:
rsync -avz flowbite-pro/ root@api.askproai.de:/var/www/api-gateway/resources/flowbite-pro/
```

### Step 3: Extract on Server
```bash
# SSH into server
ssh root@api.askproai.de

# Navigate to resources
cd /var/www/api-gateway/resources/

# Extract files
unzip flowbite-pro.zip -d flowbite-pro/

# Remove zip file
rm flowbite-pro.zip
```

## Option 2: Using wget/curl (If files are publicly accessible)

```bash
# Make the Google Drive folder temporarily public or get direct download links
# Then use wget:
cd /var/www/api-gateway/resources/flowbite-pro/
wget "YOUR_DIRECT_DOWNLOAD_LINK" -O flowbite-pro.zip
unzip flowbite-pro.zip
```

## Option 3: Manual File Structure

If you prefer to organize manually, here's the expected structure:

```
/var/www/api-gateway/resources/flowbite-pro/
├── components/
│   ├── alerts/
│   ├── buttons/
│   ├── cards/
│   ├── charts/
│   ├── datatables/
│   ├── forms/
│   ├── modals/
│   └── navigation/
├── layouts/
│   ├── admin/
│   ├── authentication/
│   ├── dashboard/
│   └── marketing/
├── pages/
│   ├── 404.html
│   ├── 500.html
│   ├── dashboard.html
│   ├── pricing.html
│   └── settings.html
├── widgets/
│   ├── analytics/
│   ├── charts/
│   └── stats/
├── js/
│   ├── flowbite-pro.js
│   ├── charts.js
│   └── datatable.js
└── css/
    ├── flowbite-pro.css
    └── flowbite-pro.min.css
```

## 🔍 Verification

After uploading, verify the files:

```bash
# Check if files are uploaded
ls -la /var/www/api-gateway/resources/flowbite-pro/

# Count total files
find /var/www/api-gateway/resources/flowbite-pro/ -type f | wc -l

# Check file permissions
ls -la /var/www/api-gateway/resources/flowbite-pro/components/
```

## 🎨 Integration Commands

Once files are uploaded, run:

```bash
# Register Pro components in Laravel
php artisan make:command RegisterFlowbiteProComponents

# Compile assets with Pro components
npm run build

# Clear all caches
php artisan optimize:clear
```

## 📱 Test Integration

Visit these URLs to test:
- Admin Dashboard: https://api.askproai.de/admin
- Test Widget: https://api.askproai.de/admin/widgets/flowbite-pro-stats

## 🆘 Troubleshooting

### If components don't appear:
```bash
# Check Tailwind config
cat tailwind.config.js | grep flowbite-pro

# Rebuild assets
npm run build

# Check browser console for errors
# Press F12 in browser
```

### If styles are missing:
```bash
# Ensure CSS is included in app.blade.php
grep flowbite resources/views/layouts/app.blade.php

# Check Vite manifest
cat public/build/manifest.json
```

## 📝 Notes

- Flowbite Pro files should NOT be committed to Git
- Add to .gitignore: `/resources/flowbite-pro/`
- Keep your license key secure
- Documentation: https://flowbite.com/pro/docs/

---
Need help? Contact support@flowbite.com with your license key.