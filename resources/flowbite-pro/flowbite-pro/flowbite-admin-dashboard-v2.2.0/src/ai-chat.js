window.addEventListener("load", function () {
  const clipboard = FlowbiteInstances.getInstance("CopyClipboard", "code-block");

  if (clipboard) {
    const $defaultMessage = document.getElementById("default-message");
    const $successMessage = document.getElementById("success-message");

    clipboard.updateOnCopyCallback((clipboard) => {
      showSuccess();

      // reset to default state
      setTimeout(() => {
        resetToDefault();
      }, 2000);
    });

    const showSuccess = () => {
      $defaultMessage.classList.add("hidden");
      $successMessage.classList.remove("hidden");
    };

    const resetToDefault = () => {
      $defaultMessage.classList.remove("hidden");
      $successMessage.classList.add("hidden");
    };
  }
});
