import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Highlight from "@tiptap/extension-highlight";
import Underline from "@tiptap/extension-underline";
import Subscript from "@tiptap/extension-subscript";
import Superscript from "@tiptap/extension-superscript";
import TextStyle from "@tiptap/extension-text-style";
import FontFamily from "@tiptap/extension-font-family";
import { Color } from "@tiptap/extension-color";
import Placeholder from "@tiptap/extension-placeholder";
import Bold from "@tiptap/extension-bold";

window.addEventListener("load", function () {
  if (document.getElementById("wysiwyg-chat-example")) {
    const FontSizeTextStyle = TextStyle.extend({
      addAttributes() {
        return {
          fontSize: {
            default: null,
            parseHTML: (element) => element.style.fontSize,
            renderHTML: (attributes) => {
              if (!attributes.fontSize) {
                return {};
              }
              return { style: "font-size: " + attributes.fontSize };
            },
          },
        };
      },
    });
    const CustomBold = Bold.extend({
      // Override the renderHTML method
      renderHTML({ HTMLAttributes }) {
        return ["span", { ...HTMLAttributes, style: "font-weight: bold;" }, 0];
      },
      // Ensure it doesn't exclude other marks
      excludes: "",
    });

    // tip tap editor setup
    const editor = new Editor({
      element: document.querySelector("#wysiwyg-chat-example"),
      extensions: [
        // Exclude the default Bold mark
        StarterKit.configure({
          marks: {
            bold: false,
          },
        }),
        // Include the custom Bold extension
        CustomBold,
        Highlight,
        Underline,
        Subscript,
        Superscript,
        TextStyle,
        FontSizeTextStyle,
        Color,
        FontFamily,
        Placeholder.configure({
          placeholder: "Write a reply...",
        }),
      ],
      autofocus: "end",
      content: "<p></p>",
      editorProps: {
        attributes: {
          class: "not-format dark:format-invert focus:outline-none format-blue max-w-none",
        },
      },
    });

    // set up custom event listeners for the buttons
    document
      .getElementById("toggleBoldButton")
      .addEventListener("click", () => editor.chain().focus().toggleBold().run());
    document
      .getElementById("toggleItalicButton")
      .addEventListener("click", () => editor.chain().focus().toggleItalic().run());
    document
      .getElementById("toggleUnderlineButton")
      .addEventListener("click", () => editor.chain().focus().toggleUnderline().run());
    document
      .getElementById("toggleStrikeButton")
      .addEventListener("click", () => editor.chain().focus().toggleStrike().run());
    document
      .getElementById("toggleSubscriptButton")
      .addEventListener("click", () => editor.chain().focus().toggleSubscript().run());
    document
      .getElementById("toggleSuperscriptButton")
      .addEventListener("click", () => editor.chain().focus().toggleSuperscript().run());
    document.getElementById("toggleHighlightButton").addEventListener("click", () => {
      const isHighlighted = editor.isActive("highlight");
      // when using toggleHighlight(), judge if is is already highlighted.
      editor
        .chain()
        .focus()
        .toggleHighlight({
          color: isHighlighted ? undefined : "#ffc078", // if is already highlighted，unset the highlight color
        })
        .run();
    });

    document.getElementById("toggleCodeButton").addEventListener("click", () => {
      editor.chain().focus().toggleCode().run();
    });

    const textSizeDropdown = FlowbiteInstances.getInstance("Dropdown", "textSizeDropdown");

    // Loop through all elements with the data-text-size attribute
    document.querySelectorAll("[data-text-size]").forEach((button) => {
      button.addEventListener("click", () => {
        const fontSize = button.getAttribute("data-text-size");

        // Apply the selected font size via pixels using the TipTap editor chain
        editor.chain().focus().setMark("textStyle", { fontSize }).run();

        // Hide the dropdown after selection
        textSizeDropdown.hide();
      });
    });

    // Listen for color picker changes
    const colorPicker = document.getElementById("color");
    colorPicker.addEventListener("input", (event) => {
      const selectedColor = event.target.value;

      // Apply the selected color to the selected text
      editor.chain().focus().setColor(selectedColor).run();
    });

    document.querySelectorAll("[data-hex-color]").forEach((button) => {
      button.addEventListener("click", () => {
        const selectedColor = button.getAttribute("data-hex-color");

        // Apply the selected color to the selected text
        editor.chain().focus().setColor(selectedColor).run();
      });
    });

    document.getElementById("reset-color").addEventListener("click", () => {
      editor.commands.unsetColor();
    });

    const fontFamilyDropdown = FlowbiteInstances.getInstance("Dropdown", "fontFamilyDropdown");

    // Loop through all elements with the data-font-family attribute
    document.querySelectorAll("[data-font-family]").forEach((button) => {
      button.addEventListener("click", () => {
        const fontFamily = button.getAttribute("data-font-family");

        // Apply the selected font size via pixels using the TipTap editor chain
        editor.chain().focus().setFontFamily(fontFamily).run();

        // Hide the dropdown after selection
        fontFamilyDropdown.hide();
      });
    });
  }
});
