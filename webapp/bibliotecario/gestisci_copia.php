<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Verifica se un codice di copia è stato fornito
if (!isset($_GET['codice'])) {
    header("Location: copie.php"); // Se manca il codice, reindirizza a copie.php
    exit;
}

$copia_codice = $_GET['codice'];

// Recupera i dettagli della copia
$query_copia = "
    SELECT c.codice, c.libro_isbn, c.stato, c.sede_cod, s.città, s.indirizzo
    FROM biblioteca_ag.copia c
    JOIN biblioteca_ag.sede s ON c.sede_cod = s.cod
    WHERE c.codice = $1
";
$result_copia = pg_query_params($db, $query_copia, array($copia_codice));
$copia = pg_fetch_assoc($result_copia);

// Gestione dell'eliminazione della copia
if (isset($_POST['elimina_copia'])) {
    // Inizia una transazione
    pg_query($db, "BEGIN");

    try {
        // Elimina i prestiti associati alla copia
        $query_elimina_prestiti = "DELETE FROM biblioteca_ag.prestito WHERE copia_codice = $1";
        $result_elimina_prestiti = pg_query_params($db, $query_elimina_prestiti, array($copia_codice));

        if (!$result_elimina_prestiti) {
            throw new Exception('Errore nell\'eliminazione dei prestiti.');
        }

        // Elimina la copia
        $query_elimina_copia = "DELETE FROM biblioteca_ag.copia WHERE codice = $1";
        $result_elimina_copia = pg_query_params($db, $query_elimina_copia, array($copia_codice));

        if (!$result_elimina_copia) {
            throw new Exception('Errore nell\'eliminazione della copia.');
        }

        // Commit della transazione
        pg_query($db, "COMMIT");
        header("Location: copie.php"); // Reindirizza a copie.php dopo l'eliminazione
        exit;
    } catch (Exception $e) {
        // Rollback della transazione in caso di errore
        pg_query($db, "ROLLBACK");
        $error = $e->getMessage();
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Copia - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsanti per Tornare a Copie e Gestire la Copia -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Copia</h2>
        <div>
            <a href="copie.php" class="btn btn-primary me-2">Torna a Copie</a>
        </div>
    </div>

    <!-- Messaggi di Errore -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Dettagli della Copia -->
    <table class="table table-bordered">
        <tr>
            <th>Codice Copia</th>
            <td><?php echo htmlspecialchars($copia['codice']); ?></td>
        </tr>
        <tr>
            <th>ISBN Libro</th>
            <td><?php echo htmlspecialchars($copia['libro_isbn']); ?></td>
        </tr>
        <tr>
            <th>Stato</th>
            <td><?php echo htmlspecialchars($copia['stato']); ?></td>
        </tr>
        <tr>
            <th>Sede</th>
            <td><?php echo htmlspecialchars($copia['città'] . ', ' . $copia['indirizzo']); ?></td>
        </tr>
    </table>

    <!-- Pulsante per Eliminare la Copia -->
    <form method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questa copia? Questa azione è irreversibile.');">
        <button type="submit" name="elimina_copia" class="btn btn-danger">Elimina Copia</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
