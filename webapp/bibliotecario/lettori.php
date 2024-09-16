<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$query_lettori = "SELECT cf, nome, cognome FROM biblioteca_ag.lettore";
$result_lettori = pg_prepare($db, "elenco_lettori", $query_lettori);
$result_lettori = pg_execute($db, "elenco_lettori", array());
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Lettori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Elenco Lettori</h2>
        <div>
            <a href="home.php" class="btn btn-primary">Home</a>
            <a href="aggiungi_lettore.php" class="btn btn-success">Aggiungi Lettore</a>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Codice Fiscale</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Azioni</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = pg_fetch_assoc($result_lettori)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['cf']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td><?php echo htmlspecialchars($row['cognome']); ?></td>
                    <td>
                        <a href="gestione_lettore.php?cf=<?php echo urlencode($row['cf']); ?>" class="btn btn-outline-primary btn-sm">Gestisci</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php
pg_close($db);
?>
