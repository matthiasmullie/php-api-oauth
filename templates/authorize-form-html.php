<?php
// available vars: $error, $email, $nonce
?>
<html>
    <body>
        <form method="POST">
            <?php if ($error): ?><p><?php echo $error; ?></p><?php endif; ?>
            <label for="email">Email</label>
            <input id="email" type="email" name="user" value="<?php echo htmlentities($email); ?>" placeholder="Email address" required>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <input type="submit" value="Authorize">
        </form>
    </body>
</html>
