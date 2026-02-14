(function($){
  // Use Material Icons (outlined). We render a <span> with the icon name; the font will display the glyph.
  function icon(name){ return '<span class="material-icons-outlined" aria-hidden="true" style="font-size:16px;line-height:1;">'+name+'</span>'; }
  function detectControlColumnIndex($table){
    var idx = -1;
    var $ths = $table.find('thead th');
    $ths.each(function(i){
      var th = this;
      if (th.classList.contains('dt-control')) { idx = i; return false; }
    });
    if (idx === -1 && $table.hasClass('dt-control-first')) idx = 0;
    return idx;
  }
  function detectPrimaryColumnIndex($table){
    var idx = -1;
    var $ths = $table.find('thead th');
    $ths.each(function(i){
      var th = this;
      if (th.dataset && (th.dataset.dtPrimary === 'true')) { idx = i; return false; }
      if (th.classList.contains('dt-primary')) { idx = i; return false; }
    });
    return idx;
  }
  function buildColumnDefs($table){
    var defs = [];
    var controlIdx = detectControlColumnIndex($table);
    if (controlIdx >= 0) {
      defs.push({ className: 'dtr-control', orderable: false, targets: controlIdx });
    }
    var primaryIdx = detectPrimaryColumnIndex($table);
    if (primaryIdx >= 0) {
      defs.push({ responsivePriority: 1, targets: primaryIdx });
      var count = $table.find('thead th').length;
      var others = [];
      for (var i=0;i<count;i++){ if (i !== primaryIdx) others.push(i); }
      if (others.length) defs.push({ responsivePriority: 1e4, targets: others });
    }
    return defs;
  }
  function defaultExportFormat(data, row, column, node){
    var raw = (node && (node.textContent || node.innerText)) ? (node.textContent || node.innerText) : (''+data);
    return raw.replace(/[★☆]/g,'').replace(/\s+/g,' ').trim();
  }
  // Accept a function that returns the desired export title/filename for the current table
  function buildButtons(getTitle){
    var exportColsNoExport = function (idx, data, node) {
      // Exclude dt-control and any header with .no-export (for copy/excel/pdf)
      return $(node).is(':visible') && !$(node).hasClass('dt-control') && !$(node).hasClass('no-export');
    };
    // For print: exclude control, Id column (if exists), and Action/Actions column
    var exportColsForPrint = function (idx, data, node) {
      var $th = $(node);
      // Fallback: if node isn't a TH, try to find the header from the table context
      if (!$th.length || node.nodeName !== 'TH') {
        var $tbl = $th.closest('table');
        if ($tbl.length) {
          var $hdr = $tbl.find('thead th').eq(idx);
          if ($hdr.length) { $th = $hdr; }
        }
      }
      var visible = $th.is(':visible');
      var isControl = $th.hasClass('dt-control');
      var text = ($th.text() || '').trim().toLowerCase();
      var isIdHeader = (text === 'id' || text === '#') || $th.hasClass('col-id') || $th.hasClass('dt-id') || ($th.data('dtId') === true);
      var isActionHeader = (text === 'action' || text === 'actions') || $th.hasClass('col-action') || $th.hasClass('col-actions') || $th.hasClass('dt-actions');
      var flaggedNoPrint = $th.hasClass('no-print') || $th.data('noPrint') === true || $th.hasClass('no-export');
      return visible && !isControl && !isIdHeader && !isActionHeader && !flaggedNoPrint;
    };
    // Helper to resolve title/filename lazily
    function titleValue(){ try { return (typeof getTitle === 'function' ? getTitle() : (document.title || 'export')); } catch(e){ return document.title || 'export'; } }

    // Clear/Search reset button - placed first
    var clearButton = {
      text: icon('filter_alt_off'),
      className: 'btn btn-sm btn-outline-secondary',
      titleAttr: 'Clear filters',
      action: function (e, dtApi, node, config) {
        try {
          // Clear global search
          dtApi.search('');
          // Clear column searches
          dtApi.columns().search('');
          // Redraw (server-side will request fresh data)
          dtApi.ajax ? dtApi.ajax.reload() : dtApi.draw();
        } catch (err) {
          console && console.debug && console.debug('Clear button error', err);
        }
      }
    };

    return [
      clearButton,
      {
        extend: 'copyHtml5',
        // Material icon: content_copy
        text: icon('content_copy'),
        className: 'btn btn-sm btn-outline-secondary',
        titleAttr: 'Copy',
        title: function(){ return titleValue(); },
        exportOptions: { columns: exportColsNoExport, format: { body: defaultExportFormat } },
        customizeData: function(data){
          try {
            var t = titleValue();
            if (!t) return;
            var colCount = (data.header && data.header.length) ? data.header.length : (data.body && data.body[0] ? data.body[0].length : 1);
            var titleRow = [t];
            while (titleRow.length < colCount) titleRow.push('');
            data.body.unshift([]); // spacer
            data.body.unshift(titleRow);
          } catch (e) { /* no-op */ }
        }
      },
      {
        extend: 'excelHtml5',
        // Material icon: article (spreadsheet-like)
        text: icon('article'),
        className: 'btn btn-sm btn-outline-secondary',
        titleAttr: 'Excel',
        title: function(){ return titleValue(); },
        filename: function(){ return titleValue(); },
        exportOptions: { columns: exportColsNoExport, format: { body: defaultExportFormat } }
      },
      {
        extend: 'pdfHtml5',
        // Material icon: picture_as_pdf
        text: icon('picture_as_pdf'),
        className: 'btn btn-sm btn-outline-secondary',
        titleAttr: 'PDF',
        title: function(){ return titleValue(); },
        filename: function(){ return titleValue(); },
        exportOptions: { columns: exportColsNoExport, format: { body: defaultExportFormat } },
        orientation: 'portrait',
        pageSize: 'A4'
      },
      {
        extend: 'print',
        // Material icon: print
        text: icon('print'),
        className: 'btn btn-sm btn-outline-secondary',
        titleAttr: 'Print',
        title: function(){ return titleValue(); },
        exportOptions: { columns: exportColsForPrint, stripHtml: false },
        customize: function (win) {
          try {
            // Add base href to resolve relative URLs
            var base = win.document.createElement('base');
            base.href = window.location.origin + '/';
            win.document.head.prepend(base);
            // Copy stylesheets from parent document to print window
            var parentLinks = window.document.querySelectorAll('link[rel="stylesheet"]');
            parentLinks.forEach(function(link){
              var l = win.document.createElement('link');
              l.rel = 'stylesheet';
              l.href = link.href; // absolute or relative preserved
              win.document.head.appendChild(l);
            });
            // Inject basic print styles to preserve look & images
            var style = win.document.createElement('style');
            style.textContent = `
              body { background: #fff !important; color: #000 !important; }
              table { width: 100% !important; border-collapse: collapse !important; }
              img { max-width: 96px !important; height: auto !important; }
              .rounded-circle { border-radius: 50% !important; }
              .table-bordered td, .table-bordered th { border: 1px solid #dee2e6 !important; }
              .text-center { text-align: center !important; }
            `;
            win.document.head.appendChild(style);
          } catch (e) { /* no-op */ }
        }
      }
    ];
  }
  function buildResponsive($table){
    var controlIdx = detectControlColumnIndex($table);
    if (controlIdx >= 0) {
      return { details: { type: 'column', target: controlIdx } };
    }
    return true;
  }
  function buildDom(){
    // Length on left; Buttons and Search on right
    return "<'row g-2 align-items-center'<'col-sm-auto'l><'col text-end'Bf>>" + 'rtip';
  }

  $(function(){
    $('table.datatable').each(function(){
      var $table = $(this);
      // Per-table dynamic title/filename pulled from the card header title; fallback to document.title
      var getTitle = function(){
        try {
          var $card = $table.closest('.card');
          var title = $.trim($card.find('.card-header .card-title').first().text());
          return title || (document.title || 'export');
        } catch (e) {
          return document.title || 'export';
        }
      };
      var options = {
        responsive: buildResponsive($table),
        columnDefs: buildColumnDefs($table),
        order: [],
        dom: buildDom(),
        buttons: buildButtons(getTitle),
        drawCallback: function(){ if (window.feather) { try { feather.replace(); } catch(e){} } }
      };

      // If table declares an AJAX URL, enable server-side processing
      var ajaxUrl = $table.data('ajaxUrl') || $table.attr('data-ajax-url');
      if (ajaxUrl) {
        options.processing = true;
        options.serverSide = true;
        options.ajax = {
          url: ajaxUrl,
          type: 'GET',
          dataType: 'json',
          headers: { 'Accept': 'application/json' }
        };
      }

      $table.DataTable(options);

      // When responsive moves columns into the child details row, ensure feather icons get replaced there as well
      $table.on('responsive-display.dt', function (e, datatable, row, showHide, update) {
        try {
          if (window.feather) { feather.replace(); }
        } catch (err) { /* no-op */ }
      });
      // No feather.replace() here: using drawCallback for dynamic rows
    });
  });
})(jQuery);
