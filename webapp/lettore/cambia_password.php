<?php
session_start();

// Verifica se l'utente è loggato come lettore
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "lettore") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Variabili per la gestione degli errori e dei messaggi
$old_password = $new_password = $confirm_password = "";
$error_message = "";
$success_message = "";

// Verifica se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = trim($_POST["old_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Verifica che tutti i campi siano compilati
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Per favore, riempi tutti i campi.";
    } else {
        // Ottieni l'email del lettore dalla sessione
        $email_lettore = $_SESSION["email"];

        // Recupera la password attuale dal database
        $query = "SELECT password FROM biblioteca_ag.utente_lettore WHERE email = $1";
        $result = pg_query_params($db, $query, array($email_lettore));

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);

            // Verifica che la vecchia password sia corretta
            if (!password_verify($old_password, $row['password'])) {
                $error_message = "La vecchia password è errata.";
            } else {
                // Verifica che la nuova password e la conferma coincidano
                if ($new_password !== $confirm_password) {
                    $error_message = "Le nuove password non coincidono.";
                } else {
                    // Aggiorna la password nel database (dopo averla hashata)
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE biblioteca_ag.utente_lettore SET password = $1 WHERE email = $2";
                    $update_result = pg_query_params($db, $update_query, array($hashed_new_password, $email_lettore));

                    if ($update_result) {
                        $success_message = "Password cambiata con successo!";
                    } else {
                        $error_message = "Errore durante l'aggiornamento della password.";
                    }
                }
            }
        } else {
            $error_message = "Errore durante il recupero dei dati dell'utente.";
        }
    }
}

?>

<!doctype html>
<html lang="it">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambia Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
    <div class="container mt-5">
      <h2 class="text-center mb-4">Cambia Password</h2>

      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
          <label for="old_password" class="form-label">Vecchia Password</label>
          <input type="password" class="form-control" id="old_password" name="old_password" required>
        </div>
        <div class="mb-3">
          <label for="new_password" class="form-label">Nuova Password</label>
          <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Cambia Password</button>
        <a href="area_personale.php" class="btn btn-secondary">Annulla</a>
      </form>
    </div>
  </body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
