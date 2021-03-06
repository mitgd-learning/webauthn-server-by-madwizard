<?php

namespace MadWizard\WebAuthn\Conformance;

use MadWizard\WebAuthn\Credential\CredentialId;
use MadWizard\WebAuthn\Credential\CredentialRegistration;
use MadWizard\WebAuthn\Credential\CredentialStoreInterface;
use MadWizard\WebAuthn\Credential\UserCredential;
use MadWizard\WebAuthn\Credential\UserCredentialInterface;
use MadWizard\WebAuthn\Credential\UserHandle;
use function array_filter;
use function array_map;

/**
 * Test credential store for conformance test
 * NOTE: this class is a simple implementation for running the FIDO conformance tests only.
 */
class TestCredentialStore implements CredentialStoreInterface
{
    public function findCredential(CredentialId $credentialId): ?UserCredentialInterface
    {
        return $_SESSION['credentials'][$credentialId->toString()]['credential'] ?? null;
    }

    public function registerCredential(CredentialRegistration $credential): void
    {
        $_SESSION['credentials'][$credential->getCredentialId()->toString()] =
            [
                'credential' => new UserCredential($credential->getCredentialId(), $credential->getPublicKey(), $credential->getUserHandle()),
                'counter' => $credential->getSignCounter(),
            ];
    }

    public function getSignatureCounter(CredentialId $credentialId): ?int
    {
        return $_SESSION['credentials'][$credentialId->toString()]['counter'] ?? null;
    }

    public function updateSignatureCounter(CredentialId $credentialId, int $counter): void
    {
        $_SESSION['credentials'][$credentialId->toString()]['counter'] = $counter;
    }

    public function getUserCredentialIds(UserHandle $userHandle): array
    {
        return array_map(function ($x) {
            return $x['credential']->getCredentialId();
        }, array_filter($_SESSION['credentials'] ?? [], function ($cred) use ($userHandle) {
            return $userHandle->equals($cred['credential']->getUserHandle());
        }));
    }
}
