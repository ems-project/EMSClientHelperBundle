<?php


namespace EMS\ClientHelperBundle\Helper\Hashcash;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class HashcashHelper
{

    /** @var CsrfTokenManager */
    private $csrfTokenManager;



    public function __construct(CsrfTokenManager $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }


    public function validateHashcash(Request $request, string $csrfId, int $hashcashLevel = 4, string $hashAlgo = 'sha256')
    {
        $hashcash = $request->headers->get('X-Hashcash');
        if ($hashcash === null) {
            throw new AccessDeniedHttpException('Unrecognized user');
        }

        $token = new Token($hashcash);

        if ($token->getLevel() < $hashcashLevel) {
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
