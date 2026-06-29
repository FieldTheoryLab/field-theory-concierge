(function () {
  'use strict';

  var questions = [
    {
      title: 'Can visitors understand your value in 5 seconds?',
      text: 'Strong websites make the offer obvious before users have to think too hard.',
      icon: 'message',
      options: [
        { label: 'Yes, immediately', detail: 'The headline, offer, and next step are very clear.', score: 20 },
        { label: 'Mostly', detail: 'It makes sense, but could be sharper.', score: 13 },
        { label: 'Not really', detail: 'The site looks fine, but the message is muddy.', score: 6 }
      ]
    },
    {
      title: 'Does your mobile experience feel effortless?',
      text: 'Mobile users need fast pages, clear buttons, readable content, and simple paths.',
      icon: 'phone',
      options: [
        { label: 'Yes, it feels great', detail: 'The mobile experience is clean, fast, and easy.', score: 20 },
        { label: 'It works', detail: 'Usable, but not as polished as it should be.', score: 12 },
        { label: 'It needs help', detail: 'The layout, speed, or forms create friction.', score: 5 }
      ]
    },
    {
      title: 'Are your calls to action obvious?',
      text: 'A good site guides users toward quote requests, bookings, purchases, or contact forms.',
      icon: 'target',
      options: [
        { label: 'Very obvious', detail: 'Users know exactly what to do next.', score: 20 },
        { label: 'Somewhat obvious', detail: 'The CTAs exist, but the journey could be smoother.', score: 12 },
        { label: 'Not obvious', detail: 'Users have to hunt for the next step.', score: 5 }
      ]
    },
    {
      title: 'Are you tracking useful behavior?',
      text: 'Traffic is only part of the story. Growth comes from understanding behavior and conversion.',
      icon: 'chart',
      options: [
        { label: 'Yes', detail: 'We track actions, sources, behavior, and conversion paths.', score: 20 },
        { label: 'A little', detail: 'Analytics exist, but we do not use them deeply.', score: 12 },
        { label: 'No', detail: 'We are mostly guessing.', score: 5 }
      ]
    },
    {
      title: 'Is your site built to support future growth?',
      text: 'The best websites are systems. They support content, SEO, automation, testing, and scale.',
      icon: 'system',
      options: [
        { label: 'Yes', detail: 'The site is flexible, scalable, and easy to improve.', score: 20 },
        { label: 'Sort of', detail: 'It works now, but changes can be clunky.', score: 12 },
        { label: 'No', detail: 'The site is hard to update or grow.', score: 5 }
      ]
    }
  ];

  var icons = {
    message: '<svg viewBox="0 0 120 120" fill="none" aria-hidden="true"><rect x="22" y="28" width="76" height="54" rx="16" stroke="white" stroke-width="8"/><path d="M43 82L32 99V78" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M43 49H78M43 63H67" stroke="white" stroke-width="8" stroke-linecap="round"/></svg>',
    phone: '<svg viewBox="0 0 120 120" fill="none" aria-hidden="true"><rect x="38" y="16" width="44" height="88" rx="14" stroke="white" stroke-width="8"/><path d="M53 88H67" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M49 32H71" stroke="white" stroke-width="6" stroke-linecap="round"/></svg>',
    target: '<svg viewBox="0 0 120 120" fill="none" aria-hidden="true"><circle cx="60" cy="60" r="38" stroke="white" stroke-width="8"/><circle cx="60" cy="60" r="18" stroke="white" stroke-width="8"/><path d="M60 20V8M60 112V100M20 60H8M112 60H100" stroke="white" stroke-width="8" stroke-linecap="round"/></svg>',
    chart: '<svg viewBox="0 0 120 120" fill="none" aria-hidden="true"><path d="M24 92H98" stroke="white" stroke-width="8" stroke-linecap="round"/><rect x="30" y="56" width="14" height="36" rx="5" fill="white"/><rect x="53" y="38" width="14" height="54" rx="5" fill="white"/><rect x="76" y="24" width="14" height="68" rx="5" fill="white"/></svg>',
    system: '<svg viewBox="0 0 120 120" fill="none" aria-hidden="true"><rect x="24" y="24" width="30" height="30" rx="9" stroke="white" stroke-width="8"/><rect x="66" y="24" width="30" height="30" rx="9" stroke="white" stroke-width="8"/><rect x="24" y="66" width="30" height="30" rx="9" stroke="white" stroke-width="8"/><rect x="66" y="66" width="30" height="30" rx="9" stroke="white" stroke-width="8"/></svg>'
  };

  function initFtAiAssessment(root) {
    if (!root || root.dataset.ftAiInitialized === 'true') return;
    root.dataset.ftAiInitialized = 'true';

    var current = 0;
    var answers = [];

    var content = root.querySelector('[data-ft-ai-content]');
    var progressBar = root.querySelector('[data-ft-ai-progress-bar]');
    var stepLabel = root.querySelector('[data-ft-ai-step-label]');
    var scorePreview = root.querySelector('[data-ft-ai-score-preview]');
    var backBtn = root.querySelector('[data-ft-ai-back]');
    var nextBtn = root.querySelector('[data-ft-ai-next]');

    if (!content || !progressBar || !stepLabel || !scorePreview || !backBtn || !nextBtn) return;

    function getScore() {
      return answers.reduce(function (sum, answerIndex, questionIndex) {
        if (answerIndex === undefined) return sum;
        return sum + questions[questionIndex].options[answerIndex].score;
      }, 0);
    }

    function updateMeta() {
      var percentComplete = Math.round((current / questions.length) * 100);
      var liveScore = Math.round((getScore() / 100) * 100);

      progressBar.style.width = percentComplete + '%';
      stepLabel.textContent = current < questions.length
        ? 'Question ' + (current + 1) + ' of ' + questions.length
        : 'Assessment Complete';
      scorePreview.textContent = 'Live Score: ' + liveScore + '%';
    }

    function renderQuestion() {
      var q = questions[current];
      var selected = answers[current];

      updateMeta();

      backBtn.style.display = current === 0 ? 'none' : 'inline-block';
      nextBtn.textContent = current === questions.length - 1 ? 'Analyze' : 'Continue';
      nextBtn.disabled = selected === undefined;

      content.innerHTML =
        '<div class="ft-ai-screen ft-ai-question">' +
          '<div class="ft-ai-visual">' + icons[q.icon] + '</div>' +
          '<h3>' + q.title + '</h3>' +
          '<p>' + q.text + '</p>' +
          '<div class="ft-ai-options">' +
            q.options.map(function (option, index) {
              return (
                '<button type="button" class="ft-ai-option' + (selected === index ? ' active' : '') + '" data-index="' + index + '">' +
                  '<span class="ft-ai-dot" aria-hidden="true"></span>' +
                  '<span class="ft-ai-option-copy">' +
                    '<strong>' + option.label + '</strong>' +
                    '<span>' + option.detail + '</span>' +
                  '</span>' +
                '</button>'
              );
            }).join('') +
          '</div>' +
        '</div>';

      root.querySelectorAll('.ft-ai-option').forEach(function (option) {
        option.addEventListener('click', function () {
          answers[current] = Number(this.getAttribute('data-index'));
          renderQuestion();
        });
      });
    }

    function renderAnalyzing() {
      progressBar.style.width = '100%';
      stepLabel.textContent = 'Analyzing';
      scorePreview.textContent = 'Building recommendations';
      backBtn.style.display = 'none';
      nextBtn.style.display = 'none';

      content.innerHTML =
        '<div class="ft-ai-analyzing">' +
          '<div class="ft-ai-loader" aria-hidden="true"></div>' +
          '<h3>Analyzing your website system</h3>' +
          '<div class="ft-ai-typing" data-ft-ai-typing></div>' +
        '</div>';

      var lines = [
        'Reviewing clarity, conversion paths, mobile experience, analytics, and scalability...',
        'Mapping your answers to a practical growth recommendation...',
        'Preparing your website health score...'
      ];

      var line = 0;
      var char = 0;
      var typingTarget = root.querySelector('[data-ft-ai-typing]');

      function typeLine() {
        if (line >= lines.length) {
          setTimeout(renderResult, 500);
          return;
        }

        if (char <= lines[line].length) {
          if (typingTarget) typingTarget.textContent = lines[line].slice(0, char);
          char++;
          setTimeout(typeLine, 22);
        } else {
          line++;
          char = 0;
          setTimeout(typeLine, 420);
        }
      }

      typeLine();
    }

    function renderResult() {
      var score = getScore();
      var headline = 'Your Website Has Growth Potential';
      var message = 'You have a working foundation, but there are likely opportunities to improve clarity, mobile flow, calls to action, analytics, and scalability.';
      var recs = [
        ['Messaging', 'Sharpen your homepage headline and service positioning.'],
        ['Conversion', 'Make the next step obvious across mobile and desktop.'],
        ['Measurement', 'Track the actions that actually lead to revenue.']
      ];

      if (score >= 85) {
        headline = 'Your Website Is Growth Ready';
        message = 'Your website appears to have a strong foundation. The opportunity now is optimization, testing, automation, and deeper insight.';
        recs = [
          ['CRO', 'Run experiments around landing pages, forms, and CTAs.'],
          ['AI', 'Add smarter response tools, recommendations, or guided experiences.'],
          ['Analytics', 'Build dashboards around a clear north star metric.']
        ];
      } else if (score < 55) {
        headline = 'Your Website Needs a Smarter System';
        message = 'Your website may be creating friction or leaving opportunities on the table. A strategic redesign or focused optimization plan could make a major difference.';
        recs = [
          ['Strategy', 'Clarify your offer, audience, and customer journey.'],
          ['UX', 'Improve mobile layout, page hierarchy, and calls to action.'],
          ['SEO/AEO', 'Create content that helps users and search engines understand your services.']
        ];
      }

      stepLabel.textContent = 'Assessment Complete';
      scorePreview.textContent = 'Final Score: ' + score + '%';

      content.innerHTML =
        '<div class="ft-ai-result">' +
          '<div class="ft-ai-score-ring" style="--score:' + score + '%">' + score + '</div>' +
          '<h3>' + headline + '</h3>' +
          '<p>' + message + '</p>' +
          '<div class="ft-ai-recs">' +
            recs.map(function (rec) {
              return '<div class="ft-ai-rec"><strong>' + rec[0] + ':</strong> ' + rec[1] + '</div>';
            }).join('') +
          '</div>' +
          '<button type="button" class="ft-ai-btn" data-prompt="Request a Proposal">Request a Strategy Review</button>' +
        '</div>';

      nextBtn.style.display = 'inline-block';
      nextBtn.textContent = 'Start Over';
      nextBtn.disabled = false;
      backBtn.style.display = 'none';
    }

    nextBtn.addEventListener('click', function () {
      if (current >= questions.length) {
        current = 0;
        answers = [];
        nextBtn.style.display = 'inline-block';
        renderQuestion();
        return;
      }

      if (current === questions.length - 1) {
        current++;
        renderAnalyzing();
      } else {
        current++;
        renderQuestion();
      }
    });

    backBtn.addEventListener('click', function () {
      if (current > 0) {
        current--;
        renderQuestion();
      }
    });

    renderQuestion();
  }

  window.ftcInitAiAssessment = initFtAiAssessment;

  document.querySelectorAll('[data-ft-ai-assessment]').forEach(initFtAiAssessment);
})();
