<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login bibliotecario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="login.css" rel="stylesheet">
  </head>
  <body>
    <div class="container-fluid text-center">
        <div class="row header-row">
            <div class="col-12">
                <h1>BiblioTECH</h1>
            </div>
        </div>
    </div>
    <div class="container form-container">
    <form>
      <div class="mb-3">
        <label for="exampleInputEmail1" class="form-label">Email</label>
        <input type="email" class="form-control" id="exampleInputEmail1">
      </div>
      <div class="mb-3">
        <label for="exampleInputPassword1" class="form-label">Password</label>
        <input type="password" class="form-control" id="exampleInputPassword1">
      </div>
      <div class="d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-primary">Login</button>
          <a href="index.php" class="btn btn-link">Login Lettore</a>
        </div>
    </form>
    </div>
  </body>
</html>
