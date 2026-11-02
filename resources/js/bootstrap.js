import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Defensive route helper fallback in case Ziggy or global route() is not injected
if (typeof window.route === 'undefined') {
    window.route = function (name, params) {
        if (!name) return '/';
        const base = '/' + name.replace(/\./g, '/');
        if (params !== undefined && params !== null) {
            const paramVal = typeof params === 'object' ? (params.id || Object.values(params)[0] || '') : params;
            return paramVal ? `${base}/${paramVal}` : base;
        }
        return base;
    };
}


