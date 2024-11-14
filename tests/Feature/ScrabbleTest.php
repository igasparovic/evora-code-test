<?php

use App\Http\Controllers\ScrabbleController;
use App\Services\ScrabbleService;

it('can start a new game and then end a turn', function () {

    $controller = new ScrabbleController(new ScrabbleService());
    $response = $controller->startGame(request()->merge([
        'p1Name' => 'Player 1',
        'p2Name' => 'Player 2',
    ]));

    expect($response)->not()->toBeNull()
        ->and($response['p1Name'])->toBe('Player 1')
        ->and($response['p2Name'])->toBe('Player 2')
        ->and($response['turnCount'])->toBe(1)
        ->and($response['winner'])->toBeNull()
        ->and($response['p1Score'])->toBe(0)
        ->and($response['p2Score'])->toBe(0);


    $letter1 = $response['p1Rack'][0];
    $letter2 = $response['p1Rack'][1];
    $letters = [
        [7, 7, $letter1 ],
        [7, 8, $letter2],
    ];

    $response = $controller->endTurn(request()->merge([
        'gameId' => $response['id'],
        'letters' => $letters,
    ]));

    expect($response['turnCount'])->toBe(2);

    $board = $response['board'];
    expect($board[7][7])->toBe($letter1)
        ->and($board[7][8])->toBe($letter2)
        ->and($response['p1Score'])->toBeGreaterThan(0);


    $response = $controller->getStatus(request()->merge([
        'gameId' => $response['id'],
    ]));

    expect($response['turnCount'])->toBe(2);
});
