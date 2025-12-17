<?php

namespace App\Service;

use App\Entity\LoginToken;
use App\Entity\User;
use App\Repository\LoginTokenRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MagicLinkService
{
    public function __construct(
        private UserRepository $userRepository,
        private LoginTokenRepository $loginTokenRepository,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(APP_URL)%')]
        private string $appUrl,
        #[Autowire('%env(int:MAGIC_LINK_EXPIRY_MINUTES)%')]
        private int $expiryMinutes = 15,
    ) {
    }

    /**
     * Send a magic link to the given email address.
     * Creates the user if they don't exist.
     */
    public function sendMagicLink(string $email): void
    {
        // Find or create user
        $user = $this->userRepository->findOrCreateByEmail($email);

        // Invalidate any existing tokens for this user
        $this->loginTokenRepository->invalidateUserTokens($user);

        // Generate a new token
        $plainToken = $this->generateToken();
        $tokenHash = $this->hashToken($plainToken);

        // Create login token entity
        $loginToken = new LoginToken();
        $loginToken->setUser($user);
        $loginToken->setTokenHash($tokenHash);
        $loginToken->setExpiresAt(new \DateTime("+{$this->expiryMinutes} minutes"));

        $this->loginTokenRepository->save($loginToken, true);

        // Generate the magic link URL
        $magicLink = $this->urlGenerator->generate(
            'app_login_verify',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send the email
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@taskpark.app', 'TaskPark'))
            ->to($user->getEmail())
            ->subject('Your TaskPark Login Link')
            ->htmlTemplate('emails/magic_link.html.twig')
            ->context([
                'user' => $user,
                'magicLink' => $magicLink,
                'expiryMinutes' => $this->expiryMinutes,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Validate a token and return the associated user if valid.
     */
    public function validateToken(string $plainToken): ?User
    {
        $tokenHash = $this->hashToken($plainToken);
        $loginToken = $this->loginTokenRepository->findValidByTokenHash($tokenHash);

        if (!$loginToken) {
            return null;
        }

        // Mark token as used
        $loginToken->setUsedAt(new \DateTime());
        $this->loginTokenRepository->save($loginToken, true);

        // Mark user as verified
        $user = $loginToken->getUser();
        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $this->userRepository->save($user, true);
        }

        // Update last login time
        $user->setLastLoginAt(new \DateTime());
        $this->userRepository->save($user, true);

        return $user;
    }

    /**
     * Generate a secure random token.
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash a token for storage.
     */
    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
