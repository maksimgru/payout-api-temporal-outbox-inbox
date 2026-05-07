<?php

namespace Modules\Payouts\Domain\Repository;

use Modules\Payouts\Domain\Entity\ProviderWebhookEvent;

interface ProviderWebhookInboxRepository
{
    public function findByEventIdForUpdate(string $eventId): ?ProviderWebhookEvent;

    public function findByIdForUpdate(int $id): ?ProviderWebhookEvent;

    /** @return list<ProviderWebhookEvent> */
    public function findUnprocessedForUpdate(int $limit): array;

    public function create(ProviderWebhookEvent $event): ProviderWebhookEvent;

    public function save(ProviderWebhookEvent $event): ProviderWebhookEvent;
}
