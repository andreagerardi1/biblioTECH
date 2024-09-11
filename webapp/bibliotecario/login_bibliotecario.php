<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login bibliotecario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="../styles/login.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  </head>
  <body>
    <?php
      // Avvia la sessione
      session_start();

      // Include il file di connessione al database
      include '../connection.php';

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
              // Query per verificare le credenziali dell'utente
              $query = "SELECT * FROM biblioteca_ag.utente_bibliotecario WHERE email = $1 AND password = $2";
              $result = pg_query_params($db, $query, array($email, $password));
              $userdata = pg_fetch_assoc($result);

              if ($result && pg_num_rows($result) > 0) {
                  // Credenziali corrette: reindirizza alla home
                  $_SESSION["loggedin"] = true;
                  $_SESSION["email"] = $email;
                  $_SESSION["tipo"] = "bibliotecario";
                  header("Location: home.php");
                  exit;
              } else {
                  // Credenziali errate: mostra messaggio di errore
                  $error_message = "Email o password non corretti.";
              }
          }
      }
    ?>
    <div class="container form-container">
    <h2 class="text-center mb-4">Login Bibliotecario</h2>
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
          <a href="../index.php" class="btn btn-link">Login Lettore</a>
        </div>
    </form>
    </div>
  </body>
</html>
