<?php

$role = getenv("OAUTH_ROLE");
$color = $role === "server" ? "red" : "blue";
?>
<html>
    <head>
        <title><?php echo strtoupper($role); ?> - <?php echo $title ?? "client"; ?></title>
        <link rel="stylesheet" type="text/css" href="/public/app.css">
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
        <script>const terms=<?php echo json_encode(get_terms()); ?>;</script>
        <script src="/public/app.js"></script>
    </head>
    <body data-role="<?php echo $role; ?>">
        <nav>
        <?php if ($role === "server"): ?>
            <a href="http://localhost:4443/client/start">Start</a>
            <a href="/server/users">Users</a>
            <a href="/server/clients">Clients</a>
        <?php else: ?>
            <a href="http://localhost:4444/server/users">Server</a>
        <?php endif; ?>
        </nav>
        <section id="app">