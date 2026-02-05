<?php
session_start();
session_unset();
session_destroy();

header("Location: ../pages/system_login_portal.html");
exit;
