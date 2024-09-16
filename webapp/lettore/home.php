<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["tipo"] !== "lettore") {
    header("Location: ../index.php");
    exit;
}

include '../connection.php';

$success_message = $error_message = "";

$query = "SELECT * FROM biblioteca_ag.libro";
$result = pg_prepare($db, "home_lettore",$query);
$result = pg_execute($db, "home_lettore",array());
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home Lettore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  </head>
  <body>
    <div class="container mt-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Benvenuto/a, <?php echo htmlspecialchars($_SESSION["email"]); ?></h2>
        <a href="area_personale.php" class="btn btn-secondary"><i class="bi bi-person-circle p-1"></i> Area Personale</a>
      </div>

      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
      <?php elseif (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <table class="table table-striped">
        <thead>
          <tr>
            <th>ISBN</th>
            <th>Titolo</th>
            <th>Trama</th>
            <th>Casa Editrice</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['isbn']); ?></td>
              <td><?php echo htmlspecialchars($row['titolo']); ?></td>
              <td><?php echo htmlspecialchars($row['trama']); ?></td>
              <td><?php echo htmlspecialchars($row['casa_ed']); ?></td>
              <td>
                <a href="prenota_libro.php?book_id=<?php echo urlencode($row['isbn']); ?>" class="btn btn-primary btn-sm">Prendi in prestito</a>
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
