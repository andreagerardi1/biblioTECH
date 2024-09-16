<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: autori.php");
    exit;
}

$autore_id = $_GET['id'];

$error = $success = "";

if (isset($_POST['elimina'])) {
    $query_el_scrivi = "DELETE FROM biblioteca_ag.scrive WHERE id_autore = $1";
    $result_el_scrivi = pg_prepare($db, "elimina_scrivi",$query_el_scrivi);
    $result_el_scrivi = pg_execute($db, "elimina_scrivi", array($autore_id));

    $query_elimina = "DELETE FROM biblioteca_ag.autore WHERE id = $1";
    $result_elimina = pg_prepare($db, "elimina_autore",$query_elimina);
    $result_elimina = pg_execute($db, "elimina_autore", array($autore_id));

    if ($result_elimina) {
        $success = "Autore eliminato con successo.";
        header("Location: autori.php");
        exit;
    } else {
        $error = "Errore durante l'eliminazione dell'autore.";
    }
}

$query_autore = "SELECT * FROM biblioteca_ag.autore WHERE id = $1";
$result_autore = pg_prepare($db, "info_autore",$query_autore);
$result_autore = pg_execute($db, "info_autore", array($autore_id));

if (!$result_autore || pg_num_rows($result_autore) == 0) {
    header("Location: autori.php");
    exit;
}

$autore = pg_fetch_assoc($result_autore);
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Autore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Autore</h2>
        <a href="autori.php" class="btn btn-primary">Torna a Elenco Autori</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            Dettagli Autore
        </div>

        <div class="card-body">
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($autore['nome']); ?></p>
            <p><strong>Cognome:</strong> <?php echo htmlspecialchars($autore['cognome']); ?></p>
            <p><strong>Data di Nascita:</strong> <?php echo htmlspecialchars($autore['data_nascita']); ?></p>
            <p><strong>Data di Morte:</strong> <?php echo $autore['data_morte'] ? htmlspecialchars($autore['data_morte']) : 'N/A'; ?></p>
            <p><strong>Biografia:</strong> <?php echo $autore['biografia'] ? htmlspecialchars($autore['biografia']) : 'N/A'; ?></p>
        </div>
    </div>

    <form method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questo autore?');">
        <button type="submit" name="elimina" class="btn btn-danger">Elimina Autore</button>
    </form>
</div>
</body>
</html>

<?php
pg_close($db);
?>
