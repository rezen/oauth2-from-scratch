<?php require 'header.php'; ?>
<?php if (isset($error)): ?>
    <?php echo $error; ?>
<?php else: ?>

    Can <strong><?php echo $name; ?></strong>
    access <strong><?php echo $scope; ?></strong>
    <a href="<?php echo $redirect_url; ?>">Allow</a>
    <form method="post"></form>
<?php endif; ?>
<?php require 'footer.php'; ?>