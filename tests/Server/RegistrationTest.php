<?php

namespace MadWizard\WebAuthn\Tests\Server;

use MadWizard\WebAuthn\Builder\ServerBuilder;
use MadWizard\WebAuthn\Config\RelyingParty;
use MadWizard\WebAuthn\Credential\CredentialRegistration;
use MadWizard\WebAuthn\Credential\CredentialStoreInterface;
use MadWizard\WebAuthn\Credential\UserCredentialInterface;
use MadWizard\WebAuthn\Credential\UserHandle;
use MadWizard\WebAuthn\Crypto\CoseAlgorithm;
use MadWizard\WebAuthn\Crypto\Ec2Key;
use MadWizard\WebAuthn\Format\Base64UrlEncoding;
use MadWizard\WebAuthn\Format\ByteBuffer;
use MadWizard\WebAuthn\Json\JsonConverter;
use MadWizard\WebAuthn\Server\Registration\RegistrationContext;
use MadWizard\WebAuthn\Server\Registration\RegistrationOptions;
use MadWizard\WebAuthn\Server\UserIdentity;
use MadWizard\WebAuthn\Server\WebAuthnServer;
use MadWizard\WebAuthn\Tests\Helper\FixtureHelper;
use MadWizard\WebAuthn\Web\Origin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    /**
     * @var CredentialStoreInterface|MockObject
     */
    private $store;

    /**
     * @var WebAuthnServer|MockObject
     */
    private $server;

    private const CREDENTIAL_ID = 'Bo-VjHOkJZy8DjnCJnIc0Oxt9QAz5upMdSJxNbd-GyAo6MNIvPBb9YsUlE0ZJaaWXtWH5FQyPS6bT_e698IirQ';

    public function testStartRegistration()
    {
        $user = new UserIdentity(UserHandle::fromHex('123456'), 'demo', 'Demo user');
        $options = RegistrationOptions::createForUser($user);
        $request = $this->server->startRegistration($options);

        $clientOptions = $request->getClientOptions();
        self::assertSame('123456', $clientOptions->getUserEntity()->getId()->getHex());
        self::assertSame('demo', $clientOptions->getUserEntity()->getName());
        self::assertSame('Demo user', $clientOptions->getUserEntity()->getDisplayName());
        self::assertSame('example.com', $request->getContext()->getRpId());
        self::assertSame(64, $clientOptions->getChallenge()->getLength());
        self::assertNull($clientOptions->getAttestation());
    }

    public function testFinishRegistration()
    {
        $json = FixtureHelper::getJsonFixture('fido2-helpers/attestation.json');

        $credential = $json['challengeResponseAttestationU2fMsgB64Url'];

        $this->store
            ->expects($this->once())
            ->method('registerCredential')
            ->with(
                $this->callback(
                    function (CredentialRegistration $reg) {
                        return $reg->getCredentialId()->toString() === self::CREDENTIAL_ID &&
                            $reg->getUserHandle()->equals(UserHandle::fromHex('00112233')) &&
                            $reg->getPublicKey() instanceof Ec2Key;
                    }
                )
            );

        $challenge = new ByteBuffer(Base64UrlEncoding::decode('Vu8uDqnkwOjd83KLj6Scn2BgFNLFbGR7Kq_XJJwQnnatztUR7XIBL7K8uMPCIaQmKw1MCVQ5aazNJFk7NakgqA'));
        $context = new RegistrationContext($challenge, Origin::parse('https://localhost:8443'), 'localhost', UserHandle::fromHex('00112233'));
        $result = $this->server->finishRegistration(JsonConverter::decodeAttestation($credential), $context);

        self::assertSame(self::CREDENTIAL_ID, $result->getCredentialId()->toString());
        self::assertSame('Basic', $result->getVerificationResult()->getAttestationType());  // TODO:ugly
    }

    protected function setUp(): void
    {
        $rp = new RelyingParty('Example', 'https://example.com');
        $this->store = $this->createMock(CredentialStoreInterface::class);

        $this->server = (new ServerBuilder())
            ->setRelyingParty($rp)
            ->setCredentialStore($this->store)
            ->build();
    }

    private function createCredential(): UserCredentialInterface
    {
        /**
         * @var $cred UserCredentialInterface|MockObject
         */
        $cred = $this->createMock(UserCredentialInterface::class);

        $cred->expects($this->any())
            ->method('getCredentialId')
            ->willReturn('AAhH7cnPRBkcukjnc2G2GM1H5dkVs9P1q2VErhD57pkzKVjBbixdsufjXhUOfiD27D0VA-fPKUVYNGE2XYcjhihtYODQv-xEarplsa7Ix6hK13FA6uyRxMgHC3PhTbx-rbq_RMUbaJ-HoGVt-c820ifdoagkFR02Van8Vr9q67Bn6zHNDT_DNrQbtpIUqqX_Rg2p5o6F7bVO3uOJG9hUNgUb');

        $cred->expects($this->any())
            ->method('getPublicKey')
            ->willReturn(
                new Ec2Key(
                    ByteBuffer::fromHex('8d617e65c9508e64bcc5673ac82a6799da3c1446682c258c463fffdf58dfd2fa'),
                    ByteBuffer::fromHex('3e6c378b53d795c4a4dffb4199edd7862f23abaf0203b4b8911ba0569994e101'),
                    Ec2Key::CURVE_P256,
                    CoseAlgorithm::ES256
                )
            );

        return $cred;
    }
}
