<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$old_password = $new_password = $confirm_password = $error_message = $success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = trim($_POST["old_password"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Riempire tutti i campi.";
    } else {
        $email_bibliotecario = $_SESSION["email"];

        $query = "SELECT password FROM biblioteca_ag.utente_bibliotecario WHERE email = $1";
        $result = pg_prepare($db, "password_bibliotecario",$query);
        $result = pg_execute($db, "password_bibliotecario", array($email_bibliotecario));

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);

            if (!password_verify($old_password, $row['password'])) {
                $error_message = "La vecchia password è errata.";
            } else {
                if ($new_password !== $confirm_password) {
                    $error_message = "Le nuove password non coincidono.";
                } else {
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE biblioteca_ag.utente_bibliotecario SET password = $1 WHERE email = $2";
                    $update_result = pg_prepare($db,"aggiorna_pw_biblio", $update_query);
                    $update_result = pg_execute($db,"aggiorna_pw_biblio", array($hashed_new_password, $email_bibliotecario));

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
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
      <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
        <h2 class="card-title text-center mb-4">Cambia Password</h2>

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

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-key-fill"></i> Cambia Password</button>
            <a href="home.php" class="btn btn-secondary btn-lg">Torna alla Home</a>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>

<?php
pg_close($db);
?>
