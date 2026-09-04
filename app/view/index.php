<?php
  $title = 'Main Page';
  require 'components/header.php';
?>
  This is a basic php application which belongs to MVC architecture.
  <form method="POST">
    <?=csrf()?>
    <?=method('POST')?>
    <input name="submit" type="submit" value="Send">
  </form>
<?php
  require 'components/footer.php';
?>