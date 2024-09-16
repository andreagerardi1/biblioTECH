<?php
session_start();

// Include il file di connessione al database
include('../connection.php');

// Variabile per gestire il messaggio di errore
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Verifica se l'email e la password sono state inserite
    if (empty($email) || empty($password)) {
        $error = "Per favore inserisci sia l'email che la password.";
    } else {
        // Query per verificare se l'email esiste
        $query = "SELECT email, password FROM biblioteca_ag.utente_bibliotecario WHERE email = $1";
        $result = pg_query_params($db, $query, array($email));

        if (pg_num_rows($result) > 0) {
            // Recupera il record dell'utente
            $utente = pg_fetch_assoc($result);

            // Verifica la password hashata
            if (password_verify($password, $utente['password'])) {
                // Imposta la sessione dell'utente come loggata
                $_SESSION["loggedin"] = true;
                $_SESSION["email"] = $utente['email'];
                $_SESSION["tipo"] = "bibliotecario";

                // Reindirizza alla pagina home
                header("Location: home.php");
                exit;
            } else {
                $error = "Password non corretta.";
            }
        } else {
            $error = "Email non trovata.";
        }
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Bibliotecario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="card p-4 shadow-lg" style="max-width: 400px; width: 100%;">
                <h2 class="text-center mb-4">Login Bibliotecario</h2>

                <!-- Messaggio di errore, se presente -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Form di login -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="exampleInputEmail1" class="form-label">Email</label>
                        <input type="email" class="form-control" id="exampleInputEmail1" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Password</label>
                        <input type="password" class="form-control" id="exampleInputPassword1" name="password" required>
                    </div>

                    <!-- Pulsanti di invio e link per login lettore -->
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">Login</button>
                        <a href="../index.php" class="btn btn-link">Login Lettore</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
