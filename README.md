# oauth2-from-scratch
The best way to learn about a OAuth2 is to implement it ... from scratch! Right now I'm working through `grant_type=authorization_code` and next I'll try out implementing the flow for SPAs.

## Todo
- Actually implement authn for server

## Notes
`refresh_token` - only used for confidential clients (read server side)

## Links
- https://www.pingidentity.com/en/company/blog/posts/2019/jwt-security-nobody-talks-about.html
- https://developer.okta.com/blog/2019/02/04/create-and-verify-jwts-in-php
- https://hasura.io/blog/best-practices-of-using-jwt-with-graphql/
- https://www.stefaanlippens.net/oauth-code-flow-pkce.html
- https://www.oauth.com/oauth2-servers/server-side-apps/possible-errors/


### Samples
Google Auth2
**Request**
```
GET https://accounts.google.com/signin/oauth?response_type=code&redirect_uri=https://samples.auth0.com/login/callback&scope=email+profile&state=a5k7sn9vtCrbgw_GytF4Y5g5NoWwA&client_id=969181497182-f7em2i6hlg66sq2hnm8f77r58pm02km9.apps.googleusercontent.com&o2v=1&as=5AwY6s38-uPbtTTCr8f6lw HTTP/1.1
Connection: keep-alive
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9
Sec-Fetch-Site: cross-site
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Host: accounts.google.com
```

**Response**
```
HTTP/1.1 302 Moved Temporarily
Content-Type: text/html; charset=UTF-8
X-Frame-Options: DENY
Cache-Control: no-cache, no-store, max-age=0, must-revalidate
Pragma: no-cache
Expires: Mon, 01 Jan 1990 00:00:00 GMT
Date: Wed, 29 Apr 2020 22:30:59 GMT
Location: https://samples.auth0.com/login/callback?state=a5k7svtCrbgw_GytF4Y5g5xoWwA&code=4%2FzQFvx_8CB0quwsC_-ZD58k8P-KXC-VwVQI7ctOgNGyMw6Go1jdp7p-kub0Doc37XWsVKi4hTmHfV4l6s&scope=email+profile+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile+openid&authuser=0&prompt=none
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: script-src 'report-sample' 'nonce-mudKfF+WTr2D8Cy6HoeNag' 'unsafe-inline' 'unsafe-eval';object-src 'none';base-uri 'self';report-uri /cspreport
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Server: GSE


<HTML>
<HEAD>
<TITLE>Moved Temporarily</TITLE>
</HEAD>
<BODY BGCOLOR="#FFFFFF" TEXT="#000000">
<H1>Moved Temporarily</H1>
The document has moved <A HREF="https://samples.auth0.com/login/callback?state=a5k8vtCrbgw_GytF4Y5g5NoWwA&amp;code=4%2FzQFvx_8CB0quwsX_-ZD8P-KXC-VwVQI7cshG7tOgNGw6Go1jdp7p-kub0Doc37XWsVKi4hTmHfV4l6s&amp;scope=email+profile+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile+openid&amp;authuser=0&amp;prompt=none">here</A>.
</BODY>
</HTML>
```