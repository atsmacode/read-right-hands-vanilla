# Commands

Run the unit test suite on Linux:

>./apptests

Run the unit test suite on Windows:

>./apptests.bat

Drop, Create and Seed all tables

> php application.php app:build-env

# Todo:

- Convert GamePlay class into a middleware pipeline
    - Class can be divided into different classes responsible for a different set of actions like:
        - Who goes next
        - What hand stage is next /What stage the hand is in
        - What are the available options for the next player
- Add custom join queries for relationships
    - Rather than multiple chained model calls resulting in a lot of queries
- Address updated_at reliance
    - Currenly manually setting these values so the expected 'action on' seat can be identified in tests
    - Could use UNIX timestamp in miliseconds value