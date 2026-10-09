import { BrowserRouter, Routes, Route } from "react-router-dom";
import { PrivateRoute } from "./PrivateRoute";
import { AppLayout } from "../components/layout/AppLayout";

//paginas fake
import Login from "../pages/Login/Login";
import Dashboard from "../pages/Dashboard/Dashboard";
import NaoAutorizado from "../pages/NaoAutorizado/NaoAutorizado";
import Register from "../pages/Register/Register";

export function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        {/* TEMPORARIO - ROTA DE TESTE */}
        <Route element={<AppLayout />}>
          <Route path="/teste-layout" element={<Dashboard />} />
        </Route>

        {/* Rota publica: qualquer um pode aceder */}
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/nao-autorizado" element={<NaoAutorizado />} />

        {/* Rotas protegidas: so quem tem login */}
        <Route element={<PrivateRoute />}>
          <Route element={<AppLayout />}>
            <Route path="/dashboard" element={<Dashboard />} />
          </Route>
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
