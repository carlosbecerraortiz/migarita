<?php
session_start();
session_destroy();
header("Location: /mi_garita/index.php?url=login/index");
exit;
?>
