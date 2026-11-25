# AI Log

## Tools I used
I used Gemini (free) in three ways. At the start I used it to think about the assignment and to help write my first prompt for Claude. Later I showed it screenshots of my first drawing and asked what looked wrong. It pointed out overlapping labels on the left side of the plan view. After that I asked Claude to fix the lines and text. At the end I asked it for a summary of my work. After that I used Claude (free plan, chat) for most of the work: planning, the Laravel API, validation, the calculator, the SVG drawing, the UI, the tests, the README and this log.

I ran everything myself in VS Code. I pasted the code, ran php artisan test and checked the drawing in the browser.

I did not use AI for DECISIONS.md. I wrote it myself.

## How I worked

Claude gave me the files, I pasted them, ran the tests, looked at the result in the browser and then committed. If something looked wrong or I did not understand it, I asked before going on.

## Key prompts

1. I gave Claude the assignment PDF, my interview notes and my stack (Laravel, SQLite, Blade, SVG). I asked for the problem framing from the reference drawing, the folder structure, the database schema, the API routes and the validation rules. This gave me a plan where all limits and sources are in one config file, config/crane.php.

2. I asked that every formula says where it came from, and that anything not in the reference drawing is marked (Estimate). Because of this, every calculated value has a source and an estimate flag, and the drawing and the table show (Estimate) from that flag.

3. After the first version I told Claude that the drawing looked very different from the reference and that lines and text were touching. I asked for a drawing that looks like an experienced engineer made it. This gave me the new drawing with front view, plan view, end view and detail Z.

## One time the AI was wrong

Gemini's summary had a mistake. It said the wheelbase is 3600 mm, but the reference drawing and my drawing show 3800 mm. So I did not copy its summary. I wrote this log from what really happened.

In the first drawing Claude drew the wheels as circles in the front view. This is wrong. The wheels roll along the runway and the front view looks along the runway, so the wheels should look like thin strips. Circles belong only in the end view.

I caught it because I compared the drawing with the reference drawing and saw it looked different. I did not know the exact reason, so I told Claude. Claude checked its own geometry and found the mistake. Now the front view shows thin wheels and there is a separate end view with circles.

What I learned: compare the AI output with the reference before accepting it. It can look fine and still be wrong.

A smaller case: after I ran the tests, my saved designs were gone from the list. Claude guessed that the tests were wiping my real database. We changed phpunit.xml so tests use an in-memory database. After that my saved designs stayed.

## How I checked the AI work

Claude said its reading of the low resolution PDF was only an interpretation, for example what 620 and 8710 mean. I kept those as unconfirmed.

The wheel load formula has a test that compares it with the reference drawing. We get 72.6 kN and the drawing shows 74.8 kN, about 3 percent apart, so it is marked as an estimate.

I tried wrong values in the form (capacity 50, a runway height that is too low) and saw the server errors.

All 19 tests pass (61 assertions). They cover the calculator, the API, the drawing with the smallest and biggest inputs, and a check that a design name cannot break the SVG.

I read the code before committing so I can explain it in the interview.