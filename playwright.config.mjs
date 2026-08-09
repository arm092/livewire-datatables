import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    use: {
        baseURL: 'http://127.0.0.1:49187',
        browserName: 'chromium',
        headless: true,
    },
});
