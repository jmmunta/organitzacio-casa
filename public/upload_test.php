<?php session_start(); if(empty($_SESSION['user'])){header('Location: login.php');exit;} ?>
<!doctype html><meta charset="utf-8"><title>Test upload</title>
<h3>Test pujada foto (sense JS)</h3>
<form method="post" action="api.php?action=upload_member_photo" enctype="multipart/form-data">
  <label>Membre ID <input name="member_id" required></label><br>
  <input type="file" name="photo" accept="image/*" required>
  <button>Enviar</button>
</form>
