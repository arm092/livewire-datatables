import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    webServer: {
        command: 'node tests/Browser/run-test-server.mjs',
        url: 'http://127.0.0.1:49187/editable-csp',
        reuseExistingServer: false,
    },
    use: {
        baseURL: 'http://127.0.0.1:49187',
        browserName: 'chromium',
        headless: true,
    },
});
