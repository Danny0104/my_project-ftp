<?php

namespace common\services;

use yii\helpers\Url;

/**
 * Platform-specific rule-based AI assistant (no external API required).
 */
class SupportAiService
{
    /**
     * @return array{answer:string,confidence:float,suggest_contact:bool,quick_replies:array<int,string>}
     */
    public function ask(string $question, string $role = 'student'): array
    {
        $q = strtolower(trim($question));
        if ($q === '') {
            return $role === 'organization' ? $this->organizationFallback() : $this->fallback();
        }

        $topics = $role === 'organization' ? $this->organizationTopics() : $this->studentTopics();
        $best = null;
        $bestScore = 0;

        foreach ($topics as $topic) {
            $score = 0;
            foreach ($topic['keywords'] as $keyword) {
                if (str_contains($q, $keyword)) {
                    $score += strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $topic;
            }
        }

        if ($best && $bestScore >= 3) {
            return [
                'answer' => $best['answer'],
                'confidence' => min(1.0, $bestScore / 20),
                'suggest_contact' => false,
                'quick_replies' => $best['quick_replies'] ?? [],
            ];
        }

        return $role === 'organization' ? $this->organizationFallback() : $this->fallback();
    }

    /**
     * @return array<int, array{keywords:array<int,string>,answer:string,quick_replies?:array<int,string>}>
     */
    private function studentTopics(): array
    {
        $applicationsUrl = Url::to(['/application/index']);
        $opportunitiesUrl = Url::to(['/position/index']);
        $profileUrl = Url::to(['/profile/student']);
        $messagesUrl = Url::to(['/message/index']);
        $notificationsUrl = Url::to(['/notification/index']);
        $interviewsUrl = Url::to(['/interview/index']);

        return [
            [
                'keywords' => ['apply', 'application', 'submit', 'internship', 'field training', 'position'],
                'answer' => "To apply for field training on this platform:\n\n"
                    . "1. Open **Opportunities** and search internships matching your field of study.\n"
                    . "2. Open a position details page and review requirements.\n"
                    . "3. Click **Apply** — your profile and CV must be complete.\n"
                    . "4. Upload or confirm your CV (PDF, DOC, or DOCX under 5MB).\n"
                    . "5. Track status under **Applications** — statuses include Submitted, Under Review, Shortlisted, Interview, Accepted, or Rejected.\n\n"
                    . "Quick links: [Applications]({$applicationsUrl}) · [Opportunities]({$opportunitiesUrl})",
                'quick_replies' => ['How do ATS statuses work?', 'CV upload requirements'],
            ],
            [
                'keywords' => ['ats', 'status', 'shortlist', 'reject', 'under review', 'kanban'],
                'answer' => "Application statuses on the Field Training Platform:\n\n"
                    . "• **Submitted** — your application was received.\n"
                    . "• **Under Review** — the organization is evaluating your profile.\n"
                    . "• **Shortlisted** — you are a strong candidate; watch for interview invites.\n"
                    . "• **Interview** — an interview has been scheduled or proposed.\n"
                    . "• **Accepted** — congratulations, you secured the placement.\n"
                    . "• **Rejected** — the organization chose other candidates.\n\n"
                    . "Organizations manage these on their ATS board. You will receive **Notifications** when your status changes.",
                'quick_replies' => ['Interview process', 'Why was I rejected?'],
            ],
            [
                'keywords' => ['interview', 'schedule', 'calendar', 'video'],
                'answer' => "Interviews on the platform:\n\n"
                    . "1. When shortlisted, organizations may schedule interviews from their panel.\n"
                    . "2. Open **Interviews** in your sidebar to see upcoming sessions.\n"
                    . "3. Each entry shows date, time, organization, and mode (in-person or online).\n"
                    . "4. You receive a **Notification** when an interview is created or updated.\n"
                    . "5. Use **Messages** to confirm details with the recruiter if needed.\n\n"
                    . "View your schedule: [Interviews]({$interviewsUrl})",
                'quick_replies' => ['Application status', 'Contact organization'],
            ],
            [
                'keywords' => ['notification', 'alert', 'bell', 'unread'],
                'answer' => "Notifications keep you updated on:\n\n"
                    . "• Application status changes\n"
                    . "• New interview invitations\n"
                    . "• Messages from organizations\n"
                    . "• Profile reminders and platform announcements\n"
                    . "• Support replies from administrators\n\n"
                    . "Open **Notifications** from the sidebar. Unread items show a badge. Click an alert to jump to the related page.",
                'quick_replies' => ['Messages vs notifications', 'Support help'],
            ],
            [
                'keywords' => ['message', 'chat', 'recruiter', 'conversation'],
                'answer' => "The **Messages** hub is for direct conversations with organizations about your applications:\n\n"
                    . "1. Open **Messages** from the sidebar.\n"
                    . "2. Select a conversation tied to an application or organization.\n"
                    . "3. Send text replies — recruiters see them in real time when online.\n"
                    . "4. Unread conversations are highlighted.\n\n"
                    . "This is separate from **Live Support** (admin help) and **Notifications** (system alerts).\n\n"
                    . "Open Messages: [Messages]({$messagesUrl})",
                'quick_replies' => ['Live support chat', 'Application help'],
            ],
            [
                'keywords' => ['profile', 'complete', 'cv', 'resume', 'upload', 'document'],
                'answer' => "Profile & CV requirements:\n\n"
                    . "• Complete university, field of study, and personal statement in **Profile**.\n"
                    . "• CV formats: **PDF, DOC, DOCX** — maximum **5MB**.\n"
                    . "• Files are stored securely and shared only with organizations you apply to.\n"
                    . "• Profile completion percentage shows on your dashboard — aim for 100% before applying.\n\n"
                    . "If upload fails: try another browser, check file size, ensure the extension is allowed, then retry from **Profile → CV**.\n\n"
                    . "Edit profile: [Profile]({$profileUrl})",
                'quick_replies' => ['CV upload failed', 'Apply for internships'],
            ],
            [
                'keywords' => ['organization', 'company', 'recruiter', 'employer'],
                'answer' => "Organizations on the platform:\n\n"
                    . "• Verified companies post internship opportunities.\n"
                    . "• They review applications on their ATS board and may message you directly.\n"
                    . "• You can view organization profiles from opportunity listings.\n"
                    . "• Only apply to roles matching your field of study.\n\n"
                    . "For disputes or verification issues, use **Request Help** below or **Live Support** to reach a platform administrator.",
                'quick_replies' => ['Application process', 'Contact admin'],
            ],
            [
                'keywords' => ['password', 'login', 'account', 'email', 'verify'],
                'answer' => "Account help:\n\n"
                    . "• Use **Forgot password** on the login page to reset via email.\n"
                    . "• Ensure your email is verified — check spam for the verification link.\n"
                    . "• Sessions expire after inactivity; log in again if prompted.\n"
                    . "• Update settings from **Profile → Settings**.\n\n"
                    . "For access issues only an admin can fix, submit a **Request Help** form or start **Live Support**.",
                'quick_replies' => ['Technical issues', 'Contact admin'],
            ],
            [
                'keywords' => ['error', 'bug', 'broken', 'technical', 'slow', '404'],
                'answer' => "Technical troubleshooting:\n\n"
                    . "1. Hard-refresh the page (Ctrl+F5).\n"
                    . "2. Clear browser cache or try Chrome/Edge/Firefox.\n"
                    . "3. Disable ad-blockers on this site.\n"
                    . "4. Check your internet connection.\n"
                    . "5. Note the page URL and steps to reproduce, then contact support.\n\n"
                    . "Platform admins can investigate server-side issues — use **Live Support** or **Send to Admin**.",
                'quick_replies' => ['CV upload failed', 'Open live chat'],
            ],
        ];
    }

    /**
     * @return array<int, array{keywords:array<int,string>,answer:string,quick_replies?:array<int,string>}>
     */
    private function organizationTopics(): array
    {
        $atsUrl = Url::to(['/application/index']);
        $interviewsUrl = Url::to(['/organization/interviews/index']);
        $studentsUrl = Url::to(['/organization/students/index']);
        $positionsUrl = Url::to(['/position/index']);
        $analyticsUrl = Url::to(['/organization/analytics/index']);
        $messagesUrl = Url::to(['/message/index']);
        $profileUrl = Url::to(['/profile/organization']);

        return [
            [
                'keywords' => ['shortlist', 'shortlisted', 'reject', 'rejected', 'review application', 'ats stage', 'kanban', 'pipeline', 'move candidate', 'next stage'],
                'answer' => "Managing candidates on your ATS board:\n\n"
                    . "1. Open **Applications (ATS)** from the sidebar.\n"
                    . "2. Review applicant cards or use the kanban view.\n"
                    . "3. **Shortlist** strong candidates by dragging to the Shortlisted column or using the status menu.\n"
                    . "4. **Reject** candidates who do not meet requirements — they are notified automatically.\n"
                    . "5. Valid stage flow: Submitted → Under Review → Shortlisted → Interview → Accepted/Rejected.\n\n"
                    . "If a stage transition fails, ensure the candidate is not withdrawn and refresh the board.\n\n"
                    . "Open ATS: [Applications]({$atsUrl})",
                'quick_replies' => ['Schedule interviews', 'Why stage transition fails?'],
            ],
            [
                'keywords' => ['cannot move', "can't move", 'stage transition', 'drag', 'stuck', 'won\'t move', 'fail to move'],
                'answer' => "If you cannot move a candidate to the next ATS stage:\n\n"
                    . "• Confirm your organization account has recruiter permissions.\n"
                    . "• Some stages require a prior status (shortlist before interview).\n"
                    . "• The student may have withdrawn — check application status.\n"
                    . "• Hard-refresh the ATS page (Ctrl+F5) and retry.\n"
                    . "• Avoid duplicate interview records when rescheduling.\n\n"
                    . "If the issue persists, contact admin with the candidate name and current stage.",
                'quick_replies' => ['How do I shortlist?', 'Interview scheduling help'],
            ],
            [
                'keywords' => ['schedule interview', 'reschedule', 'cancel interview', 'complete interview', 'interview score', 'evaluation', 'interview fail', 'interview error', 'interview action'],
                'answer' => "Interview management for organizations:\n\n"
                    . "1. **Shortlist** the candidate first on the ATS board.\n"
                    . "2. Open **Interviews** → **Schedule Interview**.\n"
                    . "3. Set date, time, mode (in-person/online), and location or meeting link.\n"
                    . "4. To **reschedule**, edit the existing interview record.\n"
                    . "5. Mark **Complete** after the session and add evaluation notes if available.\n"
                    . "6. Students receive **Notifications** for all interview updates.\n\n"
                    . "Common failures: missing datetime, candidate not shortlisted, or past dates.\n\n"
                    . "Manage interviews: [Interviews]({$interviewsUrl})",
                'quick_replies' => ['Download student CV', 'ATS shortlisting'],
            ],
            [
                'keywords' => ['download cv', 'cv download', 'resume', 'student cv', 'candidate cv', 'pdf'],
                'answer' => "To download a student's CV:\n\n"
                    . "1. Open the candidate from **Applications (ATS)** or **Students**.\n"
                    . "2. Click **Download CV** on their profile or application card.\n"
                    . "3. The file is served securely — only your organization can access applicants to your postings.\n\n"
                    . "If download fails: the student may not have uploaded a CV, or the file may be missing on the server. Ask the candidate to re-upload via their profile, or contact admin.\n\n"
                    . "Browse candidates: [Students]({$studentsUrl})",
                'quick_replies' => ['Compare candidates', 'Contact student'],
            ],
            [
                'keywords' => ['create internship', 'post internship', 'new position', 'edit internship', 'close internship', 'archive', 'visibility', 'publish'],
                'answer' => "Internship management:\n\n"
                    . "• **Create:** Internship Opportunities → Create → fill title, description, field, deadlines.\n"
                    . "• **Edit:** Open the listing and update details anytime.\n"
                    . "• **Close/Archive:** Toggle status to stop new applications; existing ATS data is preserved.\n"
                    . "• **Visibility:** Published listings appear to students matching the field of study.\n\n"
                    . "Manage listings: [Opportunities]({$positionsUrl})",
                'quick_replies' => ['Review applications', 'Analytics reports'],
            ],
            [
                'keywords' => ['export', 'analytics', 'report', 'csv', 'excel', 'metrics', 'dashboard', 'recruitment analytics'],
                'answer' => "Analytics & report exports:\n\n"
                    . "1. Open **Analytics & Reports** from the sidebar.\n"
                    . "2. Review metrics: total applications, stage conversion, and pipeline health.\n"
                    . "3. Click **Export** and select CSV or Excel.\n"
                    . "4. Choose your date range before downloading.\n\n"
                    . "Use exports for internal reporting and university coordination.\n\n"
                    . "Open analytics: [Analytics]({$analyticsUrl})",
                'quick_replies' => ['ATS pipeline help', 'Dashboard metrics'],
            ],
            [
                'keywords' => ['message student', 'contact student', 'conversation', 'messaging', 'notification trouble', 'unread'],
                'answer' => "Messaging & notifications:\n\n"
                    . "• **Messages:** Open Messages → select an application thread → reply to the student.\n"
                    . "• Conversations are tied to applications for context.\n"
                    . "• **Notifications:** Students receive alerts for status changes, interviews, and new messages.\n"
                    . "• If a student reports missing alerts, ask them to check Notifications and spam folders.\n\n"
                    . "Open Messages: [Messages]({$messagesUrl})",
                'quick_replies' => ['Shortlist candidate', 'Schedule interview'],
            ],
            [
                'keywords' => ['student profile', 'compare candidate', 'view profile', 'candidate management'],
                'answer' => "Candidate management:\n\n"
                    . "• **Students** section lists applicants across your postings.\n"
                    . "• Open a profile for university, field of study, CV, and application history.\n"
                    . "• Use ATS filters and kanban stages to compare candidates side by side.\n"
                    . "• Download CVs directly from profile or ATS cards.\n\n"
                    . "Browse students: [Students]({$studentsUrl})",
                'quick_replies' => ['Download CV', 'ATS stages'],
            ],
            [
                'keywords' => ['profile', 'password', 'verification', 'organization account', 'logo', 'team', 'settings'],
                'answer' => "Organization account:\n\n"
                    . "• Update company details under **Company Profile**.\n"
                    . "• Upload logo and contact information.\n"
                    . "• **Verification** is handled by platform admins — required for full publishing.\n"
                    . "• Change password under **Settings & Security**.\n"
                    . "• Manage team access from **Team Management** if enabled.\n\n"
                    . "Edit profile: [Organization Profile]({$profileUrl})",
                'quick_replies' => ['Technical issues', 'Contact admin'],
            ],
            [
                'keywords' => ['error', 'bug', 'broken', 'technical', 'slow', '404', 'dashboard error', 'ats problem'],
                'answer' => "Technical troubleshooting for organizations:\n\n"
                    . "1. Hard-refresh (Ctrl+F5) and clear browser cache.\n"
                    . "2. Try Chrome or Edge; disable ad-blockers.\n"
                    . "3. For ATS issues, note the candidate and stage when reporting.\n"
                    . "4. For CV download failures, verify the student uploaded a valid PDF/DOC.\n"
                    . "5. For dashboard errors, capture the URL and steps to reproduce.\n\n"
                    . "Contact admin via **Live Support** or **Send to Admin** on this page.",
                'quick_replies' => ['ATS stage help', 'Interview errors'],
            ],
        ];
    }

    /**
     * @return array{answer:string,confidence:float,suggest_contact:bool,quick_replies:array<int,string>}
     */
    private function fallback(): array
    {
        return [
            'answer' => "I'm not fully sure about that specific question on the Field Training Platform. "
                . "I can help with applications, ATS statuses, interviews, messages, notifications, profiles, CV uploads, and organization interactions.\n\n"
                . "**Would you like to contact an administrator?**",
            'confidence' => 0,
            'suggest_contact' => true,
            'quick_replies' => [
                'How do I apply?',
                'ATS status meanings',
                'CV upload requirements',
            ],
        ];
    }

    /**
     * @return array{answer:string,confidence:float,suggest_contact:bool,quick_replies:array<int,string>}
     */
    private function organizationFallback(): array
    {
        return [
            'answer' => "I'm not fully sure about that specific recruitment question. "
                . "I can help with internship management, ATS workflows, shortlisting, interviews, CV downloads, messaging, analytics exports, and organization account settings.\n\n"
                . "**Would you like to contact an administrator?**",
            'confidence' => 0,
            'suggest_contact' => true,
            'quick_replies' => [
                'How do I shortlist candidates?',
                'How do I schedule interviews?',
                'How do I export analytics reports?',
            ],
        ];
    }
}
