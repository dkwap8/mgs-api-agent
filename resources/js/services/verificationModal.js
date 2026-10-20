import { reactive, readonly } from 'vue';

const DEFAULTS = {
    success: {
        title: 'Operation completed',
        icon: 'check',
        colour: 'emerald',
        confirmText: 'Done',
    },

    warning: {
        title: 'Action required',
        icon: 'warning',
        colour: 'amber',
        confirmText: 'Review',
    },

    error: {
        title: 'Operation unsuccessful',
        icon: 'error',
        colour: 'rose',
        confirmText: 'Close',
    },

    info: {
        title: 'Information',
        icon: 'info',
        colour: 'indigo',
        confirmText: 'Got it',
    },
};

const state = reactive({
    visible: false,
    type: 'info',
    title: '',
    message: '',
    requestId: null,
    code: null,
    errors: {},
    actions: [],
    dismissible: true,
    busy: false,
});

let actionHandlers = new Map();
let pendingConfirmationResolver = null;

function firstValidationMessage(errors = {}) {
    for (const messages of Object.values(errors)) {
        if (Array.isArray(messages) && messages.length > 0) {
            return String(messages[0]);
        }

        if (
            typeof messages === 'string'
            && messages.trim() !== ''
        ) {
            return messages;
        }
    }

    return null;
}

function normaliseAction(action, index) {
    if (typeof action === 'string') {
        return {
            id: action,
            label: action.replaceAll('_', ' '),
            style: action === 'close'
                ? 'secondary'
                : 'primary',
            url: null,
            method: null,
        };
    }

    return {
        id: action.id || `action_${index}`,
        label: action.label || 'Continue',
        style: action.style || 'primary',
        url: action.url || null,
        method: action.method || null,
    };
}

function show(options = {}, handlers = {}) {
    const allowedTypes = [
        'success',
        'warning',
        'error',
        'info',
    ];

    const type = allowedTypes.includes(options.type)
        ? options.type
        : 'info';

    const defaults = DEFAULTS[type];

    const actions = (
        options.actions?.length
            ? options.actions
            : [
                {
                    id: 'close',
                    label: defaults.confirmText,
                    style: 'primary',
                },
            ]
    ).map(normaliseAction);

    actionHandlers = new Map(
        Object.entries(handlers)
    );

    Object.assign(state, {
        visible: true,
        type,
        title: options.title || defaults.title,
        message:
            options.message
            || 'The request has been processed.',
        requestId:
            options.request_id
            || options.requestId
            || null,
        code: options.code || null,
        errors: options.errors || {},
        actions,
        dismissible: options.dismissible !== false,
        busy: false,
    });
}

function fromApi(
    payload = {},
    fallback = {},
    handlers = {},
) {
    const notification = payload.notification || {};

    let success = false;

    if (typeof payload.success === 'boolean') {
        success = payload.success;
    } else if (payload.status === 'success') {
        success = true;
    } else if (payload.status === true) {
        success = true;
    }

    const type =
        notification.type
        || fallback.type
        || (success ? 'success' : 'error');

    const errors =
        payload.errors
        || fallback.errors
        || {};

    show(
        {
            type,

            title:
                notification.title
                || fallback.title,

            message:
                notification.message
                || payload.message
                || firstValidationMessage(errors)
                || fallback.message
                || 'The request could not be completed.',

            request_id:
                notification.request_id
                || payload.request_id
                || fallback.requestId,

            code:
                payload.code
                || fallback.code,

            errors,

            actions:
                notification.actions
                || fallback.actions,

            dismissible:
                notification.dismissible
                ?? fallback.dismissible,
        },
        handlers,
    );
}

function confirm(options = {}) {
    return new Promise((resolve) => {
        pendingConfirmationResolver = resolve;

        show(
            {
                type: options.type || 'warning',

                title:
                    options.title
                    || 'Confirm action',

                message:
                    options.message
                    || 'Are you sure you want to continue?',

                request_id: options.requestId,

                dismissible: true,

                actions: [
                    {
                        id: 'confirm',
                        label:
                            options.confirmText
                            || 'Continue',
                        style: options.danger
                            ? 'danger'
                            : 'primary',
                    },

                    {
                        id: 'cancel',
                        label:
                            options.cancelText
                            || 'Cancel',
                        style: 'secondary',
                    },
                ],
            },

            {
                confirm: () => {
                    pendingConfirmationResolver = null;
                    resolve(true);
                },

                cancel: () => {
                    pendingConfirmationResolver = null;
                    resolve(false);
                },
            },
        );
    });
}

function close() {
    if (! state.dismissible || state.busy) {
        return;
    }

    if (pendingConfirmationResolver) {
        pendingConfirmationResolver(false);
        pendingConfirmationResolver = null;
    }

    state.visible = false;
    state.errors = {};
    state.actions = [];

    actionHandlers.clear();
}

async function runAction(action) {
    const handler = actionHandlers.get(action.id);

    if (handler) {
        state.busy = true;

        try {
            await handler(action);

            if (state.visible) {
                state.visible = false;
                state.errors = {};
                state.actions = [];
                actionHandlers.clear();
            }
        } finally {
            state.busy = false;
        }

        return;
    }

    if (action.url) {
        window.location.assign(action.url);
        return;
    }

    close();
}

export const verificationModal = {
    state: readonly(state),

    show,

    fromApi,

    close,

    runAction,

    success(
        message,
        options = {},
        handlers = {},
    ) {
        show(
            {
                ...options,
                type: 'success',
                message,
            },
            handlers,
        );
    },

    warning(
        message,
        options = {},
        handlers = {},
    ) {
        show(
            {
                ...options,
                type: 'warning',
                message,
            },
            handlers,
        );
    },

    error(
        message,
        options = {},
        handlers = {},
    ) {
        show(
            {
                ...options,
                type: 'error',
                message,
            },
            handlers,
        );
    },

    info(
        message,
        options = {},
        handlers = {},
    ) {
        show(
            {
                ...options,
                type: 'info',
                message,
            },
            handlers,
        );
    },

    confirm,
};

/*
 * This default export fixes imports such as:
 *
 * import verificationModal
 *     from '../services/verificationModal.js';
 *
 * The named export above remains available too.
 */
export default verificationModal;