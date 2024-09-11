<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
        // Avvia la sessione
        session_start();

        // Controlla se l'utente è loggato, altrimenti reindirizza alla pagina di login
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["tipo"] !== "bibliotecario") {
            header("Location: ../index.php");
            exit;
        }

        // Stampa il messaggio di benvenuto
        echo "<h1>Benvenuto bibliotecario</h1>";
        echo "Benvenuto, " . htmlspecialchars($_SESSION["email"]) . "!";
    ?>
</body>
</html>
