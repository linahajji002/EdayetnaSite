<?php
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;


    public function __construct(RouterInterface $router, EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');

        // Vérifier si l'utilisateur existe et s'il est bloqué
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user && $user->getStatut() === 'blocked') {
            throw new CustomUserMessageAuthenticationException('USER_BLOCKED');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password'))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_CLIENT', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_home'));
        }

        return new RedirectResponse($this->router->generate('app_admin'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        // Vérifier si l'erreur est bien celle d'un compte bloqué
        if ($exception->getMessageKey() === 'USER_BLOCKED') {
            $session = $this->requestStack->getSession();
            if ($session) {
                $session->set('blocked_message', 'Votre compte est bloqué. Veuillez contacter l\'administrateur.');
            }
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

}
