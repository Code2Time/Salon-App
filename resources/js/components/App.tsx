import Container from "@mui/material/Container";
import Paper from "@mui/material/Paper";
import Typography from "@mui/material/Typography";

export default function App() {
    return (
        <Container maxWidth="sm" sx={{ py: 6 }}>
            <Paper sx={{ p: 4 }}>
                <Typography variant="h4" component="h1" gutterBottom>
                    Salon App
                </Typography>
                <Typography color="text.secondary">
                    React and MUI are working!
                </Typography>
            </Paper>
        </Container>
    );
}
