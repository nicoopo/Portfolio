<?php

namespace App\EventListener;

use App\Security\BlocIp;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Serveur MCP public (/mcp, App\Mcp\Portfolio) : limite par IP (bloc /64 en IPv6, BlocIp), comme contact et livre d'or.
 * Réponse JSON-RPC, que le client MCP sait lire, plutôt qu'une page d'erreur HTML.
 */
#[AsEventListener]
final class LimiteMcpListener
{
    public function __construct(#[Autowire(service: 'limiter.mcp')] private readonly RateLimiterFactoryInterface $mcpLimiter)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || '/mcp' !== $request->getPathInfo() || 'OPTIONS' === $request->getMethod()) {
            return;
        }
        $limite = $this->mcpLimiter->create(BlocIp::de($request))->consume();
        if (!$limite->isAccepted()) {
            $event->setResponse(new JsonResponse(
                ['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32000, 'message' => 'Trop de requêtes : réessayez dans une minute.']],
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => max(1, $limite->getRetryAfter()->getTimestamp() - time())],
            ));
        }
    }
}
