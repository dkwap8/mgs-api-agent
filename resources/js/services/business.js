import axios from 'axios';


/**
 * Remove every business-scoped client cache before changing context.
 * Authentication, theme, merchant identity, and the business list are retained.
 */
function clearBusinessScopedClientState() {
    const retainedLocalKeys = new Set([
        'token',
        'theme',
        'user',
        'merchant',
        'businesses',
    ]);

    Object.keys(localStorage).forEach((key) => {
        if (!retainedLocalKeys.has(key)) {
            localStorage.removeItem(key);
        }
    });

    sessionStorage.clear();

    window.dispatchEvent(new CustomEvent('mgs:business-context-cleared'));
}

/**
 * Save only valid business records.
 *
 * A valid business must have:
 * - id
 * - merchant_id
 *
 * This prevents old merchant records from being displayed
 * inside the business switcher.
 */
function saveBusinesses(items) {
    const businesses = Array.isArray(items)
        ? items.filter((item) => {
            return (
                item &&
                Number.isInteger(Number(item.id)) &&
                Number(item.id) > 0 &&
                Number.isInteger(Number(item.merchant_id)) &&
                Number(item.merchant_id) > 0
            );
        })
        : [];

    localStorage.setItem('businesses', JSON.stringify(businesses));

    // This old key previously contained merchant records and must not
    // be used by the business switcher anymore.
    localStorage.removeItem('merchants');

    return businesses;
}

/**
 * Fetch the merchant account, selected business, all businesses,
 * wallet, and verification information.
 */
export async function fetchMerchantProfile() {
    const response = await axios.get('/api/v1/merchant/profile');
    const data = response.data?.data || {};

    const merchant = data.current_merchant || {};
    const currentBusiness = data.current_business || {};
    const businesses = saveBusinesses(data.businesses || []);

    localStorage.setItem('merchant', JSON.stringify(merchant));
    localStorage.setItem('business', JSON.stringify(currentBusiness));

    return {
        ...data,
        current_merchant: merchant,
        current_business: currentBusiness,
        businesses,
    };
}

/**
 * Fetch every business belonging to the authenticated merchant.
 */
export async function listBusinesses() {
    const response = await axios.get('/api/v1/merchant/businesses');
    const businesses = saveBusinesses(response.data?.data || []);

    return businesses;
}

/**
 * Create a new business under the authenticated merchant account.
 */
export async function createBusiness(payload) {
    const response = await axios.post(
        '/api/v1/merchant/businesses',
        payload
    );

    const business = response.data?.data;

    if (!business?.id) {
        throw new Error('The server did not return the created business.');
    }

    clearBusinessScopedClientState();
    localStorage.setItem('business', JSON.stringify(business));
    window.dispatchEvent(new CustomEvent('mgs:business-changed', { detail: business }));

    return business;
}

/**
 * Switch the active business.
 */
export async function switchBusiness(businessId) {
    const id = Number(businessId);

    if (!Number.isInteger(id) || id <= 0) {
        throw new Error('Invalid business ID.');
    }

    const response = await axios.post('/api/v1/merchant/switch', {
        business_id: id,
    });

    const business = response.data?.data;

    if (!business?.id) {
        throw new Error('The server did not return the selected business.');
    }

    clearBusinessScopedClientState();
    localStorage.setItem('business', JSON.stringify(business));
    window.dispatchEvent(new CustomEvent('mgs:business-changed', { detail: business }));

    return business;
}