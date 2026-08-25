(function() {
   'use strict';

   var maxConcurrentRequests = 4;
   var activeRequests = 0;
   var requestQueue = [];

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
         })
         .catch(function() {
            card.classList.remove('dashboardplus-loading');
            card.innerHTML = '<div class="dashboardplus-widget-error"><i class="ti ti-alert-triangle"></i><span>' + escapeHtml(error) + '</span></div>';
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

   function bootDashboard(page) {
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
            cards.forEach(function(card) {
               reloadWidget(card, page);
            });
         }, refresh * 1000);
      }
   }

   function bootTabs(container, afterChange) {
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

   document.addEventListener('DOMContentLoaded', function() {
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
   });
})();
