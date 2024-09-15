<?php
// Avvia la sessione
session_start();

// Include il file di connessione al database
include('connection.php');

// Variabili per memorizzare i messaggi di errore e input dell'utente
$email = $password = "";
$error_message = "";

// Controlla se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Verifica se i campi sono vuoti
    if (empty($email) || empty($password)) {
        $error_message = "Per favore, riempi tutti i campi.";
    } else {
        // Query per verificare l'email dell'utente
        $query = "SELECT email, password, cf_lettore FROM biblioteca_ag.utente_lettore WHERE email = $1";
        $result = pg_query_params($db, $query, array($email));

        if ($result && pg_num_rows($result) > 0) {
            // Recupera il record dell'utente
            $utente = pg_fetch_assoc($result);

            // Verifica la password hashata
            if (password_verify($password, $utente['password'])) {
                // Credenziali corrette: imposta la sessione e reindirizza alla home
                $_SESSION["loggedin"] = true;
                $_SESSION["email"] = $utente['email'];
                $_SESSION["cf"] = $utente["cf_lettore"];
                $_SESSION["tipo"] = "lettore";
                header("Location: lettore/home.php");
                exit;
            } else {
                // Password errata
                $error_message = "Email o password non corretti.";
            }
        } else {
            // Nessun utente trovato con quella email
            $error_message = "Email o password non corretti.";
        }
    }
}
?>

<!doctype html>
<html lang="it">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Lettore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/login.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  </head>
  <body>
    <div class="container form-container">
      <h2 class="text-center mb-4">Login Lettore</h2>
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
          <label for="exampleInputEmail1" class="form-label">Email</label>
          <input type="email" class="form-control" id="exampleInputEmail1" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="mb-3">
          <label for="exampleInputPassword1" class="form-label">Password</label>
          <input type="password" class="form-control" id="exampleInputPassword1" name="password">
        </div>
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-primary">Login</button>
          <a href="bibliotecario/login_bibliotecario.php" class="btn btn-link">Login Bibliotecario</a>
        </div>
      </form>
    </div>
  </body>
</html>
