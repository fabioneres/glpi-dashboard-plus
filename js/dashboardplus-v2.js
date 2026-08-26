(function() {
   'use strict';

   var maxConcurrentRequests = 4;
   var activeRequests = 0;
   var requestQueue = [];
   var layoutEditing = false;
   var draggedCard = null;

   function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, function(character) {
         return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
         }[character];
      });
   }

   function loadWidget(card, page) {
      if (!card || card.dataset.dashboardplusLoaded === '1') {
         return;
      }

      var url = card.dataset.url;
      if (!url) {
         return;
      }

      card.dataset.dashboardplusLoaded = '1';
      enqueueWidgetLoad(card, page, url);
   }

   function enqueueWidgetLoad(card, page, url) {
      requestQueue.push({
         card: card,
         page: page,
         url: url
      });
      drainWidgetQueue();
   }

   function drainWidgetQueue() {
      if (activeRequests >= maxConcurrentRequests || !requestQueue.length) {
         return;
      }

      var item = requestQueue.shift();
      activeRequests++;
      fetchWidget(item.card, item.page, item.url);
   }

   function loadVisibleWidgets(page) {
      var cards = Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-widget]'));
      cards.forEach(function(card) {
         if (card.offsetParent !== null) {
            loadWidget(card, page);
         }
      });
   }

   function fetchWidget(card, page, url) {
      card.setAttribute('aria-busy', 'true');
      var error = page ? page.dataset.dashboardplusError : 'Widget indisponível';

      fetch(url, {
         credentials: 'same-origin',
         headers: {
            'X-Requested-With': 'XMLHttpRequest'
         }
      })
         .then(function(response) {
            return response.json();
         })
         .then(function(payload) {
            card.classList.remove('dashboardplus-loading');
            card.innerHTML = payload && payload.html ? payload.html : '';
            card.classList.toggle('dashboardplus-card-empty', !!card.querySelector('.dashboardplus-empty'));
            ensureLayoutControls(card, page);
         })
         .catch(function() {
            card.classList.remove('dashboardplus-loading');
            card.innerHTML = '<div class="dashboardplus-widget-error"><i class="ti ti-alert-triangle"></i><span>' + escapeHtml(error) + '</span></div>';
            ensureLayoutControls(card, page);
         })
         .finally(function() {
            activeRequests = Math.max(0, activeRequests - 1);
            card.removeAttribute('aria-busy');
            drainWidgetQueue();
         });
   }

   function reloadWidget(card, page) {
      if (!card || !card.dataset.url) {
         return;
      }

      var loading = page ? page.dataset.dashboardplusLoading : 'Carregando';
      card.dataset.dashboardplusLoaded = '0';
      card.classList.add('dashboardplus-loading');
      card.innerHTML = '<div class="dashboardplus-loader"><i class="ti ti-loader-2"></i><span>' + escapeHtml(loading) + '</span></div>';
      loadWidget(card, page);
   }

   function clamp(value, min, max) {
      return Math.max(min, Math.min(max, value));
   }

   function applyCardSize(card, width, height) {
      width = clamp(parseInt(width || '3', 10), 1, 12);
      height = clamp(parseInt(height || '2', 10), 1, 8);

      card.dataset.width = String(width);
      card.dataset.height = String(height);
      card.style.gridColumn = 'span ' + width;
      card.style.gridRow = 'span ' + height;
      card.style.setProperty('--dp-widget-rows', height);
   }

   function ensureLayoutControls(card, page) {
      if (!page || page.dataset.dashboardplusCanLayout !== '1' || card.querySelector('.dashboardplus-layout-controls')) {
         return;
      }

      var title = card.dataset.dashboardplusWidget || 'widget';
      card.insertAdjacentHTML('beforeend', [
         '<div class="dashboardplus-layout-controls" aria-hidden="true">',
         '<span class="dashboardplus-layout-drag" draggable="true" title="Arrastar widget"><i class="ti ti-arrows-move"></i></span>',
         '<span class="dashboardplus-layout-size" title="Redimensionar widget"><i class="ti ti-arrows-diagonal-2"></i></span>',
         '<span class="dashboardplus-layout-badge">' + escapeHtml(title) + '</span>',
         '</div>'
      ].join(''));

      var dragHandle = card.querySelector('.dashboardplus-layout-drag');
      var resizeHandle = card.querySelector('.dashboardplus-layout-size');

      card.addEventListener('dragover', function(event) {
         var grid = card.parentNode;
         if (!layoutEditing || !draggedCard || draggedCard === card || !grid || draggedCard.parentNode !== grid) {
            return;
         }

         event.preventDefault();
         var rect = card.getBoundingClientRect();
         var after = event.clientY > rect.top + (rect.height / 2)
            || (Math.abs(event.clientY - (rect.top + rect.height / 2)) < 20 && event.clientX > rect.left + (rect.width / 2));

         if (after && card.nextSibling !== draggedCard) {
            grid.insertBefore(draggedCard, card.nextSibling);
         } else if (!after && card.previousSibling !== draggedCard) {
            grid.insertBefore(draggedCard, card);
         }
      });

      if (dragHandle) {
         dragHandle.addEventListener('dragstart', function(event) {
            if (!layoutEditing) {
               event.preventDefault();
               return;
            }

            draggedCard = card;
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.dashboardplusWidget || '');
         });

         dragHandle.addEventListener('dragend', function() {
            if (draggedCard) {
               draggedCard.classList.remove('is-dragging');
            }
            draggedCard = null;
         });
      }

      if (resizeHandle) {
         resizeHandle.addEventListener('pointerdown', function(event) {
            if (!layoutEditing) {
               return;
            }

            event.preventDefault();
            startResize(card, event);
         });
      }
   }

   function startResize(card, event) {
      var grid = card.parentNode;
      if (!grid) {
         return;
      }

      var gridStyle = window.getComputedStyle(grid);
      var columnGap = parseFloat(gridStyle.columnGap || gridStyle.gap || '0') || 0;
      var rowGap = parseFloat(gridStyle.rowGap || gridStyle.gap || '0') || 0;
      var rowHeight = parseFloat(gridStyle.gridAutoRows || '64') || 64;
      var columnWidth = Math.max(24, (grid.clientWidth - (columnGap * 11)) / 12);
      var rowStep = Math.max(24, rowHeight + rowGap);
      var startX = event.clientX;
      var startY = event.clientY;
      var startWidth = parseInt(card.dataset.width || '3', 10);
      var startHeight = parseInt(card.dataset.height || '2', 10);

      card.classList.add('is-resizing');

      function move(moveEvent) {
         var nextWidth = startWidth + Math.round((moveEvent.clientX - startX) / columnWidth);
         var nextHeight = startHeight + Math.round((moveEvent.clientY - startY) / rowStep);
         applyCardSize(card, nextWidth, nextHeight);
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

   function setLayoutEditing(page, enabled) {
      layoutEditing = enabled;
      page.classList.toggle('dashboardplus-layout-editing', enabled);

      Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-widget]')).forEach(function(card) {
         ensureLayoutControls(card, page);
      });

      Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-layout-edit]')).forEach(function(button) {
         button.hidden = enabled;
      });
      Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-layout-save], [data-dashboardplus-layout-cancel]')).forEach(function(button) {
         button.hidden = !enabled;
      });

      var status = page.querySelector('[data-dashboardplus-layout-status]');
      if (status) {
         status.textContent = enabled ? 'Modo de edição ativo' : '';
      }
   }

   function serializeLayout(page) {
      return Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-widget]')).map(function(card) {
         return {
            key: card.dataset.dashboardplusWidget || '',
            width: parseInt(card.dataset.width || '3', 10),
            height: parseInt(card.dataset.height || '2', 10)
         };
      });
   }

   function saveLayout(page) {
      var url = page.dataset.dashboardplusLayoutUrl;
      var token = page.dataset.dashboardplusCsrf;
      var status = page.querySelector('[data-dashboardplus-layout-status]');
      if (!url || !token) {
         return;
      }

      if (status) {
         status.textContent = 'Salvando layout...';
      }

      var body = new URLSearchParams();
      body.append('_glpi_csrf_token', token);
      body.append('layout', JSON.stringify(serializeLayout(page)));

      fetch(url, {
         method: 'POST',
         credentials: 'same-origin',
         headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
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
               page.dataset.dashboardplusCsrf = payload.token;
            }
            if (status) {
               status.textContent = payload.message || 'Layout salvo';
            }
            setLayoutEditing(page, false);
         })
         .catch(function(error) {
            if (status) {
               status.textContent = error.message || 'Falha ao salvar layout';
            }
         });
   }

   function bootLayoutEditor(page) {
      if (page.dataset.dashboardplusCanLayout !== '1') {
         return;
      }

      Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-widget]')).forEach(function(card) {
         applyCardSize(card, card.dataset.width, card.dataset.height);
         ensureLayoutControls(card, page);
      });

      page.dataset.dashboardplusLayoutBooted = '1';
   }

   function bootDashboard(page) {
      if (page.dataset.dashboardplusBooted === '1') {
         return;
      }

      page.dataset.dashboardplusBooted = '1';
      var cards = Array.prototype.slice.call(page.querySelectorAll('[data-dashboardplus-widget]'));
      if (!cards.length) {
         return;
      }

      if ('IntersectionObserver' in window) {
         var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
               if (entry.isIntersecting) {
                  loadWidget(entry.target, page);
                  observer.unobserve(entry.target);
               }
            });
         }, {
            rootMargin: '120px'
         });

         cards.forEach(function(card) {
            observer.observe(card);
         });
      } else {
         loadVisibleWidgets(page);
      }

      loadVisibleWidgets(page);
      bootLayoutEditor(page);

      bootTabs(page, function() {
         loadVisibleWidgets(page);
      });

      var advancedToggle = page.querySelector('.dashboardplus-advanced-toggle');
      var advancedFilters = page.querySelector('.dashboardplus-filter-advanced');
      if (advancedToggle && advancedFilters) {
         advancedToggle.addEventListener('click', function() {
            var opened = advancedFilters.classList.toggle('is-open');
            advancedToggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
         });
      }

      var refresh = parseInt(page.dataset.dashboardplusRefresh || '0', 10);
      if (refresh >= 30) {
         window.setInterval(function() {
            if (layoutEditing) {
               return;
            }
            cards.forEach(function(card) {
               reloadWidget(card, page);
            });
         }, refresh * 1000);
      }
   }

   function bootTabs(container, afterChange) {
      if (container.dataset.dashboardplusTabsBooted === '1') {
         return;
      }

      container.dataset.dashboardplusTabsBooted = '1';
      Array.prototype.slice.call(container.querySelectorAll('[data-dashboardplus-tab]')).forEach(function(tab) {
         tab.addEventListener('click', function() {
            var key = tab.dataset.dashboardplusTab;
            Array.prototype.slice.call(container.querySelectorAll('[data-dashboardplus-tab]')).forEach(function(item) {
               var active = item === tab;
               item.classList.toggle('active', active);
               item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            Array.prototype.slice.call(container.querySelectorAll('[data-dashboardplus-panel]')).forEach(function(panel) {
               panel.classList.toggle('active', panel.dataset.dashboardplusPanel === key);
            });
            if (typeof afterChange === 'function') {
               afterChange();
            }
         });
      });
   }

   function bootApp() {
      var page = document.querySelector('.dashboardplus-page');
      if (page) {
         bootDashboard(page);
      }

      Array.prototype.slice.call(document.querySelectorAll('.dashboardplus-about')).forEach(function(about) {
         bootTabs(about);
      });

      Array.prototype.slice.call(document.querySelectorAll('[data-dashboardplus-color-picker]')).forEach(function(picker) {
         var text = picker.parentNode ? picker.parentNode.querySelector('input[type="text"]') : null;
         if (!text) {
            return;
         }

         picker.addEventListener('input', function() {
            text.value = picker.value;
         });

         text.addEventListener('input', function() {
            if (/^#[0-9a-fA-F]{6}$/.test(text.value)) {
               picker.value = text.value;
            }
         });
      });
   }

   document.addEventListener('click', function(event) {
      var page = document.querySelector('.dashboardplus-page');
      if (!page) {
         return;
      }

      var edit = event.target.closest ? event.target.closest('[data-dashboardplus-layout-edit]') : null;
      var save = event.target.closest ? event.target.closest('[data-dashboardplus-layout-save]') : null;
      var cancel = event.target.closest ? event.target.closest('[data-dashboardplus-layout-cancel]') : null;

      if (edit) {
         event.preventDefault();
         bootLayoutEditor(page);
         setLayoutEditing(page, true);
      } else if (save) {
         event.preventDefault();
         bootLayoutEditor(page);
         saveLayout(page);
      } else if (cancel) {
         event.preventDefault();
         window.location.reload();
      }
   });

   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bootApp);
   } else {
      bootApp();
   }
})();
