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
  <script>
    window.onload = () => {
      // const conn = new WebSocket('ws://localhost:9000');

      // conn.onopen = (event) => {
      //   console.log('WebSocket connection opened:', event);
      // };

      // conn.onmessage = (event) => {
      //   console.log(event.data);
      // };
    }
  </script>
<?php
  require 'components/footer.php';
?>