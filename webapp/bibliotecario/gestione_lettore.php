<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Ottiene il codice fiscale del lettore dalla richiesta GET
$cf = $_GET['cf'];

// Variabili per gestire i messaggi di errore/successo
$error = '';
$success = '';

// Ottiene le informazioni del lettore
$query_lettore = "
    SELECT l.cf, l.nome, l.cognome, ul.email, l.volumi_in_ritardo
    FROM biblioteca_ag.lettore l
    LEFT JOIN biblioteca_ag.utente_lettore ul ON l.cf = ul.cf_lettore
    WHERE l.cf = $1
";
$result_lettore = pg_query_params($db, $query_lettore, array($cf));
$lettore = pg_fetch_assoc($result_lettore);

// Ottiene i prestiti in corso del lettore
$query_prestiti = "
    SELECT p.id, p.copia_codice, p.data_inizio, p.fine_concessione, p.restituzione
    FROM biblioteca_ag.prestito p
    WHERE p.lettore_cf = $1 AND p.restituzione IS NULL
";
$result_prestiti = pg_query_params($db, $query_prestiti, array($cf));

// Gestione della consegna di un prestito
if (isset($_POST['consegna_prestito'])) {
    $id_prestito = $_POST['prestito_id'];
    $query_consegna = "UPDATE biblioteca_ag.prestito SET restituzione = CURRENT_DATE WHERE id = $1";
    $result_consegna = pg_query_params($db, $query_consegna, array($id_prestito));
    
    if ($result_consegna) {
        $success = 'Prestito consegnato con successo.';
        header("Refresh:0");
    } else {
        $error = 'Errore durante la consegna del prestito.';
    }
}

// Gestione del prolungamento di un prestito
if (isset($_POST['prolunga_prestito'])) {
    $id_prestito = $_POST['prestito_id'];
    $query_prolunga = "CALL biblioteca_ag.prolunga_prestito($1)";
    $result_prolunga = pg_query_params($db, $query_prolunga, array($id_prestito));
    
    if ($result_prolunga) {
        $success = 'Prestito prolungato con successo.';
        header("Refresh:0");
    } else {
        $error = 'Errore durante il prolungamento del prestito. Potrebbe essere già in ritardo.';
    }
}

// Gestione dell'eliminazione del lettore
if (isset($_POST['elimina_lettore'])) {
    // Elimina prima l'utente_lettore se esiste
    $query_delete_user = "DELETE FROM biblioteca_ag.utente_lettore WHERE cf_lettore = $1";
    pg_query_params($db, $query_delete_user, array($cf));
    
    // Elimina il lettore
    $query_delete_lettore = "DELETE FROM biblioteca_ag.lettore WHERE cf = $1";
    $result_delete_lettore = pg_query_params($db, $query_delete_lettore, array($cf));
    
    if ($result_delete_lettore) {
        header("Location: lettori.php");
        exit;
    } else {
        $error = 'Errore durante l\'eliminazione del lettore.';
    }
}

// Gestione dell'azzeramento dei ritardi
if (isset($_POST['azzera_ritardi'])) {
    $query_azzera_ritardi = "UPDATE biblioteca_ag.lettore SET volumi_in_ritardo = 0 WHERE cf = $1";
    $result_azzera_ritardi = pg_query_params($db, $query_azzera_ritardi, array($cf));
    
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
    <!-- Header con Pulsante per Tornare ai Lettori -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Lettore</h2>
        <a href="lettori.php" class="btn btn-primary">Torna ai Lettori</a>
    </div>

    <!-- Messaggi di Errore/Successo -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Informazioni Lettore -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Informazioni Lettore</h5>
            <p class="card-text"><strong>Codice Fiscale:</strong> <?php echo htmlspecialchars($lettore['cf']); ?></p>
            <p class="card-text"><strong>Nome:</strong> <?php echo htmlspecialchars($lettore['nome']); ?></p>
            <p class="card-text"><strong>Cognome:</strong> <?php echo htmlspecialchars($lettore['cognome']); ?></p>
            <p class="card-text"><strong>Email:</strong> <?php echo htmlspecialchars($lettore['email']); ?></p>
            <p class="card-text"><strong>Volumi in Ritardo:</strong> <?php echo htmlspecialchars($lettore['volumi_in_ritardo']); ?></p>

            <!-- Pulsante Azzera Ritardi -->
            <form method="post" class="d-inline">
                <button type="submit" name="azzera_ritardi" class="btn btn-warning">Azzera Ritardi</button>
            </form>
        </div>
    </div>

    <!-- Tabella dei Prestiti -->
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

    <!-- Bottone di Eliminazione del Lettore -->
    <form method="post">
        <button type="submit" name="elimina_lettore" class="btn btn-danger mt-4">Elimina Lettore</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

