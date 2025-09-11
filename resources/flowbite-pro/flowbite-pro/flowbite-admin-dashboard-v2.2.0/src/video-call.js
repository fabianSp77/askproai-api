window.addEventListener("load", () => {
  // manage drawer visibility and hide other ones when one is shown
  const drawersObject = FlowbiteInstances.getInstances("Drawer");
  const drawers = Object.values(drawersObject); // Convert object to array

  drawers.forEach((drawer) => {
    drawer.updateOnShow(() => {
      drawers.forEach((d) => {
        if (d !== drawer) {
          d.hide();
        }
      });
    });
  });

  // copy to clipboard set up
  const clipboard = FlowbiteInstances.getInstance("CopyClipboard", "link-url");
  const tooltip = FlowbiteInstances.getInstance("Tooltip", "tooltip-link-url");

  const $defaultIcon = document.getElementById("default-icon-link-url");
  const $successIcon = document.getElementById("success-icon-link-url");

  const $defaultTooltipMessage = document.getElementById("default-tooltip-message-link-url");
  const $successTooltipMessage = document.getElementById("success-tooltip-message-link-url");

  if (clipboard) {
    clipboard.updateOnCopyCallback((clipboard) => {
      showSuccess();

      // reset to default state
      setTimeout(() => {
        resetToDefault();
      }, 2000);
    });
  }

  const showSuccess = () => {
    $defaultIcon.classList.add("hidden");
    $successIcon.classList.remove("hidden");
    $defaultTooltipMessage.classList.add("hidden");
    $successTooltipMessage.classList.remove("hidden");
    tooltip.show();
  };

  const resetToDefault = () => {
    $defaultIcon.classList.remove("hidden");
    $successIcon.classList.add("hidden");
    $defaultTooltipMessage.classList.remove("hidden");
    $successTooltipMessage.classList.add("hidden");
    tooltip.hide();
  };

  // toggle all moderation checkboxes
  const toggleAllModerationCheckboxesButton = document.querySelector("[data-moderation-checkbox-toggle]");
  if (toggleAllModerationCheckboxesButton) {
    toggleAllModerationCheckboxesButton.addEventListener("click", (e) => {
      const isToggled = e.target.getAttribute("data-moderation-checkbox-toggle");

      const checkboxes = document.querySelectorAll("[data-moderation-checkbox]");
      if (isToggled === "true") {
        checkboxes.forEach((checkbox) => {
          if (checkbox.checked) {
            checkbox.checked = false;
          }
        });
        e.target.setAttribute("data-moderation-checkbox-toggle", "false");
      } else {
        checkboxes.forEach((checkbox) => {
          if (!checkbox.checked) {
            checkbox.checked = true;
          }
        });
        e.target.setAttribute("data-moderation-checkbox-toggle", "true");
      }
    });
  }
});
