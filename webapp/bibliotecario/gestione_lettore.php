<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$cf = $_GET['cf'];
$error = $success = '';

$query_lettore = "
    SELECT l.cf, l.nome, l.cognome, ul.email, l.volumi_in_ritardo
    FROM biblioteca_ag.lettore l
    LEFT JOIN biblioteca_ag.utente_lettore ul ON l.cf = ul.cf_lettore
    WHERE l.cf = $1
";
$result_lettore = pg_prepare($db, "gestione_lettore",$query_lettore);
$result_lettore = pg_execute($db, "gestione_lettore", array($cf));
$lettore = pg_fetch_assoc($result_lettore);

$query_prestiti = "
    SELECT p.id, p.copia_codice, p.data_inizio, p.fine_concessione, p.restituzione
    FROM biblioteca_ag.prestito p
    WHERE p.lettore_cf = $1 AND p.restituzione IS NULL
";
$result_prestiti = pg_prepare($db, "prestiti_lettore",$query_prestiti);

if (isset($_POST['consegna_prestito'])) {
    $id_prestito = $_POST['prestito_id'];
    $query_consegna = "UPDATE biblioteca_ag.prestito SET restituzione = CURRENT_DATE WHERE id = $1";
    $result_consegna = pg_prepare($db, "cosegna_prestito",$query_consegna);
    $result_consegna = pg_execute($db, "consegna_prestito", array($id_prestito));
    
    if ($result_consegna) {
        $success = 'Prestito consegnato con successo.';
        header("Refresh:0");
    } else {
        $error = 'Errore durante la consegna del prestito.';
    }
}

if (isset($_POST['prolunga_prestito'])) {
    $id_prestito = $_POST['prestito_id'];
    $query_prolunga = "CALL biblioteca_ag.prolunga_prestito($1)";
    $result_prolunga = pg_prepare($db, "prolunga_prestito",$query_prolunga);
    $result_prolunga = pg_execute($db, "prolunga_prestito", array($id_prestito));
    
    if ($result_prolunga) {
        $success = 'Prestito prolungato con successo.';
        header("Refresh:0");
    } else {
        $error = 'Errore durante il prolungamento del prestito. Potrebbe essere già in ritardo.';
    }
}

if (isset($_POST['elimina_lettore'])) {
    $query_delete_user = "DELETE FROM biblioteca_ag.utente_lettore WHERE cf_lettore = $1";
    $result_delete_user = pg_prepare($db, "elimina_utente_lettore",$query_delete_user);
    $result_delete_user = pg_execute($db, "elimina_utente_lettore", array($cf));
    
    $query_delete_lettore = "DELETE FROM biblioteca_ag.lettore WHERE cf = $1";
    $result_delete_lettore = pg_prepare($db, "elimina_lettore",$query_delete_lettore);
    $result_delete_lettore = pg_execute($db, "elimina_lettore", array($cf));
    
    if ($result_delete_lettore) {
        header("Location: lettori.php");
        exit;
    } else {
        $error = 'Errore durante l\'eliminazione del lettore.';
    }
}

if (isset($_POST['azzera_ritardi'])) {
    $query_azzera_ritardi = "UPDATE biblioteca_ag.lettore SET volumi_in_ritardo = 0 WHERE cf = $1";
    $result_azzera_ritardi = pg_prepare($db, "azzera_ritardi",$query_azzera_ritardi);
    $result_azzera_ritardi = pg_execute($db, "azzera_ritardi", array($cf));
    
    if ($result_azzera_ritardi) {
        $success = 'I ritardi sono stati azzerati con successo.';
        header("Refresh:0");
    } else {
        $error = 'Errore durante l\'azzeramento dei ritardi.';
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Lettore - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Lettore</h2>
        <a href="lettori.php" class="btn btn-primary">Torna ai Lettori</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Informazioni Lettore</h5>
            <p class="card-text"><strong>Codice Fiscale:</strong> <?php echo htmlspecialchars($lettore['cf']); ?></p>
            <p class="card-text"><strong>Nome:</strong> <?php echo htmlspecialchars($lettore['nome']); ?></p>
            <p class="card-text"><strong>Cognome:</strong> <?php echo htmlspecialchars($lettore['cognome']); ?></p>
            <p class="card-text"><strong>Email:</strong> <?php echo htmlspecialchars($lettore['email']); ?></p>
            <p class="card-text"><strong>Volumi in Ritardo:</strong> <?php echo htmlspecialchars($lettore['volumi_in_ritardo']); ?></p>

            <form method="post" class="d-inline">
                <button type="submit" name="azzera_ritardi" class="btn btn-warning">Azzera Ritardi</button>
            </form>
        </div>
    </div>

    <h3>Prestiti in Corso</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Copia Codice</th>
                <th>Data Inizio</th>
                <th>Fine Concessione</th>
                <th>Azioni</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($prestito = pg_fetch_assoc($result_prestiti)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prestito['copia_codice']); ?></td>
                    <td><?php echo htmlspecialchars($prestito['data_inizio']); ?></td>
                    <td><?php echo htmlspecialchars($prestito['fine_concessione']); ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="prestito_id" value="<?php echo htmlspecialchars($prestito['id']); ?>">
                            <button type="submit" name="consegna_prestito" class="btn btn-success btn-sm">Consegna</button>
                        </form>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="prestito_id" value="<?php echo htmlspecialchars($prestito['id']); ?>">
                            <button type="submit" name="prolunga_prestito" class="btn btn-warning btn-sm">Prolunga</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <form method="post">
        <button type="submit" name="elimina_lettore" class="btn btn-danger mt-4">Elimina Lettore</button>
    </form>
</div>

</body>
</html>

<?php
pg_close($db);
?>