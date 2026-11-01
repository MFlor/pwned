<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Exception\GuzzleException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use MFlor\Pwned\Models\Subscription;

class SubscriptionRepository extends AbstractServiceRepository
{
    /**
     * Get the details of the subscription the API key belongs to
     *
     * @see https://haveibeenpwned.com/API/v3#SubscriptionStatus
     *
     * @return Subscription|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function status(): ?Subscription
    {
        $data = $this->decodeJson($this->getAuthenticatedResponse('subscription/status'));

        return $data instanceof \stdClass ? new Subscription($data) : null;
    }
}
