<?php
/** @var yii\web\View $this */

use frontend\assets\HelpCenterAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

HelpCenterAsset::register($this);

$categories = \common\models\SupportConversation::categoryOptions();
$profileUrl = Url::to(['/profile/student']);

$this->registerJs(
    'window.ftHelpCenter = ' . Json::htmlEncode([
        'api' => [
            'submitRequest' => Url::to(['/help-api/submit-request']),
            'aiAsk' => Url::to(['/help-api/ai-ask']),
            'chatHistory' => Url::to(['/help-api/chat-history']),
            'chatPoll' => Url::to(['/help-api/chat-poll']),
            'chatSend' => Url::to(['/help-api/chat-send']),
            'chatMarkRead' => Url::to(['/help-api/chat-mark-read']),
            'chatStatus' => Url::to(['/help-api/chat-status']),
        ],
        'links' => [
            'opportunities' => Url::to(['/position/index']),
            'applications' => Url::to(['/application/index']),
            'interviews' => Url::to(['/interview/index']),
            'messages' => Url::to(['/message/index']),
            'notifications' => Url::to(['/notification/index']),
            'profile' => $profileUrl,
        ],
    ]) . ';',
    \yii\web\View::POS_HEAD
);

$topics = [
    ['getting_started', 'Getting Started', 'Learn how the platform works', 'fas fa-rocket', 'getting-started'],
    ['applications', 'Applications', 'Apply and track opportunities', 'fas fa-file-lines', 'applications'],
    ['interviews', 'Interviews', 'Schedules, reminders, and prep', 'fas fa-video', 'interviews'],
    ['messages', 'Messages', 'Chat with organizations', 'fas fa-comments', 'messages'],
    ['notifications', 'Notifications', 'Alerts and status updates', 'fas fa-bell', 'notifications'],
    ['profile', 'Profile & Account', 'CV, settings, and security', 'fas fa-user', 'profile'],
    ['organizations', 'Organizations', 'Recruiters and verification', 'fas fa-building', 'organizations'],
    ['technical', 'Technical Issues', 'Uploads, login, and bugs', 'fas fa-wrench', 'technical'],
];
?>

