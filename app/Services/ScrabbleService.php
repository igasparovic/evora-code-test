<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Game;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use function PHPUnit\Framework\isEmpty;

class ScrabbleService
{
    /**
     * Function to get the status of the game.
     *
     * @param int $id
     * @return array
     */
    public function getStatus(int $id): array
    {
        Validator::make([
            'id' => $id
        ], [
            'id' => 'required|integer',
        ])->validate();

        // Return entire game
        return Game::findOrFail($id)->toArray();
    }

    /**
     * Function to start a new game of Scrabble! Get excited! 🎉
     *
     * @param string $p1Name
     * @param string $p2Name
     * @return array
     */
    public function startGame(string $p1Name, string $p2Name): array
    {
        Validator::make([
            'p1Name' => $p1Name,
            'p2Name' => $p2Name
        ], [
            'p1Name' => 'required|string',
            'p2Name' => 'required|string',
        ])->validate();


        $bag = json_decode(Storage::disk('local')->get('letters.json'), true)['letters'];

        // Assign 7 random letters to each player from the bag of letters
        $p1Rack = [];
        $p2Rack = [];

        for ($i = 0; $i < 7; $i++) {
            $p1Rack[] = $this->getRandomLetter($bag);
            $p2Rack[] = $this->getRandomLetter($bag);
        }

        // Create a new game
        return Game::create([
            'p1Name' => $p1Name,
            'p2Name' => $p2Name,
            'board' => array_fill(0, 15, array_fill(0, 15, '')),
            'bag' => $bag,
            'p1Rack' => $p1Rack,
            'p2Rack' => $p2Rack,
            'winner' => null,
            'p1Score' => 0,
            'p2Score' => 0,
            'turnCount' => 1,
        ])->toArray();
    }

    /**
     * Function to end the turn by placing the letters on the board and calculating the score.
     *
     * @param int $gameId
     * @param array $letters [[int x, int y, string letter], ...]
     * @return array
     */
    public function endTurn(int $gameId, array $letters): array
    {
        Validator::make([
            'letters' => $letters,
            'gameId' => $gameId
        ], [
            'letters' => 'required|array',
            'letters.*.0' => 'required|integer',
            'letters.*.1' => 'required|integer',
            'letters.*.2' => 'required|string|size:1',
            'gameId' => 'required|integer',
        ])->validate();

        $game = Game::findOrFail($gameId);

        if($game->winner) {
            throw new RuntimeException('Game is already over.');
        }


        $isHorizontal = $this->isHorizontal($letters);

        // Sort the letters in order based on moving axis
        usort($letters, function($a, $b) use ($isHorizontal) {
            return $a[$isHorizontal ? 0 : 1] <=> $b[$isHorizontal ? 0 : 1];
        });


        $isPlayer1 = $game->turnCount % 2 === 1; // Player 1 is odd numbers
        $playerRack = $isPlayer1 ? $game->p1Rack : $game->p2Rack;
        $board = $game->board;
        $bag = $game->bag;

        if ($game->turnCount === 1) {
            if ($letters[0][0] !== 7 || $letters[0][1] !== 7) {
                throw new RuntimeException('First word must be placed on the center tile.');
            }
        }
        $words = $this->getWords($board, $letters, $isHorizontal, $playerRack);

        // Calculate score
        $score = $this->calculateScore($words);

        // Update the board, player rack, and scores
        $game->board = $board;
        $game->turnCount++;

        // Fill the player rack from the back until count = 7
        if(!isEmpty($bag)) {
            while (count($playerRack) < 7) {
                $playerRack[] = $this->getRandomLetter($bag);
            }
        } else {
            // If the bag is empty check player rack, if it is empty as well then the game is over, and we have a winner! 🎉
            if (count($playerRack) === 0) {
                $game->winner = $game->p1Score > $game->p2Score ? $game->p1Name : $game->p2Name;
            }
        }

        if($isPlayer1) {
            $game->p1Rack = $playerRack;
            $game->p1Score += $score;
        } else {
            $game->p2Rack = $playerRack;
            $game->p2Score += $score;
        }

        $game->save();

        return $game->toArray();
    }

    /**
     * Function calculates the score of the words formed by the letters placed on the board during current turn.
     *
     * @param array $words
     * @return int
     */
    public function calculateScore(array $words): int
    {
        $score = 0;
        $defaultBag = json_decode(Storage::disk('local')->get('letters.json'), true)['letters'];

        foreach ($words as $word) {
            $wordScore = 0;

            foreach (str_split($word) as $letter) {
                $letterScore = $defaultBag[$letter]['points'];
                $wordScore += $letterScore;
            }

            $score += $wordScore;
        }

        return $score;
    }

