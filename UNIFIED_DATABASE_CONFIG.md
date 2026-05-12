# =============================================================================
# YG Ecosystem — Unified Database Configuration Guide
# =============================================================================
# All modules use ONE shared database for simplified management
# =============================================================================

## 📊 Database Configuration Summary

**Single Database for ALL Modules:**
```
Database Name: ygmarket_account (or {username}_ygmarket_account on cPanel)
Username:      ygmarket_account (or {username}_ygmarket_account on cPanel)
Password:      Ygaccount@2.0##2026 (CHANGE IN PRODUCTION!)
Host:          localhost (cPanel) or 127.0.0.1
Port:          3306
```

## 🎯 Modules Using This Database

✅ **All 13 Modules Share One Database:**
1. home (Search & Landing)
2. account (User Management & Authentication)
3. mail (Email Service)
4. calendar (Calendar App)
5. chat (Messaging)
6. contacts (Contact Manager)
7. drive (File Storage)
8. notes (Note-taking)
9. xcel (Spreadsheets)
10. docx (Documents)
11. collect (Data Collection)
12. developer (API Portal)
13. ai (AI Service - if Laravel-based)

## 🔧 Configuration Steps

### For LOCAL Development:

Each module's `.env` file should have:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD=Ygaccount@2.0##2026
```

### For cPanel DEPLOYMENT:

Replace with your cPanel credentials:
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE={username}_ygmarket_account
DB_USERNAME={username}_ygmarket_account
DB_PASSWORD=your_actual_password
```

**IMPORTANT:** Replace `{username}` with your actual cPanel username!

## 📋 Module-Specific .env Updates

### 1. Home Module ✅ (Already Configured)
File: `home/.env`
```env
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
```

### 2. Account Module
File: `account/.env`
```env
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
SESSION_DRIVER=database
SESSION_DOMAIN=.ygxone.com
```

### 3. Mail Module
File: `mail/.env`
```env
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
```

### 4. Calendar Module
File: `calendar/.env`
```env
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
```

### 5. Chat Module
Create: `chat/.env` from `chat/.env.example`
```env
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
```

### 6. Contacts Module
Create: `contacts/.env` from template
```env
DB_DATABASE=ygmarket_account
DB_USERNAME=ygmarket_account
DB_PASSWORD='Ygaccount@2.0##2026'
```

### 7-12. Other Modules (drive, notes, xcel, docx, collect, developer)
Same pattern - use unified database credentials

## ⚠️ Important Notes

### Table Naming Strategy:
To avoid conflicts between modules in the same database:

**Option A: Module-Prefixed Tables (Recommended)**
```
home_pages
home_search_index
account_users
account_sessions
mail_messages
calendar_events
chat_messages
contacts_entries
drive_files
notes_items
xcel_spreadsheets
docx_documents
collect_forms
```

**Option B: Shared User Tables**
```
users (shared across all modules via account service)
sessions (shared for SSO)
cache (shared cache)
```

### Migration Order:
When deploying, run migrations in this order:
1. `account` (creates users table first)
2. `home` (may reference users)
3. Other modules (in any order)

### Cross-Module Relationships:
With one database, you can create relationships like:
```sql
-- Example: Link calendar events to users
ALTER TABLE calendar_events 
ADD CONSTRAINT fk_user 
FOREIGN KEY (user_id) REFERENCES account_users(id);
```

## 🚀 Deployment Checklist

### On cPanel:

1. ✅ Create ONE database: `{username}_ygmarket_account`
2. ✅ Create ONE user: `{username}_ygmarket_account`
3. ✅ Grant ALL PRIVILEGES to the user on the database
4. ✅ Update ALL module `.env` files with same credentials
5. ✅ Run migrations for each module (they'll all go to same DB)
6. ✅ Test cross-module functionality

### Local Testing:

1. ✅ Ensure MySQL is running
2. ✅ Create database: `CREATE DATABASE ygmarket_account;`
3. ✅ Create user and grant privileges
4. ✅ Update all `.env` files
5. ✅ Run: `php artisan migrate` in each module
6. ✅ Test services

## 🔐 Security Recommendations

1. **Change the default password** in production!
2. Use different passwords for local vs production
3. Never commit `.env` files to Git
4. Use strong passwords (16+ characters, mixed case, numbers, symbols)
5. Restrict database user permissions to only what's needed

## 🛠️ Quick Setup Script

Run this PowerShell script to configure all modules:

```powershell
# File: setup-unified-database.ps1
$modules = @('account', 'mail', 'calendar', 'chat', 'contacts', 'drive', 'notes', 'xcel', 'docx', 'collect', 'developer')
$dbConfig = @{
    DB_HOST = '127.0.0.1'
    DB_PORT = '3306'
    DB_DATABASE = 'ygmarket_account'
    DB_USERNAME = 'ygmarket_account'
    DB_PASSWORD = 'Ygaccount@2.0##2026'
}

foreach ($module in $modules) {
    $envPath = "$module\.env"
    if (Test-Path $envPath) {
        Write-Host "Updating $module..." -ForegroundColor Green
        
        # Read current .env
        $content = Get-Content $envPath -Raw
        
        # Update database config
        foreach ($key in $dbConfig.Keys) {
            $pattern = "^${key}=.*$"
            $replacement = "${key}=$($dbConfig[$key])"
            $content = $content -replace $pattern, $replacement
        }
        
        # Write back
        Set-Content $envPath -Value $content -NoNewline
        Write-Host "  ✓ $module configured" -ForegroundColor Cyan
    } else {
        Write-Host "  ✗ $module has no .env file" -ForegroundColor Yellow
    }
}

Write-Host "`n✅ All modules configured with unified database!" -ForegroundColor Green
```

## 📞 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Table already exists** | Tables are shared - this is expected. Use `php artisan migrate --force` |
| **Foreign key errors** | Run migrations in order: account → others |
| **Connection refused** | Check DB credentials match across all modules |
| **Access denied** | Verify database user has ALL PRIVILEGES |
| **Duplicate table names** | Use module prefixes in migration files |

## ✅ Verification

After configuration, test each module:
```bash
cd home && php artisan migrate:status
cd account && php artisan migrate:status
cd mail && php artisan migrate:status
# ... repeat for all modules
```

All should show successful migrations to the SAME database!

---

**Last Updated:** 2026-05-11  
**Configuration:** Unified Single Database  
**Modules:** 13 total
