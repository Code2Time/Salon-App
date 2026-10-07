import { createTheme } from "@mui/material/styles";

const theme = createTheme({
    palette: {
        primary: { main: "#7c3aed" },
        secondary: { main: "#db2777" },
        background: { default: "#f8fafc" },
    },
    typography: {
        fontFamily: 'Arial, sans-serif',
    },
    shape: {
        borderRadius: 12,
    },
});

export default theme;
