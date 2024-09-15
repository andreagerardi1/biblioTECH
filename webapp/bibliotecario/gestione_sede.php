<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Verifica se un ID di sede è stato fornito
if (!isset($_GET['sede_id'])) {
    header("Location: sedi.php"); // Se manca l'ID, reindirizza a sedi.php
    exit;
}

$sede_id = $_GET['sede_id'];

// Recupera le informazioni della sede
$query_sede = "SELECT città, indirizzo FROM biblioteca_ag.sede WHERE cod = $1";
$result_sede = pg_query_params($db, $query_sede, array($sede_id));
$sede = pg_fetch_assoc($result_sede);

// Recupera i report dei ritardi per la sede
$query_report_ritardi = "SELECT copia_codice, lettore_cf FROM biblioteca_ag.report_ritardi WHERE sede_cod = $1";
$result_report_ritardi = pg_query_params($db, $query_report_ritardi, array($sede_id));

// Gestione dell'eliminazione della sede
if (isset($_POST['elimina_sede'])) {
    // Inizia una transazione per garantire che tutte le query siano eseguite correttamente
    pg_query($db, "BEGIN");
    
    // Elimina prima tutti i prestiti associati alle copie della sede
    $query_elimina_prestiti = "
        DELETE FROM biblioteca_ag.prestito 
        WHERE copia_codice IN (SELECT codice FROM biblioteca_ag.copia WHERE sede_cod = $1)";
    $result_elimina_prestiti = pg_query_params($db, $query_elimina_prestiti, array($sede_id));

    // Elimina tutte le copie associate alla sede
    $query_elimina_copie = "DELETE FROM biblioteca_ag.copia WHERE sede_cod = $1";
    $result_elimina_copie = pg_query_params($db, $query_elimina_copie, array($sede_id));

    // Elimina la sede
    $query_elimina_sede = "DELETE FROM biblioteca_ag.sede WHERE cod = $1";
    $result_elimina_sede = pg_query_params($db, $query_elimina_sede, array($sede_id));

    // Verifica se tutte le query sono andate a buon fine
    if ($result_elimina_prestiti && $result_elimina_copie && $result_elimina_sede) {
        pg_query($db, "COMMIT");
        header("Location: sedi.php"); // Reindirizza a sedi.php dopo l'eliminazione
        exit;
    } else {
        pg_query($db, "ROLLBACK");
        echo "<p class='text-danger'>Errore nell'eliminazione della sede. Assicurati che non ci siano dipendenze esistenti.</p>";
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Sede - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsante Home -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Sede - <?php echo htmlspecialchars($sede['città']); ?></h2>
        <a href="sedi.php" class="btn btn-primary">Torna a Sedi</a>
    </div>

    <!-- Dettagli della Sede -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Informazioni Sede</h4>
            <p class="card-text"><strong>Città:</strong> <?php echo htmlspecialchars($sede['città']); ?></p>
            <p class="card-text"><strong>Indirizzo:</strong> <?php echo htmlspecialchars($sede['indirizzo']); ?></p>
        </div>
    </div>

    <!-- Report dei Ritardi -->
    <h4>Report dei Ritardi</h4>
    <table class="table table-striped mb-4">
        <thead>
            <tr>
                <th>Copia Codice</th>
                <th>Lettore CF</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = pg_fetch_assoc($result_report_ritardi)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['copia_codice']); ?></td>
                    <td><?php echo htmlspecialchars($row['lettore_cf']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pulsante per Eliminare la Sede -->
    <form method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questa sede? Questa azione è irreversibile.');">
        <button type="submit" name="elimina_sede" class="btn btn-danger">Elimina Sede</button>
    </form>
</div>

</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
