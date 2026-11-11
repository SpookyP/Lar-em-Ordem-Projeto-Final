import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";
import { PrivateRoute } from "./PrivateRoute";
import { AppLayout } from "../components/layout/AppLayout";
import { SiteLayout } from "../components/layout/SiteLayout";
import { ROLES } from "../utils/roles";

//Públicas
import Login from "../pages/Login/Login";
import Register from "../pages/Register/Register";
import ForgotPassword from "../pages/Password/ForgotPassword";
import ResetPassword from "../pages/Password/ResetPassword";
import Unauthorized from "../pages/Unauthorized/Unauthorized";
import NotFound from "../pages/NotFound/NotFound";
import VerifyEmail from "../pages/VerifyEmail/VerifyEmail";
import Privacy from "../pages/Privacy/Privacy";

//Partilhadas
import Profiles from "../pages/Profiles/Profiles";
import Settings from "../pages/Settings/Settings";
import Onboarding from "../pages/Onboarding/Onboarding";

//Morador
import Dashboard from "../pages/Resident/Dashboard/Dashboard"; //TODO: data
import Assistance from "../pages/Resident/Assistance/Assistance";
import AssistanceDetail from "../pages/Resident/AssistanceDetail/AssistanceDetail";
import Partners from "../pages/Resident/Partners/Partners";
import Offers from "../pages/Resident/Offers/Offers";
import OfferDetail from "../pages/Resident/OfferDetail/OfferDetail";
import Consumption from "../pages/Consumption/Consumption";
import Invoice from "../pages/Consumption/Invoice";

//Prestador
import JobListing from "../pages/ServiceProvider/JobListing/JobListing";
import Offer from "../pages/ServiceProvider/Offer/Offer";
import Proposals from "../pages/ServiceProvider/Proposal/Proposal";

//Parceiro
import MyOffers from "../pages/Partner/MyOffers/MyOffers";
import OfferFormPage from "../pages/Partner/OfferFormPage/OfferFormPage";

//Administrador de condominio
import Condominiums from "../pages/CondominiumAdmin/Condominiums/Condominiums";
import CondominiumFormPage from "../pages/CondominiumAdmin/CondominiumFormPage/CondominiumFormPage";
import CondominiumDetail from "../pages/CondominiumAdmin/CondominiumDetail/CondominiumDetail";
import CondominiumNotices from "../pages/CondominiumAdmin/CondominiumNotices/CondominiumNotices";

function AppRoutesInner() {
  const location = useLocation();
  //pagina de fundo ofertas
  const background = location.state?.background;

  return (
    <>
      <Routes location={background || location}>
        {/* Rota publica: qualquer um pode aceder */}
        <Route path="/" element={<Login />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/verify-email" element={<VerifyEmail />} />
        <Route path="/unauthorized" element={<Unauthorized />} />

        
        {/* Rota publica, mas com a sidebar para quem tem sessao */}
        <Route element={<SiteLayout />}>
          <Route path="/privacy" element={<Privacy />} />
        </Route>

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
              <Route path="/consumos" element={<Consumption />} />
              <Route path="/partners" element={<Partners />} />
              <Route path="/offers" element={<Offers />} />
              <Route path="/offers/:id" element={<OfferDetail />} />
              <Route path="/faturas" element={<Invoice />} />
              <Route path="/assistencia" element={<Assistance />} />
              <Route path="/assistencia/:id" element={<AssistanceDetail />} />
            </Route>

            <Route
              element={<PrivateRoute allowedRoles={[ROLES.SERVICE_PROVIDER]} />}
            >
              <Route path="/provider/requests" element={<JobListing />} />
              <Route path="/provider/requests/:id" element={<Offer />} />
              <Route path="/provider/proposals" element={<Proposals />} />
            </Route>

            <Route element={<PrivateRoute allowedRoles={[ROLES.PARTNER]} />}>
              <Route path="/partner/offers" element={<MyOffers />} />
              <Route
                path="/partner/offers/new"
                element={<OfferFormPage mode="create" />}
              />
              <Route
                path="/partner/offers/:id"
                element={<OfferFormPage mode="view" />}
              />
              <Route
                path="/partner/offers/:id/edit"
                element={<OfferFormPage mode="edit" />}
              />
            </Route>
            
            <Route
              element={<PrivateRoute allowedRoles={[ROLES.CONDOMINIUM_ADMIN]} />}
            >
              <Route path="/admin/condominiums" element={<Condominiums />} />
              <Route
                path="/admin/condominiums/new"
                element={<CondominiumFormPage mode="create" />}
              />
              <Route path="/admin/condominiums/:id" element={<CondominiumDetail />} />
              <Route
                path="/admin/condominiums/:id/edit"
                element={<CondominiumFormPage mode="edit" />}
              />
              <Route
                path="/admin/condominiums/:id/notices"
                element={<CondominiumNotices />}
              />
            </Route>
          </Route>
        </Route>

        {/* 404: nao corresponde a nenhuma */}
        <Route path="*" element={<NotFound />} />
      </Routes>

      {background && (
        <Routes>
          <Route path="/offers/:id" element={<OfferDetail />} />
        </Routes>
      )}
    </>
  );
}

export function AppRoutes() {
  return (
    <BrowserRouter>
      <AppRoutesInner />
    </BrowserRouter>
  );
}
