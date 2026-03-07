// /**
//  * Gamification Arena AMD module for Moodle.
//  *
//  * @module     local_gamificationarena/battle
//  */

// define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

//     let state = {
//         matchid: null,
//         courseid: null,
//         slotstart: Date.now(),
//         currentslot: 1,
//         finished: false,
//         sesskey: ''
//     };

//     /**
//      * Safely updates the UI based on the server payload.
//      *
//      * @param {Object} payload Server response payload.
//      */
//     const render = function(payload) {

//         if (!payload || !payload.state) {
//             return;
//         }

//         const s = payload.state;
//         const statusElem = document.getElementById('ga-status');

//         if (!statusElem) {
//             return;
//         }

//         statusElem.textContent =
//             s.status === 'queued'
//                 ? 'Waiting for opponent...'
//                 : 'Match active (' + (s.mode || 'Normal') + ')';

//         const questionElem = document.getElementById('ga-question');
//         const slotElem = document.getElementById('ga-slot');

//         if (s.question && questionElem) {
//             questionElem.textContent = s.question.questiontext;
//         }

//         if (slotElem) {
//             slotElem.textContent =
//                 'Question ' + (s.currentslot || 1) + ' / ' + (s.questioncount || 0);
//         }

//         const timerElem = document.getElementById('ga-timer');

//         if (timerElem) {
//             timerElem.textContent = 'Time left: ' + (s.timeleft || 0) + 's';
//         }

//         const list = document.getElementById('ga-scoreboard');

//         if (list && s.players) {

//             list.innerHTML = '';

//             s.players.forEach(function(player) {

//                 const item = document.createElement('li');

//                 item.className =
//                     'list-group-item d-flex justify-content-between align-items-center';

//                 const userSpan = document.createElement('span');
//                 userSpan.textContent = 'User ID: ' + player.userid;

//                 const scoreSpan = document.createElement('span');
//                 scoreSpan.className = 'badge badge-primary badge-pill';
//                 scoreSpan.textContent = player.score;

//                 item.appendChild(userSpan);
//                 item.appendChild(scoreSpan);

//                 list.appendChild(item);
//             });
//         }

//         state.currentslot = s.currentslot;

//         if (s.status === 'finished' && !state.finished) {

//             state.finished = true;
//             statusElem.textContent = 'Match finished!';

//             const submitBtn = document.getElementById('ga-submit');

//             if (submitBtn) {
//                 submitBtn.setAttribute('disabled', 'disabled');
//             }
//         }
//     };

//     /**
//      * Poll server for match state.
//      */
//     const poll = function() {

//         if (state.finished) {
//             return;
//         }

//         const url =
//             M.cfg.wwwroot +
//             '/local/gamificationarena/state.php' +
//             '?courseid=' + state.courseid +
//             '&matchid=' + state.matchid +
//             '&sesskey=' + state.sesskey;

//         fetch(url)
//             .then(function(response) {

//                 if (!response.ok) {
//                     throw new Error('Network error: ' + response.statusText);
//                 }

//                 return response.json();
//             })
//             .then(function(payload) {

//                 if (payload.error) {
//                     throw new Error(payload.error);
//                 }

//                 render(payload);
//             })
//             .catch(function() {
//                 /* Silent catch to prevent UI spam during polling */
//             })
//             .finally(function() {

//                 if (!state.finished) {
//                     setTimeout(poll, 1500);
//                 }
//             });
//     };

//     /**
//      * Submit answer to server.
//      */
//     const submitAnswer = function() {

//         if (state.finished) {
//             return;
//         }

//         const answerInput = document.getElementById('ga-answer');
//         const answer = answerInput ? answerInput.value : '';

//         const responsetime = Math.floor(
//             (Date.now() - state.slotstart) / 1000
//         );

//         const url =
//             M.cfg.wwwroot +
//             '/local/gamificationarena/submit_answer.php';

//         fetch(url, {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded'
//             },
//             body: new URLSearchParams({
//                 courseid: state.courseid,
//                 matchid: state.matchid,
//                 slot: state.currentslot,
//                 answer: answer,
//                 responsetime: responsetime,
//                 sesskey: state.sesskey
//             })
//         })
//         .then(function(response) {
//             return response.json();
//         })
//         .then(function(result) {

