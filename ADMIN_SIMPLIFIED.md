# КОНТАНТА Admin Panel - Simplified

## What Changed

✅ **Removed Complex Role System**
- No more: super-admin, admin, manager, viewer
- No permissions matrix
- Single unified admin interface

✅ **Backend Simplified**
- Old: `/api/lead-capture.php`, `/api/lead-update.php`, role checking, audit logs
- New: `/api/simple-auth.php`, `/api/simple-leads.php`, `/api/simple-stats.php`
- Token-only authentication (no role/permission checks)
- Direct lead management (get, list, delete)

✅ **Frontend Reduced to 4 Pages**
- **Dashboard** - Quick stats overview + navigation
- **Заявки (Applications)** - View, filter, delete, call leads
- **Звонки (Calls)** - Direct phone/WhatsApp interaction with leads
- **Статистика (Statistics)** - Detailed analytics

❌ **Removed Pages**
- Users management (no admin creation/editing)
- Roles management 
- Settings page
- Complex audit logs

## Quick Setup

### 1. Configure Admin Credentials
Edit `/api/simple-auth.php`:
```php
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('your_password_here', PASSWORD_BCRYPT));
```

To generate hash:
```php
<?php echo password_hash('admin123', PASSWORD_BCRYPT); ?>
```

### 2. API Endpoints

**Login:**
```
POST /api/simple-auth.php?action=login
Body: {"login": "admin", "password": "admin123"}
Response: {"ok": true, "token": "xxx", "admin": {"id": 1, "username": "admin"}}
```

**Get Applications:**
```
GET /api/simple-leads.php?token=XXX&action=list
Response: {"ok": true, "leads": [...], "total": N}
```

**Delete Application:**
```
POST /api/simple-leads.php?token=XXX&action=delete
Body: {"lead_id": 1}
```

**Get Statistics:**
```
GET /api/simple-stats.php?token=XXX
Response: {"ok": true, "stats": {"total": N, "new": N, ...}}
```

### 3. Frontend Build

```bash
cd admin-panel/frontend
npm install
npm run build
```

### 4. Credentials
- **Default Login:** admin
- **Default Password:** admin123 (CHANGE THIS!)

## File Structure

```
/api/
  ├── simple-auth.php      # Login/logout/verify token
  ├── simple-leads.php     # List, get, delete applications
  └── simple-stats.php     # Get statistics

/admin-panel/frontend/src/
  ├── pages/
  │   ├── LoginPage.tsx
  │   ├── DashboardPage.tsx
  │   ├── ApplicationsPage.tsx  (заявки)
  │   ├── CallsPage.tsx         (звонки)
  │   └── StatsPage.tsx         (статистика)
  ├── components/
  │   ├── Layout.tsx
  │   └── ProtectedRoute.tsx
  ├── context/store.ts     # Simplified auth store
  └── App.tsx              # Routes (4 pages only)
```

## Features

✅ **Applications**
- View all leads
- Filter by status
- Quick call (tel:// protocol)
- WhatsApp integration
- Delete applications

✅ **Calls**
- Select lead from list
- View full details
- Direct call button
- WhatsApp messaging

✅ **Statistics**
- Total counts by status
- Percentage breakdown
- Auto-refresh

✅ **Security**
- Token-based authentication
- 7-day session expiry
- No complex permissions needed
- Single admin account

## Next Steps

1. **Change Admin Password** - Update the hash in `simple-auth.php`
2. **Customize Branding** - Edit Layout.tsx colors and title
3. **Add Database** - Replace JSON storage with actual database (optional)
4. **Deploy** - Use Docker or your existing hosting

## Security Notes

⚠️ Default credentials must be changed before production!
⚠️ Token stored in localStorage - suitable for internal admin panels
⚠️ No encryption on sensitive data - use HTTPS
⚠️ Session expires in 7 days

## Differences vs Old System

| Feature | Old | New |
|---------|-----|-----|
| Roles | 4 (super-admin, admin, manager, viewer) | 1 (admin only) |
| Permissions | 18+ matrix | None (all can do all) |
| Pages | 7 | 4 |
| User Mgmt | Full CRUD | None |
| Audit Logs | Comprehensive | Basic token only |
| Complexity | High | Minimal |

Done! ✅
