import axios from 'axios';
import { verificationModal } from './services/verificationModal';

window.axios = axios;
// Retire browser bearer storage; API/mobile clients still use bearer tokens.
for (const key of ['admin_token', 'merchant_token', 'token', 'access_token']) localStorage.removeItem(key);

axios.defaults.baseURL =
    import.meta.env.VITE_APP_URL ||
    window.location.origin;

axios.defaults.headers.common['X-Requested-With'] =
    'XMLHttpRequest';

axios.defaults.headers.common.Accept =
    'application/json';

axios.defaults.withCredentials = true;

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.content;

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] =
        csrfToken;
}

/*
|--------------------------------------------------------------------------
| Automatically attach the correct token
|--------------------------------------------------------------------------
*/

axios.interceptors.request.use((config) => {

    const requestUrl = new URL(String(config.url || ''), config.baseURL || window.location.origin);
    const sameOrigin = requestUrl.origin === window.location.origin;
    const isAdmin = requestUrl.pathname.startsWith('/api/v1/admin/');
    if (sameOrigin) {
        config.headers = config.headers || {};
        config.headers['X-Browser-Auth'] = '1';
        const csrfName = isAdmin ? 'mgs_admin_csrf' : 'mgs_user_csrf';
        const csrf = document.cookie.split('; ').find(value => value.startsWith(csrfName + '='))?.slice(csrfName.length + 1);
        if (csrf) config.headers['X-MGS-CSRF'] = decodeURIComponent(csrf);
    }

    config.headers = config.headers || {};
    delete config.headers.Authorization;
    delete config.headers['X-Business-ID'];
    if (!sameOrigin) config.withCredentials = false;

    const rawBusiness =
        localStorage.getItem('business');

    try {

        const business = rawBusiness
            ? JSON.parse(rawBusiness)
            : null;

        if (business?.id && !isAdmin && sameOrigin) {

            config.headers =
                config.headers || {};

            config.headers['X-Business-ID'] =
                String(business.id);
        }

    } catch {

        localStorage.removeItem('business');

    }

    return config;

});

function modalSuppressed(config = {}) {

    return (
        config.suppressVerificationModal === true ||
        config.headers?.[
            'X-Suppress-Verification-Modal'
        ] === '1'
    );

}

function retryRequest(config) {

    const retryConfig = {
        ...config,
        suppressVerificationModal: false,
    };

    delete retryConfig.transformRequest;

    return axios.request(retryConfig);

}

axios.interceptors.response.use(

    (response) => {

        const notification =
            response.data?.notification;

        if (
            !modalSuppressed(response.config) &&
            notification?.auto_show !== false
        ) {

            verificationModal.fromApi(
                response.data
            );

        }

        return response;

    },

    (error) => {

        const payload =
            error.response?.data || {};

        const status =
            error.response?.status;

        const config =
            error.config || {};

        if (!modalSuppressed(config)) {

            const handlers = {};

            if (
                payload.notification?.actions?.some(
                    action =>
                        action.id === 'retry'
                ) &&
                config.url
            ) {

                handlers.retry = () =>
                    retryRequest(config);

            }

            verificationModal.fromApi(
                payload,
                {
                    type:
                        status === 422
                            ? 'warning'
                            : 'error',

                    title:
                        status === 422
                            ? 'Please review your information'
                            : 'Request unsuccessful',

                    message:
                        payload.message ||
                        error.message,

                    requestId:
                        payload.request_id ||
                        error.response?.headers?.[
                            'x-request-id'
                        ],

                    errors:
                        payload.errors || {},
                },
                handlers
            );

        }

        if (
            status === 401 &&
            !String(config.url || '').includes('/auth/login')
        ) {

            const adminRequest = new URL(String(config.url || ''), config.baseURL || window.location.origin).pathname.startsWith('/api/v1/admin/');
            localStorage.removeItem(adminRequest ? 'admin_token' : 'merchant_token');
            localStorage.removeItem(adminRequest ? 'admin_session' : 'merchant_session');
            localStorage.removeItem(adminRequest ? 'admin' : 'user');
            if (window.location.pathname.startsWith(adminRequest ? '/admin-panel/' : '/user/') && !window.location.pathname.endsWith('/login')) {
                window.location.assign(adminRequest ? '/admin-panel' : '/user/login');
            }

        }

        return Promise.reject(error);

    }

);
