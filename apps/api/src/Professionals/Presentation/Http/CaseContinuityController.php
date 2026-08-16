<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Cases\Application\CaseContinuityEntry;
use App\Cases\Application\ListCaseContinuity;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class CaseContinuityController
{
    public function __construct(
        private ListCaseContinuity $continuity,
        private OrganisationRepository $organisations,
    ) {
    }

    #[Route('/api/v1/professional/organisations/{id}/case-continuity', name: 'api_v1_professional_case_continuity', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listOrganisationCaseContinuity',
        summary: 'List open cases of an administered organisation that need a continuity decision',
        description: 'Operational metadata only. An entry means the responsible professional is absent today or the case holds overdue work; it is not a safeguarding judgement, and reading it grants no access to the case.',
        security: [['professionalSession' => []]],
        tags: ['Professional cases'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Cases needing a continuity decision. Empty when the professional does not administer the organisation.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The organisation is not available.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function list(string $id, #[CurrentUser] Professional $professional): JsonResponse
    {
        $organisation = $this->organisation($id);
        $entries = $this->continuity->list(
            $organisation,
            $professional,
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );

        return new JsonResponse(
            ['items' => array_map($this->serialize(...), $entries)],
            Response::HTTP_OK,
            ['Cache-Control' => 'no-store, private'],
        );
    }

    private function organisation(string $id): Organisation
    {
        if (!Uuid::isValid($id)) {
            throw new NotFoundHttpException('The organisation is not available.');
        }

        $organisation = $this->organisations->findById(Uuid::fromString($id));
        if ($organisation === null) {
            throw new NotFoundHttpException('The organisation is not available.');
        }

        return $organisation;
    }

    /**
     * Reference, status, responsible name and reason only. No case content,
     * involved person, note or report detail crosses this boundary.
     *
     * @return array{caseId: string, status: string, responsible: array{id: string, name: string}, reason: string, earliestOverdueAt: ?string}
     */
    private function serialize(CaseContinuityEntry $entry): array
    {
        return [
            'caseId' => $entry->managedCase->id()->toRfc4122(),
            'status' => $entry->managedCase->status()->value,
            'responsible' => [
                'id' => $entry->responsible->id()->toRfc4122(),
                'name' => $entry->responsible->name(),
            ],
            'reason' => $entry->reason->value,
            'earliestOverdueAt' => $entry->earliestOverdueAt?->format(DATE_RFC3339_EXTENDED),
        ];
    }
}
