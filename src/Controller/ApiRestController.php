<?php

namespace App\Controller;

use App\Entity\History;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiRestController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/exchange/values", methods={"POST"})
     */
    public function exchangeValues(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $first = $data['first'] ?? null;
        $second = $data['second'] ?? null;

        if ($first === null || $second === null) {
            return new JsonResponse(['error' => 'Both first and second parameters are required.'], Response::HTTP_BAD_REQUEST);
        }

        $history = new History();
        $history->setFirstIn($first);
        $history->setSecondIn($second);

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        // Zamiana wartości bez użycia dodatkowej zmiennej
        $first = $first ^ $second;
        $second = $first ^ $second;
        $first = $first ^ $second;

        $history->setFirstOut($first);
        $history->setSecondOut($second);
        $history->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'first' => $first,
            'second' => $second,
        ]);
    }

    /**
     * @Route("/exchange/values", methods={"GET"})
     */
    public function getAllHistory(): JsonResponse
    {
        $history = $this->entityManager->getRepository(History::class)->findAll();

        $responseData = [];
        foreach ($history as $record) {
            $responseData[] = [
                'id' => $record->getId(),
                'firstIn' => $record->getFirstIn(),
                'secondIn' => $record->getSecondIn(),
                'firstOut' => $record->getFirstOut(),
                'secondOut' => $record->getSecondOut(),
                'createdAt' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $record->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($responseData);
    }
}