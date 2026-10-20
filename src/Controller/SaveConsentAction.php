<?php

/*
 * This file is part of the atriametal/sylius-cookie-consent-plugin package.
 *
 * (c) Atriametal <contact@kemp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Atriametal\SyliusCookieConsentPlugin\Controller;

use Atriametal\SyliusCookieConsentPlugin\Consent\Consent;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentCookie;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentPolicy;
use Atriametal\SyliusCookieConsentPlugin\Consent\UnknownCategoryException;
use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Doctrine\Persistence\ObjectManager;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records the choice of the visitor, sent as {"categories": […]}, and stores it in the consent cookie.
 *
 * Only JSON is accepted: a browser cannot send it from another site without a CORS preflight, which this action does
 * not answer. Another site therefore cannot make a visitor consent without knowing it.
 */
final class SaveConsentAction
{
    public function __construct(
        private readonly ConsentPolicy $policy,
        private readonly ConsentCookie $cookie,
        private readonly ObjectManager $manager,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
            return self::error('The consent must be sent as JSON.', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $chosen = self::chosenCategories($request);
        if (null === $chosen) {
            return self::error('The consent must be sent as {"categories": [...]}.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $categories = $this->policy->grantedCategories($chosen);
        } catch (UnknownCategoryException $exception) {
            return self::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $consent = new Consent(
            $this->cookie->readId($request) ?? Consent::generateId(),
            $this->policy->version,
            $categories,
            new \DateTimeImmutable(),
        );

        $this->manager->persist(ConsentRecord::fromConsent($consent, $this->channelCode()));
        $this->manager->flush();

        $response = new Response(null, Response::HTTP_NO_CONTENT);
        $response->headers->setCookie($this->cookie->create($consent, $request->isSecure()));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private static function chosenCategories(Request $request): ?array
    {
        try {
            $data = json_decode($request->getContent(), true, 3, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($data) && \is_array($data['categories'] ?? null) ? $data['categories'] : null;
    }

    private function channelCode(): ?string
    {
        try {
            return $this->channelContext->getChannel()->getCode();
        } catch (ChannelNotFoundException) {
            return null;
        }
    }

    private static function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