<div class="hc-page" id="hcPage">
    <header class="hc-hero sp-glass">
        <div class="hc-hero-top">
            <div>
                <h1>Help Center</h1>
                <p>Find answers to common questions or get help from our team.</p>
            </div>
            <button type="button" class="hc-btn hc-btn--ai" id="hcOpenAi" aria-label="Ask AI Assistant">
                <i class="fas fa-robot"></i> Ask AI Assistant
            </button>
        </div>
        <div class="hc-search-wrap">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="hcSearch" class="hc-search" placeholder="Search help articles, guides, and solutions..." aria-label="Search help">
        </div>
    </header>

    <section class="hc-section">
        <h2 class="hc-section-title">Popular Topics</h2>
        <div class="hc-topics-grid">
            <?php foreach ($topics as [$key, $title, $desc, $icon, $cat]): ?>
                <button type="button" class="hc-topic-card sp-glass" data-hc-topic="<?= Html::encode($cat) ?>">
                    <span class="hc-topic-icon"><i class="<?= Html::encode($icon) ?>"></i></span>
                    <strong><?= Html::encode($title) ?></strong>
                    <span><?= Html::encode($desc) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="hc-section" id="hc-faq">
        <h2 class="hc-section-title">Frequently Asked Questions</h2>
        <div class="hc-faq-list" id="hcFaqList">
            <?php
            $faqs = [
                [
                    'applications', 'getting-started', 'fas fa-rocket',
                    'How do I apply for field training positions?',
                    '<ol class="hc-steps"><li>Open <strong>Opportunities</strong> and search internships in your field.</li><li>Open a position details page and review requirements.</li><li>Click <strong>Apply</strong> — complete your profile first.</li><li>Upload your CV (PDF, DOC, or DOCX under 5MB).</li><li>Track status under <strong>Applications</strong>.</li></ol>',
                    true,
                ],
                [
                    'applications', 'applications', 'fas fa-layer-group',
                    'What do application statuses mean?',
                    '<p>Statuses on your Applications page: <strong>Submitted</strong>, <strong>Under Review</strong>, <strong>Shortlisted</strong>, <strong>Interview</strong>, <strong>Accepted</strong>, or <strong>Rejected</strong>. Organizations update these on their ATS board — you receive a notification when yours changes.</p>',
                    false,
                ],
                [
                    'interviews', 'interviews', 'fas fa-calendar-check',
                    'How do interviews work on the platform?',
                    '<p>When shortlisted, an organization may schedule an interview. Open <strong>Interviews</strong> from the sidebar for date, time, and mode. Confirm details via <strong>Messages</strong> if needed. You are notified when interviews are created or updated.</p>',
                    false,
                ],
                [
                    'messages', 'messages', 'fas fa-envelope',
                    'How do I message a recruiter?',
                    '<p>Open <strong>Messages</strong> from the sidebar. Conversations are tied to your applications. Unread threads are highlighted. This is separate from Live Support with platform administrators.</p>',
                    false,
                ],
                [
                    'profile', 'profile', 'fas fa-file-pdf',
                    'My CV upload failed — what should I do?',
                    '<ul class="hc-bullets"><li><strong>Formats:</strong> PDF, DOC, DOCX only</li><li><strong>Size:</strong> Maximum 5MB</li><li>Remove special characters from the filename</li><li>Try Chrome or Edge if upload stalls</li><li>Update from <strong>Profile → CV</strong></li></ul><p>If it still fails, use Live Support or Send to Admin below.</p>',
                    true, true,
                ],
                [
                    'notifications', 'notifications', 'fas fa-bell',
                    'How do notifications work?',
                    '<p>Notifications alert you about application updates, interviews, new messages, profile reminders, and admin support replies. Open <strong>Notifications</strong> — the bell badge shows unread count.</p>',
                    false,
                ],
                [
                    'organizations', 'organizations', 'fas fa-building',
                    'How do organizations review my application?',
                    '<p>Verified organizations post opportunities and manage applicants on an ATS board. They can shortlist candidates, schedule interviews, and message you directly. Only organizations you apply to can access your CV.</p>',
                    false,
                ],
                [
                    'technical', 'technical', 'fas fa-screwdriver-wrench',
                    'The site is slow or showing errors',
                    '<p>Hard-refresh (Ctrl+F5), clear cache, disable ad-blockers, and try another browser. Note the page URL and steps, then contact support with those details.</p>',
                    false,
                ],
            ];
            foreach ($faqs as $faq):
                [$cat, $topic, $icon, $question, $answer, $showAi] = array_pad($faq, 6, false);
                $showChat = !empty($faq[6]);
                ?>
                <article class="hc-faq-item sp-glass" data-hc-cat="<?= Html::encode($cat) ?>" data-hc-topic="<?= Html::encode($topic) ?>" data-hc-search="<?= Html::encode(strtolower($question . ' ' . strip_tags($answer))) ?>">
                    <button type="button" class="hc-faq-q" aria-expanded="false">
                        <span class="hc-faq-q-icon"><i class="<?= Html::encode($icon) ?>"></i></span>
                        <span class="hc-faq-q-text"><?= Html::encode($question) ?></span>
                        <i class="fas fa-chevron-down hc-faq-chevron"></i>
                    </button>
                    <div class="hc-faq-a">
                        <div class="hc-faq-body"><?= $answer ?></div>
                        <?php if ($showAi || $showChat): ?>
                            <div class="hc-faq-actions">
                                <?php if ($showAi): ?>
                                    <button type="button" class="hc-btn hc-btn--ghost hc-faq-ai"><i class="fas fa-robot"></i> Ask AI Assistant</button>
                                    <button type="button" class="hc-btn hc-btn--ghost hc-faq-admin"><i class="fas fa-user-shield"></i> Contact Admin</button>
                                <?php endif; ?>
                                <?php if ($showChat): ?>
                                    <button type="button" class="hc-btn hc-btn--ghost hc-faq-chat"><i class="fas fa-comments"></i> Chat with Admin</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="hc-empty" id="hcNoResults" hidden>
            <i class="fas fa-search"></i>
            <p>No articles match your search. Try different keywords or contact support.</p>
        </div>
    </section>

    <section class="hc-section hc-request" id="hc-request-help">
        <div class="hc-request-grid">
            <div class="hc-request-form-wrap sp-glass">
                <h2>Still Need Help?</h2>
                <p class="hc-muted">Send a message directly to the admin team. We typically respond within 24 hours.</p>
                <form id="hcRequestForm" class="hc-form" novalidate>
                    <label class="hc-label" for="hcCategory">Issue Category</label>
                    <select id="hcCategory" name="category" class="hc-input" required>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>"><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="hc-label" for="hcSubject">Subject</label>
                    <input type="text" id="hcSubject" name="subject" class="hc-input" placeholder="Brief summary of your issue" required maxlength="255">
                    <label class="hc-label" for="hcBody">Description</label>
                    <textarea id="hcBody" name="body" class="hc-input hc-textarea" rows="5" placeholder="Describe what happened and what you need help with…" required></textarea>
                    <button type="submit" class="hc-btn hc-btn--primary w-100" id="hcRequestSubmit">
                        <i class="fas fa-paper-plane"></i> Send to Admin
                    </button>
                    <p class="hc-form-msg" id="hcRequestMsg" hidden></p>
                </form>
            </div>
            <div class="hc-contact-cards">
                <div class="hc-contact-card sp-glass">
                    <i class="fas fa-comments"></i>
                    <h3>Live Chat</h3>
                    <p>Mon–Fri, 9AM–6PM</p>
                    <button type="button" class="hc-link-btn" id="hcStartChat">Start Chat</button>
                </div>
                <div class="hc-contact-card sp-glass">
                    <i class="fas fa-envelope"></i>
                    <h3>Email Support</h3>
                    <p>support@fieldtraining.co.tz</p>
                    <a href="mailto:support@fieldtraining.co.tz" class="hc-link-btn">Send Email</a>
                </div>
                <div class="hc-contact-card sp-glass">
                    <i class="fas fa-book"></i>
                    <h3>Knowledge Base</h3>
                    <p>Browse topics above</p>
                    <a href="#hc-faq" class="hc-link-btn">Browse Articles</a>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- AI Assistant Panel -->
