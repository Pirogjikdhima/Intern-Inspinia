<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/iCheck/1.0.3/skins/square/green.css" rel="stylesheet">
  <link href="css/bootstrap.min.css" rel="stylesheet">
    <?php echo '<title>' . (htmlspecialchars($title) ?? "Title") . '</title>'; ?>
    <link href="font-awesome/css/fontawesome-all.css" rel="stylesheet">
  <link href="css/animate.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="css/plugins/toastr/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <?php echo $styles ?? "" ?>
</head>

