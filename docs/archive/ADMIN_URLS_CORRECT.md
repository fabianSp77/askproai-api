# AskProAI Admin Panel - Correct URLs

## ✅ Working URLs (After Fix - Now Using English Slugs)

### 🏢 Branches
- **List:** https://api.askproai.de/admin/branches
- **Create:** https://api.askproai.de/admin/branches/create  
- **View:** https://api.askproai.de/admin/branches/34c4d48e-4753-4715-9c30-c55843a943e8
- **Edit:** https://api.askproai.de/admin/branches/34c4d48e-4753-4715-9c30-c55843a943e8/edit

### 👥 Staff
- **List:** https://api.askproai.de/admin/staff
- **Create:** https://api.askproai.de/admin/staff/create
- **View:** https://api.askproai.de/admin/staff/9f47fda1-977c-47aa-a87a-0e8cbeaeb119
- **Edit:** https://api.askproai.de/admin/staff/9f47fda1-977c-47aa-a87a-0e8cbeaeb119/edit

### 🏢 Companies  
- **List:** https://api.askproai.de/admin/companies
- **Create:** https://api.askproai.de/admin/companies/create
- **View:** https://api.askproai.de/admin/companies/1
- **Edit:** https://api.askproai.de/admin/companies/1/edit

### 👤 Users
- **List:** https://api.askproai.de/admin/users
- **Create:** https://api.askproai.de/admin/users/create
- **View:** https://api.askproai.de/admin/users/5
- **Edit:** https://api.askproai.de/admin/users/5/edit

### ⏰ Working Hours
- **List:** https://api.askproai.de/admin/working-hours
- **Create:** https://api.askproai.de/admin/working-hours/create
- **View:** https://api.askproai.de/admin/working-hours/{id}
- **Edit:** https://api.askproai.de/admin/working-hours/{id}/edit

## ✅ Previously Wrong URLs - Now Fixed

These URLs previously returned 404 but are now working:
- https://api.askproai.de/admin/branches/* (was using German slug 'filialen')
- https://api.askproai.de/admin/users/* (was using German slug 'benutzer')

## 🔧 Fixed Issues

1. **500 Errors:** Removed custom view references from ViewRecord pages
2. **405 Method Not Allowed:** Removed custom form views from Create/Edit pages  
3. **404 Errors:** Identified correct URL slugs (German vs English)

## 📝 Notes

- All resources now use English slugs for consistency
- All pages now use Filament's default rendering system
- Authentication is required to access these pages
- The view pages work correctly with the default infolist implementation

Last Updated: 2025-09-04 (Fixed German slug issue)