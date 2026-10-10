import { createTheme } from "@mui/material/styles";

const theme = createTheme({
    direction: "rtl",
    palette: {
        primary: { main: "#b21e5b", dark: "#78113d", light: "#fce4ee" },
        secondary: { main: "#b7791f", dark: "#7f4f12", light: "#f8e7c8" },
        background: { default: "#fff7fa", paper: "#fffdfd" },
        text: { primary: "#351522", secondary: "#76515f" },
        divider: "#f0d5df",
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
                root: {
                    borderRadius: 12,
                    padding: "10px 20px",
                    gap: 8,
                    "&.MuiButton-containedPrimary": {
                        background: "linear-gradient(135deg, #c62a6a 0%, #941947 100%)",
                        boxShadow: "0 10px 24px rgba(178, 30, 91, 0.2)",
                        "&:hover": {
                            background: "linear-gradient(135deg, #d33376 0%, #7f123d 100%)",
                            boxShadow: "0 12px 28px rgba(178, 30, 91, 0.3)",
                        },
                    },
                },
            },
        },
        MuiOutlinedInput: {
            styleOverrides: {
                root: {
                    "&.Mui-focused": { boxShadow: "0 0 0 3px rgba(178, 30, 91, 0.1)" },
                },
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
