# Audit fix verification
PHP syntax status: 0
admin-panel/backend install=0 build=0
admin-panel/frontend install=0 build=1

> admin-panel-frontend@1.0.0 build
> tsc && vite build

[36mvite v5.4.21 [32mbuilding for production...[36m[39m
transforming...
node:internal/process/promises:394
    triggerUncaughtException(err, true /* fromPromise */);
    ^

[Failed to load PostCSS config: Failed to load PostCSS config (searchPath: /home/runner/work/contakta/contakta/admin-panel/frontend): [ReferenceError] module is not defined in ES module scope
This file is being treated as an ES module because it has a '.js' file extension and '/home/runner/work/contakta/contakta/admin-panel/frontend/package.json' contains "type": "module". To treat it as a CommonJS script, rename it to use the '.cjs' file extension.
ReferenceError: module is not defined in ES module scope
This file is being treated as an ES module because it has a '.js' file extension and '/home/runner/work/contakta/contakta/admin-panel/frontend/package.json' contains "type": "module". To treat it as a CommonJS script, rename it to use the '.cjs' file extension.
    at file:///home/runner/work/contakta/contakta/admin-panel/frontend/postcss.config.js:1:1
    at ModuleJob.run (node:internal/modules/esm/module_job:343:25)
    at async onImport.tracePromise.__proto__ (node:internal/modules/esm/loader:681:26)
    at async importDefault (file:///home/runner/work/contakta/contakta/admin-panel/frontend/node_modules/vite/dist/node/chunks/dep-BK3b2jBa.js:33759:18)
    at async Object.search (file:///home/runner/work/contakta/contakta/admin-panel/frontend/node_modules/vite/dist/node/chunks/dep-BK3b2jBa.js:25915:23)]

Node.js v22.23.2
