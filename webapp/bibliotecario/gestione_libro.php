<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "bibliotecario") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$isbn = $_GET['isbn'];

$query_libro = "
    SELECT isbn, titolo, trama, casa_ed
    FROM biblioteca_ag.libro
    WHERE isbn = $1
";
$result_libro = pg_prepare($db, "info_libro",$query_libro);
$result_libro = pg_execute($db, "info_libro", array($isbn));
$libro = pg_fetch_assoc($result_libro);

$query_autori = "
    SELECT a.nome, a.cognome
    FROM biblioteca_ag.autore a
    JOIN biblioteca_ag.scrive s ON a.id = s.autore_id
    WHERE s.libro_isbn = $1
";
$result_autori = pg_prepare($db, "autori_libro",$query_autori);
$result_autori = pg_execute($db, "autori_libro", array($isbn));

if (isset($_POST['elimina_libro'])) {
    pg_execute($db, "begin", array());

    $query_elimina_prestiti = "
        DELETE FROM biblioteca_ag.prestito
        WHERE libro_isbn = $1
    ";
    $result_elimina_prestiti = pg_prepare($db, "elimina_prestito_libro",$query_elimina_prestiti);
    $result_elimina_prestiti = pg_execute($db, "elimina_prestito_libro", array($isbn));

    $query_elimina_scrive = "
        DELETE FROM biblioteca_ag.scrive
        WHERE libro_isbn = $1
    ";
    $result_elimina_scrive = pg_prepare($db, "elimina_scrive_libro",$query_elimina_scrive);
    $result_elimina_scrive = pg_execute($db, "elimina_scrive_libro", array($isbn));

    $query_elimina_copie = "
        DELETE FROM biblioteca_ag.copia
        WHERE libro_isbn = $1
    ";
    $result_elimina_copie = pg_prepare($db, "elimina_copia_libro",$query_elimina_copie);
    $result_elimina_copie = pg_execute($db, "elimina_copia_libro", array($isbn));

    $query_elimina_libro = "
        DELETE FROM biblioteca_ag.libro
        WHERE isbn = $1
    ";
    $result_elimina_libro = pg_prepare($db, "elimina_libro",$query_elimina_libro);
    $result_elimina_libro = pg_execute($db, "elimina_libro", array($isbn));

    if ($result_elimina_prestiti && $result_elimina_scrive && $result_elimina_copie && $result_elimina_libro) {
        pg_execute($db, "commit", array());
        header("Location: libri.php"); 
        exit;
    } else {
        pg_execute($db, "rollback", array());
        echo "<p class='text-danger'>Errore nell'eliminazione del libro. Riprova.</p>";
    }
}
?>

<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestione Libro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Gestione Libro</h2>

    <table class="table table-bordered mt-4">
        <tr>
            <th>ISBN</th>
            <td><?php echo htmlspecialchars($libro['isbn']); ?></td>
        </tr>

        <tr>
            <th>Titolo</th>
            <td><?php echo htmlspecialchars($libro['titolo']); ?></td>
        </tr>

        <tr>
            <th>Trama</th>
            <td><?php echo htmlspecialchars($libro['trama']); ?></td>
        </tr>

        <tr>
            <th>Casa Editrice</th>
            <td><?php echo htmlspecialchars($libro['casa_ed']); ?></td>
        </tr>
    </table>

    <h4>Autori</h4>
    <ul class="list-group mb-4">
        <?php while ($autore = pg_fetch_assoc($result_autori)): ?>
            <li class="list-group-item">
                <?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <div class="d-flex justify-content-start gap-2">
        <a href="libri.php" class="btn btn-secondary">Torna a Libri</a>

        <form method="post" class="ms-auto" onsubmit="return confirm('Sei sicuro di voler eliminare questo libro e tutti i dati associati? Questa azione è irreversibile.');">
            <button type="submit" name="elimina_libro" class="btn btn-danger">Elimina Libro</button>
        </form>
    </div>
</div>

</body>
</html>

<?php
pg_close($db);
?>
