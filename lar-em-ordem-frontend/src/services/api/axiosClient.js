import axios from 'axios';

//Instancia unica do axios.
//Todos os services importam este ficheiro

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

//le o valor de um cookie pelo nome (procura-o na lista de cookies do browser)
function lerCookie(nome) {
    const valor = document.cookie
        .split('; ')
        .find((linha) => linha.startsWith(nome + '='));
    return valor ? decodeURIComponent(valor.split('=')[1]) : null;
}

//intercetor de pedido: antes do pedido, le o cookie XSRF-TOKEN e cola no header X-XSRF-TOKEN
axiosClient.interceptors.request.use((config) => {
    const token = lerCookie('XSRF-TOKEN');
    if (token) {
        config.headers['X-XSRF-TOKEN'] = token;
    }
    return config;
});

// Se a sessão for invalida ou expirar manda para o login
const ROTAS_SEM_LOGOUT_FORCADO = ['/login', '/register', '/api/user'];

//intercetor de resposta: corre depois de cada resposta entrar
axiosClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = error.config?.url ?? '';
        const isRotaDeAuth = ROTAS_SEM_LOGOUT_FORCADO.some((r) => url.includes(r));

        if (status === 401 && !isRotaDeAuth) {
            window.location.href = '/login';
        }

        return Promise.reject(error);
    }
);

export default axiosClient;