# Automated audit results

Commit: 895c3ed5a441e394f84d4e75221b8a7b5cf08f0c

## PHP syntax
All PHP files passed `php -l`.

## JavaScript syntax
All standalone JavaScript files passed `node --check`.

## Build: admin-panel/backend
Install exit code: `1`

Build exit code: `2`

```text
npm error code ETARGET
npm error notarget No matching version found for jsonwebtoken@^9.1.2.
npm error notarget In most cases you or one of your dependencies are requesting
npm error notarget a package version that doesn't exist.
npm error A complete log of this run can be found in: /home/runner/.npm/_logs/2026-09-11T02_12_27_022Z-debug-0.log
```
```text

> admin-panel-backend@1.0.0 build
> tsc

tsconfig.json(13,25): error TS5108: Option 'moduleResolution=node10' has been removed. Please remove it from your configuration.
```

## Build: admin-panel/frontend
Install exit code: `0`

Build exit code: `2`

```text

> admin-panel-frontend@1.0.0 build
> tsc && vite build

src/App.tsx(2,10): error TS6133: 'BrowserRouter' is declared but its value is never read.
src/App.tsx(28,9): error TS6133: 'isAuthenticated' is declared but its value is never read.
src/App.tsx(29,17): error TS6133: 'setTheme' is declared but its value is never read.
src/components/MainLayout.tsx(4,24): error TS2724: '"../context/store"' has no exported member named 'useUIStore'. Did you mean 'useAuthStore'?
src/components/MainLayout.tsx(4,36): error TS2724: '"../context/store"' has no exported member named 'useThemeStore'. Did you mean 'useAuthStore'?
src/components/MainLayout.tsx(4,51): error TS2305: Module '"../context/store"' has no exported member 'useWebSocketStore'.
src/components/MainLayout.tsx(22,15): error TS2339: Property 'role' does not exist on type 'Admin'.
src/components/MainLayout.tsx(22,40): error TS7006: Parameter 'p' implicitly has an 'any' type.
src/components/MainLayout.tsx(25,15): error TS2339: Property 'role' does not exist on type 'Admin'.
src/components/MainLayout.tsx(25,40): error TS7006: Parameter 'p' implicitly has an 'any' type.
src/components/MainLayout.tsx(90,22): error TS2339: Property 'fullName' does not exist on type 'Admin'.
src/components/MainLayout.tsx(95,26): error TS2339: Property 'fullName' does not exist on type 'Admin'.
src/components/MainLayout.tsx(98,26): error TS2339: Property 'role' does not exist on type 'Admin'.
src/components/NotificationCenter.tsx(1,1): error TS6192: All imports in import declaration are unused.
src/components/NotificationCenter.tsx(3,10): error TS2724: '"../context/store"' has no exported member named 'useUIStore'. Did you mean 'useAuthStore'?
src/components/NotificationCenter.tsx(3,22): error TS2305: Module '"../context/store"' has no exported member 'useWebSocketStore'.
src/context/WebSocketProvider.tsx(3,24): error TS2305: Module '"./store"' has no exported member 'useWebSocketStore'.
src/context/WebSocketProvider.tsx(37,11): error TS2339: Property 'accessToken' does not exist on type 'AuthState'.
src/context/WebSocketProvider.tsx(50,26): error TS2339: Property 'env' does not exist on type 'ImportMeta'.
src/context/WebSocketProvider.tsx(66,26): error TS2339: Property 'env' does not exist on type 'ImportMeta'.
src/context/WebSocketProvider.tsx(105,37): error TS2339: Property 'env' does not exist on type 'ImportMeta'.
src/context/WebSocketProvider.tsx(151,27): error TS7006: Parameter 'c' implicitly has an 'any' type.
src/context/WebSocketProvider.tsx(201,10): error TS6133: 'data' is declared but its value is never read.
src/context/WebSocketProvider.tsx(202,27): error TS7006: Parameter 'c' implicitly has an 'any' type.
src/hooks/queries.ts(3,1): error TS6192: All imports in import declaration are unused.
src/pages/ApplicationsPage.tsx(19,11): error TS6133: 'user' is declared but its value is never read.
src/pages/CallsPage.tsx(19,11): error TS6133: 'user' is declared but its value is never read.
src/pages/DashboardPage.tsx(7,11): error TS6196: 'Lead' is declared but never used.
src/pages/DashboardPage.tsx(19,11): error TS6133: 'user' is declared but its value is never read.
src/pages/DashboardPage.tsx(61,24): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/DashboardPage.tsx(68,24): error TS2339: Property 'new' does not exist on type 'never'.
src/pages/DashboardPage.tsx(75,24): error TS2339: Property 'processing' does not exist on type 'never'.
src/pages/DashboardPage.tsx(82,24): error TS2339: Property 'completed' does not exist on type 'never'.
src/pages/DashboardPage.tsx(89,24): error TS2339: Property 'rejected' does not exist on type 'never'.
src/pages/SettingsPage.tsx(2,24): error TS2724: '"../context/store"' has no exported member named 'useThemeStore'. Did you mean 'useAuthStore'?
src/pages/SettingsPage.tsx(34,30): error TS2339: Property 'email' does not exist on type 'Admin'.
src/pages/SettingsPage.tsx(45,30): error TS2339: Property 'role' does not exist on type 'Admin'.
src/pages/StatsPage.tsx(9,11): error TS6133: 'user' is declared but its value is never read.
src/pages/StatsPage.tsx(59,24): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(68,24): error TS2339: Property 'new' does not exist on type 'never'.
src/pages/StatsPage.tsx(77,24): error TS2339: Property 'processing' does not exist on type 'never'.
src/pages/StatsPage.tsx(86,24): error TS2339: Property 'completed' does not exist on type 'never'.
src/pages/StatsPage.tsx(95,24): error TS2339: Property 'rejected' does not exist on type 'never'.
src/pages/StatsPage.tsx(111,26): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(112,41): error TS2339: Property 'new' does not exist on type 'never'.
src/pages/StatsPage.tsx(112,53): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(123,26): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(124,41): error TS2339: Property 'processing' does not exist on type 'never'.
src/pages/StatsPage.tsx(124,60): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(135,26): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(136,41): error TS2339: Property 'completed' does not exist on type 'never'.
src/pages/StatsPage.tsx(136,59): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(147,26): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(148,41): error TS2339: Property 'rejected' does not exist on type 'never'.
src/pages/StatsPage.tsx(148,58): error TS2339: Property 'total' does not exist on type 'never'.
src/pages/StatsPage.tsx(159,26): error TS2339: Property 'total' does not exist on type 'never'.
src/services/api.ts(10,28): error TS2339: Property 'env' does not exist on type 'ImportMeta'.
src/services/api.ts(15,45): error TS2339: Property 'accessToken' does not exist on type 'AuthState'.
```

