<?php

namespace App\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** Une modification dans l'administration vide les PDF du CV en cache (CVController) : le prochain est refait à jour */
final class CvPdfListener
{
    public function __construct(#[Target('cv_pdf.cache')] private readonly CacheItemPoolInterface $cvPdfCache)
    {
    }

    #[AsEventListener(AfterEntityPersistedEvent::class)]
    #[AsEventListener(AfterEntityUpdatedEvent::class)]
    #[AsEventListener(AfterEntityDeletedEvent::class)]
    public function vider(): void
    {
        $this->cvPdfCache->clear();
    }
}
