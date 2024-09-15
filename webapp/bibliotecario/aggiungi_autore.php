<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Variabili per i messaggi di errore e successo
$error = "";
$success = "";

// Controllo del submit del form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preleva i dati dal form
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $data_nascita = trim($_POST['data_nascita']);
    $data_morte = trim($_POST['data_morte']);
    $biografia = trim($_POST['biografia']);

    // Validazione dei campi
    if (empty($nome) || empty($cognome) || empty($data_nascita)) {
        $error = "Tutti i campi obbligatori devono essere riempiti.";
    } else {
        // Verifica del formato delle date
        if (!empty($data_morte) && strtotime($data_morte) <= strtotime($data_nascita)) {
            $error = "La data di morte deve essere successiva alla data di nascita.";
        } else {
            // Inserimento nel database
            $query = "INSERT INTO biblioteca_ag.autore (nome, cognome, data_nascita, data_morte, biografia) VALUES ($1, $2, $3, $4, $5)";
            $result = pg_query_params($db, $query, [$nome, $cognome, $data_nascita, $data_morte ?: null, $biografia]);

            if ($result) {
                $success = "Autore aggiunto con successo!";
            } else {
                $error = "Errore nell'aggiunta dell'autore. Riprova.";
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
    <title>Aggiungi Autore - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsante per Tornare alla Lista degli Autori -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Aggiungi Nuovo Autore</h2>
        <a href="autori.php" class="btn btn-primary">Torna a Elenco Autori</a>
    </div>

    <!-- Messaggi di Errore e Successo -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Form per Aggiungere un Nuovo Autore -->
    <form method="post" action="">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
        </div>
        <div class="mb-3">
            <label for="cognome" class="form-label">Cognome</label>
            <input type="text" class="form-control" id="cognome" name="cognome" required>
        </div>
        <div class="mb-3">
            <label for="data_nascita" class="form-label">Data di Nascita</label>
            <input type="date" class="form-control" id="data_nascita" name="data_nascita" required>
        </div>
        <div class="mb-3">
            <label for="data_morte" class="form-label">Data di Morte (Opzionale)</label>
            <input type="date" class="form-control" id="data_morte" name="data_morte">
        </div>
        <div class="mb-3">
            <label for="biografia" class="form-label">Biografia (Opzionale)</label>
            <textarea class="form-control" id="biografia" name="biografia" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Aggiungi Autore</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
