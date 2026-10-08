import { BrowserRouter } from "react-router";
import AppRoutes from "../routes/AppRoutes";
import { AuthProvider } from "../auth/AuthContext";
import { ToastContainer } from "react-toastify";

export default function App() {
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
                    theme="light"
                    limit={3}
                />
            </AuthProvider>
        </BrowserRouter>
    );
}
