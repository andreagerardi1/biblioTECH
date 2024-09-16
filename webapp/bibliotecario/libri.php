<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$query_libri = "SELECT isbn, titolo, casa_ed FROM biblioteca_ag.libro";
$result_libri = pg_prepare($db,"catalogo_libri", $query_libri);
$result_libri = pg_execute($db,"catalogo_libri", array());

?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Libri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Elenco Libri</h2>
        <div>
            <a href="home.php" class="btn btn-primary me-2">Home</a>
            <a href="aggiungi_libro.php" class="btn btn-success">Aggiungi Libro</a>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ISBN</th>
                <th>Titolo</th>
                <th>Casa Editrice</th>
                <th>Azioni</th>
            </tr>
        </thead>

        <tbody>
            <?php if (pg_num_rows($result_libri) > 0): ?>
                <?php while ($libro = pg_fetch_assoc($result_libri)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($libro['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($libro['titolo']); ?></td>
                        <td><?php echo htmlspecialchars($libro['casa_ed']); ?></td>
                        <td>
                            <a href="gestione_libro.php?isbn=<?php echo htmlspecialchars($libro['isbn']); ?>" class="btn btn-outline-primary btn-sm">Gestisci</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">Nessun libro trovato.</td>
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
