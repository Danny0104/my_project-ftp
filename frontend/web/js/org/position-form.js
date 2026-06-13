/**
 * Internship create/edit modal — academic field selector & form submit.
 */
(function (global) {
  'use strict';

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function notify(message, type) {
    if (typeof global.orgToast === 'function') {
      global.orgToast({
        title: 'Internship Opportunity',
        message: message,
        variant: type === 'success' ? 'success' : 'danger',
      });
      return;
    }
    alert(message);
  }

  function hideModal() {
    if (typeof bootstrap !== 'undefined') {
      ['orgPositionModal', 'addEditPositionModal'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        var inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
        inst.hide();
      });
      return;
    }
    if (global.jQuery) {
      global.jQuery('#orgPositionModal, #addEditPositionModal').modal('hide');
    }
  }

  function FieldSelector(root) {
    this.root = root;
    this.groups = {};
    this.fieldMap = {};
    this.selected = new Set();

    try {
      this.groups = JSON.parse(root.getAttribute('data-groups') || '{}');
    } catch (e) {
      this.groups = {};
    }

    try {
      JSON.parse(root.getAttribute('data-selected') || '[]').forEach(function (id) {
        this.selected.add(parseInt(id, 10));
      }, this);
    } catch (e2) { /* ignore */ }

    Object.keys(this.groups).forEach(function (groupName) {
      (this.groups[groupName] || []).forEach(function (field) {
        this.fieldMap[field.id] = { id: field.id, name: field.name, group: groupName };
      }, this);
    }, this);

    this.tagsEl = qs('.org-field-select__tags', root);
    this.panelEl = qs('.org-field-select__panel', root);
    this.inputsEl = qs('.org-field-select__inputs', root);
    this.countEl = qs('.org-field-select__count', root);
    this.searchEl = qs('.org-field-select__search', root);
    this.errorEl = qs('.org-field-select__error', root);

    this.renderPanel();
    this.renderTags();
    this.syncInputs();
    this.bindEvents();
  }

  FieldSelector.prototype.renderPanel = function () {
    var self = this;
    var query = (this.searchEl && this.searchEl.value || '').toLowerCase().trim();
    var html = '';
    var hasVisible = false;

    Object.keys(this.groups).forEach(function (groupName) {
      var fields = (self.groups[groupName] || []).filter(function (field) {
        if (!query) return true;
        return field.name.toLowerCase().indexOf(query) >= 0
          || groupName.toLowerCase().indexOf(query) >= 0
          || (field.category || '').toLowerCase().indexOf(query) >= 0;
      });
      if (!fields.length) return;
      hasVisible = true;

      var selectedInGroup = fields.filter(function (f) { return self.selected.has(f.id); }).length;
      html += '<div class="org-field-select__group" data-group="' + encodeURIComponent(groupName) + '">';
      html += '<div class="org-field-select__group-head" data-group-toggle>';
      html += '<div><h4 class="org-field-select__group-title">' + escapeHtml(groupName) + '</h4>';
      html += '<div class="org-field-select__group-meta">' + selectedInGroup + ' / ' + fields.length + ' selected</div></div>';
      html += '<button type="button" class="org-field-select__group-toggle" aria-label="Collapse group">▼</button>';
      html += '</div><div class="org-field-select__group-body">';
      fields.forEach(function (field) {
        var checked = self.selected.has(field.id) ? ' checked' : '';
        html += '<label class="org-field-select__option">';
        html += '<input type="checkbox" value="' + field.id + '"' + checked + ' data-field-option>';
        html += '<span class="org-field-select__option-label">' + escapeHtml(field.name) + '</span>';
        html += '</label>';
      });
      html += '</div></div>';
    });

    this.panelEl.innerHTML = hasVisible
      ? html
      : '<div class="org-field-select__empty">No fields match your search.</div>';
  };

  FieldSelector.prototype.renderTags = function () {
    var self = this;
    this.tagsEl.innerHTML = '';
    Array.from(this.selected).sort(function (a, b) {
      var nameA = self.fieldMap[a] ? self.fieldMap[a].name : '';
      var nameB = self.fieldMap[b] ? self.fieldMap[b].name : '';
      return nameA.localeCompare(nameB);
    }).forEach(function (id) {
      var field = self.fieldMap[id];
      if (!field) return;
      var tag = document.createElement('span');
      tag.className = 'org-field-select__tag';
      tag.innerHTML = '<span>' + escapeHtml(field.name) + '</span>'
        + '<button type="button" class="org-field-select__tag-remove" data-remove-id="' + id + '" aria-label="Remove ' + escapeHtml(field.name) + '">&times;</button>';
      self.tagsEl.appendChild(tag);
    });
    if (this.countEl) {
      this.countEl.textContent = this.selected.size + ' selected';
    }
  };

  FieldSelector.prototype.syncInputs = function () {
    var self = this;
    this.inputsEl.innerHTML = '';
    this.selected.forEach(function (id) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'Position[allowedFieldIds][]';
      input.value = String(id);
      self.inputsEl.appendChild(input);
    });
  };

  FieldSelector.prototype.setInvalid = function (message) {
    this.root.classList.add('is-invalid');
    if (this.errorEl) this.errorEl.textContent = message || 'Please select at least one academic field.';
  };

  FieldSelector.prototype.clearInvalid = function () {
    this.root.classList.remove('is-invalid');
    if (this.errorEl) this.errorEl.textContent = '';
  };

  FieldSelector.prototype.validate = function () {
    if (this.selected.size > 0) {
      this.clearInvalid();
      return true;
    }
    this.setInvalid('Please select at least one academic field.');
    this.panelEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    return false;
  };

  FieldSelector.prototype.toggleField = function (id, on) {
    if (on) this.selected.add(id);
    else this.selected.delete(id);
    this.renderTags();
    this.syncInputs();
    this.clearInvalid();
    this.renderPanel();
  };

  FieldSelector.prototype.selectAllVisible = function () {
    var self = this;
    qsa('[data-field-option]', this.panelEl).forEach(function (input) {
      self.selected.add(parseInt(input.value, 10));
    });
    this.renderTags();
    this.syncInputs();
    this.clearInvalid();
    this.renderPanel();
  };

  FieldSelector.prototype.clearAll = function () {
    this.selected.clear();
    this.renderTags();
    this.syncInputs();
    this.renderPanel();
  };

  FieldSelector.prototype.bindEvents = function () {
    var self = this;

    if (this.searchEl) {
      this.searchEl.addEventListener('input', function () {
        self.renderPanel();
      });
    }

    var selectAllBtn = qs('[data-field-select-all]', this.root);
    if (selectAllBtn) selectAllBtn.addEventListener('click', function () {
      Object.keys(self.groups).forEach(function (groupName) {
        (self.groups[groupName] || []).forEach(function (field) {
          self.selected.add(field.id);
        });
      });
      self.renderTags();
      self.syncInputs();
      self.clearInvalid();
      self.renderPanel();
    });

    var clearAllBtn = qs('[data-field-clear-all]', this.root);
    if (clearAllBtn) clearAllBtn.addEventListener('click', function () {
      self.clearAll();
    });

    this.panelEl.addEventListener('change', function (e) {
      var input = e.target.closest('[data-field-option]');
      if (!input) return;
      self.toggleField(parseInt(input.value, 10), input.checked);
    });

    this.panelEl.addEventListener('click', function (e) {
      var toggle = e.target.closest('[data-group-toggle]');
      if (!toggle) return;
      var group = toggle.closest('.org-field-select__group');
      if (group) group.classList.toggle('is-collapsed');
    });

    this.tagsEl.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove-id]');
      if (!btn) return;
      self.toggleField(parseInt(btn.getAttribute('data-remove-id'), 10), false);
    });
  };

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function bindPositionForm(root) {
    var form = qs('.position-form', root);
    if (!form || form.dataset.orgPositionFormBound === '1') return;
    form.dataset.orgPositionFormBound = '1';

    var fieldRoot = qs('[data-org-field-select]', root);
    var fieldSelector = null;
    if (fieldRoot && fieldRoot.dataset.orgFieldSelectInit !== '1') {
      fieldRoot.dataset.orgFieldSelectInit = '1';
      fieldSelector = new FieldSelector(fieldRoot);
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      if (fieldSelector && !fieldSelector.validate()) {
        notify('Please select at least one academic field.', 'error');
        return;
      }

      var submitBtn = qs('[type="submit"]', form);
      if (submitBtn) submitBtn.disabled = true;

      var body = new FormData(form);
      if (global.yii) {
        body.append(global.yii.getCsrfParam(), global.yii.getCsrfToken());
      }

      fetch(form.getAttribute('action'), {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      })
        .then(function (r) { return r.json(); })
        .then(function (response) {
          if (response && response.success) {
            hideModal();
            notify(response.message || 'Saved successfully', 'success');
            setTimeout(function () { global.location.reload(); }, 800);
            return;
          }
          var msg = (response && response.message) || 'An error occurred';
          if (response && response.errors && response.errors.allowedFieldIds) {
            msg = response.errors.allowedFieldIds[0];
            if (fieldSelector) fieldSelector.setInvalid(msg);
          }
          notify(msg, 'error');
        })
        .catch(function () {
          notify('An error occurred while saving. Please try again.', 'error');
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  function initPositionForm(root) {
    bindPositionForm(root || document);
  }

  global.orgInitPositionForm = initPositionForm;

  document.addEventListener('DOMContentLoaded', function () {
    initPositionForm(document.getElementById('orgPositionModal') || document);
  });
})(typeof window !== 'undefined' ? window : this);
