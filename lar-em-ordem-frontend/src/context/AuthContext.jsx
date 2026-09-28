import {useState, useEffect} from 'react';
import { AuthContext } from './authContextObject';
import {login as loginService, logout as logoutService, getCurrentUser} from '../services/api/authService';

//Provider vai distribuir a info da autenticacao
export function AuthProvider({children}) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function checkSession(){
            try {
                const currentUser = await getCurrentUser();
                setUser(currentUser);
            } catch {
                setUser(null);
            } finally {
                setLoading(false);
            }
        }
        checkSession();
    }, []);

    async function login(email, password){
        const user = await loginService(email, password);
        setUser(user);
        return user;
    }

    async function logout(){
        await logoutService();
        setUser(null);
    }
    
    function hasRole(roleName) {
        if(!user?.roles)
            return false;

        return user.roles.some((role) => role.name === roleName)
    }

    const value = {
        user,
        isAuthenticated: Boolean(user),
        loading,
        login,
        logout,
        hasRole
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}