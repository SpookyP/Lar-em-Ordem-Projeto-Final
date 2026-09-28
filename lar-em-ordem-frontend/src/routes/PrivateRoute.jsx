import {Navigate, Outlet} from 'react-router-dom';
import {useAuth} from '../hooks/useAuth';

export function PrivateRoute({allowedRoles}) {
    const {isAuthenticated, loading, hasRole} = useAuth();

    if(loading){
        return null;
    }

    if(!isAuthenticated){
        return <Navigate to="/login" replace />;
    }

    const hasPermission = !allowedRoles || allowedRoles.length === 0 || allowedRoles.some(hasRole);

    if(!hasPermission){
        return <Navigate to="/nao-autorizado" replace />;
    } 

    return <Outlet />;
}