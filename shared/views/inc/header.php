<?php

$role = getenv("OAUTH_ROLE");
$color = $role === "server" ? "red" : "blue";
?>
<html>
    <head>
        <title><?php echo $title ?? "client"; ?></title>
        <link rel="stylesheet" type="text/css" href="/public/app.css">
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
        <script>const terms=<?php echo json_encode(get_terms()); ?>;</script>
        <script src="/public/app.js"></script>
    </head>
    <body data-role="<?php echo $role; ?>">
        <section id="app">