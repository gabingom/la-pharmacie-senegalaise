<?php
// Deconnexion : detruit la session et renvoie au login
session_start();
session_unset();
session_destroy();
header('Location: ../index.php');
exit;
