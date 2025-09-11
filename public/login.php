<?php /* login.php */ session_start(); ?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login · Organització Casa</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" />
</head>
<body class="container">
  <main style="max-width:560px;margin:auto">
    <h1>Inicia sessió</h1>
    <?php if(!empty($_GET['m'])): ?><p class="muted"><?=htmlspecialchars($_GET['m'])?></p><?php endif; ?>
    <form method="post" action="api.php" onsubmit="this.querySelector('[name=action]').value='login'">
      <input type="hidden" name="action" value="login" />
      <label>Email
        <input name="email" type="email" required />
      </label>
      <label>Contrasenya
        <input name="password" type="password" required />
      </label>
      <button type="submit">Entrar</button>
    </form>
    <p>Encara no tens compte? <a href="register.php">Registra't</a></p>
  </main>
</body>
</html>
