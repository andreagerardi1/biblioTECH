<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Gestione dell'inserimento di una nuova sede
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $citta = trim($_POST["città"]);
    $indirizzo = trim($_POST["indirizzo"]);

    // Controlla se i campi non sono vuoti
    if (!empty($citta) && !empty($indirizzo)) {
        // Query per inserire la nuova sede
        $query_inserisci_sede = "
            INSERT INTO biblioteca_ag.sede (città, indirizzo) 
            VALUES ($1, $2)
        ";
        $result = pg_query_params($db, $query_inserisci_sede, array($citta, $indirizzo));

        if ($result) {
            // Reindirizza alla pagina delle sedi dopo l'inserimento
            header("Location: sedi.php");
            exit;
        } else {
            $error_message = "Errore durante l'inserimento della sede.";
        }
    } else {
        $error_message = "Per favore, compila tutti i campi.";
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aggiungi Nuova Sede - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Aggiungi Nuova Sede</h2>

    <!-- Visualizza messaggio di errore, se presente -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Form per aggiungere una nuova sede -->
    <form method="post" action="aggiungi_sede.php">
        <div class="mb-3">
            <label for="città" class="form-label">Città</label>
            <input type="text" class="form-control" id="città" name="città" required>
        </div>
        <div class="mb-3">
            <label for="indirizzo" class="form-label">Indirizzo</label>
            <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
        </div>
        <button type="submit" class="btn btn-success">Aggiungi Sede</button>
        <a href="sedi.php" class="btn btn-secondary ms-2">Annulla</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
