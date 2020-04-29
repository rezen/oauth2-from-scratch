<?php require 'header.php'; ?>
    <!--
        onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=400'); return false;"
    -->
    <a href="http://localhost:4444/server/oauth/authorize?<?php echo $query; ?>"  >Login</a>
<?php require 'footer.php'; ?>