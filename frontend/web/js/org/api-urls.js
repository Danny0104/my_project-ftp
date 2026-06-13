/**
 * Resolves Yii-generated API URLs for organization portal AJAX calls.
 */
(function (global) {
  'use strict';

  function readConfig() {
    return global.ftOrgApi || {};
  }

  function resolveUrl(key, fallbackPath) {
    var cfg = readConfig();
    if (cfg[key]) {
      return cfg[key];
    }
    if (fallbackPath && global.yii && typeof yii.getUrl === 'function') {
      try {
        return yii.getUrl(fallbackPath);
      } catch (e) { /* ignore */ }
    }
    return fallbackPath || '';
  }

  global.ftOrgApiResolve = resolveUrl;
})(typeof window !== 'undefined' ? window : this);
