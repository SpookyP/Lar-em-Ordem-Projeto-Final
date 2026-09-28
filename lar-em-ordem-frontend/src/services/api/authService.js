import axiosClient from './axiosClient';

async function getCookieCsrf(){
    await axiosClient.get('/sanctum/csrf-cookie');
}

export async function login(email, password){
    await getCookieCsrf();
    await axiosClient.post('/login', {email, password});
    return getCurrentUser();
}

export async function logout(){
    await axiosClient.post('/logout');
}

export async function getCurrentUser(){
    const response = await axiosClient.get('/api/user');
    return response.data;
}