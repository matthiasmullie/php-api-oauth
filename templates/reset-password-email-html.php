<?php
// available vars: $email, $url
?>
<html>
    <body>
        <p>Hello <?php echo $email; ?>,</p>
        <p>Someone has requested a password reset.</p>
        <p>To reset your password, follow this link: <a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
        <p>If you did not make the request, please ignore this email, your password won't be changed.</p>
    </body>
</html>