<aside class="hc-ai-panel" id="hcAiPanel" aria-hidden="true">
    <div class="hc-ai-header">
        <div><i class="fas fa-robot"></i> <strong>AI Assistant</strong></div>
        <button type="button" class="hc-icon-btn" id="hcCloseAi" aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="hc-ai-messages" id="hcAiMessages">
        <div class="hc-ai-msg hc-ai-msg--bot">
            <p>Hi! I can explain applications, ATS statuses, interviews, messages, notifications, profiles, and CV requirements on the Field Training Platform. What would you like to know?</p>
        </div>
    </div>
    <div class="hc-ai-quick" id="hcAiQuick">
        <button type="button" data-q="How do I apply for internships?">How do I apply?</button>
        <button type="button" data-q="What do ATS application statuses mean?">ATS statuses</button>
        <button type="button" data-q="My CV upload failed">CV upload help</button>
    </div>
    <form class="hc-ai-compose" id="hcAiForm">
        <input type="text" id="hcAiInput" placeholder="Type your question…" autocomplete="off" maxlength="500">
        <button type="submit" class="hc-icon-btn hc-icon-btn--primary" aria-label="Send"><i class="fas fa-paper-plane"></i></button>
    </form>
</aside>
<div class="hc-ai-backdrop" id="hcAiBackdrop" hidden></div>

<!-- Live Chat -->
<button type="button" class="hc-live-fab" id="hcLiveFab" aria-label="Live Support">
    <i class="fas fa-comment-dots"></i>
    <span>Live Support</span>
</button>

<div class="hc-live-chat" id="hcLiveChat" aria-hidden="true">
    <header class="hc-live-header">
        <div>
            <strong>Live Chat</strong>
            <span class="hc-live-status" id="hcLiveStatus"><span class="hc-dot"></span> Checking…</span>
        </div>
        <div class="hc-live-actions">
            <button type="button" class="hc-icon-btn" id="hcLiveMinimize" aria-label="Minimize"><i class="fas fa-minus"></i></button>
            <button type="button" class="hc-icon-btn" id="hcLiveClose" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>
    </header>
    <div class="hc-live-offline" id="hcLiveOffline" hidden>
        Support is currently offline. Leave a message and we will respond later.
    </div>
    <div class="hc-live-messages" id="hcLiveMessages"></div>
    <form class="hc-live-compose" id="hcLiveForm">
        <input type="text" id="hcLiveInput" placeholder="Type your message…" autocomplete="off" maxlength="2000">
        <button type="submit" class="hc-icon-btn hc-icon-btn--primary" aria-label="Send"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>
