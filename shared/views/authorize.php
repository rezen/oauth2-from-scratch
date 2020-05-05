<?php require 'inc/header.php'; ?>
<h1>Server Authorize</h1>
<p>
    So the server authorize page should check out the <code>client_id</code>
    and verify it matches a registered app. A stricter server could request
    attempts without <code>state</code> and <code>code_challenge</code>
</p>
<p>
    Right now, for example's sake, there is no auth flow for a specific user
    ... the user is just logged in
</p>
<p>
    In the interface, it's a good idea to let the user know, what the client
    is asking for permissions for via <code>scope</code>.
<?php if (isset($error)): ?>
    <error-with-guide><?php echo $error; ?></error-with-guide>
<?php else: ?>
    <h4>Try</h4>
    <p>
        The data below will get sent back to the client. Depending on the 
        original params the client sent, there may be some params not related 
        to the Openidconnect flow. The client will use the <code>code</code> 
        below to acquire an <code>access_token</code>
    </p>
    <section id="authorize-window">
        Can <strong><?php echo $name; ?></strong>
        access <strong><?php echo $scope; ?></strong>
    </section>
    <oauth-client-play action="http://localhost:4443/client/cb"><?php echo urldecode($redirect_url . "&step=1"); ?></oauth-client-play>

    <form method="post"></form>
<?php endif; ?>
<?php require 'inc/footer.php'; ?>