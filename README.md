# oauth2-from-scratch
The best way to learn about a OpenConnectID (OAuth2+OpenConnect) is to implement it ... from scratch! Right now I'm working through `grant_type=authorization_code` and next I'll try out implementing the flow for SPAs.

## Run
```sh
cd ./oauth2-from-scratch
touch shared/log.txt && chmod 0777 # ... Docker permissions 
docker-compose up -d
open http://localhost:4443/
```

## Todo
- Actually implement authn for server
- Implement grant_types for SPAs
- Implement API for client to make requests against
  - Add scope checking
- Have a separate resource server example

## Notes
`refresh_token` - only used for confidential clients (read server side)
- Don't use password or implicit flows
- Refresh token
  - Sender constrained or one time use
- Don't do tokens in query strings
- Request `offline_access` to indicate you want `refresh_token`
- If the oauth server is also the resource server `HS256` is probably fine
- Use `RSA256` if the resource server is separate, then a resource server can use a public key to verify the JWT claims from a client.
  - `kid` allows for key rotation

- `jti` replay otectiiom pr
- Token types
  - self contained token
  - Reference token
    - API sends to STS and get info
- in API enforce audience (`aud`)
- https://github.com/phpseclib/phpseclib
- 
```sh
# openssl genrsa -passout pass:_passphrase_ -out private.key 4096
openssl genrsa -out private.key 4096
openssl rsa -in private.key -pubout -out public.key
```


```sh
docker-compose exec database mysql -psecret -e "SELECT * FROM access_tokens;" oauth2
```
https://contoso.auth0.com/.well-known/openid-configuration
https://accounts.google.com/.well-known/openid-configuration

## Links
- https://auth0.com/docs/quickstart/backend/python/01-authorization#protect-api-endpoints
- https://renzo.lucioni.xyz/verifying-jwts-with-jwks-and-pyjwt/
- https://redthunder.blog/2017/06/08/jwts-jwks-kids-x5ts-oh-my/
- https://zapier.com/engineering/apikey-oauth-jwt/
- https://contoso.auth0.com/.well-known/openid-configuration
- https://github.com/ory/hydra
- https://github.com/doorkeeper-gem/doorkeeper
- https://developer.okta.com/docs/reference/api/oidc/
- https://stackoverflow.com/questions/46844285/difference-between-oauth-2-0-state-and-openid-nonce-parameter-why-state-cou]
- https://www.oauth.com/playground/
- https://www.pingidentity.com/en/company/blog/posts/2019/jwt-security-nobody-talks-about.html
- https://developer.okta.com/blog/2019/02/04/create-and-verify-jwts-in-php
- https://hasura.io/blog/best-practices-of-using-jwt-with-graphql/
- https://www.stefaanlippens.net/oauth-code-flow-pkce.html
- https://www.oauth.com/oauth2-servers/server-side-apps/possible-errors/
- https://alexbilbie.com/guide-to-oauth-2-grants/
- https://www.thoughtworks.com/insights/blog/bff-soundcloud


### Samples
Here are links to docs of some specific providers
- https://developer.github.com/v3/guides/basics-of-authentication/
- https://developers.google.com/identity/protocols/oauth2/openid-connect
- https://docs.microsoft.com/en-us/azure/active-directory-b2c/openid-connect

#### Google Auth2  
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

#### Juiceshop - Google Auth2  

**Request**  
```
GET https://accounts.google.com/signin/oauth/identifier?
client_id=1005568560502-6hm16lef8oh46hr2d98vf2ohlnj4nfhq.apps.googleusercontent.com
&response_type=token
&scope=email
&redirect_uri=http%3A%2F%2Flocal3000.owasp-juice.shop&o2v=2&as=DMpbZRth71OQ6gB4WPj8nA&flowName=GeneralOAuthFlow  HTTP/1.1
```

**Redirect**
```
HTTP/1.1 302 Moved Temporarily
Content-Type: text/html; charset=UTF-8
X-Frame-Options: DENY
Cache-Control: no-cache, no-store, max-age=0, must-revalidate
Pragma: no-cache
Expires: Mon, 01 Jan 1990 00:00:00 GMT
Date: Tue, 05 May 2020 16:55:43 GMT
Location: http://local3000.owasp-juice.shop#access_token=ya29.a0Ae4lvC1HKQLnR-Km5D_9dswFE9_2QWUL5bIz0vtlkuvn7IfZ9rTHPrmJ40dFhb3xWhwV2YqFVKzWOLhhjzkVpra5MXh2V70BtHkNSB6_ly70MMT526tK0ab4bkyiu5DrXz6ZzXE9NDaWP43kbrSv0t4T9xQD1vZkAt-s&token_type=Bearer&expires_in=3599&scope=email%20openid%20https://www.googleapis.com/auth/userinfo.email&authuser=0&prompt=none
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: script-src 'report-sample' 'nonce-Xr/PrDVJocVm1wZDjFYpZQ' 'unsafe-inline' 'unsafe-eval';object-src 'none';base-uri 'self';report-uri /cspreport
Content-Length: 547
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Server: GSE
Set-Cookie: SIDCC=AJi4QfGbo05FjMbZmp8QEKPmwXRrKDGnk0tMvuSQ22BL2ZOJ40rCI82PJszKhuCN-IOL1mSdNQ; expires=Wed, 05-May-2021 16:55:43 GMT; path=/; domain=.google.com; priority=high
Alt-Svc: h3-Q050=":443"; ma=2592000,h3-Q049=":443"; ma=2592000,h3-Q048=":443"; ma=2592000,h3-Q046=":443"; ma=2592000,h3-Q043=":443"; ma=2592000,quic=":443"; ma=2592000; v="46,43"

<HTML>
<HEAD>
<TITLE>Moved Temporarily</TITLE>
</HEAD>
<BODY BGCOLOR="#FFFFFF" TEXT="#000000">
<H1>Moved Temporarily</H1>
The document has moved <A HREF="http://local3000.owasp-juice.shop#access_token=ya29.a0Ae4lvC1HKQLnR-Km5D_9dswFE9_2QWUL5bIz0vtlkuvn7IfZ9rTHPrmJ40dFhb3xWhwV2YqFVKzWOLhhjzkVpra5MXh2V70BtHkNSB6_ly70MMT526tK0ab4bkyiu5DrXz6ZzXE9NDaWP43kbrSv0t4T9xQD1vZkAt-s&amp;token_type=Bearer&amp;expires_in=3599&amp;scope=email%20openid%20https://www.googleapis.com/auth/userinfo.email&amp;authuser=0&amp;prompt=none">here</A>.
</BODY>
</HTML>
```

