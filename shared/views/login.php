<?php require 'inc/header.php'; ?>
    <!--
        onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=400'); return false;"
    -->
    <h1>Login</h1>
    <p>
        When you start the flow, you start on a client app which will consume data 
        from another app. Typically there will be some sort of login button or link to 
        take you to the Openid provider (Google/Facebook/Github) etc where you can login.
    </p>
    <h4>Github</h4>
    <p>
        The site Codepen, has a "login with Github" at <a href="//codepen.io/auth/github">codepen.io/auth/github</a>. 
        That url will redirect you with a response that looks like the one below, where you can login to Github
    </p>
<pre>
HTTP/1.1 302 Found
Date: Mon, 04 May 2020 23:20:32 GMT
Content-Length: 254
Connection: keep-alive
Status: 302 Found
Cache-Control: no-cache
X-Request-Id: fe128a77-79f6-41a4-abc0-68ca4239e6f4
Location: https://github.com/login/oauth/authorize?client_id=1d46d447dfcd2ccd7b18&redirect_uri=https%3A%2F%2Fcodepen.io%2Fauth%2Fgithub%2Fcallback&response_type=code&scope=user%3Aemail%2Cgist&state=dab7e5198c4ad9f322b2b8ffde259316c61a2648e5bc35ae
</pre>
    
    <h4>Try</h4>
    <p>
        With that intro, let's take a look at the request below. 
        We'll be sending this information on to the server to start the process.
    </p>
    <oauth-client-play method="GET" action="http://localhost:4444/server/oauth/authorize"><?php echo urldecode($query); ?></oauth-client-play>
<?php require 'inc/footer.php'; ?>