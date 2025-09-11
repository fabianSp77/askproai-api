import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Highlight from "@tiptap/extension-highlight";
import Underline from "@tiptap/extension-underline";
import Link from "@tiptap/extension-link";
import TextAlign from "@tiptap/extension-text-align";
import HorizontalRule from "@tiptap/extension-horizontal-rule";
import Image from "@tiptap/extension-image";
import YouTube from "@tiptap/extension-youtube";
import TextStyle from "@tiptap/extension-text-style";
import FontFamily from "@tiptap/extension-font-family";
import { Color } from "@tiptap/extension-color";

window.addEventListener("load", function () {
  if (document.getElementById("wysiwyg-example")) {
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

    // tip tap editor setup
    const editor = new Editor({
      element: document.querySelector("#wysiwyg-example"),
      extensions: [
        StarterKit,
        Highlight,
        Underline,
        Link.configure({
          openOnClick: false,
          autolink: true,
          defaultProtocol: "https",
        }),
        TextAlign.configure({
          types: ["heading", "paragraph"],
        }),
        HorizontalRule,
        Image,
        YouTube,
        TextStyle,
        FontSizeTextStyle,
        Color,
        FontFamily,
      ],
      content:
        '<p>Flowbite is an <strong>open-source library of UI components</strong> based on the utility-first Tailwind CSS framework featuring dark mode support, a Figma design system, and more.</p><p>It includes all of the commonly used components that a website requires, such as buttons, dropdowns, navigation bars, modals, datepickers, advanced charts and the list goes on.</p><p>Here is an example of a button component:</p><code>&#x3C;button type=&#x22;button&#x22; class=&#x22;text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800&#x22;&#x3E;Default&#x3C;/button&#x3E;</code><p>Learn more about all components from the <a href="https://flowbite.com/docs/getting-started/introduction/">Flowbite Docs</a>.</p>',
      editorProps: {
        attributes: {
          class: "format lg:format-md dark:format-invert focus:outline-none format-blue max-w-none",
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
      .getElementById("toggleHighlightButton")
      .addEventListener("click", () => editor.chain().focus().toggleHighlight({ color: "#ffc078" }).run());
    document.getElementById("toggleLinkButton").addEventListener("click", () => {
      const url = window.prompt("Enter image URL:", "https://flowbite.com");
      editor.chain().focus().toggleLink({ href: url }).run();
    });
    document.getElementById("removeLinkButton").addEventListener("click", () => {
      editor.chain().focus().unsetLink().run();
    });
    document.getElementById("toggleCodeButton").addEventListener("click", () => {
      editor.chain().focus().toggleCode().run();
    });

    document.getElementById("toggleLeftAlignButton").addEventListener("click", () => {
      editor.chain().focus().setTextAlign("left").run();
    });
    document.getElementById("toggleCenterAlignButton").addEventListener("click", () => {
      editor.chain().focus().setTextAlign("center").run();
    });
    document.getElementById("toggleRightAlignButton").addEventListener("click", () => {
      editor.chain().focus().setTextAlign("right").run();
    });
    document.getElementById("toggleListButton").addEventListener("click", () => {
      editor.chain().focus().toggleBulletList().run();
    });
    document.getElementById("toggleOrderedListButton").addEventListener("click", () => {
      editor.chain().focus().toggleOrderedList().run();
    });
    document.getElementById("toggleBlockquoteButton").addEventListener("click", () => {
      editor.chain().focus().toggleBlockquote().run();
    });
    document.getElementById("toggleHRButton").addEventListener("click", () => {
      editor.chain().focus().setHorizontalRule().run();
    });
    document.getElementById("addImageButton").addEventListener("click", () => {
      const url = window.prompt("Enter image URL:", "https://placehold.co/600x400");
      if (url) {
        editor.chain().focus().setImage({ src: url }).run();
      }
    });
    document.getElementById("addVideoButton").addEventListener("click", () => {
      const url = window.prompt("Enter YouTube URL:", "https://www.youtube.com/watch?v=KaLxCiilHns");
      if (url) {
        editor.commands.setYoutubeVideo({
          src: url,
          width: 640,
          height: 480,
        });
      }
    });

    // typography dropdown
    const typographyDropdown = FlowbiteInstances.getInstance("Dropdown", "typographyDropdown");

    document.getElementById("toggleParagraphButton").addEventListener("click", () => {
      editor.chain().focus().setParagraph().run();
      typographyDropdown.hide();
    });

    document.querySelectorAll("[data-heading-level]").forEach((button) => {
      button.addEventListener("click", () => {
        const level = button.getAttribute("data-heading-level");
        editor
          .chain()
          .focus()
          .toggleHeading({ level: parseInt(level) })
          .run();
        typographyDropdown.hide();
      });
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
