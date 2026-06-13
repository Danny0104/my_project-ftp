/**
 * Student settings workspace
 */
(function () {
    'use strict';

    var THEME_KEY = 'ftp_theme';
    var REDUCE_KEY = 'spReduceMotion';
    var CONTRAST_KEY = 'spHighContrast';
    var PREFS_KEY = 'sp_user_prefs';
    var FONT_KEY = 'spFontSize';
    var KEYBOARD_KEY = 'spKeyboardNav';

    var LEGACY_HASH_MAP = {
        profile: 'section-personal',
        account: 'section-academic',
        documents: 'section-documents',
        internship: 'section-internship',
    };

    function scrollToSection(sectionId) {
        var el = document.getElementById(sectionId);
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        history.replaceState(null, '', '#' + sectionId);
    }

    function initNav() {
        var nav = document.getElementById('spSetNav');
        var panels = document.querySelectorAll('.sp-set-panel');
        var mobileBtn = document.getElementById('spSetMobileNav');
        var mobileLabel = document.getElementById('spSetMobileNavLabel');
        if (!nav) return;

        function activate(panelId, btn) {
            panels.forEach(function (p) {
                p.classList.toggle('is-active', p.getAttribute('data-panel') === panelId);
            });
            nav.querySelectorAll('.sp-set-nav-btn').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
            });
            if (mobileLabel && btn) {
                mobileLabel.textContent = btn.textContent.trim();
            }
            nav.classList.remove('is-open');
            history.replaceState(null, '', '#' + panelId);
        }

        nav.querySelectorAll('.sp-set-nav-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(btn.getAttribute('data-panel'), btn);
            });
        });

        if (mobileBtn) {
            mobileBtn.addEventListener('click', function () {
                nav.classList.toggle('is-open');
            });
        }

        var hash = window.location.hash.replace('#', '');
        if (hash) {
            var matchBtn = nav.querySelector('[data-panel="' + hash + '"]');
            if (matchBtn) {
                activate(hash, matchBtn);
            }
        }
    }

    function initSectionJump() {
        var legacyMap = LEGACY_HASH_MAP;
        document.querySelectorAll('.sp-profile-jump, .sp-profile-checklist__link, .sp-profile-section-nav a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var href = link.getAttribute('href') || '';
                if (href.indexOf('#') !== 0) return;
                var id = href.slice(1);
                var target = document.getElementById(id);
                if (!target) return;
                e.preventDefault();
                scrollToSection(id);
            });
        });

        var hash = window.location.hash.replace('#', '');
        if (!hash) return;
        if (hash === 'verification' && !document.querySelector('.sp-set--verification')) {
            window.location.href = window.location.pathname.replace(/\/edit-profile\/?$/, '/verification').replace(/\/profile\/student\/?$/, '/profile/verification') || '/profile/verification';
            return;
        }
        var targetId = legacyMap[hash] || hash;
        var target = document.getElementById(targetId);
        if (target) {
            setTimeout(function () { scrollToSection(targetId); }, 100);
        }
    }

    function initBioCounter() {
        var input = document.getElementById('spBioInput');
        var countEl = document.getElementById('spBioCount');
        if (!input || !countEl) return;
        function update() {
            countEl.textContent = String((input.value || '').length);
        }
        input.addEventListener('input', update);
        update();
    }

    function initSkillsEditor() {
        var editor = document.getElementById('spSkillsEditor');
        var tagsEl = document.getElementById('spSkillsTags');
        var hidden = document.getElementById('spSkillsHidden');
        var addInput = document.getElementById('spSkillsAddInput');
        var addBtn = document.getElementById('spSkillsAddBtn');
        if (!editor || !tagsEl || !hidden) return;

        function getSkills() {
            return Array.prototype.map.call(tagsEl.querySelectorAll('.sp-skills-tag'), function (tag) {
                return tag.childNodes[0].textContent.trim();
            }).filter(Boolean);
        }

        function syncHidden() {
            hidden.value = getSkills().join(', ');
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function addSkill(raw) {
            var skill = (raw || '').trim();
            if (!skill) return;
            var existing = getSkills().map(function (s) { return s.toLowerCase(); });
            if (existing.indexOf(skill.toLowerCase()) !== -1) return;

            var tag = document.createElement('span');
            tag.className = 'sp-skills-tag';
            tag.appendChild(document.createTextNode(skill));
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.setAttribute('aria-label', 'Remove');
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', function () {
                tag.remove();
                syncHidden();
            });
            tag.appendChild(removeBtn);
            tagsEl.appendChild(tag);
            syncHidden();
        }

        tagsEl.querySelectorAll('.sp-skills-tag button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.parentElement.remove();
                syncHidden();
            });
        });

        function tryAddFromInput() {
            addSkill(addInput.value);
            addInput.value = '';
        }

        if (addBtn) addBtn.addEventListener('click', tryAddFromInput);
        if (addInput) {
            addInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    tryAddFromInput();
                }
            });
        }

        document.querySelectorAll('.sp-skills-suggest').forEach(function (btn) {
            btn.addEventListener('click', function () {
                addSkill(btn.getAttribute('data-skill'));
            });
        });
    }

    function initDirtyState() {
        var form = document.getElementById('spSettingsForm');
        var bar = document.getElementById('spSetSavebar');
        var status = document.getElementById('spSetSaveStatus');
        if (!form || !bar) return;

        function setDirty() {
            bar.classList.add('is-visible');
            if (status) {
                status.textContent = 'Unsaved changes';
                status.classList.add('is-dirty');
            }
        }

        form.addEventListener('input', setDirty);
        form.addEventListener('change', setDirty);
    }

    var ID_MAX_BYTES = 5 * 1024 * 1024;
    var ID_ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'pdf'];

    function getCsrfFromWidget(widget) {
        return {
            param: widget.getAttribute('data-csrf-param') || '_csrf-frontend',
            token: widget.getAttribute('data-csrf-token') || '',
        };
    }

    function getFileExtension(name) {
        var parts = (name || '').split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    function validateIdFile(file) {
        if (!file) {
            return 'No file selected.';
        }
        var ext = getFileExtension(file.name);
        if (ID_ALLOWED_EXT.indexOf(ext) === -1) {
            return 'Invalid format. Accepted: JPG, PNG, PDF.';
        }
        if (file.size > ID_MAX_BYTES) {
            return 'File too large. Maximum size is 5MB.';
        }
        return null;
    }

    function showIdAlert(widget, message, type) {
        var alert = document.getElementById('spIdVerifyAlert');
        if (!alert) return;
        alert.textContent = message;
        alert.className = 'sp-id-verify-alert sp-id-verify-alert--' + (type || 'error');
        alert.hidden = false;
    }

    function hideIdAlert() {
        var alert = document.getElementById('spIdVerifyAlert');
        if (alert) alert.hidden = true;
    }

    function updateProfilePercent(percent) {
        var headerPct = document.querySelector('.sp-set-strength span');
        var bar = document.querySelector('.sp-set-strength-bar span');
        var sidebarPct = document.querySelector('.ftp-profile-ring-label');
        if (headerPct) {
            headerPct.textContent = 'Profile ' + percent + '%';
        }
        if (bar) {
            bar.style.width = percent + '%';
            bar.parentElement.setAttribute('aria-valuenow', String(percent));
        }
        if (sidebarPct) {
            sidebarPct.textContent = 'Profile ' + percent + '% complete';
        }
        var widget = document.getElementById('spIdVerifyWidget');
        if (widget) {
            widget.setAttribute('data-profile-percent', String(percent));
        }
    }

    function renderIdStatusCard(data) {
        var card = document.getElementById('spIdVerifyStatusCard');
        if (!card) return;

        if (!data.hasDocument) {
            card.hidden = true;
            return;
        }

        var statusKey = data.statusKey || 'pending_review';
        var tone = statusKey === 'auto_verified' || statusKey === 'verified' || data.verificationStatus === 'approved'
            ? 'success'
            : statusKey === 'rejected' ? 'danger' : 'warning';

        card.hidden = false;
        card.className = 'sp-id-verify-status-card sp-id-verify-status-card--' + tone;

        var html = '';
        if (statusKey === 'fraud') {
            html = '<div class="sp-id-verify-status-card__main">' +
                '<i class="fas fa-triangle-exclamation sp-id-verify-status-card__icon sp-id-verify-status-card__icon--warning"></i>' +
                '<div><strong>Potential Duplicate Identity Detected</strong>' +
                '<span class="sp-id-verify-status-card__reason">' + (data.fraudReason || 'Sent for manual review.') + '</span></div></div>';
        } else if (statusKey === 'auto_verified') {
            html = '<div class="sp-id-verify-status-card__main">' +
                '<i class="fas fa-circle-check sp-id-verify-status-card__icon sp-id-verify-status-card__icon--success"></i>' +
                '<div><strong>Student Identity Matched</strong>' +
                '<span class="sp-id-verify-status-card__time">Profile Verified' +
                (data.verifiedAt ? ' · ' + data.verifiedAt : '') + '</span></div></div>';
        } else if (data.verificationStatus === 'approved') {
            html = '<div class="sp-id-verify-status-card__main">' +
                '<i class="fas fa-circle-check sp-id-verify-status-card__icon sp-id-verify-status-card__icon--success"></i>' +
                '<div><strong>Profile Verified</strong>' +
                (data.verifiedAt ? '<span class="sp-id-verify-status-card__time">Verified ' + data.verifiedAt + '</span>' : '') +
                '</div></div>';
        } else if (data.verificationStatus === 'rejected') {
            html = '<div class="sp-id-verify-status-card__main">' +
                '<i class="fas fa-circle-xmark sp-id-verify-status-card__icon sp-id-verify-status-card__icon--danger"></i>' +
                '<div><strong>Verification Failed</strong>' +
                (data.rejectionReason ? '<span class="sp-id-verify-status-card__reason">' + data.rejectionReason + '</span>' : '') +
                (data.verificationScore != null ? '<span class="sp-id-verify-status-card__time">Verification score: ' + data.verificationScore + '%</span>' : '') +
                '</div></div>';
        } else {
            html = '<div class="sp-id-verify-status-card__main">' +
                '<i class="fas fa-clock sp-id-verify-status-card__icon sp-id-verify-status-card__icon--warning"></i>' +
                '<div><strong>Manual Review Required</strong>' +
                (data.reviewReason || data.rejectionReason
                    ? '<span class="sp-id-verify-status-card__reason">' + (data.reviewReason || data.rejectionReason) + '</span>'
                    : '<span class="sp-id-verify-status-card__reason">Some fields need manual confirmation.</span>') +
                (data.verificationScore != null ? '<span class="sp-id-verify-status-card__time">Verification score: ' + data.verificationScore + '%</span>' : '') +
                (data.uploadedAt ? '<span class="sp-id-verify-status-card__time">Submitted ' + data.uploadedAt + '</span>' : '') +
                '</div></div>';
        }
        card.innerHTML = html;
    }

    function renderVerificationScore(data) {
        var scoreBlock = document.getElementById('spVcScoreBlock');
        var scoreValue = document.getElementById('spVcScoreValue');
        var scoreFill = document.getElementById('spVcScoreFill');
        if (data.verificationScore == null) {
            if (scoreBlock) scoreBlock.hidden = true;
            return;
        }

        if (scoreBlock) scoreBlock.hidden = false;
        if (scoreValue) scoreValue.textContent = data.verificationScore + '%';
        if (scoreFill) scoreFill.style.width = Math.min(100, Math.max(0, data.verificationScore)) + '%';
    }

    function renderVerificationStatusBadge(data) {
        var badge = document.getElementById('spVcStatusBadge');
        if (!badge) return;

        var statusKey = data.statusKey || 'none';
        var label = 'Not Verified';
        var klass = 'sp-vc-badge--muted';

        if (statusKey === 'auto_verified' || statusKey === 'verified' || data.verificationStatus === 'approved') {
            label = 'Verified';
            klass = 'sp-vc-badge--success';
        } else if (statusKey === 'rejected' || data.verificationStatus === 'rejected') {
            label = 'Rejected';
            klass = 'sp-vc-badge--danger';
        } else if (statusKey === 'pending_review' || statusKey === 'fraud' || data.hasDocument) {
            label = 'Pending Review';
            klass = 'sp-vc-badge--warning';
        }

        badge.textContent = label;
        badge.className = 'sp-vc-badge ' + klass;
    }

    function renderVerificationRequirements(data) {
        var list = document.getElementById('spVcRequirements');
        if (!list || !data.checks) return;

        var hasResult = data.hasDocument && data.verificationScore != null;
        list.querySelectorAll('[data-req]').forEach(function (item) {
            var key = item.getAttribute('data-req');
            var pass = !!data.checks[key];
            var icon = item.querySelector('.sp-vc-requirements__icon');
            item.classList.remove('is-pass', 'is-fail', 'is-pending');
            if (!hasResult) {
                item.classList.add('is-pending');
                if (icon) icon.textContent = '○';
                return;
            }
            item.classList.add(pass ? 'is-pass' : 'is-fail');
            if (icon) icon.textContent = pass ? '✓' : '✗';
        });
    }

    function renderVerificationTimeline(data) {
        var card = document.getElementById('spVcTimelineCard');
        var list = document.getElementById('spVcTimeline');
        if (!list) return;

        var events = data.timeline || [];
        if (card) card.hidden = events.length === 0;
        if (events.length === 0) {
            list.innerHTML = '';
            return;
        }

        list.innerHTML = events.map(function (event) {
            var timeHtml = event.at ? '<span class="sp-vc-timeline__time">' + event.at + '</span>' : '';
            var metaHtml = event.meta ? '<span class="sp-vc-timeline__meta">' + event.meta + '</span>' : '';
            return '<li class="sp-vc-timeline__item sp-vc-timeline__item--' + event.type + '">' +
                '<span class="sp-vc-timeline__dot"></span>' +
                '<div><strong>' + event.label + '</strong>' + timeHtml + metaHtml + '</div></li>';
        }).join('');
    }

    function renderComparisonTable(data) {
        var section = document.getElementById('spVcCompareSection');
        var body = document.getElementById('spVcCompareBody');
        var rows = data.comparisonRows || [];
        if (!body) return;

        if (section) section.hidden = rows.length === 0;
        if (rows.length === 0) {
            body.innerHTML = '';
            return;
        }

        body.innerHTML = rows.map(function (row) {
            var resultClass = row.result === 'match' ? 'is-match' : (row.result === 'partial' ? 'is-partial' : 'is-mismatch');
            var icon = row.result === 'match' ? '✓' : (row.result === 'partial' ? '⚠' : '✗');
            return '<tr class="' + resultClass + '" data-field="' + row.key + '">' +
                '<th scope="row">' + row.label + '</th>' +
                '<td>' + (row.profile || '—') + '</td>' +
                '<td>' + (row.ocr || '—') + '</td>' +
                '<td><span class="sp-vc-result-badge sp-vc-result-badge--' + row.result + '">' + icon + ' ' + row.resultLabel + '</span></td>' +
                '</tr>';
        }).join('');
    }

    function renderOcrPanel(data) {
        var section = document.getElementById('spVcOcrSection');
        var extracted = data.extracted || {};
        var hasData = data.hasDocument && data.verificationScore != null;

        if (section) section.hidden = !hasData;

        var confidenceEl = document.getElementById('spVcOcrConfidence');
        if (confidenceEl && data.ocrConfidence != null) {
            confidenceEl.textContent = data.ocrConfidence + '%';
        }

        var lowBadge = document.getElementById('spVcOcrLowBadge');
        if (data.lowOcrConfidence) {
            if (!lowBadge && confidenceEl && confidenceEl.parentNode) {
                lowBadge = document.createElement('span');
                lowBadge.id = 'spVcOcrLowBadge';
                lowBadge.className = 'sp-vc-ocr-low-badge';
                lowBadge.textContent = 'Low OCR Confidence — manual review';
                confidenceEl.parentNode.appendChild(lowBadge);
            } else if (lowBadge) {
                lowBadge.hidden = false;
            }
        } else if (lowBadge) {
            lowBadge.hidden = true;
        }

        var rawDetails = document.getElementById('spVcOcrRawDetails');
        var rawPre = document.getElementById('spVcOcrRawText');
        var rawText = data.rawOcrText || '';
        if (rawDetails) rawDetails.hidden = rawText === '';
        if (rawPre) rawPre.textContent = rawText || '—';

        var fields = [
            ['spIdExtractedName', extracted.name],
            ['spIdExtractedReg', extracted.registrationNumber],
            ['spIdExtractedUniversity', extracted.university],
            ['spIdExtractedProgram', extracted.program],
            ['spIdExtractedField', extracted.fieldOfStudy],
            ['spIdExtractedExpiry', extracted.expiryDate],
        ];
        fields.forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.textContent = pair[1] || '—';
        });
    }

    function renderVerificationCenter(data) {
        renderVerificationScore(data);
        renderVerificationStatusBadge(data);
        renderVerificationRequirements(data);
        renderVerificationTimeline(data);
        renderComparisonTable(data);
        renderOcrPanel(data);
    }

    function renderIdVerificationDetails(data) {
        var feedback = document.getElementById('spIdVerifyFeedback');
        if (feedback) {
            var lines = data.fieldFeedback || [];
            feedback.innerHTML = lines.map(function (line) {
                return '<li class="sp-id-verify-feedback__item">⚠ ' + line + '</li>';
            }).join('');
            feedback.hidden = lines.length === 0;
        }

        renderVerificationCenter(data);
    }

    function renderIdUploadedCard(data) {
        var card = document.getElementById('spIdUploadedCard');
        var preview = document.getElementById('spIdServerPreview');
        var badge = document.getElementById('spIdStatusBadge');
        var downloadBtn = document.getElementById('spIdDownloadBtn');
        var dropzone = document.getElementById('spIdDropzone');
        if (!card || !preview) return;

        if (!data.hasDocument) {
            card.hidden = true;
            if (dropzone) dropzone.classList.remove('sp-id-dropzone--compact');
            return;
        }

        card.hidden = false;
        if (dropzone) dropzone.classList.add('sp-id-dropzone--compact');

        if (badge) {
            badge.textContent = data.verificationLabel || 'Pending verification';
            badge.className = 'sp-id-verify-status sp-id-verify-status--' + (
                data.verificationStatus === 'approved' ? 'success'
                    : data.verificationStatus === 'rejected' ? 'danger'
                        : 'warning'
            );
        }

        if (downloadBtn && data.downloadUrl) {
            downloadBtn.href = data.downloadUrl;
        }

        if (data.isImage && data.previewUrl) {
            preview.innerHTML = '<img src="' + data.previewUrl + '" alt="Student ID preview" class="sp-id-verify-img" id="spIdPreviewImg">';
        } else {
            preview.innerHTML = '<div class="sp-id-verify-pdf" id="spIdPreviewPdf">' +
                '<i class="fas fa-file-pdf"></i>' +
                '<span>' + (data.filename || 'student-id.pdf') + '</span></div>';
        }
    }

    function resetIdLocalPreview() {
        var input = document.getElementById('idDocumentInput');
        var localPreview = document.getElementById('spIdLocalPreview');
        var idle = document.getElementById('spIdDropzoneIdle');
        var progress = document.getElementById('spIdProgress');
        if (input) input.value = '';
        if (localPreview) localPreview.hidden = true;
        if (idle) idle.hidden = false;
        if (progress) progress.hidden = true;
    }

    function showIdLocalPreview(file) {
        var localPreview = document.getElementById('spIdLocalPreview');
        var idle = document.getElementById('spIdDropzoneIdle');
        var content = document.getElementById('spIdPreviewContent');
        var nameEl = document.getElementById('spIdFileName');
        if (!localPreview || !content) return;

        hideIdAlert();
        idle.hidden = true;
        localPreview.hidden = false;

        if (nameEl) nameEl.textContent = file.name;

        var ext = getFileExtension(file.name);
        if (ext === 'pdf') {
            content.innerHTML = '<div class="sp-id-verify-pdf"><i class="fas fa-file-pdf"></i><span>' + file.name + '</span></div>';
        } else {
            var reader = new FileReader();
            reader.onload = function (e) {
                content.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="sp-id-verify-img">';
            };
            reader.readAsDataURL(file);
        }
    }

    function uploadIdDocument(file, widget) {
        var error = validateIdFile(file);
        if (error) {
            showIdAlert(widget, error, 'error');
            return;
        }

        if (widget.getAttribute('data-profile-ready') !== '1') {
            var editUrl = widget.getAttribute('data-edit-profile-url') || '/profile/edit-profile#section-academic';
            showIdAlert(widget, 'Save your profile before verifying. Complete name, registration number, university, program, and field of study.', 'error');
            return;
        }

        var csrf = getCsrfFromWidget(widget);
        var formData = new FormData();
        formData.append('id_document', file);
        if (csrf.token) {
            formData.append(csrf.param, csrf.token);
        }

        var progress = document.getElementById('spIdProgress');
        var progressBar = document.getElementById('spIdProgressBar');
        var progressLabel = document.getElementById('spIdProgressLabel');
        var localPreview = document.getElementById('spIdLocalPreview');
        var uploadBtn = document.getElementById('spIdUploadBtn');

        if (progress) progress.hidden = false;
        if (localPreview) localPreview.hidden = true;
        if (uploadBtn) uploadBtn.disabled = true;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', widget.getAttribute('data-upload-url'), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable || !progressBar) return;
            var pct = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = pct + '%';
            if (progressLabel) progressLabel.textContent = 'Uploading & verifying… ' + pct + '%';
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;

            if (uploadBtn) uploadBtn.disabled = false;
            if (progress) progress.hidden = true;
            if (progressBar) progressBar.style.width = '0%';

            var response;
            try {
                response = JSON.parse(xhr.responseText || '{}');
            } catch (err) {
                showIdAlert(widget, 'Upload failed. Unexpected server response.', 'error');
                resetIdLocalPreview();
                return;
            }

            if (xhr.status === 0) {
                showIdAlert(widget, 'Network error. Check your connection and try again.', 'error');
                resetIdLocalPreview();
                return;
            }

            if (!response.success) {
                showIdAlert(widget, response.message || 'Upload failed. Please try again.', 'error');
                resetIdLocalPreview();
                return;
            }

            hideIdAlert();
            resetIdLocalPreview();
            widget.setAttribute('data-has-document', '1');

            if (response.data) {
                renderIdStatusCard(response.data);
                renderIdVerificationDetails(response.data);
                renderIdUploadedCard(response.data);
                if (typeof response.data.profilePercent === 'number') {
                    updateProfilePercent(response.data.profilePercent);
                }
            }

            if (window.ftpShowToast) {
                window.ftpShowToast(response.message || 'Student ID uploaded successfully.');
            }
        };

        xhr.onerror = function () {
            if (uploadBtn) uploadBtn.disabled = false;
            if (progress) progress.hidden = true;
            showIdAlert(widget, 'Network error. Check your connection and try again.', 'error');
            resetIdLocalPreview();
        };

        xhr.send(formData);
    }

    function removeIdDocument(widget) {
        if (!window.confirm('Remove your student ID document?')) return;

        var csrf = getCsrfFromWidget(widget);
        var formData = new FormData();
        if (csrf.token) {
            formData.append(csrf.param, csrf.token);
        }

        fetch(widget.getAttribute('data-remove-url'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(function (res) { return res.json(); })
            .then(function (response) {
                if (!response.success) {
                    showIdAlert(widget, response.message || 'Could not remove document.', 'error');
                    return;
                }
                widget.setAttribute('data-has-document', '0');
                if (response.data) {
                    renderIdStatusCard(response.data);
                    renderIdVerificationDetails(response.data);
                    renderIdUploadedCard(response.data);
                    if (typeof response.data.profilePercent === 'number') {
                        updateProfilePercent(response.data.profilePercent);
                    }
                }
                resetIdLocalPreview();
                if (window.ftpShowToast) {
                    window.ftpShowToast(response.message || 'Student ID document removed.');
                }
            })
            .catch(function () {
                showIdAlert(widget, 'Network error. Could not remove document.', 'error');
            });
    }

    function initIdDropzone() {
        var widget = document.getElementById('spIdVerifyWidget');
        var zone = document.getElementById('spIdDropzone');
        var input = document.getElementById('idDocumentInput');
        var browseBtn = document.getElementById('spIdBrowseBtn');
        var uploadBtn = document.getElementById('spIdUploadBtn');
        var clearBtn = document.getElementById('spIdClearBtn');
        var replaceBtn = document.getElementById('spIdReplaceBtn');
        var removeBtn = document.getElementById('spIdRemoveBtn');
        if (!widget || !zone || !input) return;

        var pendingFile = null;

        function handleSelectedFile(file) {
            if (widget.getAttribute('data-profile-ready') !== '1') {
                showIdAlert(widget, 'Save your profile before uploading a student ID.', 'error');
                return;
            }
            var error = validateIdFile(file);
            if (error) {
                showIdAlert(widget, error, 'error');
                resetIdLocalPreview();
                pendingFile = null;
                return;
            }
            pendingFile = file;
            showIdLocalPreview(file);
            uploadIdDocument(file, widget);
        }

        if (browseBtn) {
            browseBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                input.click();
            });
        }

        zone.addEventListener('click', function (e) {
            if (e.target === input || e.target.closest('button')) return;
            if (!pendingFile && widget.getAttribute('data-has-document') !== '1') {
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) {
                handleSelectedFile(e.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                handleSelectedFile(input.files[0]);
            }
        });

        if (uploadBtn) {
            uploadBtn.addEventListener('click', function () {
                if (pendingFile) uploadIdDocument(pendingFile, widget);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                pendingFile = null;
                resetIdLocalPreview();
                hideIdAlert();
            });
        }

        if (replaceBtn) {
            replaceBtn.addEventListener('click', function () {
                pendingFile = null;
                resetIdLocalPreview();
                hideIdAlert();
                input.click();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                removeIdDocument(widget);
            });
        }
    }

    function initCvDropzone() {
        var zone = document.getElementById('spCvDropzone');
        var input = document.getElementById('spCvFileInput');
        var nameEl = document.getElementById('spCvFileName');
        if (!zone || !input) return;

        zone.addEventListener('click', function (e) {
            if (e.target === input) return;
            input.click();
        });

        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                showFile(e.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) showFile(input.files[0]);
        });

        function showFile(file) {
            if (nameEl) {
                nameEl.textContent = 'Selected: ' + file.name;
                nameEl.hidden = false;
            }
            if (window.ftpShowToast) {
                window.ftpShowToast('CV selected — save to upload');
            }
            var bar = document.getElementById('spSetSavebar');
            if (bar) bar.classList.add('is-visible');
        }
    }

    function initStoredPrefs() {
        var root = document.querySelector('.sp-set--prefs-only');
        if (!root) return;

        var prefs = {};
        try {
            prefs = JSON.parse(localStorage.getItem(PREFS_KEY) || '{}');
        } catch (e) {
            prefs = {};
        }

        root.querySelectorAll('[data-pref]').forEach(function (input) {
            var key = input.getAttribute('data-pref');
            if (Object.prototype.hasOwnProperty.call(prefs, key)) {
                input.checked = !!prefs[key];
            }
            input.addEventListener('change', function () {
                prefs[key] = input.checked;
                localStorage.setItem(PREFS_KEY, JSON.stringify(prefs));
                if (window.ftpShowToast) {
                    window.ftpShowToast('Preference saved');
                }
            });
        });
    }

    function applyFontSize(size) {
        document.body.classList.remove('sp-font-large', 'sp-font-xlarge');
        if (size === 'large') {
            document.body.classList.add('sp-font-large');
        } else if (size === 'xlarge') {
            document.body.classList.add('sp-font-xlarge');
        }
    }

    function initFontSize() {
        var select = document.getElementById('spSetFontSize');
        if (!select) return;

        var stored = localStorage.getItem(FONT_KEY) || 'default';
        if (select.querySelector('option[value="' + stored + '"]')) {
            select.value = stored;
        }
        applyFontSize(select.value);

        select.addEventListener('change', function () {
            localStorage.setItem(FONT_KEY, select.value);
            applyFontSize(select.value);
            if (window.ftpShowToast) {
                window.ftpShowToast('Font size updated');
            }
        });
    }

    function initKeyboardNav() {
        var cb = document.getElementById('spSetKeyboardNav');
        if (!cb) return;

        cb.checked = localStorage.getItem(KEYBOARD_KEY) === '1';
        document.body.classList.toggle('sp-keyboard-nav', cb.checked);

        cb.addEventListener('change', function () {
            localStorage.setItem(KEYBOARD_KEY, cb.checked ? '1' : '0');
            document.body.classList.toggle('sp-keyboard-nav', cb.checked);
        });
    }

    function initClearCache() {
        var btn = document.getElementById('spSetClearCacheBtn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!window.confirm('Clear all local preferences stored in this browser?')) {
                return;
            }

            localStorage.removeItem(PREFS_KEY);
            localStorage.removeItem(FONT_KEY);
            localStorage.removeItem(KEYBOARD_KEY);
            localStorage.removeItem(REDUCE_KEY);
            localStorage.removeItem(CONTRAST_KEY);
            window.location.reload();
        });
    }

    function initThemeCards() {
        var cards = document.querySelectorAll('.sp-set-theme-card');
        var stored = localStorage.getItem(THEME_KEY) || 'light';

        function apply(theme) {
            if (window.ftThemeBridge) {
                window.ftThemeBridge.apply(theme, THEME_KEY);
            }
            localStorage.setItem(THEME_KEY, theme);
            cards.forEach(function (c) {
                c.classList.toggle('is-active', c.getAttribute('data-theme') === theme);
            });
            var icon = document.getElementById('ftpThemeIcon');
            var dark = window.ftThemeBridge
                ? window.ftThemeBridge.isDark()
                : (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches));
            if (icon) {
                icon.classList.toggle('fa-sun', dark);
                icon.classList.toggle('fa-moon', !dark);
            }
        }

        apply(stored);

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                apply(card.getAttribute('data-theme'));
            });
        });
    }

    function initAccessibilityToggles() {
        var reduce = document.getElementById('spSetReduceMotion');
        var contrast = document.getElementById('spSetHighContrast');

        if (reduce) {
            reduce.checked = localStorage.getItem(REDUCE_KEY) === '1';
            reduce.addEventListener('change', function () {
                document.body.classList.toggle('sp-reduce-motion', reduce.checked);
                localStorage.setItem(REDUCE_KEY, reduce.checked ? '1' : '0');
            });
            if (reduce.checked) document.body.classList.add('sp-reduce-motion');
        }

        if (contrast) {
            contrast.checked = localStorage.getItem(CONTRAST_KEY) === '1';
            contrast.addEventListener('change', function () {
                document.body.classList.toggle('sp-high-contrast', contrast.checked);
                localStorage.setItem(CONTRAST_KEY, contrast.checked ? '1' : '0');
            });
            if (contrast.checked) document.body.classList.add('sp-high-contrast');
        }
    }

    function syncVerificationSummary() {
        var uniSelect = document.getElementById('student-university');
        var otherInput = document.getElementById('other-university-input');
        var regInput = document.querySelector('[name="Student[student_id]"]');
        var uniDisplay = document.getElementById('spVerifyUniversityDisplay');
        var regDisplay = document.getElementById('spVerifyStudentIdDisplay');
        if (!uniDisplay || !regDisplay) return;

        var university = '';
        if (uniSelect && uniSelect.value === 'Other (Please specify)' && otherInput) {
            university = otherInput.value.trim();
        } else if (uniSelect) {
            university = uniSelect.value.trim();
        }
        uniDisplay.textContent = university || '—';
        regDisplay.textContent = (regInput && regInput.value.trim()) || '—';
    }

    function initUniversityOther() {
        if (typeof jQuery === 'undefined') return;
        function syncUniversityFields() {
            var select = jQuery('#student-university');
            var otherField = jQuery('#other-university-field');
            var otherInput = jQuery('#other-university-input');
            var selected = select.val();
            var isOther = selected === 'Other (Please specify)';
            if (isOther) {
                otherField.show();
                otherInput.prop('required', true).prop('disabled', false);
                select.prop('disabled', true);
            } else {
                otherField.hide();
                otherInput.prop('required', false).prop('disabled', true);
                select.prop('disabled', false);
            }
            syncVerificationSummary();
        }

        jQuery('#student-university').on('change', syncUniversityFields);
        jQuery('#other-university-input').on('input', syncVerificationSummary);
        jQuery('[name="Student[student_id]"]').on('input', syncVerificationSummary);
        syncUniversityFields();
    }

    function initVerifyAccountLink() {
        var btn = document.getElementById('spVerifyEditAccountBtn');
        var widget = document.getElementById('spIdVerifyWidget');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (document.getElementById('section-academic')) {
                scrollToSection('section-academic');
                return;
            }
            var editUrl = widget && widget.getAttribute('data-edit-profile-url');
            if (editUrl) {
                window.location.href = editUrl;
            }
        });
    }

    function initProfileFormGuard() {
        var form = document.getElementById('spSettingsForm');
        if (!form) return;

        form.addEventListener('submit', function () {
            syncVerificationSummary();
            var select = document.getElementById('student-university');
            var otherInput = document.getElementById('other-university-input');
            if (select && select.disabled && otherInput && !otherInput.value.trim()) {
                if (window.ftpShowToast) {
                    window.ftpShowToast('Please specify your university name.');
                }
            }
        });
    }

    function initGlobalAccessibilityState() {
        var fs = localStorage.getItem(FONT_KEY);
        if (fs) {
            applyFontSize(fs);
        }
        if (localStorage.getItem(KEYBOARD_KEY) === '1') {
            document.body.classList.add('sp-keyboard-nav');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initGlobalAccessibilityState();
        initNav();
        initSectionJump();
        initDirtyState();
        initBioCounter();
        initSkillsEditor();
        initCvDropzone();
        initIdDropzone();
        initThemeCards();
        initAccessibilityToggles();
        initUniversityOther();
        initVerifyAccountLink();
        initProfileFormGuard();
        initStoredPrefs();
        initFontSize();
        initKeyboardNav();
        initClearCache();
    });
})();
