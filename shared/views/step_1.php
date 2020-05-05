<?php require 'inc/header.php'; ?>
<h1>Step 1</h1>
<p>
    At this stage, the client has received the <code>code</code> from the server
    and the matching <code>state</code> that it sent to the server. The client
    is going to post the <code>code</code> as well as the original <code>code_verifier</code>
    used to create the <code>code_challenge</code> sent in the first request.
</p>
<pre><?php print_r($id_data); ?></pre>

<strong><?php echo $token_url; ?></strong>
<h4>Client Request</h4>
<params-formatted><?php echo urldecode($client_post); ?></params-formatted>

<?php require 'inc/footer.php'; ?>