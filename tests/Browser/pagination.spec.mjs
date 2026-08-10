import { expect, test } from '@playwright/test';

test('pagination hover and focus preserve button geometry', async ({ page }) => {
    await page.goto('/pagination');

    const pageTwo = page.getByRole('button', { name: 'Go to page 2' });
    const before = await pageTwo.boundingBox();

    await pageTwo.hover();
    await expect(pageTwo).toHaveCSS('background-color', 'rgba(253, 151, 31, 0.1)');
    expect(await pageTwo.boundingBox()).toEqual(before);

    await pageTwo.focus();
    expect(await pageTwo.boundingBox()).toEqual(before);
    await expect(page.getByRole('button', { name: 'Go to page 1' })).toHaveAttribute('aria-current', 'page');
});
