<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\ScrabbleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class ScrabbleController extends Controller
{
    public function __construct(
        readonly ScrabbleService $scrabbleService
    ) {}

    /**
     * @throws ValidationException
     */
    public function startGame(Request $request): array
    {
        return $this->scrabbleService->startGame($request->input('p1Name'), $request->input('p2Name'));
    }

    /**
     * @throws ValidationException
     */
    public function endTurn(Request $request) : array
    {
        return $this->scrabbleService->endTurn($request->input('gameId'), $request->input('letters'));
    }

    /**
     * @throws ValidationException
     */
    public function getStatus(Request $request) : array
    {
        return $this->scrabbleService->getStatus($request->input('gameId'));
    }

}
