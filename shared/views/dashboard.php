<?php require 'inc/header.php'; ?>

<?php if (!is_null($error)): ?>
    <?php echo $error; ?>
<?php else: ?>
    Is verified!
<?php endif; ?>
<a href="/client/logout">Logout</a>

<h4>Codes</h4>
<?php partial("table", [
    'attrs' => [
        'client_id', 'user_id', 'code',
        'scope', 'code_challenge', 'expiration', 'used_at'
    ],
    'rows'  => $codes,
]);
?>

<?php echo defaultHash($access_token); ?>
<h4>Tokens</h4>
<?php partial("table", [
    'attrs' => ['client_id', 'user_id', 'code_id', 'scope', 'token_hash', 'expiration'],
    'rows'  => $tokens,
]);
?>
<strong><?php echo $token_url; ?></strong>
<h4>Client Request</h4>
<params-formatted><?php echo urldecode($client_post); ?></params-formatted>

<h4>Server Response</h4>
<params-formatted fmt="json"><?php echo json_encode($server_response, JSON_PRETTY_PRINT); ?></params-formatted>

<h4>JWT</h4>
<strong>header</strong><br />
<pre><?php echo json_encode($jwt_header, JSON_PRETTY_PRINT); ?></pre>

<strong>payload</strong><br />
<pre><?php echo json_encode($jwt_payload, JSON_PRETTY_PRINT); ?></pre>
<small>
    <a href="https://www.iana.org/assignments/jwt/jwt.xhtml#claims">Spec</a>
    <a href="https://jwt.io/">JWT Debugger</a>
</small>
<?php require 'inc/footer.php'; ?>