**Token Usage**  
```
GET https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=ya29.a0Ae4lvC1HKQLnR-Km5D_9dswFE9_2QWUL5bIz0vtlkuvn7IfZ9rTHPrmJ40dFhb3xWhwV2YqFVKzWOLhhj7DVpra5MXh2V70BtHkNSB6_ly70MMT526tK0ab4bkyiu5DrXz6ZzXE9NDaWP43kbrSv0t4T9xQD1v7DAt-s HTTP/1.1
Connection: keep-alive
Accept: application/json, text/plain, */*
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36
```

```
GET http://localhost:3000/rest/user/whoami HTTP/1.1
Proxy-Connection: keep-alive
Accept: application/json, text/plain, */*
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdGF0dXMiOiJzdWNjZXNzIiwiZGF0YSI6eyJpZCI6MjAsInVzZXJuYW1lIjoiIiwiZW1haWwiOiJ1c2VyQHRlc3QuY29tIiwicGFzc3dvcmQiOiIwNWE2NzFjNjZhZWZlYTEyNGNjMDhiNzZlYTZkMzBiYiIsInJvbGUiOiJjdXN0b21lciIsImRlbHV4ZVRva2VuIjoiIiwibGFzdExvZ2luSXAiOiIwLjAuMC4wIiwicHJvZmlsZUltYWdlIjoiL2Fzc2V0cy9wdWJsaWMvaW1hZ2VzL3VwbG9hZHMvZGVmYXVsdC5zdmciLCJ0b3RwU2VjcmV0IjoiIiwiaXNBY3RpdmUiOnRydWUsImNyZWF0ZWRBdCI6IjIwMjAtMDUtMDcgMTc6NDc6NTYuNjkwICswMDowMCIsInVwZGF0ZWRBdCI6IjIwMjAtMDUtMDcgMTc6NDc6NTYuNjkwICswMDowMCIsImRlbGV0ZWRBdCI6bnVsbH0sImlhdCI6MTU4ODg3MzY4MywiZXhwIjoxNTg4ODkxNjgzfQ.qWdnv02ktYH6BQE6jqBN-LHzFIUBJ8LBLjeLSmBqAZC1yUGBP7NMPKmjKRodID8dAgtBC9KKAXR-_Tt49I99m2SwMUZDRWMxhyCnq7EEK8p9fmSFFFmURbKUo6i97N7JiUczL_vG6ooma5Y1_vqI1c36XiO_dadXHxbNBwqa-pg
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36
Sec-Fetch-Site: same-origin
Sec-Fetch-Mode: cors
Sec-Fetch-Dest: empty
Referer: http://localhost:3000/
Accept-Language: en-US,en;q=0.9
Cookie: io=pudwJkOMmQzLRRthAAAH; language=en; token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdGF0dXMiOiJzdWNjZXNzIiwiZGF0YSI6eyJpZCI6MjAsInVzZXJuYW1lIjoiIiwiZW1haWwiOiJ1c2VyQHRlc3QuY29tIiwicGFzc3dvcmQiOiIwNWE2NzFjNjZhZWZlYTEyNGNjMDhiNzZlYTZkMzBiYiIsInJvbGUiOiJjdXN0b21lciIsImRlbHV4ZVRva2VuIjoiIiwibGFzdExvZ2luSXAiOiIwLjAuMC4wIiwicHJvZmlsZUltYWdlIjoiL2Fzc2V0cy9wdWJsaWMvaW1hZ2VzL3VwbG9hZHMvZGVmYXVsdC5zdmciLCJ0b3RwU2VjcmV0IjoiIiwiaXNBY3RpdmUiOnRydWUsImNyZWF0ZWRBdCI6IjIwMjAtMDUtMDcgMTc6NDc6NTYuNjkwICswMDowMCIsInVwZGF0ZWRBdCI6IjIwMjAtMDUtMDcgMTc6NDc6NTYuNjkwICswMDowMCIsImRlbGV0ZWRBdCI6bnVsbH0sImlhdCI6MTU4ODg3MzY4MywiZXhwIjoxNTg4ODkxNjgzfQ.qWdnv02ktYH6BQE6jqBN-LHzFIUBJ8LBLjeLSmBqAZC1yUGBP7NMPKmjKRodID8dAgtBC9KKAXR-_Tt49I99m2SwMUZDRWMxhyCnq7EEK8p9fmSFFFmURbKUo6i97N7JiUczL_vG6ooma5Y1_vqI1c36XiO_dadXHxbNBwqa-pg
If-None-Match: W/"b-/5bSboVjVhGw3qRgvUfZjE1r1Ns"
Host: localhost:3000

```