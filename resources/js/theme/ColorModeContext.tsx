import { createContext, useContext, useEffect, useMemo, useState } from "react";
import type { PaletteMode } from "@mui/material";
import type { ReactNode } from "react";
import { ThemeProvider } from "@mui/material/styles";
import { createAppTheme } from "./theme";

interface ColorModeContextValue {
    mode: PaletteMode;
    toggleMode: () => void;
}

const storageKey = "ara-color-mode";
const ColorModeContext = createContext<ColorModeContextValue | null>(null);

function initialMode(): PaletteMode {
    try {
        return window.localStorage.getItem(storageKey) === "dark" ? "dark" : "light";
    } catch {
        return "light";
    }
}

export function ColorModeProvider({ children }: { children: ReactNode }) {
    const [mode, setMode] = useState<PaletteMode>(initialMode);
    const theme = useMemo(() => createAppTheme(mode), [mode]);

    useEffect(() => {
        document.documentElement.dataset.colorMode = mode;
        document.documentElement.style.colorScheme = mode;
        document.querySelector('meta[name="theme-color"]')
            ?.setAttribute("content", mode === "light" ? "#fff7fa" : "#171116");
        try {
            window.localStorage.setItem(storageKey, mode);
        } catch {
            // The selected mode still applies when browser storage is unavailable.
        }
    }, [mode]);

    const value = useMemo(() => ({
        mode,
        toggleMode: () => setMode((current) => current === "light" ? "dark" : "light"),
    }), [mode]);

    return (
        <ColorModeContext.Provider value={value}>
            <ThemeProvider theme={theme}>{children}</ThemeProvider>
        </ColorModeContext.Provider>
    );
}

export function useColorMode(): ColorModeContextValue {
    const context = useContext(ColorModeContext);
    if (!context) throw new Error("useColorMode must be used inside ColorModeProvider");
    return context;
}
