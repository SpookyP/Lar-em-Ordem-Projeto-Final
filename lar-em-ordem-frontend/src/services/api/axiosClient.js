import axios from 'axios';

// Single axios instance.
// Every service imports this file

const axiosClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL, //.env
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// reads a cookie's value by name (looks it up in the browser's cookie list)
function lerCookie(nome) {
    const valor = document.cookie
        .split('; ')
        .find((linha) => linha.startsWith(nome + '='));
    return valor ? decodeURIComponent(valor.split('=')[1]) : null;
}

// request interceptor: before each request, reads the XSRF-TOKEN cookie and puts it in the X-XSRF-TOKEN header
axiosClient.interceptors.request.use((config) => {
    const token = lerCookie('XSRF-TOKEN');
    if (token) {
        config.headers['X-XSRF-TOKEN'] = token;
    }
    return config;
});

// If the session is invalid or expired, send the user to the login
const ROTAS_SEM_LOGOUT_FORCADO = ['/login', '/logout', '/register', '/forgot-password', '/reset-password'];

// response interceptor: runs after each response comes in
axiosClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = error.config?.url ?? '';
        const isRotaDeAuth = url === '/api/user' || ROTAS_SEM_LOGOUT_FORCADO.some((r) => url.includes(r));

        if ((status === 401 || status === 419) && !isRotaDeAuth) {
            window.location.href = '/login?erro=sessao_expirada';
        }

        return Promise.reject(error);
    }
);

export default axiosClient;