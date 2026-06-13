<?php
/** @var yii\web\View $this */

use frontend\assets\HelpCenterAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

HelpCenterAsset::register($this);

$categories = \common\models\SupportConversation::categoryOptionsForRole('organization');

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
            'interviews' => Url::to(['/organization/interviews/index']),
            'students' => Url::to(['/organization/students/index']),
            'messages' => Url::to(['/message/index']),
            'notifications' => Url::to(['/notification/index']),
            'analytics' => Url::to(['/organization/analytics/index']),
            'profile' => Url::to(['/profile/organization']),
            'dashboard' => Url::to(['/dashboard/index']),
        ],
    ]) . ';',
    \yii\web\View::POS_HEAD
);

$topics = [
    ['internships', 'Internship Management', 'Create, edit, close, and visibility', 'fas fa-briefcase', 'internships'],
    ['ats', 'Applications & ATS', 'Review, shortlist, reject, and pipeline', 'fas fa-layer-group', 'ats'],
    ['interviews', 'Interviews', 'Schedule, score, and evaluate candidates', 'fas fa-video', 'interviews'],
    ['students', 'Students', 'Profiles, CVs, and candidate comparison', 'fas fa-user-graduate', 'students'],
    ['messaging', 'Messaging & Notifications', 'Contact students and manage alerts', 'fas fa-comments', 'messaging'],
    ['analytics', 'Analytics & Reports', 'Dashboard metrics and exports', 'fas fa-chart-line', 'analytics'],
    ['account', 'Organization Account', 'Profile, password, and verification', 'fas fa-building', 'account'],
    ['technical', 'Technical Issues', 'ATS, interviews, CV download, errors', 'fas fa-wrench', 'technical'],
];
?>

