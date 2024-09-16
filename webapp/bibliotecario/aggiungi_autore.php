<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $data_nascita = trim($_POST['data_nascita']);
    $data_morte = trim($_POST['data_morte']);
    $biografia = trim($_POST['biografia']);

    if (empty($nome) || empty($cognome) || empty($data_nascita)) {
        $error = "Tutti i campi obbligatori devono essere riempiti.";
    } else {
        if (!empty($data_morte) && strtotime($data_morte) <= strtotime($data_nascita)) {
            $error = "La data di morte deve essere successiva alla data di nascita.";
        } else {
            $query = "INSERT INTO biblioteca_ag.autore (nome, cognome, data_nascita, data_morte, biografia) VALUES ($1, $2, $3, $4, $5)";
            $result = pg_prepare($db, "inserisci_autore",$query);
            $result = pg_execute($db, "inserisci_autore", [$nome, $cognome, $data_nascita, $data_morte ?: null, $biografia]);

            if ($result) {
                $success = "Autore aggiunto con successo!";
            } else {
                $error = "Errore nell'aggiunta dell'autore. Riprova.";
            }
        }
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aggiungi Autore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4" style="max-width: 500px; width: 100%;">
        <div class="card-body">
            <h5 class="card-title text-center mb-4">Aggiungi Nuovo Autore</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="mb-3">
                    <label for="cognome" class="form-label">Cognome</label>
                    <input type="text" class="form-control" id="cognome" name="cognome" required>
                </div>

                <div class="mb-3">
                    <label for="data_nascita" class="form-label">Data di Nascita</label>
                    <input type="date" class="form-control" id="data_nascita" name="data_nascita" required>
                </div>

                <div class="mb-3">
                    <label for="data_morte" class="form-label">Data di Morte (Opzionale)</label>
                    <input type="date" class="form-control" id="data_morte" name="data_morte">
                </div>

                <div class="mb-3">
                    <label for="biografia" class="form-label">Biografia (Opzionale)</label>
                    <textarea class="form-control" id="biografia" name="biografia" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100">Aggiungi Autore</button>
                <a href="autori.php" class="btn btn-secondary w-100 mt-2">Torna a Elenco Autori</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>

<?php
pg_close($db);
?>
