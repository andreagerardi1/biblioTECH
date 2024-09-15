<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Query per ottenere gli autori dalla tabella autore
$query_autori = "SELECT id, nome, cognome FROM biblioteca_ag.autore ORDER BY cognome, nome";
$result_autori = pg_query($db, $query_autori);

// Variabili per i messaggi di errore o successo
$errore = '';
$successo = '';

// Gestione invio del modulo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $isbn = $_POST['isbn'];
    $titolo = $_POST['titolo'];
    $trama = $_POST['trama'];
    $casa_ed = $_POST['casa_ed'];
    $autori_selezionati = $_POST['autori'] ?? [];

    // Verifica campi obbligatori
    if (empty($isbn) || empty($titolo) || empty($autori_selezionati)) {
        $errore = "Compila tutti i campi obbligatori (ISBN, Titolo, Autori).";
    } else {
        // Inserimento del libro nella tabella libro
        $query_inserisci_libro = "
            INSERT INTO biblioteca_ag.libro (isbn, titolo, trama, casa_ed)
            VALUES ($1, $2, $3, $4)
        ";
        $result_inserisci_libro = pg_query_params($db, $query_inserisci_libro, array($isbn, $titolo, $trama, $casa_ed));

        if ($result_inserisci_libro) {
            // Inserimento degli autori selezionati nella tabella scrive
            foreach ($autori_selezionati as $autore_id) {
                $query_inserisci_autore = "
                    INSERT INTO biblioteca_ag.scrive (autore_id, libro_isbn)
                    VALUES ($1, $2)
                ";
                pg_query_params($db, $query_inserisci_autore, array($autore_id, $isbn));
            }
            $successo = "Libro e autori associati aggiunti con successo!";
        } else {
            $errore = "Errore durante l'inserimento del libro. Verifica che l'ISBN non sia già presente.";
        }
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aggiungi Libro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Aggiungi un nuovo libro</h2>

    <!-- Messaggi di errore o successo -->
    <?php if (!empty($errore)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errore); ?></div>
    <?php endif; ?>
    <?php if (!empty($successo)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
    <?php endif; ?>

    <!-- Form per aggiungere il libro -->
    <form method="post">
        <div class="mb-3">
            <label for="isbn" class="form-label">ISBN (13 cifre) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="isbn" name="isbn" required maxlength="13">
        </div>
        <div class="mb-3">
            <label for="titolo" class="form-label">Titolo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="titolo" name="titolo" required>
        </div>
        <div class="mb-3">
            <label for="trama" class="form-label">Trama</label>
            <textarea class="form-control" id="trama" name="trama" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label for="casa_ed" class="form-label">Casa Editrice</label>
            <input type="text" class="form-control" id="casa_ed" name="casa_ed">
        </div>
        <div class="mb-3">
            <label for="autori" class="form-label">Seleziona Autori <span class="text-danger">*</span></label>
            <select class="form-select" id="autori" name="autori[]" multiple required>
                <?php while ($autore = pg_fetch_assoc($result_autori)): ?>
                    <option value="<?php echo htmlspecialchars($autore['id']); ?>">
                        <?php echo htmlspecialchars($autore['nome']) . " " . htmlspecialchars($autore['cognome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small class="form-text text-muted">Tieni premuto CTRL (o CMD su Mac) per selezionare più autori.</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Aggiungi Libro</button>
        <a href="libri.php" class="btn btn-secondary">Torna a Libri</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
