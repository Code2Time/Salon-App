import { createTheme } from "@mui/material/styles";

const theme = createTheme({
    direction: "rtl",
    palette: {
        primary: { main: "#795466", dark: "#573747", light: "#f0e5ea" },
        secondary: { main: "#8b703e" },
        background: { default: "#faf8f5", paper: "#ffffff" },
        text: { primary: "#342d33", secondary: "#766e73" },
        divider: "#ece6e7",
    },
    typography: {
        fontFamily: "Vazirmatn, Tahoma, Arial, sans-serif",
        h1: { fontSize: "2.5rem", fontWeight: 700, lineHeight: 1.6 },
        h2: { fontSize: "1.65rem", fontWeight: 700, lineHeight: 1.7 },
        h3: { fontSize: "1.1rem", fontWeight: 600, lineHeight: 1.8 },
        body1: { lineHeight: 1.9 },
        body2: { lineHeight: 1.9 },
        button: { textTransform: "none", fontWeight: 600 },
    },
    shape: {
        borderRadius: 16,
    },
    components: {
        MuiButton: {
            defaultProps: { disableElevation: true },
            styleOverrides: {
                root: { borderRadius: 12, padding: "10px 20px", gap: 8 },
            },
        },
        MuiPaper: { defaultProps: { elevation: 0 } },
        MuiCssBaseline: {
            styleOverrides: {
                body: { margin: 0 },
                "*": { boxSizing: "border-box" },
            },
        },
    },
});

export default theme;
