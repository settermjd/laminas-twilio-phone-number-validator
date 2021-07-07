<?php

declare(strict_types=1);

namespace Settermjd\Validator\Twilio;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\AbstractValidator;
use Traversable;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class PhoneNumber extends AbstractValidator
{
    public const PHONE_NUMBER_INTL = 'phone_number_intl';
    public const PHONE_NUMBER_NTL = 'phone_number_ntl';

    protected array $messageTemplates = [
        self::PHONE_NUMBER_INTL => "'%value%' is not a valid phone number in E.164 format.",
        self::PHONE_NUMBER_NTL => "'%value%' is not a valid nationally formatted phone number.",
    ];

    private Client $client;

    private string $countryCode = '';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (array_key_exists('client', $options)) {
            $this->client = $options['client'];
        }

        if (array_key_exists('countryCode', $options) && $options['countryCode'] !== '') {
            $this->countryCode = $options['countryCode'];
        }
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        if ($value === '') {
            return false;
        }

        $this->setValue($value);

        try {
            $options = ["type" => ["carrier"]];
            if ($this->countryCode !== '') {
                $options['countryCode'] = $this->countryCode;
            }

            $this->client
                ->lookups
                ->v1
                ->phoneNumbers($value)
                ->fetch($options);
            return true;
        } catch (TwilioException $e) {
            if ($this->countryCode === '') {
                $this->error(self::PHONE_NUMBER_INTL);
            } else {
                $this->error(self::PHONE_NUMBER_NTL);
            }
            return false;
        }
    }
}