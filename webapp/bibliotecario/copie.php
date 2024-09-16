<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$query_copie = "SELECT codice, libro_isbn, stato, sede_cod FROM biblioteca_ag.copia";
$result_copie = pg_prepare($db, "info_copia", $query_copie);
$result_copie = pg_execute($db, "info_copia", array());
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Copie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Elenco Copie</h2>
        <div>
            <a href="home.php" class="btn btn-primary me-2">Home</a>
            <a href="aggiungi_copia.php" class="btn btn-success">Aggiungi Copia</a>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Codice</th>
                <th>ISBN Libro</th>
                <th>Stato</th>
                <th>Sede Codice</th>
                <th>Azioni</th>
            </tr>
        </thead>

        <tbody>
            <?php if (pg_num_rows($result_copie) > 0): ?>
                <?php while ($copia = pg_fetch_assoc($result_copie)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($copia['codice']); ?></td>
                        <td><?php echo htmlspecialchars($copia['libro_isbn']); ?></td>
                        <td><?php echo htmlspecialchars($copia['stato']); ?></td>
                        <td><?php echo htmlspecialchars($copia['sede_cod']); ?></td>
                        <td>
                            <a href="gestisci_copia.php?codice=<?php echo htmlspecialchars($copia['codice']); ?>" class="btn btn-outline-primary btn-sm">Gestisci</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Nessuna copia trovata.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
pg_close($db);
?>
