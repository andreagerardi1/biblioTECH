<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "lettore") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$cf_lettore = $_SESSION["cf"];
$query_lettore = "SELECT * FROM biblioteca_ag.lettore WHERE cf = $1";
$result_lettore = pg_prepare($db, "dati_lettore",$query_lettore);
$result_lettore = pg_execute($db, "dati_lettore", array($cf_lettore));
$lettore = pg_fetch_assoc($result_lettore);

$query_prestiti_attivi = "SELECT * FROM biblioteca_ag.prestito WHERE lettore_cf = $1 AND restituzione IS NULL";
$result_prestiti_attivi = pg_prepare($db, "prestiti_attivi_lettore",$query_prestiti_attivi);
$result_prestiti_attivi = pg_execute($db, "prestiti_attivi_lettore", array($cf_lettore));


$query_prestiti_terminati = "SELECT * FROM biblioteca_ag.prestito WHERE lettore_cf = $1 AND restituzione IS NOT NULL";
$result_prestiti_terminati = pg_prepare($db, "prestiti_terminati_lettore",$query_prestiti_terminati);
$result_prestiti_terminati = pg_execute($db, "prestiti_terminati_lettore", array($cf_lettore));

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
    <title>Area Personale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>

  <body>
    <div class="container mt-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <a href="home.php" class="btn btn-primary me-2">Indietro</a>
          <a href="cambia_password.php" class="btn btn-warning me-2">Cambia Password</a>
        </div>

        <h2>Area Personale di <?php echo htmlspecialchars($lettore['nome'] . ' ' . $lettore['cognome']); ?></h2>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
          <button type="submit" name="logout" class="btn btn-danger">Logout</button>
        </form>
      </div>

      <div class="mb-4">
        <h4>Dettagli del Lettore</h4>
        <ul class="list-group">
          <li class="list-group-item"><strong>CF:</strong> <?php echo htmlspecialchars($lettore['cf']); ?></li>
          <li class="list-group-item"><strong>Nome:</strong> <?php echo htmlspecialchars($lettore['nome']); ?></li>
          <li class="list-group-item"><strong>Cognome:</strong> <?php echo htmlspecialchars($lettore['cognome']); ?></li>
          <li class="list-group-item"><strong>Volumi in Ritardo:</strong> <?php echo htmlspecialchars($lettore['volumi_in_ritardo']); ?></li>
          <li class="list-group-item"><strong>Categoria:</strong> <?php echo htmlspecialchars($lettore['categoria']); ?></li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-6">
          <h4>Prestiti in Corso</h4>
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID Prestito</th>
                <th>ISBN Libro</th>
                <th>Data Inizio</th>
                <th>Data Scadenza</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = pg_fetch_assoc($result_prestiti_attivi)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['id']); ?></td>
                  <td><?php echo htmlspecialchars($row['libro_isbn']); ?></td>
                  <td><?php echo htmlspecialchars($row['data_inizio']); ?></td>
                  <td><?php echo htmlspecialchars($row['fine_concessione']); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="col-md-6">
          <h4>Prestiti Terminati</h4>
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID Prestito</th>
                <th>ISBN Libro</th>
                <th>Data Inizio</th>
                <th>Data Scadenza</th>
                <th>Data Restituzione</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = pg_fetch_assoc($result_prestiti_terminati)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['id']); ?></td>
                  <td><?php echo htmlspecialchars($row['libro_isbn']); ?></td>
                  <td><?php echo htmlspecialchars($row['data_inizio']); ?></td>
                  <td><?php echo htmlspecialchars($row['fine_concessione']); ?></td>
                  <td><?php echo htmlspecialchars($row['restituzione']); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </body>
</html>

<?php
pg_close($db);
?>
