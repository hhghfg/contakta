# Verification results

## PHP syntax
Passed.

## admin-panel/backend
Install: `0`; build: `0`

## admin-panel/frontend
Install: `0`; build: `2`
```text

> admin-panel-frontend@1.0.0 build
> tsc && vite build

src/components/NotificationCenter.tsx(1,1): error TS6192: All imports in import declaration are unused.
src/context/WebSocketProvider.tsx(201,10): error TS6133: 'data' is declared but its value is never read.
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
```
