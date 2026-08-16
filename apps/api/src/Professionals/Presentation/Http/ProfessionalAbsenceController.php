<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Application\ManageProfessionalAbsence;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAbsence;
use App\Professionals\Domain\ProfessionalAbsenceRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

/**
 * Planned absence is recorded by a professional about themselves only. There is
 * no route here to record, read or cancel anyone else's absence.
 */
final readonly class ProfessionalAbsenceController
{
    public function __construct(
        private ManageProfessionalAbsence $manageAbsence,
        private ProfessionalAbsenceRepository $absences,
    ) {
    }

    #[Route('/api/v1/professional/absences', name: 'api_v1_professional_list_absences', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listProfessionalAbsences',
        summary: 'List the current professional own planned absences',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The professional own planned absences.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function list(#[CurrentUser] Professional $professional): JsonResponse
    {
        return $this->json([
            'items' => array_map(
                $this->serialize(...),
                $this->absences->findActiveFor($professional),
            ),
        ]);
    }

    #[Route('/api/v1/professional/absences', name: 'api_v1_professional_record_absence', methods: ['POST'])]
    #[OA\Post(
        operationId: 'recordProfessionalAbsence',
        summary: 'Record a planned absence for the current professional',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'The planned absence was recorded. It moves no case and changes no access.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'The absence period is invalid.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function record(
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')] RecordProfessionalAbsenceRequest $payload,
    ): JsonResponse {
        try {
            $absence = $this->manageAbsence->record(
                $professional,
                new DateTimeImmutable($payload->startsOn),
                new DateTimeImmutable($payload->endsOn),
                $payload->note,
                DateTimeImmutable::createFromTimestamp(microtime(true)),
            );
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The absence period is invalid.', $exception);
        }

        return $this->json($this->serialize($absence), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/professional/absences/{id}', name: 'api_v1_professional_cancel_absence', methods: ['DELETE'])]
    #[OA\Delete(
        operationId: 'cancelProfessionalAbsence',
        summary: 'Cancel one of the current professional own planned absences',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'The planned absence was cancelled.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'No such absence belongs to this professional.'),
        ],
    )]
    public function cancel(string $id, #[CurrentUser] Professional $professional): Response
    {
        if (!Uuid::isValid($id)) {
            throw new NotFoundHttpException('The absence is not available.');
        }

        $absence = $this->absences->findOwn(Uuid::fromString($id), $professional);
        if ($absence === null) {
            throw new NotFoundHttpException('The absence is not available.');
        }

        $this->manageAbsence->cancel($absence, DateTimeImmutable::createFromTimestamp(microtime(true)));

        return new Response(status: Response::HTTP_NO_CONTENT, headers: ['Cache-Control' => 'no-store, private']);
    }

    /** @return array{id: string, startsOn: string, endsOn: string, note: ?string} */
    private function serialize(ProfessionalAbsence $absence): array
    {
        return [
            'id' => $absence->id()->toRfc4122(),
            'startsOn' => $absence->startsOn()->format('Y-m-d'),
            'endsOn' => $absence->endsOn()->format('Y-m-d'),
            'note' => $absence->note(),
        ];
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'no-store, private']);
    }
}
