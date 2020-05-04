<?php require 'inc/header.php'; ?>
    <!--
        onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=400'); return false;"
    -->
    <oauth-client-play method="GET" action="http://localhost:4444/server/oauth/authorize"><?php echo urldecode($query); ?></oauth-client-play>
<?php require 'inc/footer.php'; ?>