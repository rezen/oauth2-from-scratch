<?php require 'inc/header.php'; ?>
<p class="error">
    <?php echo htmlentities($error); ?>
</p>
<form method="post" class="enclose"> 
    <h3>Add User</h3>
    <input type="email" name="email" placeholder="user@email.com" required value="" />
    <input type="text" name="password" placeholder="NotPasword123" required value="" />
    <button type="submit">Add</button>
</form>

<?php partial("table", [
    'attrs' => ['id', 'email', 'password_unsafe_never_do'],
    'rows'  => $users,
]);
?>
<?php require 'inc/footer.php'; ?>