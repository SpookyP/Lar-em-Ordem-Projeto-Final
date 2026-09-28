import axios from 'axios';

//Instancia unica do axios.
//Todos os services importam este ficheiro

const axiosClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL, //.env
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Se a sessão for invalida ou expirar manda para o login
const ROTAS_SEM_LOGOUT_FORCADO = ['/login', '/register'];

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