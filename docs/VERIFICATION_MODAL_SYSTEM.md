# Centralised Verification Modal System

## API contract

Every JSON response under `/api/*` is normalised by `NormalizeApiResponse` and includes:

```json
{
  "success": true,
  "request_id": "uuid",
  "message": "Operation completed.",
  "data": {},
  "notification": {
    "type": "success",
    "title": "Virtual account successful",
    "message": "Operation completed.",
    "icon": "success",
    "colour": "emerald",
    "request_id": "uuid",
    "auto_show": true,
    "dismissible": true,
    "actions": [
      { "id": "close", "label": "Done", "style": "primary" }
    ]
  }
}
```

Supported notification types are `success`, `warning`, `error`, and `info`.

Successful `GET`, `HEAD`, and `OPTIONS` responses set `auto_show` to `false`. Failed requests and state-changing requests are shown automatically.

## Frontend integration

`resources/js/bootstrap.js` installs global Axios response interceptors. Controllers and Vue pages do not need to call a modal after ordinary API mutations. The interceptor reads the response contract and opens `VerificationModal.vue` automatically.

Use the service for browser-only operations:

```js
import { verificationModal } from '@/services/verificationModal';

verificationModal.success('Account number copied.');
verificationModal.warning('Enter a BVN before continuing.');
verificationModal.error('The file could not be copied.');
verificationModal.info('Verification is still processing.');
```

For confirmations:

```js
const confirmed = await verificationModal.confirm({
    title: 'Approve payout?',
    message: 'The payout will be sent for provider processing.',
    confirmText: 'Approve payout',
    danger: false,
});
```

## Suppressing an automatic modal

Background polling and silent refresh requests may opt out:

```js
axios.get('/api/v1/providers/health', {
    suppressVerificationModal: true,
});
```

or by sending `X-Suppress-Verification-Modal: 1`.

## Request IDs

The modal displays and copies `request_id` values. Support teams should request this ID when investigating failed operations.
