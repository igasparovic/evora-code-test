I spent 8h as instructed, couldn't get it fully to work in time. I'm submitting what I have so far.

docker compose up --build should get you up and running.

These should work: 

GET /scrabble/status?gameId=1
POST /scrabble/start ( needs p1Name & p2Name provided)

POST /scrabble/end-turn is not working as intended,  something is up with calculation and would need to debug more to get it working.

Logic is in app/Services/ScrabbleService.php and there is a poorly written test file in tests/Feature/ScrabbleTest.php ( it of course fails when trying to end turn )
I haven't used Laravel before this, so I'm not super confident on the best practice file structure.


Additionally, it's Node.js but here is a code test I did for AstraZeneca last year if you are interested! (no docker and haven't tried running this one recently) : https://github.com/igasparovic/az-test

