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
    public function testCanSuccessfullyValidatePhoneNumber(
        string $phoneNumberInternational,
        string $phoneNumberNational
    ): void
    {
        $response = '{
  "caller_name": null,
  "carrier": {
    "error_code": null,
    "mobile_country_code": "310",
    "mobile_network_code": "456",
    "name": "verizon",
    "type": "mobile"
  },
  "country_code": "US",
  "national_format": "(510) 867-5310",
  "phone_number": "+15108675310",
  "add_ons": null,
  "url": "https://lookups.twilio.com/v1/PhoneNumbers/+15108675310"
}';
        $phoneNumberInstance = $this->prophesize(Lookups\V1\PhoneNumberInstance::class);

        $phoneNumberContext = $this->prophesize(Lookups\V1\PhoneNumberContext::class);
        $phoneNumberContext->fetch(["type" => ["carrier"]])
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
        $validator = new PhoneNumber($options);

        $this->assertTrue($validator->isValid($phoneNumberInternational));
        $this->assertEmpty($validator->getMessages());
    }

    /**
     * @dataProvider invalidPhoneNumberProvider
     */
    public function testCanSuccessfullyValidateInvalidPhoneNumbers(string $phoneNumber): void
    {
        $phoneNumberContext = $this->prophesize(Lookups\V1\PhoneNumberContext::class);
        $phoneNumberContext->fetch(["type" => ["carrier"]])
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
        $validator = new PhoneNumber($options);

        $this->assertFalse($validator->isValid($phoneNumber));
        $this->assertEquals(
            sprintf(
                "'%s' is not a valid phone number in E.164 format.",
                $phoneNumber
            ),
            $validator->getMessages()[PhoneNumber::PHONE_NUMBER]
        );
    }

    public function validPhoneNumberProvider()
    {
        return [
            [
                '+4910000000000',
                '0100 00000000',
            ]
        ];
    }

    public function invalidPhoneNumberProvider()
    {
        return [
            [
                '+100'
            ]
        ];
    }
}