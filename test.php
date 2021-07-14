<?php

$data = "yxz";
$pkeyid = openssl_pkey_get_private("file://private.key");

echo OPENSSL_ALGO_SHA256 . "\n";
openssl_sign($data, $signature, $pkeyid, "sha256WithRSAEncryption");

// free the key from memory
openssl_free_key($pkeyid);

echo base64_encode($signature) . "\n";
echo "\n";

echo openssl_verify($data, $signature, file_get_contents("public.key"), OPENSSL_ALGO_SHA256);
echo "\n";