//             if (result.error) {
//                 Notification.alert('Error', result.error, 'Dismiss');
//             } else {

//                 state.slotstart = Date.now();

//                 if (answerInput) {
//                     answerInput.value = '';
//                 }

//                 poll();
//             }
//         })
//         .catch(Notification.exception);
//     };

//     return {

//         init: function(args) {

//             state.matchid = args.matchid;
//             state.courseid = args.courseid;
//             state.sesskey = args.sesskey;

//             const submitBtn = document.getElementById('ga-submit');

//             if (submitBtn) {

//                 submitBtn.addEventListener('click', function(e) {

//                     e.preventDefault();
//                     submitAnswer();
//                 });
//             }

//             poll();
//         }
//     };

// });

/**
 * Gamification Arena AMD module for Moodle.
 *
 * @module      local_gamificationarena/battle
 * @copyright   2026 Your Name
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/notification'], function(Notification) {

    /**
     * @type {Object} state Internal module state.
     */
    let state = {
        matchid: null,
        courseid: null,
        slotstart: Date.now(),
        currentslot: 1,
        finished: false,
        sesskey: ''
    };

    /**
/**
     * Update the UI based on server payload.
     *
     * @param {Object} s The state object returned from the server.
     */
    const render = function(s) {

        if (!s) {
            return;
        }

        // 1. Status text with safe fallbacks.
        const statusElem = document.getElementById('ga-status');
        if (statusElem) {
            statusElem.textContent =
                s.status === 'queued' ? 'Waiting for opponent...' :
                s.status === 'active' ? 'Match active (' + (s.mode || 'Normal') + ')' :
                s.status === 'finished' ? 'Match finished!' :
                'Loading...';
        }

        // 2. Question display with null-safety.
        const questionElem = document.getElementById('ga-question');
        if (questionElem) {
            if (s.question && typeof s.question.questiontext === 'string') {
                questionElem.textContent = s.question.questiontext;
                questionElem.classList.remove('text-muted');
            } else if (s.status === 'queued') {
                questionElem.textContent = 'Waiting for match to start...';
                questionElem.classList.add('text-muted');
            } else if (s.status === 'finished') {
                questionElem.textContent = 'Match complete! Check your score.';
                questionElem.classList.add('text-muted');
            } else {
                questionElem.textContent = 'Loading question...';
                questionElem.classList.add('text-muted');
            }
        }

        // 3. Slot counter.
        const slotElem = document.getElementById('ga-slot');
        if (slotElem && s.questioncount > 0) {
            slotElem.textContent = 'Question ' + (s.currentslot || 1) + ' / ' + s.questioncount;
        }

        // 4. Timer with visual warning when low.
        const timerElem = document.getElementById('ga-timer');
        if (timerElem) {
            const timeleft = s.timeleft || 0;
            timerElem.textContent = 'Time left: ' + timeleft + 's';
            if (timeleft <= 10 && timeleft > 0) {
                timerElem.classList.add('text-danger', 'font-weight-bold');
            } else {
                timerElem.classList.remove('text-danger', 'font-weight-bold');
            }
        }

        // 5. Scoreboard with username fallback.
        const list = document.getElementById('ga-scoreboard');
        if (list && Array.isArray(s.players)) {
            list.innerHTML = '';
            s.players.forEach(function(player, index) {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';

                const rankSpan = document.createElement('small');
                rankSpan.className = 'text-muted mr-2';
                rankSpan.textContent = '#' + (index + 1);

                const userSpan = document.createElement('span');
                userSpan.textContent = player.username ? player.username : 'User #' + player.userid;

                if (player.isbot) {
                    userSpan.textContent += ' 🤖';
                    userSpan.classList.add('text-secondary');
                }

                const scoreSpan = document.createElement('span');
                scoreSpan.className = 'badge badge-primary badge-pill';
                scoreSpan.textContent = player.score;

                const leftDiv = document.createElement('div');
                leftDiv.className = 'd-flex align-items-center';
                leftDiv.appendChild(rankSpan);
                leftDiv.appendChild(userSpan);

                item.appendChild(leftDiv);
                item.appendChild(scoreSpan);
                list.appendChild(item);
            });
        }

        // 6. Update internal state.
        if (typeof s.currentslot === 'number') {
            state.currentslot = s.currentslot;
        }

        // 7. Handle match finish.
        if (s.status === 'finished' && !state.finished) {
            state.finished = true;

            const submitBtn = document.getElementById('ga-submit');
            if (submitBtn) {
                submitBtn.setAttribute('disabled', 'disabled');
                submitBtn.classList.add('disabled');
            }

            const answerInput = document.getElementById('ga-answer');
            if (answerInput) {
                answerInput.setAttribute('disabled', 'disabled');
                answerInput.placeholder = 'Match ended';
            }

            // Calculate final score for current user.
            let finalScore = 0;
            if (s.players) {
                const me = s.players.find(function(p) {
                    return p.userid === state.userid;
                });
                finalScore = me ? me.score : 0;
            }

            Notification.alert('Match Complete', 'Your final score: ' + finalScore, 'OK');
        }
    };
    /**
     * Poll the server for match state every 1.5s.
     */
    const poll = function() {
    // Prevent polling if essential params are missing
    if (!state.courseid || !state.matchid || !state.sesskey || state.finished) {
        return;
    }

    const url = M.cfg.wwwroot +
        '/local/gamificationarena/state.php' +
        '?courseid=' + encodeURIComponent(state.courseid) +
        '&matchid=' + encodeURIComponent(state.matchid) +
        '&sesskey=' + encodeURIComponent(state.sesskey);

    fetch(url)
        .then(function(r) {
            if (!r.ok){ throw new Error('Network error: ' + r.statusText);}
            return r.json();
        })
        .then(function(payload) {
            if (payload.error) {
                throw new Error(payload.error);
            }
            render(payload.state || payload);
        })
        .catch(function(err) {
            // eslint-disable-next-line no-console
            console.warn('Poll error:', err);
        })
        .finally(function() {
            if (!state.finished) {
                setTimeout(poll, 1500);
            }
        });
};

    /**
     * Submit answer to the server.
     */
    const submitAnswer = function() {

        if (!state.courseid || !state.matchid || !state.sesskey || state.finished) {
            return;
        }

        const answerInput = document.getElementById('ga-answer');
        const answer = answerInput ? answerInput.value : '';
        const responsetime = Math.floor((Date.now() - state.slotstart) / 1000);

        fetch(M.cfg.wwwroot + '/local/gamificationarena/submit_answer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                courseid: state.courseid,
                matchid: state.matchid,
                slot: state.currentslot,
                answer: answer,
                responsetime: responsetime,
                sesskey: state.sesskey
            })
        })
        .then(function(r) {
            return r.json();
        })
        .then(function(result) {
            if (result.error) {
                Notification.alert('Error', result.error, 'Dismiss');
            } else {
                state.slotstart = Date.now();
                if (answerInput) {
                    answerInput.value = '';
                }
            }
        })
        .catch(Notification.exception);
    };

    return {
    /**
     * Initialize the battle module.
     *
     * @param {Number} matchid The match ID
     * @param {Number} courseid The course ID
     * @param {String} sesskey The session key
     */
    init: function(matchid, courseid, sesskey) {
        // Validate required parameters
        if (!matchid || !courseid || !sesskey) {
           Notification.alert(
    'Error',
    'GamificationArena: Missing initialization arguments',
    'Dismiss'
);

            return;
        }

        // Populate state
        state.matchid = matchid;
        state.courseid = courseid;
        state.sesskey = sesskey;

        // Bind submit button
        const submitBtn = document.getElementById('ga-submit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                submitAnswer();
            });
        }


        // Inside init(), after binding the submit button:
const answerInput = document.getElementById('ga-answer');
if (answerInput) {
    answerInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !state.finished) {
            e.preventDefault();
            submitAnswer();
        }
    });
}

        // Start polling
        poll();
    }
};
});