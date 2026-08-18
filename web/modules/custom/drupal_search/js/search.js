/** Define selected-tag template */
const SelectedTag = ({linkedId, label}) => `
<div class="search-pill d-flex align-items-center remove" data-id="${linkedId}">
  <div>${label}</div>
  <div class="remove-icon">
    <svg class="bi" width="16" height="16">
      <use xlink:href="/themes/contrib/barrio_base_theme/node_modules/bootstrap-icons/bootstrap-icons.svg#x"/>
    </svg>
  </div>
</div>
`;

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.drupal_search = {
    attach: function (context, settings) {

      // Flags to track if any select has been manually changed
      let selectsManuallyChanged = {};

      // Function to mark selects as manually changed
      function markSelectsAsChanged(selectIds) {
        selectIds.forEach(function (selectId) {
          $('[id^="' + selectId + '"]', context).change(function () {
            selectsManuallyChanged[selectId] = true;
          });
        });
      }

      // Apply the change listener to all specified selects
      markSelectsAsChanged(['edit-sort-bef-combine', 'edit-items-per-page']);

      // Listen for clicks on the submit button
      $('[id^="edit-submit"]', context).each(function () {
        if (!$(this).hasClass('submit-search-processed')) {
          $(this).addClass('submit-search-processed').click(function () {
            let searchText = $('[id^="edit-search-api-fulltext"]', context).val();
            // Check if there is search text and no select has been manually changed
            if (searchText.trim() !== '') {
              let anySelectManuallyChanged = Object.values(selectsManuallyChanged).some(v => v);
              if (!anySelectManuallyChanged) {
                $('[id^="edit-sort-bef-combine"]', context).val('search_api_relevance_DESC').trigger('change');
              }
            }
          });
        }
      });

      let isProgrammaticChange = false;

      // Function to synchronize select lists and trigger auto-submit
      function synchronizeSelectsAndSubmit(selectNames) {
        selectNames.forEach(function (selectName) {
          $(document).on('change', `select[name="${selectName}"]`, function () {
            if (isProgrammaticChange) {
              return;
            }
            isProgrammaticChange = true;

            let currentValue = $(this).val();

            // Directly set the value on other selects of the same name without triggering 'change'
            $(`select[name="${selectName}"]`).not(this).each(function () {
              $(this).val(currentValue);
            });

            // Trigger the click only if the change was not manual
            let anySelectManuallyChanged = Object.values(selectsManuallyChanged).some(v => v);
            if (!anySelectManuallyChanged) {
              $('.region-sidebar-first input[data-bef-auto-submit-click]').click();
            }

            isProgrammaticChange = false;
          });
        });
      }

      // Initialize synchronization for all specified select lists
      synchronizeSelectsAndSubmit(["sort_bef_combine", "items_per_page"]);

      // Function to save the state of facet-content sections
      function saveFacetState() {
        $('.block-facet__wrapper').each(function (index) {
          const isOpen = $(this).find('.accordion-collapse').is(':visible');
          sessionStorage.setItem('facetState-' + index, isOpen ? 'open' : 'closed');
        });
      }

      // Function to restore the state of facet-content sections
      function restoreFacetState() {
        $('.block-facet__wrapper').each(function (index) {
          const state = sessionStorage.getItem('facetState-' + index);
          const accordionButton = $(this).find('.accordion-button');
          const accordionCollapse = $(this).find('.accordion-collapse');

          if (state === 'open') {
            accordionButton.attr('aria-expanded', 'true');
            accordionButton.removeClass('collapsed');
            accordionCollapse.addClass('show');
          } else {
            accordionButton.attr('aria-expanded', 'false');
            accordionButton.addClass('collapsed');
            accordionCollapse.removeClass('show');
          }
        });
      }

      // Initially restore the states if any
      restoreFacetState();

      // Filter filters
      $(document).on('keyup', 'input.filter-search', function () {
        const search = $(this).val().toLowerCase();
        const filterContainer = $(this).data('filter-content');

        if (filterContainer.length) {
          const $data = $(this).parent().siblings('.filter-list').find('ul');
          if ($data.length) {
            $data.find('li').filter(function () {
              $(this).toggle($(this).find('.facet-item__value').text().toLowerCase().indexOf(search) > -1)
            });
          }
        }
      });

      // Save facet state each time one is opened/closed
      $(document).on('click', '.accordion-button', function () {
        saveFacetState();
      })

      // Hide the mobile sort and items per page
      $('#offcanvasFilters')
        .find('.js-form-item-items-per-page, .js-form-item-sort-bef-combine')
        .addClass('d-lg-none');

      // Function creates pill badges of selected facets
      function createdPillsForSelected() {
        let sidebarFilters = $(document).find('#offcanvasFilters');
        const allFilters = $('.block-facets-ajax:not(.hidden) li.facet-item');

        // Clear previous pills
        sidebarFilters.find('.remora-search-selected-filters-block').html('');

        let items = [];

        allFilters.each(function () {
          if ($(this).find('input').is(':checked')) {
            items.push({
              linkedId: $(this).find('input').prop('id'),
              label: $(this).find('.facet-item__value').html(),
            })
          }
        })

        if (items.length === 0) {
          sidebarFilters.find('.remora-search-selected-filters-block').removeClass('entries');
        } else {
          sidebarFilters.find('.remora-search-selected-filters-block').append(items.map(SelectedTag).join(''));
          sidebarFilters.find('.remora-search-selected-filters-block').addClass('entries');
        }

      }

      // Add listener for pills to click related facets
      $(document).on('click', '.search-pill.remove', function () {
        if ($(this).data('id')) {
          $('#' + $(this).data('id')).trigger('click');
        }
      })

      // Update pills when search page loaded with filters applied
      setTimeout(function () {
        createdPillsForSelected();
      }, 200);

      // Called only once after all ajax done
      $(document).ajaxStop(function () {
        createdPillsForSelected();
      })

    }
  }
})(jQuery, Drupal);