## Broken local HTML references
- `ADMIN_START.html` → `MAP_OF_DOCS.md`
- `ADMIN_START.html` → `README_NEW_ADMIN.md`
- `admin-panel/frontend/index.html` → `/apple-touch-icon.png`
- `admin-panel/frontend/index.html` → `/favicon.svg`
- `admin-panel/frontend/index.html` → `/icon-192.png`
- `admin-panel/frontend/index.html` → `/src/main.tsx`
- `admin-panel/frontend/node_modules/@tailwindcss/forms/index.html` → `/dist/tailwind.css`
- `admin-panel/frontend/node_modules/@tailwindcss/forms/index.html` → `/kitchen-sink.html`
- `admin-panel/frontend/node_modules/@tailwindcss/forms/kitchen-sink.html` → `/dist/tailwind.css`
- `root/index.html` → `images/spec-driver-mechanic.jpeg`
- `root/index.html` → `images/spec-engineer-sapper.jpeg`
- `root/index.html` → `images/spec-fpv-operator.jpeg`
- `root/index.html` → `images/spec-it-specialist.jpeg`
- `root/index.html` → `images/spec-logistics-supply.jpeg`
- `root/index.html` → `images/spec-military-medic.jpeg`
- `root/index.html` → `images/spec-motor-rifle.jpeg`
- `root/index.html` → `images/spec-sniper.jpeg`
- `root/index.html` → `images/spec-tank-crew.jpeg`

## Duplicate files
- 3 bytes: `storage/rate_limit.json`, `storage/api_rate_limit.json`, `storage/lead_locks.json`, `storage/leads_meta.json`, `storage/sessions.json`, `storage/csrf_tokens.json`
- 3 bytes: `storage/leads.json`, `storage/admin_sessions.json`
