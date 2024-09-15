<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Verifica se è stato passato un ID autore valido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: autori.php");
    exit;
}

$autore_id = $_GET['id'];

// Variabile per messaggio di errore/successo
$error = "";
$success = "";

// Controllo del submit per l'eliminazione dell'autore
if (isset($_POST['elimina'])) {
    // Query per eliminare l'autore
    $query_elimina = "DELETE FROM biblioteca_ag.autore WHERE id = $1";
    $result_elimina = pg_query_params($db, $query_elimina, [$autore_id]);

    if ($result_elimina) {
        $success = "Autore eliminato con successo.";
        header("Location: autori.php");
        exit;
    } else {
        $error = "Errore durante l'eliminazione dell'autore.";
    }
}

// Query per ottenere le informazioni dell'autore
$query_autore = "SELECT * FROM biblioteca_ag.autore WHERE id = $1";
$result_autore = pg_query_params($db, $query_autore, [$autore_id]);

if (!$result_autore || pg_num_rows($result_autore) == 0) {
    // Se non trova l'autore, reindirizza a autori.php
    header("Location: autori.php");
    exit;
}

// Recupera i dati dell'autore
$autore = pg_fetch_assoc($result_autore);
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Autore - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsante per Tornare all'Elenco degli Autori -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Autore</h2>
        <a href="autori.php" class="btn btn-primary">Torna a Elenco Autori</a>
    </div>

    <!-- Messaggi di Errore e Successo -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Informazioni sull'Autore -->
    <div class="card mb-4">
        <div class="card-header">
            Dettagli Autore
        </div>
        <div class="card-body">
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($autore['nome']); ?></p>
            <p><strong>Cognome:</strong> <?php echo htmlspecialchars($autore['cognome']); ?></p>
            <p><strong>Data di Nascita:</strong> <?php echo htmlspecialchars($autore['data_nascita']); ?></p>
            <p><strong>Data di Morte:</strong> <?php echo $autore['data_morte'] ? htmlspecialchars($autore['data_morte']) : 'N/A'; ?></p>
            <p><strong>Biografia:</strong> <?php echo $autore['biografia'] ? htmlspecialchars($autore['biografia']) : 'N/A'; ?></p>
        </div>
    </div>

    <!-- Form per Eliminare l'Autore -->
    <form method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questo autore?');">
        <button type="submit" name="elimina" class="btn btn-danger">Elimina Autore</button>
    </form>
</div>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
