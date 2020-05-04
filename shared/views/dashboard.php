<?php require 'inc/header.php'; ?>

<a href="/client/logout">Logout</a>
<pre><?php print_r($id_data); ?></pre>

<strong><?php echo $token_url; ?></strong>
<h4>Client Request</h4>
<todo-item><?php echo urldecode($client_post); ?></todo-item>

<h4>Server Response</h4>
<todo-item fmt="json"><?php echo json_encode($server_response, JSON_PRETTY_PRINT); ?></todo-item>

<?php require 'inc/footer.php'; ?>