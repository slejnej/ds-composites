document.addEventListener('DOMContentLoaded', () => {
  /**
   * Popover
   */
  const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
  if (popoverTriggerList) {
    const popoverList = [ ...popoverTriggerList ].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))
  }

  /**
   *  Modal
   */
  const varyingModal = document.getElementById('varyingModal')
  if (varyingModal) {
    varyingModal.addEventListener('show.bs.modal', event => {
      // Button that triggered the modal
      const button = event.relatedTarget
      // Extract info from data-bs-* attributes
      const recipient = button.getAttribute('data-bs-whatever')
      // If necessary, you could initiate an AJAX request here
      // and then do the updating in a callback.
      //
      // Update the modal's content.
      const modalTitle = varyingModal.querySelector('.modal-title')
      const modalBodyInput = varyingModal.querySelector('.modal-body input')

      modalTitle.textContent = `New message to ${recipient}`
      modalBodyInput.value = recipient
    })
  }

  /**
   *  Tooltip
   */
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
  if (tooltipTriggerList) {
    const tooltipList = [ ...tooltipTriggerList ].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
  }

  /**
   *  Dropdowns / Navbar
   */
  if (window.innerWidth < 992) {
    // close all inner dropdowns when parent is closed
    document.querySelectorAll('.navbar .dropdown').forEach(function (everydropdown) {
      everydropdown.addEventListener('hidden.bs.dropdown', function () {
        // after dropdown is hidden, then find all submenus
        this.querySelectorAll('.submenu').forEach(function (everysubmenu) {
          // hide every submenu as well
          everysubmenu.style.display = 'none';
        });
      })
    });

    document.querySelectorAll('.dropdown-menu a').forEach(function (element) {
      element.addEventListener('click', function (e) {
        let nextEl = this.nextElementSibling;
        if (nextEl && nextEl.classList.contains('submenu')) {
          // prevent opening link if link needs to open dropdown
          e.preventDefault();
          if (nextEl.style.display == 'block') {
            nextEl.style.display = 'none';
          } else {
            nextEl.style.display = 'block';
          }

        }
      });
    })
  }

  /**
   * Toasts
   */
  const selectToastPlacement = document.getElementById('selectToastPlacement')
  if (selectToastPlacement) {
    const toastPlacement = document.getElementById('toastPlacement')
    const defClass = 'toast-container p-3';
    selectToastPlacement.addEventListener('change', (e) => {
      toastPlacement.className = defClass + ' ' + (e.target.value);
    })
  }

  const toastTrigger = document.getElementById('liveToastBtn')
  const toastTriggerStacked = document.getElementById('liveToastBtnStacked')
  const toastLiveExample = document.getElementById('liveToast')
  const toastLiveExampleStacked = document.getElementById('liveToastStacked')
  if (toastTrigger) {
    toastTrigger.addEventListener('click', () => {
      const toast = new bootstrap.Toast(toastLiveExample)

      toast.show()
    })
  }
  if (toastTriggerStacked) {
    toastTriggerStacked.addEventListener('click', () => {
      const toast = new bootstrap.Toast(toastLiveExampleStacked)

      toast.show()
    })
  }
})