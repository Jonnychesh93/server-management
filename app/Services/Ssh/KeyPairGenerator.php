<?php

namespace App\Services\Ssh;

use phpseclib3\Crypt\EC;

class KeyPairGenerator
{
    /**
     * Generate a fresh Ed25519 SSH keypair.
     *
     * @return array{private: string, public: string}
     */
    public static function generateEd25519(): array
    {
        $key = EC::createKey('Ed25519');

        return [
            'private' => $key->toString('OpenSSH'),
            'public' => (string) $key->getPublicKey()->toString('OpenSSH'),
        ];
    }
}
