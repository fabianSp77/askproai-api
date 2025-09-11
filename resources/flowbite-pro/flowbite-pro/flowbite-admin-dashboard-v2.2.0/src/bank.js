window.addEventListener("load", () => {
  // copy to clipboard set up
  const clipboard = FlowbiteInstances.getInstance("CopyClipboard", "account-id");
  const tooltip = FlowbiteInstances.getInstance("Tooltip", "tooltip-account-id");

  const $defaultIcon = document.getElementById("default-icon-account-id");
  const $successIcon = document.getElementById("success-icon-account-id");

  const $defaultTooltipMessage = document.getElementById("default-tooltip-message-account-id");
  const $successTooltipMessage = document.getElementById("success-tooltip-message-account-id");

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
});
