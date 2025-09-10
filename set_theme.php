<?php
if (isset($_POST['theme'])) {
    $_SESSION['theme'] = $_POST['theme'] === 'dark' ? 'dark' : 'light';
}
?>