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

// Recupera i libri e le sedi disponibili per i menu a discesa
$query_libri = "SELECT isbn, titolo FROM biblioteca_ag.libro";
$result_libri = pg_query($db, $query_libri);

$query_sedi = "SELECT cod, città, indirizzo FROM biblioteca_ag.sede";
$result_sedi = pg_query($db, $query_sedi);

// Gestione dell'invio del form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $libro_isbn = $_POST['libro_isbn'];
    $sede_cod = $_POST['sede_cod'];

    // Validazione di base
    if (empty($libro_isbn) || empty($sede_cod)) {
        $error = 'Tutti i campi sono obbligatori.';
    } else {
        // Inserimento nel database
        $query_insert = "
            INSERT INTO biblioteca_ag.copia (libro_isbn, sede_cod)
            VALUES ($1, $2)
        ";
        $result_insert = pg_query_params($db, $query_insert, array($libro_isbn, $sede_cod));

        if ($result_insert) {
            $success = 'Copia aggiunta con successo.';
        } else {
            $error = 'Errore nell\'aggiunta della copia. Riprova.';
        }
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aggiungi Copia - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <!-- Header con Pulsanti per Tornare a Copie e Home -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Aggiungi Nuova Copia</h2>
        <div>
            <a href="copie.php" class="btn btn-primary me-2">Torna a Copie</a>
        </div>
    </div>

    <!-- Messaggi di Errore/Successo -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Form per Aggiungere una Copia -->
    <form method="post">
        <div class="mb-3">
            <label for="libro_isbn" class="form-label">ISBN Libro</label>
            <select class="form-select" id="libro_isbn" name="libro_isbn" required>
                <option value="">Seleziona un libro</option>
                <?php while ($libro = pg_fetch_assoc($result_libri)): ?>
                    <option value="<?php echo htmlspecialchars($libro['isbn']); ?>">
                        <?php echo htmlspecialchars($libro['titolo']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="sede_cod" class="form-label">Sede</label>
            <select class="form-select" id="sede_cod" name="sede_cod" required>
                <option value="">Seleziona una sede</option>
                <?php while ($sede = pg_fetch_assoc($result_sedi)): ?>
                    <option value="<?php echo htmlspecialchars($sede['cod']); ?>">
                        <?php echo htmlspecialchars($sede['città'] . ' - ' . $sede['indirizzo']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Aggiungi Copia</button>
    </form>
</div>

</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
