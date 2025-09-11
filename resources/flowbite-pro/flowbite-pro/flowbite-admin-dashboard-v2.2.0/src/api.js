window.addEventListener("load", function () {
  document
    .querySelectorAll("[data-copy-to-clipboard-target]")
    .forEach((element) => {
      const clipboard = FlowbiteInstances.getInstance(
        "CopyClipboard",
        element.getAttribute("data-copy-to-clipboard-target"),
      );
      const tooltip = FlowbiteInstances.getInstance(
        "Tooltip",
        element.getAttribute("data-tooltip-target"),
      );
      const $tooltipEl = document.getElementById(
        element.getAttribute("data-tooltip-target"),
      );

      const $defaultIcon = element.querySelector("[data-tooltip-default-icon]");
      const $successIcon = element.querySelector("[data-tooltip-success-icon]");

      const $defaultTooltipMessage = $tooltipEl.querySelector(
        "[data-tooltip-default-message]",
      );
      const $successTooltipMessage = $tooltipEl.querySelector(
        "[data-tooltip-success-message]",
      );

      clipboard.updateOnCopyCallback((clipboard) => {
        showSuccess();

        // reset to default state
        setTimeout(() => {
          resetToDefault();
        }, 2000);
      });

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
});
