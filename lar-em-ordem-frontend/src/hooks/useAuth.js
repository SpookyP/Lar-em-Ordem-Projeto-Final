import {useContext} from 'react';
import {AuthContext} from '../context/authContextObject'

//Hook (atalho) para qualquer componente ler a info da autenticacao
export function useAuth() {
    const context = useContext(AuthContext);

    if(!context){
        throw new Error('useAuth tem de ser usado dentro de um <AuthProvider>');
    }

    return context;
}