import { AuthProvider } from "./context/AuthContext";
import { AppRoutes } from "./routes/AppRoutes";
import { HousesProvider } from "./context/HousesContext";

export default function App() {
  return (
    <AuthProvider>
      <HousesProvider>
        <AppRoutes />
      </HousesProvider>
    </AuthProvider>
  );
}
