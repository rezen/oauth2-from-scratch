<?php require 'inc/header.php'; ?>
<?php if (isset($error)): ?>
    <error-with-guide><?php echo $error; ?></error-with-guide>
<?php else: ?>

    <section id="authorize-window">
        Can <strong><?php echo $name; ?></strong>
        access <strong><?php echo $scope; ?></strong>
        <br />
        <br />
    </section>
    <oauth-client-play action="http://localhost:4443/client/cb"><?php echo urldecode($redirect_url); ?></oauth-client-play>

    <form method="post"></form>
<?php endif; ?>
<?php require 'inc/footer.php'; ?>