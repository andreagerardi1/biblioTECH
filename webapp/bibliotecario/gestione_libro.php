<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Recupera l'ISBN del libro dalla query string
$isbn = $_GET['isbn'];

// Query per ottenere le informazioni del libro
$query_libro = "
    SELECT isbn, titolo, trama, casa_ed
    FROM biblioteca_ag.libro
    WHERE isbn = $1
";
$result_libro = pg_query_params($db, $query_libro, array($isbn));
$libro = pg_fetch_assoc($result_libro);

// Query per ottenere gli autori associati al libro
$query_autori = "
    SELECT a.nome, a.cognome
    FROM biblioteca_ag.autore a
    JOIN biblioteca_ag.scrive s ON a.id = s.autore_id
    WHERE s.libro_isbn = $1
";
$result_autori = pg_query_params($db, $query_autori, array($isbn));

// Gestione dell'eliminazione del libro
if (isset($_POST['elimina_libro'])) {
    // Inizia una transazione per garantire che tutte le query vengano eseguite correttamente
    pg_query($db, "BEGIN");

    // Elimina tutti i prestiti associati alle copie di questo libro
    $query_elimina_prestiti = "
        DELETE FROM biblioteca_ag.prestito
        WHERE libro_isbn = $1
    ";
    $result_elimina_prestiti = pg_query_params($db, $query_elimina_prestiti, array($isbn));

    // Elimina tutte le relazioni nella tabella scrive per questo libro
    $query_elimina_scrive = "
        DELETE FROM biblioteca_ag.scrive
        WHERE libro_isbn = $1
    ";
    $result_elimina_scrive = pg_query_params($db, $query_elimina_scrive, array($isbn));

    // Elimina tutte le copie associate a questo libro
    $query_elimina_copie = "
        DELETE FROM biblioteca_ag.copia
        WHERE libro_isbn = $1
    ";
    $result_elimina_copie = pg_query_params($db, $query_elimina_copie, array($isbn));

    // Elimina il libro stesso
    $query_elimina_libro = "
        DELETE FROM biblioteca_ag.libro
        WHERE isbn = $1
    ";
    $result_elimina_libro = pg_query_params($db, $query_elimina_libro, array($isbn));

    // Verifica se tutte le query sono andate a buon fine
    if ($result_elimina_prestiti && $result_elimina_scrive && $result_elimina_copie && $result_elimina_libro) {
        pg_query($db, "COMMIT");
        header("Location: libri.php"); // Reindirizza a libri.php dopo l'eliminazione
        exit;
    } else {
        pg_query($db, "ROLLBACK");
        echo "<p class='text-danger'>Errore nell'eliminazione del libro. Riprova.</p>";
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Libro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Gestione Libro</h2>

    <!-- Dettagli del libro -->
    <table class="table table-bordered mt-4">
        <tr>
            <th>ISBN</th>
            <td><?php echo htmlspecialchars($libro['isbn']); ?></td>
        </tr>
        <tr>
            <th>Titolo</th>
            <td><?php echo htmlspecialchars($libro['titolo']); ?></td>
        </tr>
        <tr>
            <th>Trama</th>
            <td><?php echo htmlspecialchars($libro['trama']); ?></td>
        </tr>
        <tr>
            <th>Casa Editrice</th>
            <td><?php echo htmlspecialchars($libro['casa_ed']); ?></td>
        </tr>
    </table>

    <!-- Lista degli autori -->
    <h4>Autori</h4>
    <ul class="list-group mb-4">
        <?php while ($autore = pg_fetch_assoc($result_autori)): ?>
            <li class="list-group-item">
                <?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <!-- Pulsanti di gestione: Torna a Libri a sinistra ed Elimina Libro a destra -->
    <div class="d-flex justify-content-start gap-2">
        <a href="libri.php" class="btn btn-secondary">Torna a Libri</a>

        <!-- Pulsante per eliminare il libro -->
        <form method="post" class="ms-auto" onsubmit="return confirm('Sei sicuro di voler eliminare questo libro e tutti i dati associati? Questa azione è irreversibile.');">
            <button type="submit" name="elimina_libro" class="btn btn-danger">Elimina Libro</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
