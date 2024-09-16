<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

if (!isset($_GET['sede_id'])) {
    header("Location: sedi.php"); 
    exit;
}

$sede_id = $_GET['sede_id'];

$query_sede = "SELECT città, indirizzo FROM biblioteca_ag.sede WHERE cod = $1";
$result_sede = pg_prepare($db, "indirizzo_sede",$query_sede);
$result_sede = pg_execute($db, "indirizzo_sede", array($sede_id));
$sede = pg_fetch_assoc($result_sede);

$query_report_ritardi = "SELECT copia_codice, lettore_cf FROM biblioteca_ag.report_ritardi WHERE sede_cod = $1";
$result_report_ritardi = pg_prepare($db, "ritardi_sede",$query_report_ritardi);
$result_report_ritardi = pg_execute($db, "ritardi_sede", array($sede_id));

if (isset($_POST['elimina_sede'])) {

    pg_prepare($db, "begin","BEGIN");
    pg_execute($db, "begin", array());
    
    $query_elimina_prestiti = "
        DELETE FROM biblioteca_ag.prestito 
        WHERE copia_codice IN (SELECT codice FROM biblioteca_ag.copia WHERE sede_cod = $1)";
    $result_elimina_prestiti = pg_prepare($db, "elimina_prestiti_sede",$query_elimina_prestiti);
    $result_elimina_prestiti = pg_execute($db, "elimina_prestiti_sede", array($sede_id));

    $query_elimina_copie = "DELETE FROM biblioteca_ag.copia WHERE sede_cod = $1";
    $result_elimina_copie = pg_prepare($db, "elimina_copie_sede",$query_elimina_copie);
    $result_elimina_copie = pg_execute($db, "elimina_copie_sede", array($sede_id));

    $query_elimina_sede = "DELETE FROM biblioteca_ag.sede WHERE cod = $1";
    $result_elimina_sede = pg_prepare($db, "elimina_sede",$query_elimina_sede);
    $result_elimina_sede = pg_execute($db, "elimina_sede", array($sede_id));

    if ($result_elimina_prestiti && $result_elimina_copie && $result_elimina_sede) {
        pg_prepare($db, "commit","COMMIT");
        pg_execute($db, "commit", array());
        header("Location: sedi.php");
        exit;
    } else {
        pg_prepare($db, "rollback","ROLLBACK");
        pg_execute($db, "rollback", array());
        echo "<p class='text-danger'>Errore nell'eliminazione della sede. Assicurati che non ci siano dipendenze esistenti.</p>";
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Sede</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Gestione Sede - <?php echo htmlspecialchars($sede['città']); ?></h2>
        <a href="sedi.php" class="btn btn-primary">Torna a Sedi</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Informazioni Sede</h4>
            <p class="card-text"><strong>Città:</strong> <?php echo htmlspecialchars($sede['città']); ?></p>
            <p class="card-text"><strong>Indirizzo:</strong> <?php echo htmlspecialchars($sede['indirizzo']); ?></p>
        </div>
    </div>

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

    <form method="post" onsubmit="return confirm('Sei sicuro di voler eliminare questa sede? Questa azione è irreversibile.');">
        <button type="submit" name="elimina_sede" class="btn btn-danger">Elimina Sede</button>
    </form>
</div>

</body>
</html>

<?php
pg_close($db);
?>
