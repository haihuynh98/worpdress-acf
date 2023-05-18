<?php

define('LF_HASH_CIPHERING', 'AES-256-CTR');

class LF_Hash
{
    private $algo = LF_HASH_CIPHERING;
    private $key = '';
    private $iv_length = '';
    private $encryption_iv_value = '';
    private $options = 0;

    public function __construct($key, $ciphering = null)
    {
        if ($ciphering != null) {
            $this->algo = $ciphering;
        }
        $this->key = $key;

        $this->iv_length = openssl_cipher_iv_length($this->algo);
        $this->set_encryption_iv_value();
    }

    public function encrypt($text)
    {
        return openssl_encrypt($text, $this->algo, $this->key, $this->options, $this->encryption_iv_value);
    }

    public function decrypt($hash)
    {
        return openssl_decrypt($hash, $this->algo, $this->key, $this->options, $this->encryption_iv_value);
    }

    private function set_encryption_iv_value()
    {
        $this->encryption_iv_value = substr(AUTH_SALT, 0, $this->iv_length);
    }
}