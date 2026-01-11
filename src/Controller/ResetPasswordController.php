<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        // If user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $emailSent = false;
        $error = null;
        $devResetLink = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            if (!$email) {
                $error = 'Veuillez entrer votre adresse email.';
            } else {
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if ($user) {
                    // Generate a secure token
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = new \DateTimeImmutable('+1 hour');

                    $user->setResetToken($token);
                    $user->setResetTokenExpiresAt($expiresAt);

                    $this->entityManager->flush();

                    // Generate the reset URL
                    $resetUrl = $this->generateUrl(
                        'app_reset_password',
                        ['token' => $token],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    // Send the email
                    try {
                        $emailMessage = (new Email())
                            ->from('noreply@mybookstore.com')
                            ->to($user->getEmail())
                            ->subject('Réinitialisation de votre mot de passe - MyBookstore')
                            ->html($this->renderView('emails/reset_password.html.twig', [
                                'user' => $user,
                                'resetUrl' => $resetUrl,
                                'expiresAt' => $expiresAt,
                            ]));

                        $mailer->send($emailMessage);
                    } catch (\Exception $e) {
                        // Log the error but don't expose it to the user for security
                    }

                    // In dev mode, show the reset link directly (remove in production!)
                    if ($this->getParameter('kernel.environment') === 'dev') {
                        $devResetLink = $resetUrl;
                    }
                }

                // Always show success message to prevent email enumeration
                $emailSent = true;
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'emailSent' => $emailSent,
            'error' => $error,
            'devResetLink' => $devResetLink,
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // If user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Find user by token
        $user = $this->userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            if (!$password || strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                // Hash and set the new password
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                // Clear the reset token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);

                $this->entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'error' => $error,
        ]);
    }
}
