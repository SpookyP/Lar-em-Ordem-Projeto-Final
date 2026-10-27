import { BrowserRouter, Routes, Route } from "react-router-dom";
import { PrivateRoute } from "./PrivateRoute";
import { AppLayout } from "../components/layout/AppLayout";
import Login from "../pages/Login/Login";
import Register from "../pages/Register/Register";
import Profiles from "../pages/Profiles/Profiles";
import Settings from "../pages/Settings/Settings";
import Onboarding from "../pages/Onboarding/Onboarding";
import ForgotPassword from "../pages/Password/ForgotPassword";
import ResetPassword from "../pages/Password/ResetPassword";

//paginas fake

import Dashboard from "../pages/Resident/Dashboard/Dashboard";
import Assistance from "../pages/Resident/Assistance/Assistance";
import AssistanceDetail from "../pages/Resident/AssistanceDetail/AssistanceDetail";
import JobListing from "../pages/ServiceProvider/JobListing/JobListing";
import Offer from "../pages/ServiceProvider/Offer/Offer";
import Proposals from "../pages/ServiceProvider/Proposal/Proposal";
import Unauthorized from "../pages/Unauthorized/Unauthorized";
import { ROLES } from "../utils/roles";

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
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/unauthorized" element={<Unauthorized />} />

        {/* Rotas protegidas: so quem tem login */}
        <Route element={<PrivateRoute />}>
          <Route path="/profiles" element={<Profiles />} />
          <Route path="/welcome" element={<Onboarding />} />
          <Route element={<AppLayout />}>
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/settings" element={<Settings />} />

            <Route element={<PrivateRoute allowedRoles={[ROLES.RESIDENT]} />}>
              <Route path="/assistencia" element={<Assistance />} />
              <Route path="/assistencia/:id" element={<AssistanceDetail />} />
            </Route>

            <Route
              element={<PrivateRoute allowedRoles={[ROLES.SERVICE_PROVIDER]} />}
            >
              <Route path="/prestador/pedidos" element={<JobListing />} />
              <Route path="/prestador/pedidos/:id" element={<Offer />} />
              <Route path="/prestador/propostas" element={<Proposals />} />
            </Route>
          </Route>
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
