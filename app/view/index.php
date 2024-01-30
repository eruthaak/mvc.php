<?php
  $title = 'Main Page';
  require 'components/header.php';
?>
  <form method="POST">
    <?=csrf()?>
    <?=method('PUT')?>
    <input name="name" type="text">
    <input name="surname" type="text">
    <input name="password" type="password">
    <input name="submit" type="submit" value="1">
  </form>
<?php
  require 'components/footer.php';
?>