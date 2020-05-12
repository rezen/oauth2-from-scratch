<?php require 'inc/header.php'; ?>
<?php echo $error; ?>

<form method="post" class="enclose"> 
    <input type="email" name="email" placeholder="user@email.com" required value="" />
    <input type="password" name="password" placeholder="NotPasword123" required value="" />
    <button type="submit">Login</button>
</form>
<?php require 'inc/footer.php'; ?>