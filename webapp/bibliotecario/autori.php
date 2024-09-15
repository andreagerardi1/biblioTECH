<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Query per ottenere tutti gli autori
$query_autori = "SELECT id, nome, cognome, data_nascita FROM biblioteca_ag.autore ORDER BY cognome, nome";
$result_autori = pg_query($db, $query_autori);

// Gestione del logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autori - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsante per Tornare alla Home e Aggiungere un Nuovo Autore -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="home.php" class="btn btn-primary">Home</a>
        <h2 class="m-0">Elenco Autori</h2>
        <a href="aggiungi_autore.php" class="btn btn-success">Aggiungi Autore</a>
    </div>

    <!-- Tabella degli Autori -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Data di Nascita</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($autore = pg_fetch_assoc($result_autori)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($autore['nome']); ?></td>
                    <td><?php echo htmlspecialchars($autore['cognome']); ?></td>
                    <td><?php echo htmlspecialchars($autore['data_nascita']); ?></td>
                    <td>
                        <a href="gestione_autore.php?id=<?php echo $autore['id']; ?>" class="btn btn-info btn-sm">Gestisci</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
