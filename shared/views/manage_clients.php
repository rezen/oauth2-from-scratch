<?php require 'inc/header.php'; ?>
<p class="error">
    <?php echo htmlentities($error); ?>
</p>
<?php if ($client): ?>
    <div class="message">Added client</div>
    client_id=<?php echo $client->client_id; ?>
    client_secret=<?php echo $client->client_secret; ?>
<?php endif; ?>
<form method="post" class="enclose">
    <h3>Add Client</h3>
    <input type="text" name="name" placeholder="Name ..." required value="Test" />
    <input type="url" name="redirect_uri" placeholder="https://app.dev/oauth/callback" value="http://localhost:4443/client/callback" required />
    <button type="submit">Add</button>
</form>

<?php partial("table", [
    'attrs' => ['name', 'client_id', 'client_secret', 'redirect_uri'],
    'rows'  => $clients,
]);
?>
<?php require 'inc/footer.php'; ?>