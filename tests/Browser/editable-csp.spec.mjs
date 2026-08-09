import { expect, test } from '@playwright/test';
import { startServer } from './server.mjs';

let server;

test.beforeAll(async () => {
    server = await startServer();
});

test.afterAll(async () => {
    await new Promise((resolve, reject) => server.close(error => error ? reject(error) : resolve()));
});

test('editable cells remain stable through CSP-safe hydration and morphing', async ({ page }) => {
    const errors = [];

    page.on('console', message => {
        if (message.type() === 'error' || message.type() === 'warning') {
            errors.push(message.text());
        }
    });
    page.on('pageerror', error => errors.push(error.message));

    await page.goto('/editable-csp');
    await page.waitForFunction(() => window.Livewire && window.Alpine && document.querySelector('#cell-1')._x_dataStack);

    const firstPanel = page.locator('#cell-1 [data-edit-panel]');
    const secondPanel = page.locator('#cell-2 [data-edit-panel]');

    await expect(firstPanel).toBeHidden();
    await expect(secondPanel).toBeHidden();

    await page.locator('#cell-1 button').click();
    await expect(firstPanel).toBeVisible();
    await expect(page.locator('#cell-1 input')).toBeFocused();
    await expect(secondPanel).toBeHidden();

    await page.locator('#cell-2 button').click();
    await expect(firstPanel).toBeHidden();
    await expect(secondPanel).toBeVisible();
    await expect(page.locator('#cell-2 input')).toBeFocused();

    await page.locator('#cell-2 input').press('Enter');
    await expect(secondPanel).toBeHidden();

    await page.evaluate(() => {
        document.querySelector('main[wire\\:id]').dispatchEvent(new CustomEvent('fieldEdited', {
            bubbles: true,
            detail: { rowId: 1, column: 'slug' },
        }));
    });
    await expect(page.locator('#cell-1 button')).toHaveClass(/text-green-500/);
    await expect(page.locator('#cell-2 button')).not.toHaveClass(/text-green-500/);

    const visibilityDuringMorph = await page.evaluate(async () => {
        const cell = document.querySelector('#cell-1');
        const replacement = cell.outerHTML.replace('first', 'first after morph');
        const samples = [];

        window.Alpine.morph(cell, replacement);
        samples.push(getComputedStyle(document.querySelector('#cell-1 [data-edit-panel]')).display);

        await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
        samples.push(getComputedStyle(document.querySelector('#cell-1 [data-edit-panel]')).display);

        return samples;
    });

    expect(visibilityDuringMorph).toEqual(['none', 'none']);
    await expect(firstPanel).toBeHidden();
    await page.locator('#cell-1 button').click();
    await expect(page.locator('#cell-1 input')).toBeFocused();
    await page.locator('#cell-2 button').click();
    await expect(firstPanel).toBeHidden();
    await expect(secondPanel).toBeVisible();
    expect(errors).toEqual([]);
});
