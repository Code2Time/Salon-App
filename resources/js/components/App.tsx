import { BrowserRouter } from "react-router";
import AppRoutes from "../routes/AppRoutes";
import { AuthProvider } from "../auth/AuthContext";
import { ToastContainer } from "react-toastify";
import { useColorMode } from "../theme/ColorModeContext";

export default function App() {
    const { mode } = useColorMode();
    return (
        <BrowserRouter>
            <AuthProvider>
                <AppRoutes />
                <ToastContainer
                    position="top-center"
                    autoClose={4500}
                    closeOnClick
                    pauseOnHover
                    newestOnTop
                    rtl
                    theme={mode}
                    limit={3}
                />
            </AuthProvider>
        </BrowserRouter>
    );
}
