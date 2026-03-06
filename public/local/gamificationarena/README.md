# local_gamificationarena

## Why a local plugin type
A **local plugin** is the best fit because the feature is cross-cutting and course-wide. It integrates with course navigation, enrolment checks, gradebook, question APIs, and global leaderboard logic without forcing teachers to add a new activity instance in each section.

## Architecture overview
- **UI layer**: Mustache templates + AMD modules (`templates/*`, `amd/src/*`).
- **Controller layer**: `index.php`, `match.php`, `state.php`, `submit_answer.php`, `leaderboard.php`.
- **Domain services**:
  - `match_manager`: queueing, matchmaking, state machine, scoring.
  - `question_provider`: random question retrieval + server-side validation.
  - `bot_engine`: AI opponent simulation by difficulty.
  - `stats_manager`: XP/ranking/win-loss persistence + gradebook updates.
- **Storage**: schema in `db/install.xml`.

## Real-time strategy
Current implementation uses **AJAX polling** (1.2s interval). For larger scale, you can replace polling with a WebSocket gateway and keep the same server domain model.

## Caching and performance
- Cache definitions in `db/caches.php` for leaderboard and session match state.
- Use Redis as Moodle cache store for `application` mode to reduce DB reads.
- Keep match state payload compact and indexed DB lookups (`course_status_idx`, `match_slot_uix`, etc.).

## Security controls
- `require_login`, capability checks, and `sesskey` validation in all endpoints.
- Duplicate-answer lock with unique key (`matchid,questionslot,userid`).
- Response-time boundaries validated server-side.

## Installation steps
1. Copy folder to `local/gamificationarena`.
2. Visit **Site administration → Notifications** to install DB schema.
3. Assign capability `local/gamificationarena:play` to student roles if needed.
4. Open a course and click **Gamification Arena** in course navigation.

## Integration notes
- **Enrol API / access checks**: relies on course context + `require_login($course)`.
- **Question API**: reads from question bank tables and validates against answers table.
- **Gradebook API**: sends participation grade through `grade_update`.
- **Event API**: can be extended with custom events at match start/end (recommended next step).

## Game rules implemented
- 7 questions per match by default (configurable in service constants).
- 30s per question.
- Scoring: correctness + speed + streak.
- Modes: multiplayer if another player joins in 20s, else bot mode.

## Bot algorithm
- Easy: 60% accuracy.
- Medium: 75% accuracy.
- Hard: 90% accuracy.
- Human-like response latency randomized between 3–26s.
