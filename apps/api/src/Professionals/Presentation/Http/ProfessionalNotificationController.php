<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Cases\Application\AuthoriseCaseAccess;
use App\Cases\Domain\CaseAccessDenied;
use App\Cases\Domain\CasePermission;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalNotification;
use App\Professionals\Domain\ProfessionalNotificationRepository;
use App\Professionals\Domain\ProfessionalNotificationType;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalNotificationController
{
    private const LIMIT = 50;
    public function __construct(private ProfessionalNotificationRepository $notifications, private AuthoriseCaseAccess $authorise) {}

    #[Route('/api/v1/professional/notifications', methods: ['GET'])]
    #[OA\Get(operationId: 'listProfessionalNotifications', summary: 'List permission-safe in-product notifications', security: [['professionalSession' => []]], tags: ['Professional notifications'])]
    public function list(#[CurrentUser] Professional $professional): JsonResponse
    {
        $items = [];
        foreach ($this->notifications->findFor($professional, self::LIMIT) as $notification) {
            if ($this->canAccess($notification, $professional)) $items[] = $this->serialize($notification);
        }
        return $this->json(['items' => $items, 'unreadCount' => count(array_filter($items, static fn (array $item): bool => $item['readAt'] === null))]);
    }

    #[Route('/api/v1/professional/notifications/{id}/read', methods: ['POST'])]
    #[OA\Post(operationId: 'markProfessionalNotificationRead', summary: 'Mark an accessible notification as read', security: [['professionalSession' => []]], tags: ['Professional notifications'])]
    public function read(string $id, #[CurrentUser] Professional $professional): JsonResponse
    {
        $notification = $this->notification($id, $professional);
        if (!$this->canAccess($notification, $professional)) throw new ProfessionalCaseNotFoundHttpException();
        $notification->markRead(DateTimeImmutable::createFromTimestamp(microtime(true)));
        $this->notifications->save($notification);
        return $this->json($this->serialize($notification));
    }

    #[Route('/api/v1/professional/notification-preferences', methods: ['GET'])]
    #[OA\Get(operationId: 'getProfessionalNotificationPreferences', summary: 'Get professional notification preferences', security: [['professionalSession' => []]], tags: ['Professional notifications'])]
    public function preferences(#[CurrentUser] Professional $professional): JsonResponse
    {
        return $this->json(['items' => [
            ['type' => ProfessionalNotificationType::CaseAssigned->value, 'enabled' => true, 'required' => true],
            ['type' => ProfessionalNotificationType::CaseLifecycleChanged->value, 'enabled' => $this->notifications->enabled($professional, ProfessionalNotificationType::CaseLifecycleChanged), 'required' => false],
        ]]);
    }

    #[Route('/api/v1/professional/notification-preferences/{type}', methods: ['PATCH'])]
    #[OA\Patch(operationId: 'changeProfessionalNotificationPreference', summary: 'Change an optional professional notification preference', security: [['professionalSession' => []]], tags: ['Professional notifications'])]
    public function changePreference(string $type, Request $request, #[CurrentUser] Professional $professional): JsonResponse
    {
        $notificationType = ProfessionalNotificationType::tryFrom($type);
        $data = $request->toArray();
        if ($notificationType === null || $notificationType->isRequired() || !array_key_exists('enabled', $data) || !is_bool($data['enabled'])) throw new BadRequestHttpException('The notification preference is invalid.');
        $this->notifications->changePreference($professional, $notificationType, $data['enabled']);
        return $this->json(['type' => $notificationType->value, 'enabled' => $data['enabled'], 'required' => false]);
    }

    private function notification(string $id, Professional $professional): ProfessionalNotification
    {
        if (!Uuid::isValid($id) || ($notification = $this->notifications->findForRecipient(Uuid::fromString($id), $professional)) === null) throw new ProfessionalCaseNotFoundHttpException();
        return $notification;
    }
    private function canAccess(ProfessionalNotification $notification, Professional $professional): bool
    {
        try { $this->authorise->require($notification->managedCase(), $professional, CasePermission::View); return true; } catch (CaseAccessDenied) { return false; }
    }
    /** @return array{id: string,type: string,createdAt: string,readAt: ?string,href: string} */
    private function serialize(ProfessionalNotification $notification): array
    {
        return ['id' => $notification->id()->toRfc4122(), 'type' => $notification->type()->value, 'createdAt' => $notification->createdAt()->format(DATE_RFC3339_EXTENDED), 'readAt' => $notification->readAt()?->format(DATE_RFC3339_EXTENDED), 'href' => '/profesionales/casos/'.$notification->managedCase()->id()->toRfc4122()];
    }
    /** @param array<string, mixed> $data */
    private function json(array $data): JsonResponse { return new JsonResponse($data, Response::HTTP_OK, ['Cache-Control' => 'no-store']); }
}
