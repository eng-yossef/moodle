/**
 * Gamification Arena AMD module for Moodle.
 *
 * @module     local_gamificationarena/battle
 */

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    let state = {
        matchid: null,
        courseid: null,
        slotstart: Date.now(),
        currentslot: 1,
        finished: false,
        sesskey: ''
    };

    /**
     * Safely updates the UI based on the server payload.
     *
     * @param {Object} payload Server response payload.
     */
    const render = function(payload) {

        if (!payload || !payload.state) {
            return;
        }

        const s = payload.state;
        const statusElem = document.getElementById('ga-status');

        if (!statusElem) {
            return;
        }

        statusElem.textContent =
            s.status === 'queued'
                ? 'Waiting for opponent...'
                : 'Match active (' + (s.mode || 'Normal') + ')';

        const questionElem = document.getElementById('ga-question');
        const slotElem = document.getElementById('ga-slot');

        if (s.question && questionElem) {
            questionElem.textContent = s.question.questiontext;
        }

        if (slotElem) {
            slotElem.textContent =
                'Question ' + (s.currentslot || 1) + ' / ' + (s.questioncount || 0);
        }

        const timerElem = document.getElementById('ga-timer');

        if (timerElem) {
            timerElem.textContent = 'Time left: ' + (s.timeleft || 0) + 's';
        }

        const list = document.getElementById('ga-scoreboard');

        if (list && s.players) {

            list.innerHTML = '';

            s.players.forEach(function(player) {

                const item = document.createElement('li');

                item.className =
                    'list-group-item d-flex justify-content-between align-items-center';

                const userSpan = document.createElement('span');
                userSpan.textContent = 'User ID: ' + player.userid;

                const scoreSpan = document.createElement('span');
                scoreSpan.className = 'badge badge-primary badge-pill';
                scoreSpan.textContent = player.score;

                item.appendChild(userSpan);
                item.appendChild(scoreSpan);

                list.appendChild(item);
            });
        }

        state.currentslot = s.currentslot;

        if (s.status === 'finished' && !state.finished) {

            state.finished = true;
            statusElem.textContent = 'Match finished!';

            const submitBtn = document.getElementById('ga-submit');

            if (submitBtn) {
                submitBtn.setAttribute('disabled', 'disabled');
            }
        }
    };

    /**
     * Poll server for match state.
     */
    const poll = function() {

        if (state.finished) {
            return;
        }

        const url =
            M.cfg.wwwroot +
            '/local/gamificationarena/state.php' +
            '?courseid=' + state.courseid +
            '&matchid=' + state.matchid +
            '&sesskey=' + state.sesskey;

        fetch(url)
            .then(function(response) {

                if (!response.ok) {
                    throw new Error('Network error: ' + response.statusText);
                }

                return response.json();
            })
            .then(function(payload) {

                if (payload.error) {
                    throw new Error(payload.error);
                }

                render(payload);
            })
            .catch(function() {
                /* Silent catch to prevent UI spam during polling */
            })
            .finally(function() {

                if (!state.finished) {
                    setTimeout(poll, 1500);
                }
            });
    };

    /**
     * Submit answer to server.
     */
    const submitAnswer = function() {

        if (state.finished) {
            return;
        }

        const answerInput = document.getElementById('ga-answer');
        const answer = answerInput ? answerInput.value : '';

        const responsetime = Math.floor(
            (Date.now() - state.slotstart) / 1000
        );

        const url =
            M.cfg.wwwroot +
            '/local/gamificationarena/submit_answer.php';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                courseid: state.courseid,
                matchid: state.matchid,
                slot: state.currentslot,
                answer: answer,
                responsetime: responsetime,
                sesskey: state.sesskey
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(result) {

            if (result.error) {
                Notification.alert('Error', result.error, 'Dismiss');
            } else {

                state.slotstart = Date.now();

                if (answerInput) {
                    answerInput.value = '';
                }

                poll();
            }
        })
        .catch(Notification.exception);
    };

    return {

        init: function(args) {

            state.matchid = args.matchid;
            state.courseid = args.courseid;
            state.sesskey = args.sesskey;

            const submitBtn = document.getElementById('ga-submit');

            if (submitBtn) {

                submitBtn.addEventListener('click', function(e) {

                    e.preventDefault();
                    submitAnswer();
                });
            }

            poll();
        }
    };

});