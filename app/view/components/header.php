<!DOCTYPE html>
<html lang="TR">
  <head>
    <!-- <meta> -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?="This is a project."?>">
    <meta name="author" content="<?=APP_CREATOR?>">
    <meta name="csrfToken" content="<?=$csrfToken?>">
    <!-- <meta /> -->
    <title>
      <?=isset($title) ? APP_NAME . " | " . $title : APP_NAME;?>
    </title>
    <!-- Favicon -->
    <link rel="icon" href="<?=APP_URL?>/favicon.ico" type="image/x-icon">
  </head>
  <body>