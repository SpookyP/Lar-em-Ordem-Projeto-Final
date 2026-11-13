import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";
import { PrivateRoute } from "./PrivateRoute";
import { AppLayout } from "../components/layout/AppLayout";
import { SiteLayout } from "../components/layout/SiteLayout";
import { ROLES } from "../utils/roles";
import { lazy, Suspense } from "react";

//Login fica eager: é a página de entrada, evita loading no arranque
import Login from "../pages/Login/Login";

//Lazy :devolve um componente que só pede o código quando é preciso renderizá-lo
//Públicas
const Register = lazy(() => import("../pages/Register/Register"));
const ForgotPassword = lazy(() => import("../pages/Password/ForgotPassword"));
const ResetPassword = lazy(() => import("../pages/Password/ResetPassword"));
const Unauthorized = lazy(() => import("../pages/Unauthorized/Unauthorized"));
const NotFound = lazy(() => import("../pages/NotFound/NotFound"));
const VerifyEmail = lazy(() => import("../pages/VerifyEmail/VerifyEmail"));
const Privacy = lazy(() => import("../pages/Privacy/Privacy"));

//Partilhadas
const Profiles = lazy(() => import("../pages/Profiles/Profiles"));
const Settings = lazy(() => import("../pages/Settings/Settings"));
const Onboarding = lazy(() => import("../pages/Onboarding/Onboarding"));

//Morador
const Dashboard = lazy(() => import("../pages/Resident/Dashboard/Dashboard"));
const Assistance = lazy(
  () => import("../pages/Resident/Assistance/Assistance"),
);
const AssistanceDetail = lazy(
  () => import("../pages/Resident/AssistanceDetail/AssistanceDetail"),
);
const ResidentCondominium = lazy(
  () => import("../pages/Resident/Condominium/Condominium"),
);
const Partners = lazy(() => import("../pages/Resident/Partners/Partners"));
const Offers = lazy(() => import("../pages/Resident/Offers/Offers"));
const OfferDetail = lazy(
  () => import("../pages/Resident/OfferDetail/OfferDetail"),
);
const Consumption = lazy(() => import("../pages/Consumption/Consumption"));
const Invoice = lazy(() => import("../pages/Consumption/Invoice"));
const Vault = lazy(() => import("../pages/Resident/Vault/Vault"));

//Prestador
const JobListing = lazy(
  () => import("../pages/ServiceProvider/JobListing/JobListing"),
);
const Offer = lazy(() => import("../pages/ServiceProvider/Offer/Offer"));
const Proposals = lazy(
  () => import("../pages/ServiceProvider/Proposal/Proposal"),
);

//Parceiro
const MyOffers = lazy(() => import("../pages/Partner/MyOffers/MyOffers"));
const OfferFormPage = lazy(
  () => import("../pages/Partner/OfferFormPage/OfferFormPage"),
);

//Administrador de condominio
const Condominiums = lazy(
  () => import("../pages/CondominiumAdmin/Condominiums/Condominiums"),
);
const CondominiumFormPage = lazy(
  () =>
    import("../pages/CondominiumAdmin/CondominiumFormPage/CondominiumFormPage"),
);
const CondominiumDetail = lazy(
  () => import("../pages/CondominiumAdmin/CondominiumDetail/CondominiumDetail"),
);
const CondominiumNotices = lazy(
  () =>
    import("../pages/CondominiumAdmin/CondominiumNotices/CondominiumNotices"),
);
const CondominiumDocuments = lazy(
  () =>
    import("../pages/CondominiumAdmin/CondominiumDocuments/CondominiumDocuments"),
);

//Ecrã mostrado enquanto o código de uma página ainda está a carregar
function RouteFallback() {
  return (
    <div className="min-h-screen bg-surface flex items-center justify-center">
      <p className="text-muted">A carregar...</p>
    </div>
  );
}

function AppRoutesInner() {
  const location = useLocation();
  //pagina de fundo ofertas
  const background = location.state?.background;

  return (
    <Suspense fallback={<RouteFallback />}>
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
              <Route path="/condominio" element={<ResidentCondominium />} />
              <Route path="/consumos" element={<Consumption />} />
              <Route path="/partners" element={<Partners />} />
              <Route path="/offers" element={<Offers />} />
              <Route path="/offers/:id" element={<OfferDetail />} />
              <Route path="/faturas" element={<Invoice />} />
              <Route path="/vault" element={<Vault />} />
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
              element={
                <PrivateRoute allowedRoles={[ROLES.CONDOMINIUM_ADMIN]} />
              }
            >
              <Route path="/admin/condominiums" element={<Condominiums />} />
              <Route
                path="/admin/condominiums/new"
                element={<CondominiumFormPage mode="create" />}
              />
              <Route
                path="/admin/condominiums/:id"
                element={<CondominiumDetail />}
              />
              <Route
                path="/admin/condominiums/:id/edit"
                element={<CondominiumFormPage mode="edit" />}
              />
              <Route
                path="/admin/condominiums/:id/notices"
                element={<CondominiumNotices />}
              />
              <Route
                path="/admin/condominiums/:id/documents"
                element={<CondominiumDocuments />}
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
    </Suspense>
  );
}

export function AppRoutes() {
  return (
    <BrowserRouter>
      <AppRoutesInner />
    </BrowserRouter>
  );
}
