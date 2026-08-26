(function() {
   'use strict';

   var editing = false;
   var draggedCard = null;

   function page() {
      return document.querySelector('.dashboardplus-page');
   }

   function cards(root) {
      return Array.prototype.slice.call(root.querySelectorAll('[data-dashboardplus-widget]'));
   }

   function clamp(value, min, max) {
      return Math.max(min, Math.min(max, value));
   }

   function applySize(card, width, height) {
      width = clamp(parseInt(width || '3', 10), 1, 12);
      height = clamp(parseInt(height || '2', 10), 1, 8);
      card.dataset.width = String(width);
      card.dataset.height = String(height);
      card.style.gridColumn = 'span ' + width;
      card.style.gridRow = 'span ' + height;
      card.style.setProperty('--dp-widget-rows', height);
   }

   function ensureControls(card) {
      if (!card || card.querySelector('.dashboardplus-layout-controls')) {
         return;
      }

      card.insertAdjacentHTML('beforeend', [
         '<div class="dashboardplus-layout-controls" aria-hidden="true">',
         '<span class="dashboardplus-layout-drag" draggable="true" title="Arrastar widget"><i class="ti ti-arrows-move"></i></span>',
         '<span class="dashboardplus-layout-size dashboardplus-layout-size-x" data-dashboardplus-resize="x" title="Ajustar largura"></span>',
         '<span class="dashboardplus-layout-size dashboardplus-layout-size-y" data-dashboardplus-resize="y" title="Ajustar altura"></span>',
         '<span class="dashboardplus-layout-size dashboardplus-layout-size-xy" data-dashboardplus-resize="xy" title="Ajustar largura e altura"><i class="ti ti-arrows-diagonal-2"></i></span>',
         '</div>'
      ].join(''));

      var drag = card.querySelector('.dashboardplus-layout-drag');
      var resizeHandles = Array.prototype.slice.call(card.querySelectorAll('[data-dashboardplus-resize]'));

      card.addEventListener('dragover', function(event) {
         var grid = card.parentNode;
         if (!editing || !draggedCard || draggedCard === card || !grid || draggedCard.parentNode !== grid) {
            return;
         }
         event.preventDefault();

         var rect = card.getBoundingClientRect();
         var after = event.clientY > rect.top + (rect.height / 2);
         grid.insertBefore(draggedCard, after ? card.nextSibling : card);
      });

      drag.addEventListener('dragstart', function(event) {
         if (!editing) {
            event.preventDefault();
            return;
         }
         draggedCard = card;
         card.classList.add('is-dragging');
         event.dataTransfer.effectAllowed = 'move';
         event.dataTransfer.setData('text/plain', card.dataset.dashboardplusWidget || '');
      });

      drag.addEventListener('dragend', function() {
         if (draggedCard) {
            draggedCard.classList.remove('is-dragging');
         }
         draggedCard = null;
      });

      resizeHandles.forEach(function(resize) {
         resize.addEventListener('pointerdown', function(event) {
            if (!editing) {
               return;
            }
            event.preventDefault();
            startResize(card, event, resize.dataset.dashboardplusResize || 'xy');
         });
      });
   }

   function startResize(card, event, axis) {
      var grid = card.parentNode;
      if (!grid) {
         return;
      }

      var style = window.getComputedStyle(grid);
      var gap = parseFloat(style.columnGap || style.gap || '0') || 0;
      var rowGap = parseFloat(style.rowGap || style.gap || '0') || 0;
      var rowHeight = parseFloat(style.gridAutoRows || '64') || 64;
      var columnWidth = Math.max(24, (grid.clientWidth - (gap * 11)) / 12);
      var rowStep = Math.max(24, rowHeight + rowGap);
      var startX = event.clientX;
      var startY = event.clientY;
      var startWidth = parseInt(card.dataset.width || '3', 10);
      var startHeight = parseInt(card.dataset.height || '2', 10);

      card.classList.add('is-resizing');

      function move(moveEvent) {
         var nextWidth = startWidth;
         var nextHeight = startHeight;

         if (axis.indexOf('x') !== -1) {
            nextWidth = startWidth + Math.round((moveEvent.clientX - startX) / columnWidth);
         }

         if (axis.indexOf('y') !== -1) {
            nextHeight = startHeight + Math.round((moveEvent.clientY - startY) / rowStep);
         }

         applySize(
            card,
            nextWidth,
            nextHeight
         );
      }

      function stop() {
         card.classList.remove('is-resizing');
         document.removeEventListener('pointermove', move);
         document.removeEventListener('pointerup', stop);
         document.removeEventListener('pointercancel', stop);
      }

      document.addEventListener('pointermove', move);
      document.addEventListener('pointerup', stop);
      document.addEventListener('pointercancel', stop);
   }

   function setButtons(root, enabled) {
      var edit = root.querySelector('[data-dashboardplus-layout-edit]');
      var save = root.querySelector('[data-dashboardplus-layout-save]');
      var cancel = root.querySelector('[data-dashboardplus-layout-cancel]');
      var status = root.querySelector('[data-dashboardplus-layout-status]');

      if (edit) {
         edit.hidden = enabled;
      }
      if (save) {
         save.hidden = !enabled;
      }
      if (cancel) {
         cancel.hidden = !enabled;
      }
      if (status) {
         status.textContent = enabled ? 'Modo de edição ativo' : '';
      }
   }

   function setEditing(enabled) {
      var root = page();
      if (!root || root.dataset.dashboardplusCanLayout !== '1') {
         return;
      }

      editing = enabled;
      root.classList.toggle('dashboardplus-layout-editing', enabled);
      cards(root).forEach(function(card) {
         applySize(card, card.dataset.width, card.dataset.height);
         ensureControls(card);
      });
      setButtons(root, enabled);
   }

   function serialize(root) {
      return cards(root).map(function(card) {
         return {
            key: card.dataset.dashboardplusWidget || '',
            width: parseInt(card.dataset.width || '3', 10),
            height: parseInt(card.dataset.height || '2', 10)
         };
      });
   }

   function saveLayout() {
      var root = page();
      if (!root || !root.dataset.dashboardplusLayoutUrl || !root.dataset.dashboardplusCsrf) {
         return;
      }

      var status = root.querySelector('[data-dashboardplus-layout-status]');
      if (status) {
         status.textContent = 'Salvando layout...';
      }

      var layoutJson = JSON.stringify(serialize(root));
      var body = new URLSearchParams();
      body.append('_glpi_csrf_token', root.dataset.dashboardplusCsrf);
      body.append('layout', layoutJson);
      body.append('layout_b64', btoa(layoutJson));

      fetch(root.dataset.dashboardplusLayoutUrl, {
         method: 'POST',
         credentials: 'same-origin',
         headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Glpi-Csrf-Token': root.dataset.dashboardplusCsrf
         },
         body: body.toString()
      })
         .then(function(response) {
            return response.json().then(function(payload) {
               if (!response.ok || !payload || !payload.ok) {
                  throw new Error(payload && payload.message ? payload.message : 'Falha ao salvar layout');
               }
               return payload;
            });
         })
         .then(function(payload) {
            if (payload.token) {
               root.dataset.dashboardplusCsrf = payload.token;
            }
            if (status) {
               status.textContent = payload.message || 'Layout salvo';
            }
            setEditing(false);
         })
         .catch(function(error) {
            if (status) {
               status.textContent = error.message || 'Falha ao salvar layout';
            }
         });
   }

   document.addEventListener('click', function(event) {
      if (event.target.closest('[data-dashboardplus-layout-edit]')) {
         event.preventDefault();
         event.stopImmediatePropagation();
         setEditing(true);
      } else if (event.target.closest('[data-dashboardplus-layout-save]')) {
         event.preventDefault();
         event.stopImmediatePropagation();
         saveLayout();
      } else if (event.target.closest('[data-dashboardplus-layout-cancel]')) {
         event.preventDefault();
         event.stopImmediatePropagation();
         window.location.reload();
      }
   }, true);

   var observer = new MutationObserver(function() {
      var root = page();
      if (editing && root) {
         cards(root).forEach(ensureControls);
      }
   });

   if (document.documentElement) {
      observer.observe(document.documentElement, {
         childList: true,
         subtree: true
      });
   }
})();
