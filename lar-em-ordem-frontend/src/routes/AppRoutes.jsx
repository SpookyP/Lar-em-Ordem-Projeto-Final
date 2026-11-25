import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";
import { ScrollToTop } from "../components/layout/ScrollToTop"
import { PrivateRoute } from "./PrivateRoute";
import { GuestRoute } from "./GuestRoute";
import { AppLayout } from "../components/layout/AppLayout";
import { SiteLayout } from "../components/layout/SiteLayout";
import { SessionTimeout } from "../components/layout/SessionTimeout";
import { ROLES } from "../utils/roles";
import { lazy, Suspense } from "react";
import { RequireHouse } from "./RequireHouse";
import { LoadingState } from "../components/ui/LoadingState";

// Login loads eagerly: it is the entry page, so there is no loading screen at startup
import Login from "../pages/Login/Login";

// Lazy: returns a component that only fetches its code when it has to render
// Public
const Register = lazy(() => import("../pages/Register/Register"));
const ForgotPassword = lazy(() => import("../pages/Password/ForgotPassword"));
const ResetPassword = lazy(() => import("../pages/Password/ResetPassword"));
const Unauthorized = lazy(() => import("../pages/Unauthorized/Unauthorized"));
const NotFound = lazy(() => import("../pages/NotFound/NotFound"));
const VerifyEmail = lazy(() => import("../pages/VerifyEmail/VerifyEmail"));
const Privacy = lazy(() => import("../pages/Privacy/Privacy"));
const SitePage = lazy(() => import("../pages/SitePage/SitePage"));

// Shared
const Profiles = lazy(() => import("../pages/Profiles/Profiles"));
const Settings = lazy(() => import("../pages/Settings/Settings"));
const Onboarding = lazy(() => import("../pages/Onboarding/Onboarding"));

// Resident
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
const Consumption = lazy(
  () => import("../pages/Resident/Consumption/Consumption"),
);
const Invoice = lazy(() => import("../pages/Resident/Invoice/Invoice"));
const Vault = lazy(() => import("../pages/Resident/Vault/Vault"));

// Service provider
const JobListing = lazy(
  () => import("../pages/ServiceProvider/JobListing/JobListing"),
);
const Offer = lazy(() => import("../pages/ServiceProvider/Offer/Offer"));
const Proposals = lazy(
  () => import("../pages/ServiceProvider/Proposal/Proposal"),
);
const ProposalFormPage = lazy(
  () => import("../pages/ServiceProvider/ProposalFormPage/ProposalFormPage"),
);

// Partner
const MyOffers = lazy(() => import("../pages/Partner/MyOffers/MyOffers"));
const OfferFormPage = lazy(
  () => import("../pages/Partner/OfferFormPage/OfferFormPage"),
);

// Condominium admin
const Condominiums = lazy(
  () => import("../pages/CondominiumAdmin/Condominiums/Condominiums"),
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

function RouteFallback() {
  return (
    <div className="min-h-screen bg-surface flex items-center justify-center">
      <LoadingState />
    </div>
  );
}

function AppRoutesInner() {
  const location = useLocation();
  // background page for the offers
  const background = location.state?.background;

  return (
    <Suspense fallback={<RouteFallback />}>
      <Routes location={background || location}>
        {/* Public route limited to guests: a user with a session is sent to their home page */}
        <Route element={<GuestRoute />}>
          <Route element={<SiteLayout flush />}>
            <Route path="/" element={<Login />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
          </Route>
          <Route path="/forgot-password" element={<ForgotPassword />} />
        </Route>
        {/* Public route: anyone can open it */}
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/verify-email" element={<VerifyEmail />} />

        {/* Public route, with the sidebar for users with a session */}
        <Route element={<SiteLayout />}>
          <Route path="/privacy" element={<Privacy />} />
          <Route path="/about" element={<SitePage page="about" />} />
          <Route path="/help" element={<SitePage page="help" />} />
          <Route path="/terms" element={<SitePage page="terms" />} />
        </Route>

        {/* Protected routes: logged-in users only */}
        <Route element={<PrivateRoute />}>
          <Route path="/unauthorized" element={<Unauthorized />} />

          <Route path="/profiles" element={<Profiles />} />
          <Route path="/welcome" element={<Onboarding />} />

          <Route element={<AppLayout />}>
            <Route path="/settings" element={<Settings />} />

            <Route element={<PrivateRoute allowedRoles={[ROLES.RESIDENT]} />}>
              <Route element={<RequireHouse />}>
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
            </Route>

            <Route
              element={<PrivateRoute allowedRoles={[ROLES.SERVICE_PROVIDER]} />}
            >
              <Route path="/provider/requests" element={<JobListing />} />
              <Route path="/provider/requests/:id" element={<Offer />} />
              <Route path="/provider/proposals" element={<Proposals />} />
              <Route
                path="/provider/requests/:id/proposal"
                element={<ProposalFormPage />}
              />
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
                path="/admin/condominiums/:id"
                element={<CondominiumDetail />}
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

        {/* 404: no route matches */}
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
      <ScrollToTop />
      <AppRoutesInner />
      <SessionTimeout />
    </BrowserRouter>
  );
}
