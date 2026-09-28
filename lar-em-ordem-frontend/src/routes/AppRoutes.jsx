import { BrowserRouter, Routes, Route} from 'react-router-dom';
import {PrivateRoute} from './PrivateRoute';

//paginas fake
import Login from '../pages/Login/Login';
import Dashboard from '../pages/Dashboard/Dashboard';
import NaoAutorizado from '../pages/NaoAutorizado/NaoAutorizado';

export function AppRoutes(){
    return (
        <BrowserRouter>
            <Routes>
                {/* Rota publica: qualquer um pode aceder */}
                <Route path="/login" element={<Login />} />
                <Route path="/nao-autorizado" element={<NaoAutorizado />} />
            
                {/* Rotas protegidas: so quem tem login */}
                <Route element={<PrivateRoute />}>
                    <Route path="/dashboard" element={<Dashboard />} />
                </Route>
            </Routes>
        </BrowserRouter>
    )
}