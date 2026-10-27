import { BrowserRouter, Routes, Route } from "react-router-dom";
import { PrivateRoute } from "./PrivateRoute";
import { AppLayout } from "../components/layout/AppLayout";
import { ROLES } from "../utils/roles";

//Públicas
import Login from "../pages/Login/Login";
import Register from "../pages/Register/Register";
import ForgotPassword from "../pages/Password/ForgotPassword";
import ResetPassword from "../pages/Password/ResetPassword";
import Unauthorized from "../pages/Unauthorized/Unauthorized";
//import NotFound from "../pages/NotFound/NotFound";

//Partilhadas
import Profiles from "../pages/Profiles/Profiles";
import Settings from "../pages/Settings/Settings";
import Onboarding from "../pages/Onboarding/Onboarding";

//Morador
import Dashboard from "../pages/Resident/Dashboard/Dashboard"; //TODO: data
import Assistance from "../pages/Resident/Assistance/Assistance";
import AssistanceDetail from "../pages/Resident/AssistanceDetail/AssistanceDetail";

//Prestador
import JobListing from "../pages/ServiceProvider/JobListing/JobListing";
import Offer from "../pages/ServiceProvider/Offer/Offer";
import Proposals from "../pages/ServiceProvider/Proposal/Proposal";

export function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
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
            <Route path="/settings" element={<Settings />} />

            <Route element={<PrivateRoute allowedRoles={[ROLES.RESIDENT]} />}>
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/assistance" element={<Assistance />} />
              <Route path="/assistance/:id" element={<AssistanceDetail />} />
            </Route>

            <Route
              element={<PrivateRoute allowedRoles={[ROLES.SERVICE_PROVIDER]} />}
            >
              <Route path="/provider/requests" element={<JobListing />} />
              <Route path="/provider/requests/:id" element={<Offer />} />
              <Route path="/provider/proposals" element={<Proposals />} />
            </Route>
          </Route>
        </Route>

        {/* 404: nao corresponde a nenhuma
        <Route path="*" element={<NotFound />} /> */}
      </Routes>
    </BrowserRouter>
  );
}
