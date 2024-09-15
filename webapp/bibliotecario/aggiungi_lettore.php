<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Variabili per gestire i messaggi di errore/successo
$error = '';
$success = '';

// Gestione dell'invio del form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cf = $_POST['cf'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validazione di base
    if (empty($cf) || empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        $error = 'Tutti i campi sono obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Formato email non valido.';
    } elseif (strlen($password) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } else {
        // Controllo se il codice fiscale esiste già
        $query_check_cf = "SELECT cf FROM biblioteca_ag.lettore WHERE cf = $1";
        $result_check_cf = pg_query_params($db, $query_check_cf, array($cf));

        // Controllo se l'email esiste già
        $query_check_email = "SELECT email FROM biblioteca_ag.utente_lettore WHERE email = $1";
        $result_check_email = pg_query_params($db, $query_check_email, array($email));

        if (pg_num_rows($result_check_cf) > 0) {
            $error = 'Il codice fiscale esiste già nel sistema.';
        } elseif (pg_num_rows($result_check_email) > 0) {
            $error = 'L\'email esiste già nel sistema.';
        } else {
            // Inizio della transazione
            pg_query($db, 'BEGIN');

            // Inserimento del lettore
            $query_insert_lettore = "INSERT INTO biblioteca_ag.lettore (cf, nome, cognome) VALUES ($1, $2, $3)";
            $result_insert_lettore = pg_query_params($db, $query_insert_lettore, array($cf, $nome, $cognome));

            // Inserimento dell'utente lettore
            if ($result_insert_lettore) {
                $query_insert_utente = "INSERT INTO biblioteca_ag.utente_lettore (email, password, cf_lettore) VALUES ($1, $2, $3)";
                $result_insert_utente = pg_query_params($db, $query_insert_utente, array($email, $password, $cf));

                if ($result_insert_utente) {
                    // Se entrambe le operazioni sono riuscite
                    pg_query($db, 'COMMIT');
                    $success = 'Lettore aggiunto con successo.';
                } else {
                    // Se c'è un errore, annulliamo la transazione
                    pg_query($db, 'ROLLBACK');
                    $error = 'Errore nell\'aggiunta dell\'utente lettore. Riprova.';
                }
            } else {
                pg_query($db, 'ROLLBACK');
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
    <title>Aggiungi Lettore - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsante per Tornare ai Lettori -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Aggiungi Nuovo Lettore</h2>
        <a href="lettori.php" class="btn btn-primary">Torna ai Lettori</a>
    </div>

    <!-- Messaggi di Errore/Successo -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Form per Aggiungere un Lettore -->
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
        <button type="submit" class="btn btn-success">Aggiungi Lettore</button>
    </form>
</div>

</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
