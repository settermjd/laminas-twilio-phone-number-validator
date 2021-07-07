<?php

namespace SettermjdTest\Validator\Twilio;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Settermjd\Validator\Twilio\PhoneNumber;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\Rest\Lookups;

class PhoneNumberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider validPhoneNumberProvider
     */
    public function testCanSuccessfullyValidatePhoneNumber(string $phoneNumberInternational, string $countryCode = ''): void
    {
        $phoneNumberInstance = $this->prophesize(Lookups\V1\PhoneNumberInstance::class);
        $phoneNumberContext = $this->prophesize(Lookups\V1\PhoneNumberContext::class);

        $fetchData = [
            "type" => ["carrier"]
        ];
        if ($countryCode !== '') {
            $fetchData["countryCode"] = $countryCode;
        }

        $phoneNumberContext->fetch($fetchData)
            ->shouldBeCalled()
            ->willReturn($phoneNumberInstance);

        /** @var ObjectProphecy|Lookups\V1 $v1 */
        $v1 = $this->prophesize(Lookups\V1::class);
        $v1->phoneNumbers($phoneNumberInternational)
            ->shouldBeCalled()
            ->willReturn($phoneNumberContext);

        /** @var ObjectProphecy|Lookups $lookups */
        $lookups = $this->prophesize(Lookups::class);
        $lookups->v1 = $v1->reveal();

        /** @var ObjectProphecy|Client $client */
        $client = $this->prophesize(Client::class);
        $client->lookups = $lookups->reveal();

        $options = ['client' => $client->reveal()];
        if ($countryCode !== '') {
            $options['countryCode'] = $countryCode;
        }

        $validator = new PhoneNumber($options);

        $this->assertTrue($validator->isValid($phoneNumberInternational));
        $this->assertEmpty($validator->getMessages());
    }

    /**
     * @dataProvider invalidPhoneNumberProvider
     */
    public function testCanSuccessfullyValidateInvalidPhoneNumbers(string $phoneNumber, string $countryCode = ''): void
    {
        $fetchData = [
            "type" => ["carrier"]
        ];
        if ($countryCode !== '') {
            $fetchData["countryCode"] = $countryCode;
        }

        $phoneNumberContext = $this->prophesize(Lookups\V1\PhoneNumberContext::class);
        $phoneNumberContext->fetch($fetchData)
            ->shouldBeCalled()
            ->willThrow(TwilioException::class);

        /** @var ObjectProphecy|Lookups\V1 $v1 */
        $v1 = $this->prophesize(Lookups\V1::class);
        $v1->phoneNumbers($phoneNumber)
            ->shouldBeCalled()
            ->willReturn($phoneNumberContext);

        /** @var ObjectProphecy|Lookups $lookups */
        $lookups = $this->prophesize(Lookups::class);
        $lookups->v1 = $v1->reveal();

        /** @var ObjectProphecy|Client $client */
        $client = $this->prophesize(Client::class);
        $client->lookups = $lookups->reveal();

        $options = ['client' => $client->reveal()];
        if ($countryCode !== '') {
            $options['countryCode'] = $countryCode;
        }

        $validator = new PhoneNumber($options);

        $this->assertFalse($validator->isValid($phoneNumber));

        $expectedMessage = ($countryCode === '')
            ? "'%s' is not a valid phone number in E.164 format."
            : "'%s' is not a valid nationally formatted phone number." ;

        $message = function () use ($countryCode, $validator) {
            return ($countryCode === '')
                ? $validator->getMessages()[PhoneNumber::PHONE_NUMBER_INTL]
                : $validator->getMessages()[PhoneNumber::PHONE_NUMBER_NTL];
        };

        $this->assertEquals(
            sprintf($expectedMessage, $phoneNumber),
            $message()
        );
    }

    public function validPhoneNumberProvider()
    {
        return [
            [
                    '+4910000000000',
            ],
            [
                    '(510)867-5310',
                    'US'
            ],
            [
                    '0100 00000000',
            ]
        ];
    }

    public function invalidPhoneNumberProvider()
    {
        return [
            [
                '+100'
            ],
            [
                '+100',
                'US'
            ]
        ];
    }
}