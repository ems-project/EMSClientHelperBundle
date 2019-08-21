<?php


namespace EMS\ClientHelperBundle\Helper\Hashcash;


use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class HashcashHelper
{

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CsrfTokenManager
     */
    private $csrfTokenManager;



    public function __construct(RequestStack $requestStack, CsrfTokenManager $csrfTokenManager)
    {
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
    }


    public function validateHashcash(string $csrfId, int $hashcashLevel = 4, string $hashAlgo = 'sha256')
    {
        $request = $this->requestStack->getCurrentRequest();
        $hashcash = $request->headers->get('X-Hashcash');
        if ($hashcash === null) {
            throw new AccessDeniedHttpException('Unrecognized user');
        }

        $token = new Token($hashcash);

        if (intval($token->getLevel()) < $hashcashLevel) {
            throw new AccessDeniedHttpException('Insufficient security level by definition');
        }

        if (!preg_match(sprintf('/^0{%d}/', $hashcashLevel), hash($hashAlgo, $hashcash))) {
            throw new AccessDeniedHttpException('Insufficient security level');
        }

        if ($this->csrfTokenManager->getToken($csrfId)->getValue() !== $token->getCsrf()) {
            throw new AccessDeniedHttpException('Unrecognized key');
        }
    }
}
