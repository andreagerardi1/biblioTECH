<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$error = $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cf = $_POST['cf'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($cf) || empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        $error = 'Tutti i campi sono obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Formato email non valido.';
    } elseif (strlen($password) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } else {
        $query_check_cf = "SELECT cf FROM biblioteca_ag.lettore WHERE cf = $1";
        $result_check_cf = pg_prepare($db, "contolla_duplicati_cf",$query_check_cf);
        $result_check_cf = pg_execute($db, "contolla_duplicati_cf", array($cf));

        $query_check_email = "SELECT email FROM biblioteca_ag.utente_lettore WHERE email = $1";
        $result_check_email = pg_prepare($db, "controlla_duplicati_email",$query_check_email);
        $result_check_email = pg_execute($db, "controlla_duplicati_email", array($email));

        if (pg_num_rows($result_check_cf) > 0) {
            $error = 'Il codice fiscale esiste già nel sistema.';
        } elseif (pg_num_rows($result_check_email) > 0) {
            $error = 'L\'email esiste già nel sistema.';
        } else {
            pg_execute($db, "begin", array());

            $query_insert_lettore = "INSERT INTO biblioteca_ag.lettore (cf, nome, cognome) VALUES ($1, $2, $3)";
            $result_insert_lettore = pg_prepare($db, "inserisci_lettore",$query_insert_lettore);
            $result_insert_lettore = pg_execute($db, "inserisci_lettore", array($cf, $nome, $cognome));

            if ($result_insert_lettore) {
                $query_insert_utente = "INSERT INTO biblioteca_ag.utente_lettore (email, password, cf_lettore) VALUES ($1, $2, $3)";
                $result_insert_utente = pg_prepare($db, "inserisci_utente_lettore",$query_insert_utente);
                $result_insert_utente = pg_execute($db, "inserisci_utente_lettore", array($email, password_hash($password, PASSWORD_DEFAULT), $cf));

                if ($result_insert_utente) {
                    pg_execute($db, "commit",array());
                    $success = 'Lettore aggiunto con successo.';
                } else {
                    pg_execute($db, "rollback",array());
                    $error = 'Errore nell\'aggiunta dell\'utente lettore. Riprova.';
                }
            } else {
                pg_execute($db, "rollback",array());
                $error = 'Errore nell\'aggiunta del lettore. Riprova.';
            }
        }
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aggiungi Lettore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4" style="max-width: 500px; width: 100%;">
        <div class="card-body">
            <h5 class="card-title text-center mb-4">Aggiungi Nuovo Lettore</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="cf" class="form-label">Codice Fiscale</label>
                    <input type="text" class="form-control" id="cf" name="cf" required>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="mb-3">
                    <label for="cognome" class="form-label">Cognome</label>
                    <input type="text" class="form-control" id="cognome" name="cognome" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Aggiungi Lettore</button>
                <a href="lettori.php" class="btn btn-secondary w-100 mt-2">Torna ai Lettori</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>

<?php
pg_close($db);
?>
