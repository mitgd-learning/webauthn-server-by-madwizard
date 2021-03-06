<?php

namespace MadWizard\WebAuthn\Server\Registration;

use MadWizard\WebAuthn\Credential\UserHandle;
use MadWizard\WebAuthn\Format\ByteBuffer;
use MadWizard\WebAuthn\Server\AbstractContext;
use MadWizard\WebAuthn\Web\Origin;
use Serializable;

final class RegistrationContext extends AbstractContext implements Serializable
{
    /**
     * @var UserHandle
     */
    private $userHandle;

    public function __construct(ByteBuffer $challenge, Origin $origin, string $rpId, UserHandle $userHandle)
    {
        parent::__construct($challenge, $origin, $rpId);
        $this->userHandle = $userHandle;
    }

    public function getUserHandle(): UserHandle
    {
        return $this->userHandle;
    }

    public function __serialize(): array
    {
        return [
            'parent' => parent::__serialize(),
            'userHandle' => $this->userHandle,
        ];
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data['parent']);
        $this->userHandle = $data['userHandle'];
    }
}
