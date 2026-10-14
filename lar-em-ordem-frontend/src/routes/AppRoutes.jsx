import { BrowserRouter, Routes, Route } from "react-router-dom";
import { PrivateRoute } from "./PrivateRoute";
import { AppLayout } from "../components/layout/AppLayout";
import Login from "../pages/Login/Login";
import Register from "../pages/Register/Register";
import Profiles from "../pages/Profiles/Profiles";
import Definicoes from "../pages/Definicoes/Definicoes";

//paginas fake

import Dashboard from "../pages/Resident/Dashboard/Dashboard";
import Assistance from "../pages/Resident/Assistance/Assistance";
import JobListing from "../pages/ServiceProvider/JobListing/JobListing";
import Offer from "../pages/ServiceProvider/Offer/Offer";
import NaoAutorizado from "../pages/NaoAutorizado/NaoAutorizado";

export function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        {/* TEMPORARIO - ROTA DE TESTE */}
        <Route element={<AppLayout />}>
          <Route path="/teste-layout" element={<Dashboard />} />
          <Route path="/teste-support" element={<Assistance />} />
          <Route path="/teste-service" element={<JobListing />} />
          <Route path="/teste-offer/:id" element={<Offer />} />
        </Route>

        {/* Rota publica: qualquer um pode aceder */}
        <Route path="/" element={<Login />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/nao-autorizado" element={<NaoAutorizado />} />

        {/* Rotas protegidas: so quem tem login */}
        <Route element={<PrivateRoute />}>
          <Route path="/perfis" element={<Profiles />} />
          <Route element={<AppLayout />}>
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/definicoes" element={<Definicoes />} />
          </Route>
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
