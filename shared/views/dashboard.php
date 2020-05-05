<?php require 'inc/header.php'; ?>

<a href="/client/logout">Logout</a>
<pre><?php print_r($id_data); ?></pre>

<strong><?php echo $token_url; ?></strong>
<h4>Client Request</h4>
<params-formatted><?php echo urldecode($client_post); ?></params-formatted>

<h4>Server Response</h4>
<params-formatted fmt="json"><?php echo json_encode($server_response, JSON_PRETTY_PRINT); ?></params-formatted>

<?php require 'inc/footer.php'; ?>