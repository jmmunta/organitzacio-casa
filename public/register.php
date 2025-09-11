<?php /* register.php */ ?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registre · Organització Casa</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" />
</head>
<body class="container">
  <main style="max-width:560px;margin:auto">
    <h1>Crea una família</h1>
    <p class="muted">Registra't amb el teu correu. Seràs l'<strong>administrador</strong> d'aquesta família.</p>

    <form method="post" action="api.php" onsubmit="this.querySelector('[name=action]').value='register'">
      <input type="hidden" name="action" value="register" />
      <label>Nom de la família
        <input name="family_name" required />
      </label>
      <label>Email
        <input name="email" type="email" required />
      </label>
      <label>Contrasenya
        <input name="password" type="password" minlength="6" required />
      </label>
      <button type="submit">Crear família i entrar</button>
    </form>
    <p>Ja tens compte? <a href="login.php">Inicia sessió</a></p>
  </main>
</body>
</html>