    /**
     * Function checks if the letters are within valid boundaries of 15x15 matrix and if they are placed in a straight line, either horizontally or vertically.
     *
     * @param $letters array [[int x, int y, string letter], ...]
     * @return bool true if the letters are placed horizontally, false if vertically
     */
    private function isHorizontal(array $letters): bool {
        $isHorizontal = true;
        $isVertical = true;

        $firstX = $letters[0][0];
        $firstY = $letters[0][1];

        foreach ($letters as $letter) {
            // Ensure coordinates are within bounds (0 to 14)
            if ($letter[0] < 0 || $letter[0] >= 15 || $letter[1] < 0 || $letter[1] >= 15) {
                throw new RuntimeException('Invalid position for letter.');
            }

            // Check if x-coordinates are the same (vertical)
            if ($isVertical && $letter[0] !== $firstX) {
                $isVertical = false;
            }

            // Check if y-coordinates are the same (horizontal)
            if ($isHorizontal && $letter[1] !== $firstY) {
                $isHorizontal = false;
            }
        }

        // If neither horizontal nor vertical, it's not a valid word placement
        if (!$isHorizontal && !$isVertical) {
            throw new RuntimeException('Letters must be placed in a straight line.');
        }

        return $isHorizontal;
    }

    /**
     * Function ensures that the coordinates of each letter aren't already occupied
     * and that this combination of letters with any existing letters on the board form only a single word on the moving axis.
     *
     * @param array $board
     * @param array $letters
     * @param bool $isHorizontal
     * @param array $playerRack
     * @return array list of words created by the letters placed on the board
     */
    private function getWords(array $board, array $letters, bool $isHorizontal, array $playerRack): array {
        $mainWord = '';
        $words = [];

        // Ensure $previousCoordinate is initialized based on the axis
        $previousCoordinate = $letters[0][$isHorizontal ? 1 : 0];

        foreach ($letters as $letter) {
            $x = $letter[0];
            $y = $letter[1];
            $letterChar = $letter[2];

            // Ensure the player rack contain the letter
            if (!in_array($letterChar, $playerRack)) {
                throw new RuntimeException('Player rack does not contain the letter ' . $letterChar);
            }

            // Ensure the position is empty on the board
            if (!empty($board[$x][$y])) {
                throw new RuntimeException('Position already occupied. Cannot place letter.' . $x . $y);
            }

            // Ensure that the letters form a single word along the moving axis
            $currentCoordinate = $isHorizontal ? $x : $y;
            if ($currentCoordinate - $previousCoordinate > 1) {
                // There is a gap; check if the gap is filled by letters in on the board
                for ($i = $previousCoordinate + 1; $i < $currentCoordinate; $i++) {
                    if ($isHorizontal) {
                        $coordinateCheck = $board[$i][$y]; // Horizontal movement
                    } else {
                        $coordinateCheck = $board[$x][$i]; // Vertical movement
                    }

                    if (empty($coordinateCheck)) {
                        throw new RuntimeException('Letters have to form a single word on the moving axis');
                    } else {
                        // Append the letter to the word
                        $mainWord .= $coordinateCheck;
                    }
                }
            }

            // Place the letter from the rack on the board
            $board[$x][$y] = $letterChar;
            // Remove the letter from the rack
            unset($playerRack[array_search($letterChar, $playerRack)]);
            $mainWord .= $letterChar;

            // Check for secondary words in the opposite direction and add if found
            $secondaryWord = $this->getPerpendicularWord($board, $x, $y, $isHorizontal);
            if (!empty($secondaryWord)) {
                $words[] = $secondaryWord;
            }

            $previousCoordinate = $currentCoordinate;
        }

        $words[] = $mainWord;
        return $words;
    }

    /**
     * Function returns a word that is formed in the perpendicular direction to the main axis if the letter has adjacent letters in that direction.
     *
     * @param array $board
     * @param int $x
     * @param int $y
     * @param bool $isHorizontal
     * @return string
     */
    private function getPerpendicularWord(array $board, int $x, int $y, bool $isHorizontal): string {
        $word = '';

        // Check for adjacent letters in the perpendicular direction
        if ($isHorizontal) {
            if (empty($board[$x - 1][$y]) && empty($board[$x + 1][$y])) {
                return '';
            }
        } else {
            if (empty($board[$x][$y - 1]) && empty($board[$x][$y + 1])) {
                return '';
            }
        }

        $current = $isHorizontal ? $y - 1 : $x - 1;
        while ($current >= 0 && !empty($isHorizontal ? $board[$x][$current] : $board[$current][$y])) {
            $word = ($isHorizontal ? $board[$x][$current] : $board[$current][$y]) . $word;
            $current--;
        }

        $word .= $board[$x][$y];

        $current = $isHorizontal ? $y + 1 : $x + 1;
        while ($current < 15 && !empty($isHorizontal ? $board[$x][$current] : $board[$current][$y])) {
            $word .= $isHorizontal ? $board[$x][$current] : $board[$current][$y];
            $current++;
        }

        return $word;
    }

    /**
     * Function returns a random letter from the bag of letters and decrements the count of that letter in the bag.
     * Letter is removed to avoid recursion.
     *
     * @param array $bag
     * @return string
     */
    private function getRandomLetter(array $bag): string
    {
        $letterKey = array_rand($bag);
        if($bag[$letterKey]['tiles'] > 1) {
            $bag[$letterKey]['tiles']--;
        } else {
            unset($bag[$letterKey]);
        }

        return $letterKey;
    }
}
