<?php


namespace MadWizard\WebAuthn\Server\Registration;

use MadWizard\WebAuthn\Dom\AttestationConveyancePreference;
use MadWizard\WebAuthn\Exception\ConfigurationException;

class RegistrationOptions
{
    /**
     * @var string|null
     */
    private $attestation;

    public function __construct()
    {
    }

    /**
     * @return null|string
     */
    public function getAttestation(): ?string
    {
        return $this->attestation;
    }

    /**
     * @param null|string $attestation
     */
    public function setAttestation(?string $attestation): void
    {
        if ($attestation !== null && !AttestationConveyancePreference::isValidValue($attestation)) {
            throw new ConfigurationException(sprintf('Value "%s" is not a valid attestation conveyance preference.', $attestation));
        }
        $this->attestation = $attestation;
    }
}
