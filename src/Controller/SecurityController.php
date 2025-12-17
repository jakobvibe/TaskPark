<?php

namespace App\Controller;

use App\Service\MagicLinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        MagicLinkService $magicLinkService
    ): Response {
        // If already logged in, redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $magicLinkService->sendMagicLink($email);
                    return $this->redirectToRoute('app_login_check_email', ['email' => $email]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Unable to send login link. Please try again.');
                }
            } else {
                $this->addFlash('error', 'Please enter a valid email address.');
            }
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/login/check-email', name: 'app_login_check_email', methods: ['GET'])]
    public function checkEmail(Request $request): Response
    {
        $email = $request->query->get('email', '');

        return $this->render('security/check_email.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/login/verify/{token}', name: 'app_login_verify', methods: ['GET'])]
    public function verify(): Response
    {
        // This route is handled by the MagicLinkAuthenticator
        // If we reach here, authentication failed
        $this->addFlash('error', 'Invalid or expired login link.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
