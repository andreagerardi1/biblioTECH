<?php
session_start();

// Verifica se l'utente è loggato come bibliotecario
if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

// Include il file di connessione al database
include('../connection.php');

// Query per ottenere le statistiche delle sedi con città e indirizzo
$query_statistiche_sedi = "
    SELECT s.città, s.indirizzo, ss.copie, ss.libri, ss.prestiti_attivi
    FROM biblioteca_ag.sede s
    JOIN biblioteca_ag.statistiche_sedi ss ON s.cod = ss.sede
";
$result_statistiche_sedi = pg_query($db, $query_statistiche_sedi);

// Gestione del logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home Bibliotecario - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <!-- Menù Laterale -->
    <div class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="width: 250px; height: 100vh;">
        <h4 class="mb-4">Biblioteca - Dashboard</h4>
        <div class="d-grid gap-2">
            <a href="sedi.php" class="btn btn-outline-dark" role="button">Sedi</a>
            <a href="lettori.php" class="btn btn-outline-dark" role="button">Lettori</a>
            <a href="autori.php" class="btn btn-outline-dark" role="button">Autori</a>
            <a href="libri.php" class="btn btn-outline-dark" role="button">Libri</a>
            <a href="copie.php" class="btn btn-outline-dark" role="button">Copie</a>
        </div>
        <hr class="mt-4">
        <div class="mt-auto">
            <!-- Form per il logout -->
            <form method="post" class="d-grid gap-2">
                <button type="submit" name="logout" class="btn btn-danger w-100 mb-2">Logout</button>
            </form>
            <a href="cambia_password.php" class="btn btn-secondary w-100">Cambia Password</a>
        </div>
    </div>

    <!-- Contenuto Principale -->
    <div class="container-fluid p-4">
        <h3>Statistiche delle Sedi della Biblioteca</h3>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>Città</th>
                    <th>Indirizzo</th>
                    <th>Copie</th>
                    <th>Libri</th>
                    <th>Prestiti Attivi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = pg_fetch_assoc($result_statistiche_sedi)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['città']); ?></td>
                        <td><?php echo htmlspecialchars($row['indirizzo']); ?></td>
                        <td><?php echo htmlspecialchars($row['copie']); ?></td>
                        <td><?php echo htmlspecialchars($row['libri']); ?></td>
                        <td><?php echo htmlspecialchars($row['prestiti_attivi']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Chiudi la connessione al database
pg_close($db);
?>
