import React from "react";
import { createRoot } from "react-dom/client";
import CssBaseline from "@mui/material/CssBaseline";
import { ThemeProvider } from "@mui/material/styles";
import App from "./components/App";
import theme from "./theme/theme";
import { CacheProvider } from "@emotion/react";
import createCache from "@emotion/cache";
import rtlPlugin from "@mui/stylis-plugin-rtl";
import { prefixer } from "stylis";
import { LocalizationProvider } from "@mui/x-date-pickers/LocalizationProvider";
import { AdapterDateFnsJalali } from "@mui/x-date-pickers/AdapterDateFnsJalali";
import { faIR } from "@mui/x-date-pickers/locales";
import type { PickersInputLocaleText } from "@mui/x-date-pickers/locales";
import "@fontsource/vazirmatn/400.css";
import "@fontsource/vazirmatn/500.css";
import "@fontsource/vazirmatn/600.css";
import "@fontsource/vazirmatn/700.css";
import "react-toastify/dist/ReactToastify.css";
import "../css/layout.css";

const rtlCache = createCache({
    key: "salon-rtl",
    stylisPlugins: [prefixer, rtlPlugin],
});

const persianPickerLocale = Object.fromEntries(
    Object.entries(faIR.components.MuiLocalizationProvider.defaultProps.localeText)
        .filter(([, value]) => value !== undefined),
) as PickersInputLocaleText;

const root = createRoot(document.getElementById("app")!);

root.render(
    <React.StrictMode>
        <CacheProvider value={rtlCache}>
            <ThemeProvider theme={theme}>
                <LocalizationProvider
                    dateAdapter={AdapterDateFnsJalali}
                    localeText={persianPickerLocale}
                >
                    <CssBaseline />
                    <App />
                </LocalizationProvider>
            </ThemeProvider>
        </CacheProvider>
    </React.StrictMode>,
);