<div class="hc-page" id="hcPage">
    <header class="hc-hero sp-glass">
        <div class="hc-hero-top">
            <div>
                <h1>Help Center</h1>
                <p>Recruitment guides, ATS workflows, and organization support for your team.</p>
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
                    'internships', 'internships', 'fas fa-plus-circle',
                    'How do I create a new internship posting?',
                    '<ol class="hc-steps"><li>Open <strong>Internship Opportunities</strong> from the sidebar.</li><li>Click <strong>Create</strong> and fill in title, description, field of study, location, and deadlines.</li><li>Set visibility — published listings appear to matching students.</li><li>Save and publish when ready to receive applications.</li></ol>',
                    true,
                ],
                [
                    'internships', 'internships', 'fas fa-pen',
                    'How do I edit, close, or archive an internship?',
                    '<p>Open <strong>Internship Opportunities</strong>, select the listing, and use <strong>Edit</strong> to update details. To stop new applications, toggle status to <strong>Closed</strong> or archive the position. Existing applicants remain visible on your ATS board.</p>',
                    false,
                ],
                [
                    'ats', 'ats', 'fas fa-user-check',
                    'How do I shortlist candidates?',
                    '<ol class="hc-steps"><li>Open <strong>Applications (ATS)</strong> from the sidebar.</li><li>Select an applicant card or open the kanban board.</li><li>Drag the candidate to <strong>Shortlisted</strong> or use the status dropdown.</li><li>The student receives a <strong>Notification</strong> when their status changes.</li></ol>',
                    true,
                ],
                [
                    'ats', 'ats', 'fas fa-arrows-left-right',
                    'Why can\'t I move a candidate to the next ATS stage?',
                    '<ul class="hc-bullets"><li>Ensure you have permission on the organization account.</li><li>Some transitions require a prior stage (e.g. shortlist before interview).</li><li>Refresh the ATS board if the page was open a long time.</li><li>Check that the application is still active (not withdrawn).</li></ul><p>If the action still fails, note the candidate name and use <strong>Live Support</strong> below.</p>',
                    true, true,
                ],
                [
                    'interviews', 'interviews', 'fas fa-calendar-plus',
                    'How do I schedule interviews?',
                    '<ol class="hc-steps"><li>Shortlist the candidate on your ATS board first.</li><li>Open <strong>Interviews</strong> and click <strong>Schedule Interview</strong>.</li><li>Select the applicant, date, time, mode (in-person/online), and location or meeting link.</li><li>Submit — the student is notified automatically.</li></ol>',
                    true,
                ],
                [
                    'interviews', 'interviews', 'fas fa-triangle-exclamation',
                    'Why is the interview action failing?',
                    '<ul class="hc-bullets"><li>Confirm the candidate is <strong>Shortlisted</strong> or in <strong>Interview</strong> stage.</li><li>Date and time must be in the future.</li><li>Required fields: applicant, datetime, and interview mode.</li><li>If rescheduling, open the existing interview and update rather than creating a duplicate.</li></ul>',
                    true, true,
                ],
                [
                    'students', 'students', 'fas fa-file-pdf',
                    'How do I download a student\'s CV?',
                    '<p>Open the candidate from <strong>Applications (ATS)</strong> or <strong>Students</strong>. Click <strong>Download CV</strong> on their profile card. CVs are served securely from the platform — only your organization can access applicants to your postings.</p>',
                    true, true,
                ],
                [
                    'students', 'students', 'fas fa-users',
                    'How do I view profiles and compare candidates?',
                    '<p>Use <strong>Students</strong> to browse applicants across your postings. Open a profile to see university, field of study, CV, and application history. On the ATS board, filter by stage and sort by date to compare your pipeline side by side.</p>',
                    false,
                ],
                [
                    'messaging', 'messaging', 'fas fa-envelope',
                    'How do I contact students via Messages?',
                    '<p>Open <strong>Messages</strong> from the sidebar. Conversations are linked to applications — open a thread from an applicant card or start messaging from the ATS view. Students receive notifications for new messages.</p>',
                    false,
                ],
                [
                    'analytics', 'analytics', 'fas fa-file-export',
                    'How do I export analytics reports?',
                    '<ol class="hc-steps"><li>Open <strong>Analytics &amp; Reports</strong> from the sidebar.</li><li>Review dashboard metrics: applications, conversion, and pipeline stages.</li><li>Click <strong>Export</strong> and choose CSV or Excel format.</li><li>The report downloads with your selected date range.</li></ol>',
                    true,
                ],
                [
                    'account', 'account', 'fas fa-shield-halved',
                    'Organization profile and verification',
                    '<p>Update your company profile under <strong>Company Profile</strong>. Upload your logo and contact details. Platform admins verify organizations before full publishing rights. Check <strong>Settings &amp; Security</strong> for password changes and team access.</p>',
                    false,
                ],
                [
                    'technical', 'technical', 'fas fa-screwdriver-wrench',
                    'ATS, dashboard, or CV download errors',
                    '<p>Hard-refresh (Ctrl+F5), clear cache, and try another browser. For CV download issues, confirm the student has uploaded a valid file. For dashboard errors, note the page URL and steps, then contact admin via <strong>Send to Admin</strong> or <strong>Live Support</strong>.</p>',
                    true, true,
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
            <p>Hi! I can help with internship management, ATS workflows, shortlisting, interviews, candidate CVs, messaging, analytics exports, and organization account settings. What would you like to know?</p>
        </div>
    </div>
    <div class="hc-ai-quick" id="hcAiQuick">
        <button type="button" data-q="How do I shortlist candidates?">Shortlist candidates</button>
        <button type="button" data-q="Why can't I move a candidate to the next stage?">ATS stage transitions</button>
        <button type="button" data-q="How do I schedule interviews?">Schedule interviews</button>
        <button type="button" data-q="How do I download a student's CV?">Download CV</button>
        <button type="button" data-q="How do I export analytics reports?">Export reports</button>
        <button type="button" data-q="Why is the interview action failing?">Interview errors</button>
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
