import svgMap from "svgmap";

if (document.getElementById("map")) {
  const initSVGMap = () => {
    let themeOptions = {};
    if (document.documentElement.classList.contains("dark")) {
      themeOptions = {
        colorNoData: "#4B5563",
      };
    } else {
      themeOptions = {
        colorNoData: "#D1D5DB",
      };
    }

    new svgMap({
      targetElementID: "map",
      colorMin: "#A4CAFE",
      colorMax: "#1A56DB",
      flagType: "image",
      flagURL: "https://flowbite.com/application-ui/demo/images/flags/{0}.svg",
      data: {
        data: {
          visitors: {
            name: "Visitors:",
            format: "{0}",
            thousandSeparator: ",",
            thresholdMax: 500000,
            thresholdMin: 0,
          },
          change: {
            name: "Change:",
            format: "{0} %",
          },
        },
        applyData: "visitors",
        values: {
          US: { visitors: 272109, change: 4.73 },
          CA: { visitors: 160064, change: 11.09 },
          DE: { visitors: 120048, change: -2.3 },
          GB: { visitors: 110048, change: 3.3 },
          FR: { visitors: 100048, change: 1.3 },
          ES: { visitors: 90048, change: 1.5 },
          JP: { visitors: 56022, change: 3.5 },
          IT: { visitors: 48019, change: 1 },
          NL: { visitors: 40016, change: 2 },
          RU: { visitors: 30016, change: 3.4 },
          CN: { visitors: 50016, change: 6 },
          IN: { visitors: 140016, change: 2 },
          BR: { visitors: 40016, change: 5 },
        },
      },
      ...themeOptions,
    });
  };

  initSVGMap();

  // init again when toggling dark mode
  document.addEventListener("dark-mode", function () {
    document.getElementById("map").innerHTML = "";
    initSVGMap();
  });
}

if (document.getElementById("map-orders")) {
  const initSVGMap = () => {
    let themeOptions = {};
    if (document.documentElement.classList.contains("dark")) {
      themeOptions = {
        colorNoData: "#4B5563",
      };
    } else {
      themeOptions = {
        colorNoData: "#D1D5DB",
      };
    }

    new svgMap({
      targetElementID: "map-orders",
      colorMin: "#A4CAFE",
      colorMax: "#1A56DB",
      flagType: "image",
      flagURL: "https://flowbite.com/application-ui/demo/images/flags/{0}.svg",
      data: {
        data: {
          orders: {
            name: "Orders:",
            format: "{0}",
            thousandSeparator: ",",
            thresholdMax: 500000,
            thresholdMin: 0,
          },
          change: {
            name: "Change:",
            format: "{0} %",
          },
        },
        applyData: "orders",
        values: {
          US: { orders: 8756, change: 4.73 },
          CA: { orders: 7236, change: 11.09 },
          DE: { orders: 2551, change: -2.3 },
          GB: { orders: 2551, change: 3.3 },
          FR: { orders: 3753, change: 1.3 },
          ES: { orders: 3123, change: 1.5 },
          JP: { orders: 2257, change: 3.5 },
          IT: { orders: 2080, change: 1 },
          NL: { orders: 1025, change: 2 },
          RU: { orders: 56, change: 3.4 },
          TR: { orders: 429, change: 2 },
          AU: { orders: 200, change: 13.4 },
          CN: { orders: 987, change: 6 },
          IN: { orders: 265, change: 2 },
          BR: { orders: 123, change: 5 },
        },
      },
      ...themeOptions,
    });
  };

  initSVGMap();

  // init again when toggling dark mode
  document.addEventListener("dark-mode", function () {
    document.getElementById("map-orders").innerHTML = "";
    initSVGMap();
  });
}

if (document.getElementById("world-map")) {
  const initSVGMap = () => {
    let themeOptions = {};
    if (document.documentElement.classList.contains("dark")) {
      themeOptions = {
        colorNoData: "#4B5563",
      };
    } else {
      themeOptions = {
        colorNoData: "#D1D5DB",
      };
    }

    new svgMap({
      targetElementID: "world-map",
      colorMin: "#A4CAFE",
      colorMax: "#1A56DB",
      flagType: "image",
      flagURL: "https://flowbite.com/application-ui/demo/images/flags/{0}.svg",
      data: {
        data: {
          visitors: {
            name: "Visitors:",
            format: "{0}",
            thousandSeparator: ",",
            thresholdMax: 500000,
            thresholdMin: 0,
          },
          change: {
            name: "Change:",
            format: "{0} %",
          },
        },
        applyData: "visitors",
        values: {
          US: { visitors: 272109, change: 4.73 },
          CA: { visitors: 160064, change: 11.09 },
          DE: { visitors: 120048, change: -2.3 },
          GB: { visitors: 110048, change: 3.3 },
          FR: { visitors: 100048, change: 1.3 },
          ES: { visitors: 90048, change: 1.5 },
          JP: { visitors: 56022, change: 3.5 },
          IT: { visitors: 48019, change: 1 },
          NL: { visitors: 40016, change: 2 },
          RU: { visitors: 30016, change: 3.4 },
          CN: { visitors: 50016, change: 6 },
          IN: { visitors: 140016, change: 2 },
          BR: { visitors: 40016, change: 5 },
        },
      },
      ...themeOptions,
    });
  };

  initSVGMap();

  // init again when toggling dark mode
  document.addEventListener("dark-mode", function () {
    document.getElementById("world-map").innerHTML = "";
    initSVGMap();
  });
}
