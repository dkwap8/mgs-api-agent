import { createRequire } from 'node:module';
import { mkdirSync, writeFileSync } from 'node:fs';
import assert from 'node:assert/strict';
const require = createRequire(process.env.TEMP + '/mgs-browser-verification/package.json');
const { chromium } = require('playwright');
const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const base = process.env.MGS_BROWSER_URL || 'http://127.0.0.1:8000';
const evidence = 'docs/review/browser';
mkdirSync(evidence, { recursive: true });
const findings = [], errors = [];
async function screenshot(options) { try { await page.screenshot({ ...options, animations: 'disabled', timeout: 120000 }); } catch (error) { console.log('Screenshot unavailable: ' + error.message.split('\n')[0]); } }
const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
const page = await context.newPage();
page.setDefaultTimeout(30000);
page.setDefaultNavigationTimeout(120000);
page.on('pageerror', e => errors.push(e.message));
async function open(path) { await page.goto(base + path); await page.waitForLoadState('networkidle'); }
async function record(name, operation) { await operation(); findings.push({ check: name, result: 'passed' }); console.log('PASS ' + name); }
try {
    await record('Public user login has no administrator selector, query cannot switch context', async () => {
        await open('/user/login?type=admin');
        assert.equal(await page.getByText('Administrator sign in', { exact: true }).count(), 0);
        assert.equal(await page.getByRole('button', { name: 'Admin Login', exact: true }).count(), 0);
        assert.equal(await page.getByRole('heading', { name: 'Sign in to your account' }).count(), 1);
        await screenshot({ path: evidence + '/user-login-desktop.png' });
    });
    await record('Unauthenticated admin page opens dedicated admin login', async () => {
        await open('/admin-panel/business-activations');
        assert.equal(new URL(page.url()).pathname, '/admin-panel');
        assert.equal(await page.getByRole('heading', { name: 'Sign in to the admin panel' }).count(), 1);
        await screenshot({ path: evidence + '/admin-login-desktop.png' });
    });
    await record('Unauthenticated user dashboard opens user login', async () => {
        await open('/user/dashboard'); assert.equal(new URL(page.url()).pathname, '/user/login');
    });
    await record('Administrator token does not redirect user login to administrator dashboard', async () => {
        await page.evaluate(() => localStorage.setItem('admin_session', '1'));
        await open('/user/login'); assert.equal(new URL(page.url()).pathname, '/user/login');
    });
    await page.evaluate(() => localStorage.clear());
    await record('Mobile login and dark theme fit the viewport', async () => {
        await page.setViewportSize({ width: 390, height: 844 }); await open('/user/login');
        await page.evaluate(() => document.documentElement.classList.add('dark'));
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        await screenshot({ path: evidence + '/user-login-mobile-dark.png', fullPage: true });
    });
    await page.setViewportSize({ width: 1366, height: 900 });
    await context.addCookies([{name:'mgs_user_session',value:'browser-user',url:base+'/api/v1',httpOnly:true,sameSite:'Strict'},{name:'mgs_admin_session',value:'browser-admin',url:base+'/api/v1/admin',httpOnly:true,sameSite:'Strict'}]);
    let customerRequests = 0;
    let previewAuthorization;
    const apiRequests = [];
    const activation = { id: 1, business_type: 'starter', business_name: 'Browser fixture business', status: 'under_review', proof_of_address: 'activations/proof-address/fixture.png', business_email: 'fixture@example.test', user: { name: 'Fixture user' }, created_at: '2026-10-04T10:00:00Z' };
    await page.route('**/api/**', async route => {
        const request = route.request(), path = new URL(request.url()).pathname;
        apiRequests.push({ path, authorization: request.headers().authorization, browserAuth: request.headers()['x-browser-auth'] });
        if (path.includes('/documents/')) {
            previewAuthorization = request.headers().authorization;
            return route.fulfill({ status: 200, contentType: 'image/png', body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=', 'base64') });
        }
        if (path === '/api/v1/customers') customerRequests++;
        let data = { data: [], total: 0, current_page: 1, last_page: 1 };
        if (path === '/api/v1/merchant/profile') data = { current_merchant: { id: 1, business_name: 'Fixture' }, current_business: { id: 1, name: 'Pending fixture', verification_status: 'under_review' }, businesses: [{ id: 1, name: 'Pending fixture', verification_status: 'under_review' }], wallet: { available_balance: '0.00', reserved_balance: '0.00' } };
        if (path === '/api/v1/merchant/businesses') data = [{ id: 1, name: 'Pending fixture', verification_status: 'under_review' }];
        if (path === '/api/v1/admin/business-activations') data = { data: [activation], total: 1, current_page: 1, last_page: 1 };
        if (path === '/api/v1/admin/business-activations/1') data = activation;
        if (path.endsWith('/auth/me')) data = { user: { name: 'Fixture user' }, admin: { name: 'Fixture admin', role: 'super_admin', two_factor_confirmed_at: null } };
        if (path === '/api/v1/admin/notifications/unread') data = [];
        if (path === '/api/v1/auth/devices') data = [{id:1,device_name:'Recorded browser fixture',ip_address:'192.0.2.1',last_seen_at:'2026-10-04T10:00:00Z'}];
        if (path === '/api/v1/auth/setup-two-factor') data = {secret:'FIXTURE-SETUP-KEY'};
        if (path === '/api/v1/admin/dashboard/summary') data = {total_merchants:25,total_transactions:101,total_customers:12,pending_payouts:2,total_wallet_balance:'50.01',health_status:'unmonitored'};
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, data, notification: { auto_show: false } }) });
    });
    await record('Pending dashboard renders without requesting restricted customers (mocked API)', async () => {
        await page.evaluate(() => { localStorage.setItem('merchant_session', '1'); localStorage.setItem('user', JSON.stringify({ name: 'Fixture User' })); localStorage.setItem('business', JSON.stringify({ id: 1, name: 'Pending fixture', verification_status: 'under_review' })); });
        await open('/user/dashboard'); assert.equal(customerRequests, 0);
        assert(apiRequests.some(request => request.path === '/api/v1/merchant/profile' && request.authorization === undefined && request.browserAuth === '1'));
        assert.equal(new URL(page.url()).pathname, '/user/dashboard');
        await screenshot({ path: evidence + '/pending-dashboard.png' });
    });
    await record('Activation preview retrieves authorized blob and displays image (mocked API)', async () => {
        await page.evaluate(() => { localStorage.setItem('admin_session', '1'); localStorage.setItem('admin', JSON.stringify({ name: 'Fixture Admin', role: 'super_admin' })); });
        await open('/admin-panel/business-activations');
        await page.getByRole('button', { name: 'Review application', exact: true }).click();
        await page.getByRole('button', { name: 'Preview', exact: true }).click();
        await page.getByRole('button', { name: 'Close preview' }).waitFor();
        assert.equal(previewAuthorization, undefined);
        assert.equal(await page.locator('img[alt="Proof of Address"]').count(), 1);
        await screenshot({ path: evidence + '/authorized-preview.png' });
        await page.getByRole('button', { name: 'Close preview' }).click();
        const download = page.waitForEvent('download');
        await page.getByRole('button', { name: 'Download', exact: true }).click();
        assert.equal((await download).suggestedFilename(), 'proof_of_address.png');
    });
    await record('Administrator dashboard loads its summary using its own browser context (mocked API)', async () => {
        await open('/admin-panel/dashboard');
        assert.equal(new URL(page.url()).pathname, '/admin-panel/dashboard');
        assert.equal(await page.getByText('Platform overview', {exact:true}).count(), 1);
        assert(apiRequests.some(request => request.path === '/api/v1/admin/dashboard/summary' && request.authorization === undefined && request.browserAuth === '1'));
    });
    await record('User security shows recorded devices and usable authenticator setup (mocked API)', async () => {
        await open('/user/settings');
        const security = page.getByRole('region', {name:'Account security'});
        await security.getByText('Recorded browser fixture', {exact:true}).waitFor();
        await security.getByLabel('Current password').fill('fixture-password');
        await security.getByRole('button', {name:'Set up authenticator',exact:true}).click();
        await security.getByText('FIXTURE-SETUP-KEY', {exact:true}).waitFor();
        assert.equal(await security.getByRole('button', {name:'Confirm authenticator',exact:true}).count(),1);
    });
    await record('Fixture API/file URLs return 404; Laravel route behavior is covered by PHPUnit', async () => {
        assert.equal((await context.request.get(base + '/api/v1/unknown')).status(), 404);
        assert.equal((await context.request.get(base + '/storage/activations/missing.jpg')).status(), 404);
    });
    assert.deepEqual(errors, []);
} finally {
    writeFileSync(evidence + '/results.json', JSON.stringify({ findings, pageErrors: errors, finalUrl: page.url(), finalPageText: (await page.locator('body').innerText()).slice(0, 5000), authenticatedData: 'mocked fixtures; no real provider calls or payments', url: base }, null, 2));
    await browser.close();
}
