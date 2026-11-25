import {useContext} from 'react';
import {AuthContext} from '../context/authContextObject'

// Shortcut hook so any component can read the auth info
export function useAuth() {
    const context = useContext(AuthContext);

    if(!context){
        throw new Error('useAuth tem de ser usado dentro de um <AuthProvider>');
    }

    return context;
}