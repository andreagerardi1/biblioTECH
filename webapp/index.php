<?php

session_start();

include 'connection.php';

$email = $password = $error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error_message = "Riempire tutti i campi.";
    } else {
        $query = "SELECT email, password, cf_lettore FROM biblioteca_ag.utente_lettore WHERE email = $1";
        $result = pg_prepare($db,"login_lettore",$query);
        $result = pg_execute($db,"login_lettore",array($email));

        if ($result && pg_num_rows($result) > 0) {

            $lettore = pg_fetch_assoc($result);

            if (password_verify($password, $lettore['password'])) {

                $_SESSION["loggedin"] = true;
                $_SESSION["email"] = $lettore['email'];
                $_SESSION["cf"] = $lettore["cf_lettore"];
                $_SESSION["tipo"] = "lettore";
                header("Location: lettore/home.php");
                exit;
            } else {
                $error_message = "Email o password non corretti.";
            }
        } else {
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
  </head>
  <body>
    <div class="container">
      <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-lg" style="max-width: 400px; width: 100%;">

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
      </div>
    </div>
  </body>
</html>
