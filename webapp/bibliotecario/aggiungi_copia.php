<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$error = $success = '';

$query_libri = "SELECT isbn, titolo FROM biblioteca_ag.libro";
$result_libri = pg_prepare($db, "info_libro_copia", $query_libri);
$result_libri = pg_execute($db, "info_libro_copia", array());

$query_sedi = "SELECT cod, città, indirizzo FROM biblioteca_ag.sede";
$result_sedi = pg_prepare($db, "info_sede_copia", $query_sedi);
$result_sedi = pg_execute($db, "info_sede_copia", array());

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $libro_isbn = $_POST['libro_isbn'];
    $sede_cod = $_POST['sede_cod'];

    if (empty($libro_isbn) || empty($sede_cod)) {
        $error = 'Tutti i campi sono obbligatori.';
    } else {
        $query_insert = "
            INSERT INTO biblioteca_ag.copia (libro_isbn, sede_cod)
            VALUES ($1, $2)
        ";
        $result_insert = pg_prepare($db, "inserisci_copia", $query_insert);
        $result_insert = pg_execute($db, "inserisci_copia", array($libro_isbn, $sede_cod));

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
    <title>Aggiungi Copia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4" style="max-width: 600px; width: 100%;">
        <div class="card-body">
            <h5 class="card-title text-center mb-4">Aggiungi Nuova Copia</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

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

                <button type="submit" class="btn btn-success w-100">Aggiungi Copia</button>
                <a href="copie.php" class="btn btn-secondary w-100 mt-2">Torna a Copie</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>

<?php
pg_close($db);
?>
