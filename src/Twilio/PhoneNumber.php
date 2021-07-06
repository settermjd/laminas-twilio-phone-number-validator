<?php

declare(strict_types=1);

namespace Settermjd\Validator\Twilio;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\AbstractValidator;
use Traversable;
use Twilio\Rest\Client;

class PhoneNumber extends AbstractValidator
{
    private Client $client;

    public const PHONE_NUMBER = 'phone_number';

    protected array $messageTemplates = [
        self::PHONE_NUMBER => "'%value%' is not a valid phone number in E.164 format.",
    ];

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (array_key_exists('client', $options)) {
            $this->client = $options['client'];
        }
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $this->setValue($value);

        $response = $this->client
            ->lookups
            ->v1
            ->phoneNumbers($value);

        $response = json_decode($response, false);

        if (property_exists($response, 'status') && $response->status === 404) {
            $this->error(self::PHONE_NUMBER);
            return false;
        }

        return true;
    }
}