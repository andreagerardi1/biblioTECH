<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "lettore") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$success_message = $error_message = "";

$book_isbn = isset($_GET['book_id']) ? $_GET['book_id'] : '';

$query = "SELECT * FROM biblioteca_ag.libro WHERE isbn = $1";
$result = pg_prepare($db, "info_libro",$query);
$result = pg_execute($db, "info_libro", array($book_isbn));
$book = pg_fetch_assoc($result);

$query_autori = "SELECT a.nome, a.cognome 
                 FROM biblioteca_ag.autore AS a
                 JOIN biblioteca_ag.scrive AS s ON a.id = s.autore_id
                 WHERE s.libro_isbn = $1";
$result_autori = pg_prepare($db, "autori_da_libro",$query_autori);
$result_autori = pg_execute($db, "autori_da_libro", array($book_isbn));

$autori = [];
while ($row_autore = pg_fetch_assoc($result_autori)) {
    $autori[] = htmlspecialchars($row_autore['nome']) . " " . htmlspecialchars($row_autore['cognome']);
}
$autori_string = implode(", ", $autori);

$query_sedi = "SELECT sede.cod, sede.città, sede.indirizzo, 
               COUNT(copia.codice) AS disponibilità
               FROM biblioteca_ag.sede AS sede
               LEFT JOIN biblioteca_ag.copia AS copia ON sede.cod = copia.sede_cod 
               AND copia.libro_isbn = $1 AND copia.stato = 'disponibile'
               GROUP BY sede.cod, sede.città, sede.indirizzo";
$result_sedi = pg_prepare($db, "sedi_per_libro",$query_sedi);
$result_sedi = pg_execute($db, "sedi_per_libro", array($book_isbn));

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $sede = isset($_POST['sede']) && $_POST['sede'] !== '' ? $_POST['sede'] : null; // se la sede non è selezionata il default è null

    if ($sede !== null) {
        $query_prestito = "CALL biblioteca_ag.richiedi_prestito_isbn($1, $2, $3)";
        $params = array($book_isbn, $_SESSION["cf"], $sede);
    } else {
        $query_prestito = "CALL biblioteca_ag.richiedi_prestito_isbn($1, $2, NULL)";
        $params = array($book_isbn, $_SESSION["cf"]);
    }

    $result_prestito = pg_prepare($db, "query_prestito",$query_prestito);
    $result_prestito = pg_execute($db, "query_prestito", $params);

    if ($result_prestito) {
        $success_message = "Prestito effettuato con successo!";
    } else {
        $error_message = "Errore durante il prestito del libro. Per favore, riprova.";
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prenota Libro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="container mt-5">
      <h2 class="mb-4 text-center">Prenota il Libro</h2>

      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success mb-4"><?php echo $success_message; ?></div>
      <?php elseif (!empty($error_message)): ?>
        <div class="alert alert-danger mb-4"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <?php if ($book): ?>
        <div class="card mb-4">
          <div class="card-header">
            <h3><?php echo htmlspecialchars($book['titolo']); ?></h3>
          </div>
          <div class="card-body">
            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
            <p><strong>Trama:</strong> <?php echo htmlspecialchars($book['trama']); ?></p>
            <p><strong>Casa Editrice:</strong> <?php echo htmlspecialchars($book['casa_ed']); ?></p>
            <p><strong>Autori:</strong> <?php echo $autori_string; ?></p>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h4>Seleziona la Sede della Biblioteca</h4>
          </div>
          <div class="card-body">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?book_id=" . urlencode($book_isbn); ?>">
              <div class="mb-3">
                <label for="sede" class="form-label">Sede</label>
                <select id="sede" name="sede" class="form-select">
                  <option value="" selected disabled>Seleziona una sede</option>
                  <?php while ($row_sede = pg_fetch_assoc($result_sedi)): ?>
                    <option value="<?php echo htmlspecialchars($row_sede['cod']); ?>">
                      <?php echo htmlspecialchars($row_sede['città']) . " - " . htmlspecialchars($row_sede['indirizzo']); ?>
                      (Disponibilità: <?php echo htmlspecialchars($row_sede['disponibilità']); ?>)
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="d-flex justify-content-between">
                <button type="submit" name="submit" class="btn btn-primary">Prenota</button>
                <a href="home.php" class="btn btn-secondary">Torna alla Home</a>
              </div>
            </form>
          </div>
        </div>

      <?php else: ?>
        <p class="text-center">Libro non trovato.</p>
      <?php endif; ?>
    </div>

  </body>
</html>

<?php
pg_close($db);
?>
