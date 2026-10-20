from pathlib import Path
root=Path(__file__).resolve().parents[1]
for relative in ['resources/js/views/Login.vue','resources/js/views/Register.vue']:
    path=root/relative
    source=path.read_text(encoding='utf-8')
    source=source.replace('if (!data.token)', 'if (!data.browser_session)')
    source=source.replace("localStorage.setItem('merchant_token', data.token);", "localStorage.removeItem('merchant_token');\n            localStorage.setItem('merchant_session', '1');")
    source=source.replace("localStorage.setItem('admin_token', data.token);", "localStorage.removeItem('admin_token');\n            localStorage.setItem('admin_session', '1');")
    source=source.replace("localStorage.setItem('token', data.token);", "localStorage.removeItem('token');\n            localStorage.removeItem('merchant_token');\n            localStorage.setItem('merchant_session', '1');")
    source=source.replace("axios.defaults.headers.common.Authorization =\n                `Bearer ${data.token}`;", "delete axios.defaults.headers.common.Authorization;")
    source=source.replace('The server did not return an authentication token.', 'The server did not establish a browser session.')
    path.write_text(source,encoding='utf-8')
path=root/'resources/js/router/index.js'
source=path.read_text(encoding='utf-8')
source=source.replace("localStorage.getItem('admin_token');", "localStorage.getItem('admin_token') || localStorage.getItem('admin_session');")
source=source.replace("localStorage.getItem('merchant_token');", "localStorage.getItem('merchant_token') || localStorage.getItem('merchant_session');")
path.write_text(source,encoding='utf-8')
for relative in ['resources/js/layouts/AdminLayout.vue','resources/js/layouts/MerchantLayout.vue','resources/js/views/admin/Security.vue','resources/js/components/UserSecuritySettings.vue']:
    path=root/relative
    source=path.read_text(encoding='utf-8')
    source=source.replace("localStorage.removeItem('admin_token');", "localStorage.removeItem('admin_token'); localStorage.removeItem('admin_session');")
    source=source.replace("localStorage.removeItem('merchant_token');", "localStorage.removeItem('merchant_token'); localStorage.removeItem('merchant_session');")
    path.write_text(source,encoding='utf-8')
