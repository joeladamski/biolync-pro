(function () {
  'use strict';
  function initialize() {
    document.querySelectorAll('textarea[data-rich-text]').forEach(function (field) {
      if (field.dataset.editorReady || !field.getClientRects().length) return;
      if (!window.Jodit) return; // The original textarea remains usable if an asset fails.
      var wasRequired = field.required;
      try {
        var editor = Jodit.make(field, {
          theme: 'dark', language: 'en', height: 360, minHeight: 240,
          toolbarAdaptive: false, toolbarSticky: false,
          buttons: ['undo', 'redo', '|', 'paragraph', 'bold', 'italic', 'underline', 'strikethrough', '|', 'ul', 'ol', 'outdent', 'indent', 'align', '|', 'link', 'image', 'table', 'hr', 'brush', '|', 'eraser', 'source', 'fullsize'],
          controls: { paragraph: { list: { p: 'Paragraph', h2: 'Heading 2', h3: 'Heading 3', h4: 'Heading 4', blockquote: 'Quote', pre: 'Code' } } },
          beautifyHTML: false,
          sourceEditor: 'area', // No remote Ace editor dependency.
          uploader: { insertImageAsBase64URI: false },
          disablePlugins: ['file', 'video', 'powered-by-jodit'],
          showCharsCounter: true, showWordsCounter: true, showXPathInStatusbar: false,
          askBeforePasteHTML: false, askBeforePasteFromWord: false,
          defaultActionOnPaste: 'insert_clear_html',
          events: { change: function (value) { field.value = value; } }
        });
        field.dataset.editorReady = 'true';
        field.required = false; // Hidden textarea must not block native form validation.
        editor.editor.setAttribute('aria-label', field.dataset.richTextLabel || 'Content editor');
        field.form.addEventListener('submit', function (event) {
          var source = editor.container.querySelector('.jodit-source__mirror');
          if (editor.getRealMode() === Jodit.MODE_SOURCE && source) {
            // Capture immediately, even before the source plugin's debounced sync.
            field.value = source.value;
          } else {
            editor.synchronizeValues();
            field.value = editor.value;
          }
          var text = new DOMParser().parseFromString(field.value, 'text/html').body.textContent.trim();
          if (wasRequired && !text && !/<(img|hr|table)\b/i.test(field.value)) {
            event.preventDefault();
            editor.focus();
            field.form.querySelector('[data-editor-error]')?.remove();
            var notice = document.createElement('p');
            notice.dataset.editorError = 'true';
            notice.className = 'text-danger';
            notice.setAttribute('role', 'alert');
            notice.textContent = 'Enter page content before saving.';
            editor.container.after(notice);
          }
        }, true);
      } catch (error) {
        console.error('Unable to initialize content editor', error);
        field.required = wasRequired;
      }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
  document.addEventListener('toggle', initialize, true);
  window.addEventListener('hashchange', function () { requestAnimationFrame(initialize); });
  document.addEventListener('click', function () { requestAnimationFrame(initialize); });
})();
