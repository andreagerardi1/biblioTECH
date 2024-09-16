<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $citta = trim($_POST["città"]);
    $indirizzo = trim($_POST["indirizzo"]);

    if (!empty($citta) && !empty($indirizzo)) {
        $query_inserisci_sede = "
            INSERT INTO biblioteca_ag.sede (città, indirizzo) 
            VALUES ($1, $2)
        ";
        $result = pg_prepare($db, "nuova_sede" ,$query_inserisci_sede);
        $result = pg_execute($db, "nuova_sede", array($citta, $indirizzo));

        if ($result) {
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
    <title>Aggiungi Nuova Sede</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4" style="max-width: 400px; width: 100%;">
        <div class="card-body">
            <h5 class="card-title text-center mb-4">Aggiungi Nuova Sede</h5>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="post" action="aggiungi_sede.php">
                <div class="mb-3">
                    <label for="città" class="form-label">Città</label>
                    <input type="text" class="form-control" id="città" name="città" required>
                </div>

                <div class="mb-3">
                    <label for="indirizzo" class="form-label">Indirizzo</label>
                    <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Aggiungi Sede</button>
                    <a href="sedi.php" class="btn btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>

<?php
pg_close($db);
?>
