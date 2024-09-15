<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Query per ottenere le informazioni delle sedi
$query_sedi = "SELECT cod, città, indirizzo FROM biblioteca_ag.sede";
$result_sedi = pg_query($db, $query_sedi);
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Sedi - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsante Home, Titolo, e Pulsante Aggiungi Sede -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="home.php" class="btn btn-primary">Home</a>
        <h2 class="m-0">Elenco delle Sedi</h2>
        <a href="aggiungi_sede.php" class="btn btn-success">Aggiungi Nuova Sede</a>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Città</th>
                <th>Indirizzo</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = pg_fetch_assoc($result_sedi)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['città']); ?></td>
                    <td><?php echo htmlspecialchars($row['indirizzo']); ?></td>
                    <td>
                        <a href="gestione_sede.php?sede_id=<?php echo $row['cod']; ?>" class="btn btn-secondary btn-sm">Gestisci</a>
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
