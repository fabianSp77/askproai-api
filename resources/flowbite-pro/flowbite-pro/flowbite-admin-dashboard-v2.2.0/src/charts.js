import { auto } from "@popperjs/core";
import ApexCharts from "apexcharts";
import { get } from "sortablejs";

const getWhiteAndDark900Colors = () => {
  return document.documentElement.classList.contains("dark")
    ? "#1F2937"
    : "#FFFFFF";
}

const getGray100AndDark700Colors = () => {
  return document.documentElement.classList.contains("dark")
    ? "#374151"
    : "#f3f4f6";
}

const getPrimaryColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#1C64F2"
    : "#1A56DB";
}

const getSecondaryColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#FF8A4C"
    : "#FF9963";
}

const getTertiaryColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#16BDCA"
    : "#12ADBD";
}

const getQuaternaryColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#9061F9"
    : "#7E3AF2";
}

const getQuinaryColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#F559A5"
    : "#E74694";
}

const getSuccessColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#31C48D"
    : "#0E9F6E";
}

const getDangerColor = () => {
  return document.documentElement.classList.contains("dark")
    ? "#F05252"
    : "#E02424";
}

const legendColors = {
  class: "text-xs font-normal fill-gray-500 dark:fill-gray-400"
}

const getMainChartOptions = () => {
  let mainChartColors = {};

  if (document.documentElement.classList.contains("dark")) {
    mainChartColors = {
      borderColor: "#374151",
      labelColor: "#9CA3AF",
      opacityFrom: 0,
      opacityTo: 0.15,
    };
  } else {
    mainChartColors = {
      borderColor: "#F3F4F6",
      labelColor: "#6B7280",
      opacityFrom: 0.45,
      opacityTo: 0,
    };
  }

  return {
    chart: {
      width: "100%",
      height: 412,
      type: "area",
      fontFamily: "Inter, sans-serif",
      foreColor: mainChartColors.labelColor,
      toolbar: {
        show: false,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        enabled: true,
        opacityFrom: mainChartColors.opacityFrom,
        opacityTo: mainChartColors.opacityTo,
      },
    },
    stroke: {
      curve: "smooth",
    },
    dataLabels: {
      enabled: false,
    },
    tooltip: {
      style: {
        fontSize: "14px",
        fontFamily: "Inter, sans-serif",
      },
    },
    grid: {
      show: false,
      padding: {
       left: 8,
       right: 8,
       top: 0,
       bottom: 5
      },
    },
    series: [
      {
        name: "Revenue",
        data: [10356, 10456, 10356, 10456, 10556, 10556, 10656],
        color: getPrimaryColor(),
      },
      {
        name: "Revenue (previous period)",
        data: [10256, 10356, 10456, 10356, 10556, 10456, 10556],
        color: getSecondaryColor(),
      },
    ],
    markers: {
      size: 5,
      strokeColors: getWhiteAndDark900Colors(),
      hover: {
        size: undefined,
        sizeOffset: 3,
      },
    },
    xaxis: {
      categories: [
        "01 Mar",
        "02 Mar",
        "03 Mar",
        "04 Mar",
        "05 Mar",
        "06 Mar",
        "07 Mar",
      ],
      labels: {
        trim: true,
        style: {
          colors: [mainChartColors.labelColor],
          fontSize: "14px",
          fontWeight: 400,
        },
      },
      axisBorder: {
        color: mainChartColors.borderColor,
      },
      axisTicks: {
        color: mainChartColors.borderColor,
      },
      crosshairs: {
        show: true,
        position: "back",
        stroke: {
          color: mainChartColors.borderColor,
          width: 1,
          dashArray: 10,
        },
      },
    },
    yaxis: {
      show: false,
    },
    legend: {
      fontSize: "14px",
      fontWeight: 400,
      fontFamily: "Inter, sans-serif",
      labels: {
        colors: [mainChartColors.labelColor],
      },
      itemMargin: {
        horizontal: 10,
      },
    },
    responsive: [
      {
        breakpoint: 1024,
        options: {
          xaxis: {
            labels: {
              show: false,
            },
          },
        },
      },
      {
        breakpoint: 640,
        options: {
          legend: {
            show: false,
          },
          grid: {
            padding: {
              left: 0,
              bottom: -100,
            },
          },
          xaxis: {
            labels: {
              show: false,
            },
          },
        },
      },
    ],
  };
};

if (document.getElementById("main-chart")) {
  const chart = new ApexCharts(
    document.getElementById("main-chart"),
    getMainChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getMainChartOptions());
  });
}

if (document.getElementById("new-products-chart")) {
  const options = {
    colors: [getPrimaryColor(), getSecondaryColor()],
    series: [
      {
        name: "Digital",
        color: getPrimaryColor(),
        data: [
          { x: "01 Feb", y: 170 },
          { x: "02 Feb", y: 180 },
          { x: "03 Feb", y: 164 },
          { x: "04 Feb", y: 145 },
          { x: "05 Feb", y: 174 },
          { x: "06 Feb", y: 170 },
          { x: "07 Feb", y: 155 },
        ],
      },
      {
        name: "Goods",
        color: getSecondaryColor(),
        data: [
          { x: "01 Feb", y: 120 },
          { x: "02 Feb", y: 134 },
          { x: "03 Feb", y: 167 },
          { x: "04 Feb", y: 179 },
          { x: "05 Feb", y: 145 },
          { x: "06 Feb", y: 182 },
          { x: "07 Feb", y: 143 },
        ],
      },
    ],
    chart: {
      type: "bar",
      height: "316px",
      fontFamily: "Inter, sans-serif",
      foreColor: "#4B5563",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        columnWidth: "80%",
        borderRadius: 3,
        borderRadiusApplication: "top",
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
      style: {
        fontSize: "14px",
        fontFamily: "Inter, sans-serif",
      },
    },
    states: {
      hover: {
        filter: {
          type: "darken",
          value: 1,
        },
      },
    },
    stroke: {
      show: true,
      width: 5,
      colors: ["transparent"],
    },
    grid: {
      show: false,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      floating: true,
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
    fill: {
      opacity: 1,
    },
  };

  const chart = new ApexCharts(
    document.getElementById("new-products-chart"),
    options,
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(options);
  });
}

const options = {
  colors: [getPrimaryColor(), getSecondaryColor(), getTertiaryColor()],
  series: [
    {
      name: "Projects",
      data: [
        { x: "Completed", y: 7 },
        { x: "In review", y: 5 },
        { x: "In progress", y: 8 },
        { x: "To do", y: 6 },
        { x: "Rejected", y: 9 },
      ],
    },
  ],
  chart: {
    type: "bar",
    height: "520px",
    fontFamily: "Inter, sans-serif",
    toolbar: {
      show: false,
    },
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: "70%",
      borderRadiusApplication: "end",
      borderRadius: 8,
    },
  },
  tooltip: {
    shared: true,
    intersect: false,
    style: {
      fontFamily: "Inter, sans-serif",
    },
  },
  states: {
    hover: {
      filter: {
        type: "darken",
        value: 1,
      },
    },
  },
  stroke: {
    show: true,
    width: 0,
    colors: ["transparent"],
  },
  grid: {
    show: false,
    strokeDashArray: 4,
    padding: {
      left: 2,
      right: 2,
      top: -14,
    },
  },
  dataLabels: {
    enabled: false,
  },
  legend: {
    show: false,
  },
  xaxis: {
    floating: false,
    labels: {
      show: true,
      style: {
        fontFamily: "Inter, sans-serif",
        cssClass: legendColors.class
      },
    },
    axisBorder: {
      show: false,
    },
    axisTicks: {
      show: false,
    },
  },
  yaxis: {
    show: false,
  },
  fill: {
    opacity: 1,
  },
};

if (
  document.getElementById("column-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.getElementById("column-chart"),
    options,
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(options);
  });
}

const getRadialChartOptions = () => {
  return {
    series: [90, 85, 70, 25],
    colors: [getPrimaryColor(), getTertiaryColor(), getSecondaryColor(), getQuaternaryColor()],
    chart: {
      height: "320px",
      width: "100%",
      type: "radialBar",
      sparkline: {
        enabled: true,
      },
    },

    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: "300px",
          },
        },
      },
    ],

    plotOptions: {
      radialBar: {
        track: {
          background: getGray100AndDark700Colors(),
          style: {
            cssClass: "dark:bg-gray-700",
          },
        },
        dataLabels: {
          show: false,
        },
        hollow: {
          margin: 0,
          size: "32%",
        },
      },
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -23,
        bottom: -20,
      },
    },
    labels: ["Done", "In progress", "To do"],
    legend: {
      show: false,
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    yaxis: {
      show: false,
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
  };
};

if (
  document.getElementById("radial-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#radial-chart"),
    getRadialChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRadialChartOptions());
  });
}

const getCommitsChartOptions = () => {
  return {
    colors: [getPrimaryColor()],
    series: [
      {
        name: "Mon",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Tue",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Wed",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Thu",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Fri",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Sat",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Sun",
        data: generateData(53, {
          min: 0,
          max: 90,
        }),
      },
    ],
    chart: {
      width: '100%',
      height: 350,
      type: "heatmap",
      toolbar: {
        show: false,
      },
    },
    grid: {
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0
      }
    },
    dataLabels: {
      enabled: false,
    },
    yaxis: {
      show: false,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
    },
    tooltip: {
      shared: false,
      followCursor: true,
      fillSeriesColor: true,
      style: {
        fontSize: "14px",
        fontFamily: "Inter, sans-serif",
      },
      y: {
        formatter: function (value) {
          return value + " commits";
        },
      },
    },
    xaxis: {
      type: "months",
      categories: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
      floating: false,
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      tooltip: {
        enabled: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          yaxis: {
            labels: {
              show: false,
            },
          },
        },
      },
    ],
  };
};

if (
  document.getElementById("commits-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#commits-chart"),
    getCommitsChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getCommitsChartOptions());
  });
}

function generateData(count, yrange) {
  var i = 0;
  var series = [];
  while (i < count) {
    var x = (i + 1).toString();
    var y =
      Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;

    series.push({
      x: x,
      y: y,
    });
    i++;
  }
  return series;
}

// Total sales chart
const getTotalSalesChartOptions = () => {
  return {
    colors: [getPrimaryColor(), getSecondaryColor(), getTertiaryColor()],
    series: [
      {
        name: "Templates",
        color: getPrimaryColor(),
        data: [
          { x: "Mon", y: 631 },
          { x: "Tue", y: 600 },
          { x: "Wed", y: 540 },
          { x: "Thu", y: 580 },
          { x: "Fri", y: 490 },
          { x: "Sat", y: 580 },
          { x: "Sun", y: 620 },
        ],
      },
      {
        name: "Icons",
        color: getSecondaryColor(),
        data: [
          { x: "Mon", y: 460 },
          { x: "Tue", y: 490 },
          { x: "Wed", y: 390 },
          { x: "Thu", y: 620 },
          { x: "Fri", y: 410 },
          { x: "Sat", y: 640 },
          { x: "Sun", y: 360 },
        ],
      },
      {
        name: "Illustrations",
        color: getTertiaryColor(),
        data: [
          { x: "Mon", y: 232 },
          { x: "Tue", y: 630 },
          { x: "Wed", y: 341 },
          { x: "Thu", y: 224 },
          { x: "Fri", y: 522 },
          { x: "Sat", y: 411 },
          { x: "Sun", y: 243 },
        ],
      },
    ],
    chart: {
      type: "bar",
      height: "520px",
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "70%",
        borderRadiusApplication: "end",
        borderRadius: 8,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
      style: {
        fontFamily: "Inter, sans-serif",
      },
    },
    states: {
      hover: {
        filter: {
          type: "darken",
          value: 1,
        },
      },
    },
    stroke: {
      show: true,
      width: 0,
      colors: ["transparent"],
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -14,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      floating: false,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
    fill: {
      opacity: 1,
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
      offsetY: 20,
      height: 40,
      markers: {
        radius: 99,
      },
    },
  };
};

if (
  document.getElementById("total-sales-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#total-sales-chart"),
    getTotalSalesChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTotalSalesChartOptions());
  });
}

// Total sales chart
const getVisitsChartOptions = () => {
  return {
    colors: [getPrimaryColor(), getSecondaryColor(), getTertiaryColor()],
    series: [
      {
        name: "Desktop",
        data: [45],
      },
      {
        name: "Phone",
        data: [35],
      },
      {
        name: "Tablet",
        data: [20],
      },
    ],
    chart: {
      type: "bar",
      height: 130,
      width: '100%',
      stacked: true,
      stackType: "100%",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 8,
      },
    },
    stroke: {
      width: 0,
      colors: [getWhiteAndDark900Colors()],
    },
    xaxis: {
      categories: ["Visits"],
      labels: {
        trim: true,
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      title: {
        text: undefined,
      },
      labels: {
        trim: true,
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val + "%";
        },
      },
    },
    grid: {
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0
      }
    },
    fill: {
      opacity: 1,
    },
    legend: {
      position: "bottom",
      horizontalAlign: "center",
      offsetX: 40,
      fontFamily: "Inter, sans-serif",
      markers: {
        radius: 99,
      },
    },
  };
};

if (
  document.getElementById("visits-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#visits-chart"),
    getVisitsChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getVisitsChartOptions());
  });
}

// Total sales chart
const getRevenueChartOptions = () => {
  return {
    chart: {
      height: "100%",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Revenue",
        data: [23500, 22418, 24178, 21200, 20157, 23200, 22246],
        color: getPrimaryColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("revenue-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#revenue-chart"),
    getRevenueChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRevenueChartOptions());
  });
}

// Total sales chart
const getCustomersChartOptions = () => {
  return {
    chart: {
      height: "100%",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Customers",
        data: [213457, 214374, 213638, 214111, 213457, 214768, 213869],
        color: getPrimaryColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("customers-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#customers-chart"),
    getCustomersChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getCustomersChartOptions());
  });
}

// Delivered shipments chart
const getDeliveredShipmentsChartOptions = () => {
  return {
    chart: {
      height: "346px",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 6,
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Shipments",
        data: [78234, 76555, 74853, 79033, 76756, 77373, 76221],
        color: getPrimaryColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("delivered-shipments-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#delivered-shipments-chart"),
    getDeliveredShipmentsChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getDeliveredShipmentsChartOptions());
  });
}

// Total sales chart
const getConversionChartOptions = () => {
  return {
    chart: {
      height: "100%",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Conversion",
        data: [0.7, 0.7, 0.6, 0.8, 0.6, 0.7, 0.8],
        color: getPrimaryColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("conversion-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#conversion-chart"),
    getConversionChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getConversionChartOptions());
  });
}

const getPerformanceChartOptions = () => {
  return {
    series: [90, 85, 70, 25],
    colors: [getPrimaryColor(), getTertiaryColor(), getSecondaryColor(), getQuaternaryColor()],
    chart: {
      height: "320px",
      width: "100%",
      type: "radialBar",
      sparkline: {
        enabled: true,
      },
    },

    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: "300px",
          },
        },
      },
    ],

    plotOptions: {
      radialBar: {
        track: {
          background: getGray100AndDark700Colors(),
          style: {
            cssClass: "dark:bg-gray-700",
          },
        },
        dataLabels: {
          show: false,
        },
        hollow: {
          margin: 0,
          size: "32%",
        },
      },
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -23,
        bottom: -20,
      },
    },
    labels: ["Direct", "Sponsor", "Affiliate", "E-mail"],
    legend: {
      show: false,
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    yaxis: {
      show: false,
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
  };
};

if (
  document.getElementById("performance-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#performance-chart"),
    getPerformanceChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getPerformanceChartOptions());
  });
}

const getDonutChartOptions = () => {
  return {
    series: [75, 14, 6.4, 2.6, 2],
    colors: [getPrimaryColor(), getTertiaryColor(), getSecondaryColor(), getQuinaryColor(), getQuaternaryColor()],
    chart: {
      height: 400,
      width: "100%",
      type: "donut",
    },
    stroke: {
      colors: ["transparent"],
      lineCap: "",
    },
    plotOptions: {
      pie: {
        donut: {
          labels: {
            show: true,
            name: {
              show: true,
              fontFamily: "Inter, sans-serif",
              offsetY: 20,
            },
            total: {
              showAlways: true,
              show: true,
              label: "Unique visitors",
              fontFamily: "Inter, sans-serif",
              formatter: function (w) {
                const sum = w.globals.seriesTotals.reduce((a, b) => {
                  return a + b;
                }, 0);
                return sum + "k";
              },
            },
            value: {
              show: true,
              fontFamily: "Inter, sans-serif",
              offsetY: -20,
              formatter: function (value) {
                return value + "%";
              },
            },
          },
          size: "80%",
        },
      },
    },
    grid: {
      padding: {
        top: -2,
      },
    },
    labels: ["Direct", "Google", "Reddit", "Twitter", "Producthunt"],
    dataLabels: {
      enabled: false,
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
    xaxis: {
      labels: {
        formatter: function (value) {
          return value + "k";
        },
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
  };
};

if (
  document.getElementById("donut-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#donut-chart"),
    getDonutChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getDonutChartOptions());
  });

  // Get all the checkboxes by their class name
  const checkboxes = document.querySelectorAll(
    '#devices input[type="checkbox"]',
  );

  // Function to handle the checkbox change event
  function handleCheckboxChange(event, chart) {
    const checkbox = event.target;
    if (checkbox.checked) {
      switch (checkbox.value) {
        case "desktop":
          chart.updateSeries([15.1, 22.5, 4.4, 8.4]);
          break;
        case "tablet":
          chart.updateSeries([25.1, 26.5, 1.4, 3.4]);
          break;
        case "mobile":
          chart.updateSeries([45.1, 27.5, 8.4, 2.4]);
          break;
        default:
          chart.updateSeries([55.1, 28.5, 1.4, 5.4]);
      }
    } else {
      chart.updateSeries([35.1, 23.5, 2.4, 5.4]);
    }
  }

  // Attach the event listener to each checkbox
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", (event) =>
      handleCheckboxChange(event, chart),
    );
  });
}

const getTrafficChartOptions = () => {
  return {
    series: [52.8, 26.8, 20.4],
    colors: [getPrimaryColor(), getTertiaryColor(), getQuaternaryColor()],
    chart: {
      height: 420,
      width: "100%",
      type: "pie",
    },
    stroke: {
      colors: [getWhiteAndDark900Colors()],
      lineCap: "",
    },
    plotOptions: {
      pie: {
        labels: {
          show: true,
        },
        size: "100%",
        dataLabels: {
          offset: -25,
        },
      },
    },
    labels: ["Direct", "Organic search", "Referrals"],
    dataLabels: {
      enabled: true,
      style: {
        fontFamily: "Inter, sans-serif",
      },
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
    xaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
  };
};

if (
  document.getElementById("traffic-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#traffic-chart"),
    getTrafficChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTrafficChartOptions());
  });
}

const getSessionsChartOptions = () => {
  return {
    series: [74, 36],
    colors: [getPrimaryColor(), getSecondaryColor()],
    chart: {
      type: "donut",
      height: 220,
    },
    stroke: {
      colors: [getWhiteAndDark900Colors()],
      lineCap: "",
    },
    plotOptions: {
      pie: {
        startAngle: -90,
        endAngle: 90,
        offsetY: 10,
      },
    },
    labels: ["Windows", "macOS"],
    grid: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: -180,
      },
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
    responsive: [
      {
        breakpoint: 390,
        options: {
          chart: {
            height: 200,
          },
          legend: {
            position: "bottom",
          },
          grid: {
            padding: {
              bottom: -120,
            },
          },
        },
      },
    ],
  };
};

if (
  document.getElementById("sessions-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#sessions-chart"),
    getSessionsChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSessionsChartOptions());
  });
}

const getUserCountChartOptions = () => {
  return {
    colors: [getPrimaryColor()],
    series: [
      {
        name: "Mon",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Tue",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Wed",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Thu",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Fri",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Sat",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Sun",
        data: generateData(10, {
          min: 0,
          max: 90,
        }),
      },
    ],
    chart: {
      height: 350,
      type: "heatmap",
      toolbar: {
        show: false,
      },
    },
    dataLabels: {
      enabled: false,
    },
    yaxis: {
      show: false,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
    },
    tooltip: {
      shared: false,
      followCursor: true,
      fillSeriesColor: true,
      style: {
        fontSize: "14px",
        fontFamily: "Inter, sans-serif",
      },
      y: {
        formatter: function (value) {
          return value + " users";
        },
      },
    },
    grid: {
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0
      }
    },
    xaxis: {
      type: "months",
      categories: [
        "08:00",
        "09:00",
        "10:00",
        "11:00",
        "12:00",
        "13:00",
        "14:00",
        "15:00",
        "16:00",
        "17:00",
      ],
      floating: false,
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      tooltip: {
        enabled: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          yaxis: {
            labels: {
              show: false,
            },
          },
        },
      },
    ],
  };
};

if (
  document.getElementById("user-count-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#user-count-chart"),
    getUserCountChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getUserCountChartOptions());
  });
}

// radial charts (car service homepage)

const getFrontLeftBrakeChartOptions = () => {
  return {
    series: [30],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["FL"],
  };
};

if (
  document.getElementById("front-left-brake-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#front-left-brake-chart"),
    getFrontLeftBrakeChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getFrontLeftBrakeChartOptions());
  });
}

const getFrontRightBrakeChartOptions = () => {
  return {
    series: [50],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["FR"],
  };
};

if (
  document.getElementById("front-right-brake-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#front-right-brake-chart"),
    getFrontRightBrakeChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getFrontRightBrakeChartOptions());
  });
}

const getRearLeftBrakeChartOptions = () => {
  return {
    series: [35],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["RL"],
  };
};

if (
  document.getElementById("rear-left-brake-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#rear-left-brake-chart"),
    getRearLeftBrakeChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRearLeftBrakeChartOptions());
  });
}

const getRearRightBrakeChartOptions = () => {
  return {
    series: [10],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["RL"],
  };
};

if (
  document.getElementById("rear-right-brake-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#rear-right-brake-chart"),
    getRearRightBrakeChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRearRightBrakeChartOptions());
  });
}

// radial charts for tires (car service homepage)

const getFrontLeftTireChartOptions = () => {
  return {
    series: [50],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["FL"],
  };
};

if (
  document.getElementById("front-left-tire-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#front-left-tire-chart"),
    getFrontLeftTireChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getFrontLeftTireChartOptions());
  });
}

const getFrontRightTireChartOptions = () => {
  return {
    series: [25],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["FR"],
  };
};

if (
  document.getElementById("front-right-tire-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#front-right-tire-chart"),
    getFrontRightTireChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getFrontRightTireChartOptions());
  });
}

const getRearLeftTireChartOptions = () => {
  return {
    series: [57],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["RL"],
  };
};

if (
  document.getElementById("rear-left-tire-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#rear-left-tire-chart"),
    getRearLeftTireChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRearLeftTireChartOptions());
  });
}

const getRearRightTireChartOptions = () => {
  return {
    series: [30],
    chart: {
      height: 164,
      width: 100,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        hollow: {
          size: "55%",
        },
        track: {
          show: true,
          background: getGray100AndDark700Colors(),
        },
        dataLabels: {
          show: true,
          name: {
            offsetY: -8,
            fontFamily: "Inter, sans-serif",
          },
          value: {
            show: true,
            fontFamily: "Inter, sans-serif",
            offsetY: 0,
            formatter: function (val) {
              return val + "%";
            },
          },
        },
      },
    },
    fill: {
      background: getGray100AndDark700Colors(),
    },
    stroke: {
      lineCap: "round",
    },
    colors: [getSecondaryColor()],
    labels: ["RL"],
  };
};

if (
  document.getElementById("rear-right-tire-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#rear-right-tire-chart"),
    getRearRightTireChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRearRightTireChartOptions());
  });
}

// Logistics homepage charts

// Revenue
const getRevenueSmallChartOptions = () => {
  return {
    chart: {
      height: "100px",
      maxWidth: "100%",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      y: {
        formatter: function (value) {
          return "$ " + value;
        },
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
      curve: "smooth",
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Revenue",
        data: [23500, 22418, 24178, 21200, 20157, 23200, 22246],
        color: getSuccessColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("revenue-small-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#revenue-small-chart"),
    getRevenueSmallChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getRevenueSmallChartOptions());
  });
}

// Orders
const getCompletedOrdersChartOptions = () => {
  return {
    chart: {
      height: "100px",
      maxWidth: "100%",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
      curve: "smooth",
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Orders",
        data: [3500, 3418, 3378, 3400, 3457, 3400, 3546],
        color: getSuccessColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("completed-orders-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#completed-orders-chart"),
    getCompletedOrdersChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getCompletedOrdersChartOptions());
  });
}

// Material stock
const getMaterialStockChartOptions = () => {
  return {
    chart: {
      height: "100px",
      maxWidth: "100%",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
      curve: "smooth",
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Stock",
        data: [5000, 4997, 5378, 5400, 5057, 4400, 4346],
        color: getDangerColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("material-stock-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#material-stock-chart"),
    getMaterialStockChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getMaterialStockChartOptions());
  });
}

// Successful deliveries
const getSuccessfulDeliveriesChartOptions = () => {
  return {
    chart: {
      height: "100px",
      maxWidth: "100%",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      y: {
        formatter: function (value) {
          return value + " %";
        },
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
      curve: "smooth",
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Success rate",
        data: [98, 96, 97, 95, 95, 98, 98],
        color: getSuccessColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("successful-deliveries-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#successful-deliveries-chart"),
    getSuccessfulDeliveriesChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSuccessfulDeliveriesChartOptions());
  });
}

// Total sales chart
const getInventoryChartOptions = () => {
  return {
    colors: [getPrimaryColor()],
    series: [
      {
        name: "Units",
        color: getPrimaryColor(),
        data: [
          { x: "Mon", y: 5631 },
          { x: "Tue", y: 4600 },
          { x: "Wed", y: 5540 },
          { x: "Thu", y: 5080 },
          { x: "Fri", y: 4490 },
          { x: "Sat", y: 4580 },
          { x: "Sun", y: 5620 },
        ],
      },
    ],
    chart: {
      type: "bar",
      height: "520px",
      widh: "100%",
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "70%",
        borderRadiusApplication: "end",
        borderRadius: 8,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
      style: {
        fontFamily: "Inter, sans-serif",
      },
    },
    states: {
      hover: {
        filter: {
          type: "darken",
          value: 1,
        },
      },
    },
    stroke: {
      show: false,
      width: 0,
      colors: ["transparent"],
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      floating: false,
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
    fill: {
      opacity: 1,
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
      offsetY: 20,
      height: 40,
      markers: {
        radius: 99,
      },
    },
  };
};

if (
  document.getElementById("inventory-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#inventory-chart"),
    getInventoryChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getInventoryChartOptions());
  });
}

// Total sales chart
const getTrucksChartOptions = () => {
  return {
    colors: [getPrimaryColor()],
    series: [
      {
        name: "Units",
        color: getPrimaryColor(),
        data: [
          { x: "Mon", y: 5631 },
          { x: "Tue", y: 4600 },
          { x: "Wed", y: 5540 },
          { x: "Thu", y: 5080 },
          { x: "Fri", y: 4490 },
          { x: "Sat", y: 4580 },
          { x: "Sun", y: 5620 },
        ],
      },
    ],
    chart: {
      type: "bar",
      height: "420px",
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "70%",
        borderRadiusApplication: "end",
        borderRadius: 8,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
      style: {
        fontFamily: "Inter, sans-serif",
      },
    },
    states: {
      hover: {
        filter: {
          type: "darken",
          value: 1,
        },
      },
    },
    stroke: {
      show: true,
      width: 0,
      colors: ["transparent"],
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -14,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      floating: false,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
    fill: {
      opacity: 1,
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
      offsetY: 20,
      height: 40,
      markers: {
        radius: 99,
      },
    },
  };
};

if (
  document.getElementById("trucks-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#trucks-chart"),
    getTrucksChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTrucksChartOptions());
  });
}

const getTotalDeliveriesChartOptions = () => {
  return {
    colors: [getPrimaryColor(), getSecondaryColor()],
    series: [
      {
        name: "Organic",
        color: getPrimaryColor(),
        data: [
          { x: "Mon", y: 231 },
          { x: "Tue", y: 122 },
          { x: "Wed", y: 63 },
          { x: "Thu", y: 421 },
          { x: "Fri", y: 122 },
          { x: "Sat", y: 323 },
          { x: "Sun", y: 111 },
        ],
      },
      {
        name: "Social media",
        color: getSecondaryColor(),
        data: [
          { x: "Mon", y: 232 },
          { x: "Tue", y: 113 },
          { x: "Wed", y: 341 },
          { x: "Thu", y: 224 },
          { x: "Fri", y: 522 },
          { x: "Sat", y: 411 },
          { x: "Sun", y: 243 },
        ],
      },
    ],
    chart: {
      type: "bar",
      height: "420px",
      fontFamily: "Inter, sans-serif",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "70%",
        borderRadiusApplication: "end",
        borderRadius: 8,
      },
    },
    tooltip: {
      shared: true,
      intersect: false,
      style: {
        fontFamily: "Inter, sans-serif",
      },
    },
    states: {
      hover: {
        filter: {
          type: "darken",
          value: 1,
        },
      },
    },
    stroke: {
      show: true,
      width: 0,
      colors: ["transparent"],
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -14,
      },
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      floating: false,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
    fill: {
      opacity: 1,
    },
  };
};

if (
  document.getElementById("total-deliveries-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#total-deliveries-chart"),
    getTotalDeliveriesChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTotalDeliveriesChartOptions());
  });
}

const getTotalExpensesChartOptions = () => {
  return {
    chart: {
      height: "520px",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: true,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 6,
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Expenses",
        data: [2450, 3550, 2400, 4350, 2756, 3450],
        color: getPrimaryColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 Jan",
        "02 Jan",
        "03 Jan",
        "04 Jan",
        "05 Jan",
        "06 Jan",
        "07 Jan",
      ],
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        offsetY: 4,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: true,
      },
    },
    yaxis: {
      show: true,
      labels: {
        show: true,
        align: "right",
        minWidth: 60,
        maxWidth: 60,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        offsetX: -20,
        rotate: 0,
        formatter: function (val) {
          if (val >= 1000) {
            return "$" + (val / 1000) + "k";
          }
          return "$" + val;
        },
      },
    },
  };
};

if (
  document.getElementById("expenses-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#expenses-chart"),
    getTotalExpensesChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTotalExpensesChartOptions());
  });
}

const getPortfolioChartOptions = () => {
  return {
    series: [
      {
        name: "Portfolio value",
        color: getPrimaryColor(),
        data: [
          [1327359600000, 730678.95],
          [1327446000000, 731846.34],
          [1327532400000, 731234.18],
          [1327618800000, 731243.05],
          [1327878000000, 731957.0],
          [1327964400000, 730735.95],
          [1328050800000, 731735.24],
          [1328137200000, 731126.29],
          [1328223600000, 731745.85],
          [1328482800000, 731068.86],
          [1328569200000, 732845.28],
          [1328655600000, 737352.1],
          [1328742000000, 732869.65],
          [1328828400000, 732427.21],
          [1329087600000, 737582.35],
          [1329174000000, 732445.44],
          [1329260400000, 732334.46],
          [1329346800000, 732456.86],
          [1329433200000, 732334.75],
          [1329778800000, 732100.54],
          [1329865200000, 732355.33],
          [1329951600000, 732869.97],
          [1330038000000, 733399.41],
          [1330297200000, 733483.27],
          [1330383600000, 733293.27],
          [1330470000000, 732947.89],
          [1330556400000, 733947.1],
          [1330642800000, 733234.73],
          [1330902000000, 733342.22],
          [1330988400000, 731947.99],
          [1331074800000, 732836.41],
          [1331161200000, 733867.05],
          [1331247600000, 733375.64],
          [1331506800000, 733068.56],
          [1331593200000, 734746.22],
          [1331679600000, 733475.77],
          [1331766000000, 734345.17],
          [1331852400000, 733947.82],
          [1332111600000, 734264.51],
          [1332198000000, 733843.16],
          [1332284400000, 733247.56],
          [1332370800000, 733624.71],
          [1332457200000, 733134.81],
          [1332712800000, 734589.4],
          [1332799200000, 734964.63],
          [1332885600000, 734274.46],
          [1332972000000, 734368.48],
          [1333058400000, 734958.31],
          [1333317600000, 734845.7],
          [1333404000000, 734346.31],
          [1333490400000, 733374.46],
          [1333576800000, 733058.59],
          [1333922400000, 733346.22],
          [1334008800000, 732967.61],
          [1334095200000, 733485.01],
          [1334181600000, 733836.55],
          [1334268000000, 733068.18],
          [1334527200000, 732857.84],
          [1334613600000, 733065.84],
          [1334700000000, 733333.39],
          [1334786400000, 732467.91],
          [1334872800000, 733432.06],
          [1335132000000, 732456.62],
          [1335218400000, 732867.4],
          [1335304800000, 733456.13],
          [1335391200000, 733333.26],
          [1335477600000, 733466.58],
          [1335736800000, 733567.55],
          [1335823200000, 733235.77],
          [1335909600000, 733234.76],
          [1335996000000, 733467.32],
          [1336082400000, 732436.61],
          [1336341600000, 732235.52],
          [1336428000000, 732235.67],
          [1336514400000, 732789.52],
          [1336600800000, 731223.92],
          [1336687200000, 732346.2],
          [1336946400000, 732346.23],
          [1337032800000, 732399.33],
          [1337119200000, 732999.36],
          [1337205600000, 732888.01],
          [1337292000000, 731678.31],
          [1337551200000, 732535.01],
          [1337637600000, 732689.01],
          [1337724000000, 732445.18],
          [1337810400000, 731654.54],
          [1337896800000, 731777.6],
          [1338242400000, 732896.05],
          [1338328800000, 731567.29],
          [1338415200000, 731754.05],
          [1338501600000, 729345.82],
          [1338760800000, 730357.31],
          [1338847200000, 730565.7],
          [1338933600000, 731586.69],
          [1339020000000, 731778.32],
          [1339106400000, 731778.65],
          [1339365600000, 731898.13],
          [1339452000000, 731567.77],
          [1339538400000, 731273.79],
          [1339624800000, 731342.67],
          [1339711200000, 732334.39],
          [1339970400000, 732573.63],
          [1340056800000, 732237.89],
          [1340143200000, 731274.99],
          [1340229600000, 731333.23],
          [1340316000000, 731468.57],
          [1340575200000, 730373.84],
          [1340661600000, 731444.07],
          [1340748000000, 731235.41],
          [1340834400000, 731142.17],
          [1340920800000, 732357.37],
          [1341180000000, 732325.19],
          [1341266400000, 732223.51],
          [1341439200000, 732222.53],
          [1341525600000, 731363.37],
          [1341784800000, 730467.43],
          [1341871200000, 730343.44],
          [1341957600000, 730234.2],
          [1342044000000, 730234.14],
          [1342130400000, 730231.65],
          [1342389600000, 730234.4],
          [1342476000000, 730136.65],
          [1342562400000, 731886.43],
          [1342648800000, 731566.89],
          [1342735200000, 731888.38],
          [1342994400000, 730754.64],
          [1343080800000, 730574.02],
          [1343167200000, 730555.33],
          [1343253600000, 730458.95],
          [1343340000000, 731432.89],
          [1343599200000, 731464.01],
          [1343685600000, 730345.88],
          [1343772000000, 730332.69],
          [1343858400000, 730353.58],
          [1343944800000, 732221.02],
          [1344204000000, 732245.14],
          [1344290400000, 732941.37],
          [1344376800000, 732923.51],
          [1344463200000, 732815.65],
          [1344549600000, 732222.64],
          [1344808800000, 732444.27],
          [1344895200000, 732377.1],
          [1344981600000, 732432.91],
          [1345068000000, 733954.65],
          [1345154400000, 733222.8],
          [1345413600000, 733353.92],
          [1345500000000, 733468.75],
          [1345586400000, 733473.84],
          [1345672800000, 733383.5],
          [1345759200000, 732998.26],
          [1346018400000, 732448.32],
          [1346104800000, 732746.06],
          [1346191200000, 731382.96],
          [1346277600000, 731038.46],
          [1346364000000, 731271.27],
          [1346709600000, 731949.43],
          [1346796000000, 732826.26],
          [1346882400000, 732184.79],
          [1346968800000, 732222.46],
          [1347228000000, 732776.13],
          [1347314400000, 732123.43],
          [1347400800000, 732353.42],
          [1347487200000, 732758.81],
          [1347573600000, 733222.34],
          [1347832800000, 733427.41],
          [1347919200000, 732275.57],
          [1348005600000, 733267.12],
          [1348092000000, 734273.53],
          [1348178400000, 733837.83],
          [1348437600000, 733333.41],
          [1348524000000, 732435.9],
          [1348610400000, 732333.53],
          [1348696800000, 732567.8],
          [1348783200000, 732262.44],
          [1349042400000, 732836.62],
          [1349128800000, 732746.57],
          [1349215200000, 732246.6],
          [1349301600000, 732235.68],
          [1349388000000, 732523.47],
          [1349647200000, 732997.23],
          [1349733600000, 731346.68],
          [1349820000000, 731222.51],
          [1349906400000, 731324.78],
          [1349992800000, 731444.94],
          [1350252000000, 732384.33],
          [1350338400000, 733374.24],
          [1350424800000, 733746.44],
          [1350511200000, 733847.48],
          [1350597600000, 733343.24],
          [1350856800000, 733476.49],
          [1350943200000, 733374.31],
          [1351029600000, 733345.36],
          [1351116000000, 733234.4],
          [1351202400000, 734234.01],
          [1351638000000, 734223.02],
          [1351724400000, 734264.36],
          [1351810800000, 734345.39],
          [1352070000000, 734345.24],
          [1352156400000, 734322.39],
          [1352242800000, 733345.47],
          [1352329200000, 732321.98],
          [1352415600000, 732346.9],
          [1352674800000, 732234.7],
          [1352761200000, 732343.54],
          [1352847600000, 732464.23],
          [1352934000000, 732343.64],
          [1353020400000, 732478.65],
          [1353279600000, 732234.92],
          [1353366000000, 732111.64],
          [1353452400000, 732353.84],
          [1353625200000, 733222.4],
          [1353884400000, 733978.3],
          [1353970800000, 733578.18],
          [1354057200000, 733347.88],
          [1354143600000, 734184.09],
          [1354230000000, 734483.61],
          [1354489200000, 734355.7],
          [1354575600000, 735658.3],
          [1354662000000, 735284.4],
          [1354748400000, 735231.14],
          [1354834800000, 735433.48],
          [1355094000000, 735564.75],
          [1355180400000, 735235.54],
          [1355266800000, 735238.96],
          [1355353200000, 735856.53],
          [1355439600000, 737493.56],
          [1355698800000, 737273.42],
          [1355785200000, 737111.49],
          [1355871600000, 738234.09],
          [1355958000000, 737434.87],
          [1356044400000, 737345.71],
          [1356303600000, 737333.53],
          [1356476400000, 737667.55],
          [1356562800000, 737765.3],
          [1356649200000, 736567.9],
          [1356908400000, 737567.68],
          [1357081200000, 738456.34],
          [1357167600000, 737444.75],
          [1357254000000, 738567.13],
          [1357513200000, 737435.94],
          [1357599600000, 738786.14],
          [1357686000000, 738333.66],
          [1357772400000, 738998.62],
          [1357858800000, 738667.09],
          [1358118000000, 738125.16],
          [1358204400000, 738686.15],
          [1358290800000, 737234.88],
          [1358377200000, 737567.73],
          [1358463600000, 737333.98],
          [1358809200000, 737457.95],
          [1358895600000, 738234.25],
          [1358982000000, 738637.1],
          [1359068400000, 738918.32],
          [1359327600000, 738391.24],
          [1359414000000, 738847.52],
          [1359500400000, 737347.94],
          [1359586800000, 737234.83],
          [1359673200000, 738222.34],
          [1359932400000, 738464.1],
          [1360018800000, 738321.51],
          [1360105200000, 737835.4],
          [1360191600000, 738123.07],
          [1360278000000, 739468.12],
          [1360537200000, 738233.64],
          [1360623600000, 738457.89],
          [1360710000000, 738223.81],
          [1360796400000, 738777.61],
          [1360882800000, 738888.63],
          [1361228400000, 738978.99],
          [1361314800000, 738979.77],
          [1361401200000, 738345.34],
          [1361487600000, 738989.55],
          [1361746800000, 738990.11],
          [1361833200000, 738991.59],
          [1361919600000, 739992.6],
        ],
      },
    ],
    chart: {
      height: "520px",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 6,
    },
    grid: {
      show: true,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    xaxis: {
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        offsetY: 0,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      type: "datetime",
      min: new Date("01 Mar 2012").getTime(),
    },
    yaxis: {
      show: true,
      labels: {
        show: true,
        align: "right",
        minWidth: 60,
        maxWidth: 60,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        offsetX: -20,
        rotate: 0,
        formatter: function (val) {
          if (val >= 1000) {
            return "$" + (val / 1000) + "k";
          }
          return "$" + val;
        },
      },
    },
    tooltip: {
      x: {
        format: "dd MMM yyyy",
      },
    },
  };
};

if (
  document.getElementById("portfolio-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#portfolio-chart"),
    getPortfolioChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getPortfolioChartOptions());
  });
}

const getCoinChartOptions = () => {
  return {
    chart: {
      height: "360px",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: true,
      },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getSuccessColor(),
        gradientToColors: [getSuccessColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 6,
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: 0,
      },
    },
    series: [
      {
        name: "Price",
        data: [2450, 2550, 2578, 2590, 2756, 3020, 3150],
        color: getSuccessColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 Jan",
        "02 Jan",
        "03 Jan",
        "04 Jan",
        "05 Jan",
        "06 Jan",
        "07 Jan",
      ],
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        offsetY: 4,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: true,
      },
    },
    yaxis: {
      show: true,
      labels: {
        show: false,
        formatter: function (val) {
          if (val >= 1000) {
            return "$" + (val / 1000) + "k";
          }
          return "$" + val;
        },
      },
    },
  };
};

if (
  document.getElementById("coin-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#coin-chart"),
    getCoinChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getCoinChartOptions());
  });
}

const getProfitChartOptions = () => {
  return {
    series: [
      {
        name: "Income",
        color: getSuccessColor(),
        data: ["1420", "1620", "1820", "1420", "1650", "2120"],
      },
      {
        name: "Expense",
        data: ["788", "810", "866", "788", "1100", "1200"],
        color: getDangerColor(),
      },
    ],
    chart: {
      sparkline: {
        enabled: false,
      },
      type: "bar",
      width: "100%",
      height: 400,
      toolbar: {
        show: false,
      },
    },
    fill: {
      opacity: 1,
    },
    plotOptions: {
      bar: {
        horizontal: true,
        columnWidth: "100%",
        borderRadiusApplication: "end",
        borderRadius: 6,
        dataLabels: {
          position: "top",
        },
      },
    },
    legend: {
      show: true,
      position: "bottom",
    },
    dataLabels: {
      enabled: false,
    },
    tooltip: {
      shared: true,
      intersect: false,
      formatter: function (val) {
        if (val >= 1000) {
          return "$" + (val / 1000) + "k";
        }
        return "$" + val;
      },
    },
    xaxis: {
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        formatter: function (val) {
          if (val >= 1000) {
            return "$" + (val / 1000) + "k";
          }
          return "$" + val;
        },
      },
      categories: ["Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
    },
    grid: {
      show: true,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -20,
      },
    },
    fill: {
      opacity: 1,
    },
  };
};

if (
  document.getElementById("profit-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#profit-chart"),
    getProfitChartOptions(),
  );
  chart.render();
  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getProfitChartOptions());
  });
}

const getDonutTrafficChartOptions = () => {
  return {
    series: [75, 14, 6.4, 2.6, 2],
    colors: [getPrimaryColor(), getTertiaryColor(), getSecondaryColor(), getQuaternaryColor(), getQuinaryColor()],
    chart: {
      height: 400,
      width: "100%",
      type: "donut",
    },
    stroke: {
      colors: [getWhiteAndDark900Colors()],
      lineCap: "",
    },
    plotOptions: {
      pie: {
        donut: {
          labels: {
            show: true,
            name: {
              show: true,
              fontFamily: "Inter, sans-serif",
              offsetY: 20,
            },
            total: {
              showAlways: true,
              show: true,
              label: "Unique visitors",
              fontFamily: "Inter, sans-serif",
              formatter: function (w) {
                const sum = w.globals.seriesTotals.reduce((a, b) => {
                  return a + b;
                }, 0);
                return sum + "k";
              },
            },
            value: {
              show: true,
              fontFamily: "Inter, sans-serif",
              offsetY: -20,
              formatter: function (value) {
                return value + "%";
              },
            },
          },
          size: "80%",
        },
      },
    },
    grid: {
      padding: {
        top: -2,
      },
    },
    labels: ["Direct", "Google", "Reddit", "Twitter", "Producthunt"],
    dataLabels: {
      enabled: false,
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
    xaxis: {
      labels: {
        formatter: function (value) {
          return value + "k";
        },
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
  };
};

if (
  document.getElementById("donut-traffic-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#donut-traffic-chart"),
    getDonutTrafficChartOptions(),
  );
  chart.render();

  // Get all the checkboxes by their class name
  const checkboxes = document.querySelectorAll(
    '#devices input[type="checkbox"]',
  );

  // Function to handle the checkbox change event
  function handleCheckboxChange(event, chart) {
    const checkbox = event.target;
    if (checkbox.checked) {
      switch (checkbox.value) {
        case "desktop":
          chart.updateSeries([15.1, 22.5, 4.4, 8.4]);
          break;
        case "tablet":
          chart.updateSeries([25.1, 26.5, 1.4, 3.4]);
          break;
        case "mobile":
          chart.updateSeries([45.1, 27.5, 8.4, 2.4]);
          break;
        default:
          chart.updateSeries([55.1, 28.5, 1.4, 5.4]);
      }
    } else {
      chart.updateSeries([35.1, 23.5, 2.4, 5.4]);
    }
  }

  // Attach the event listener to each checkbox
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", (event) =>
      handleCheckboxChange(event, chart),
    );
  });
}

const getProjectCompletionChartOptions = () => {
  return {
    series: [92],
    fill: {
      colors: [getPrimaryColor()],
      opacity: 1,
      type: "solid",
    },
    chart: {
      height: "400px",
      width: "100%",
      type: "radialBar",
      sparkline: {
        enabled: true,
      },
    },

    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: "300px",
          },
        },
      },
    ],
    plotOptions: {
      radialBar: {
        track: {
          background: getGray100AndDark700Colors(),
          style: {
            cssClass: "dark:bg-gray-700",
          },
        },
        hollow: {
          margin: 0,
          size: "60%",
        },
      },
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -23,
        bottom: -20,
      },
    },
    labels: ["Completion rate"],
    tooltip: {
      enabled: true,
      x: {
        show: true,
      },
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
  };
};

if (
  document.getElementById("project-completion-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#project-completion-chart"),
    getProjectCompletionChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getProjectCompletionChartOptions());
  });
}

const getProjectsTakenChartOptions = () => {
  return {
    fill: {
      colors: [getPrimaryColor()],
      opacity: 1,
      type: "solid",
    },
    backgroundBarOpacity: 0,
    series: [
      {
        name: "Projects",
        data: [7, 8, 9, 6, 7, 10],
      },
    ],
    chart: {
      height: 400,
      type: "bar",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        borderRadius: 8,
        dataLabels: {
          position: "top", // top, center, bottom
        },
      },
    },
    dataLabels: {
      enabled: false,
      offsetY: -30,
      style: {
        fontSize: "16px",
        colors: ["#304758"],
        cssClass: legendColors.class
      },
    },
    xaxis: {
      categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
      position: "bottom",
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      tooltip: {
        enabled: true,
      },
    },
    yaxis: {
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        show: false,
      },
    },
    grid: {
      show: false,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0
      }
    },
  };
};

if (
  document.getElementById("projects-taken-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#projects-taken-chart"),
    getProjectsTakenChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getProjectsTakenChartOptions());
  });
}

const getUsersByDayChartOptions = () => {
  return {
    fill: {
      colors: [getPrimaryColor()],
      opacity: 1,
      type: "solid",
    },
    backgroundBarOpacity: 0,
    series: [
      {
        name: "Users",
        data: [2138, 3547, 4567, 2948, 3946, 4056, 3067],
      },
    ],
    chart: {
      width: "100%",
      height: 400,
      type: "bar",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        borderRadius: 8,
        dataLabels: {
          position: "top", // top, center, bottom
        },
      },
    },
    dataLabels: {
      enabled: false,
      offsetY: -30,
    },
    xaxis: {
      categories: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      position: "bottom",
      labels: {
        show: true,
        trim: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      tooltip: {
        enabled: true,
      },
    },
    yaxis: {
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        show: false,
      },
    },
    grid: {
      show: false,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
      }
    },
  };
};

if (
  document.getElementById("users-by-day") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#users-by-day"),
    getUsersByDayChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getUsersByDayChartOptions());
  });
}

const getTicketsOverviewChartOptions = () => {
  return {
    // set grid lines to improve the readability of the chart, learn more here: https://apexcharts.com/docs/grid/
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 2,
        right: 2,
        top: -26,
      },
    },
    series: [
      {
        name: "Received tickets",
        data: [1500, 2918, 1456, 4526, 1656, 1856],
        color: getPrimaryColor(),
      },
      {
        name: "Solved Tickets",
        data: [643, 2413, 765, 3412, 1423, 1731],
        color: getSecondaryColor(),
      },
    ],
    chart: {
      height: "520px",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    legend: {
      show: true,
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 6,
      curve: "smooth",
    },
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("tickets-overview-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#tickets-overview-chart"),
    getTicketsOverviewChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTicketsOverviewChartOptions());
  });
}

const getTotalRevenueChartOptions = () => {
  return {
    // set grid lines to improve the readability of the chart, learn more here: https://apexcharts.com/docs/grid/
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0
      }
    },
    series: [
      {
        name: "Income",
        data: [7564, 5643, 4300, 8033, 6444, 4300, 7752],
        color: getPrimaryColor(),
      },
      {
        name: "Expenses",
        data: [4443, 4413, 3765, 4412, 7423, 3731, 1856],
        color: getSecondaryColor(),
      },
    ],
    chart: {
      height: "520px",
      width: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    legend: {
      show: false,
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: getPrimaryColor(),
        gradientToColors: [getPrimaryColor()],
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 6,
      curve: "smooth",
    },
    xaxis: {
      categories: [
        "01 Feb",
        "02 Feb",
        "03 Feb",
        "04 Feb",
        "05 Feb",
        "06 Feb",
        "07 Feb",
      ],
      labels: {
        trim: true,
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: true,
      labels: {
        show: true,
        trim: true,
        align: "right",
        minWidth: 60,
        maxWidth: 60,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
        offsetX: -20,
        rotate: 0,
        formatter: function (val) {
          if (val >= 1000) {
            return "$" + (val / 1000) + "k";
          }
          return "$" + val;
        },
      },
    },
  };
};

if (
  document.getElementById("total-revenue") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#total-revenue"),
    getTotalRevenueChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getTotalRevenueChartOptions());
  });
}

const getServiceLevelChartOptions = () => {
  return {
    series: [87.2],
    fill: {
      colors: [getSuccessColor()],
      opacity: 1,
      type: "solid",
    },
    chart: {
      height: "180px",
      width: "100%",
      type: "radialBar",
      sparkline: {
        enabled: true,
      },
    },

    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: "200px",
          },
        },
      },
    ],
    plotOptions: {
      radialBar: {
        track: {
          background: getGray100AndDark700Colors(),
          style: {
            cssClass: "dark:bg-gray-700",
          },
        },
        hollow: {
          size: "60%",
        },
        dataLabels: {
          name: {
            show: true,
            offsetY: -5,
          },
          value: {
            offsetY: 4,
          },
        },
      },
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        bottom: -20,
      },
    },
    labels: ["Service level"],
    tooltip: {
      enabled: true,
      x: {
        show: true,
      },
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
  };
};

if (
  document.getElementById("service-level-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#service-level-chart"),
    getServiceLevelChartOptions(),
  );
  chart.render();

  // init again when toggling dark mode
  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getServiceLevelChartOptions());
  });
}

const getSmallUsersChartOptions = () => {
  return {
    chart: {
      height: "100%",
      width: "140px",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },

    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
    },
    grid: {
      show: false,
    },
    series: [
      {
        name: "Users",
        data: [6500, 6418, 3456, 6526, 2356, 6456],
        color: getSuccessColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 April",
        "02 April",
        "03 April",
        "04 April",
        "05 April",
        "06 April",
        "07 April",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("small-users-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#small-users-chart"),
    getSmallUsersChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSmallUsersChartOptions());
  });
}

const getSmallCustomersChartOptions = () => {
  return {
    chart: {
      height: "100%",
      width: "140px",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },

    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
    },
    grid: {
      show: false,
    },
    series: [
      {
        name: "Customers",
        data: [100, 80, 230, 300, 104, 80],
        color: getDangerColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 April",
        "02 April",
        "03 April",
        "04 April",
        "05 April",
        "06 April",
        "07 April",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("small-customers-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#small-customers-chart"),
    getSmallCustomersChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSmallCustomersChartOptions());
  });
}

const getSmallRevenueChartOptions = () => {
  return {
    chart: {
      height: "100%",
      width: "140px",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      followCursor: true,
      x: {
        show: false,
      },
      fixed: {
        enabled: true,
        position: "top",
        offsetX: 0,
        offsetY: -24,
      },
    },

    grid: {
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0
      }
    },

    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
    },
    grid: {
      show: false,
    },
    series: [
      {
        name: "Revenue",
        data: [1500, 800, 947, 1102, 1640, 1847],
        color: getSuccessColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
        trim: true
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("small-revenue-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#small-revenue-chart"),
    getSmallRevenueChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSmallRevenueChartOptions());
  });
}

const getSmallProductsChartOptions = () => {
  return {
    colors: [getPrimaryColor(), getSecondaryColor()],
    series: [
      {
        name: "Quantity",
        color: getPrimaryColor(),
        data: [
          { x: "01 Feb", y: 170 },
          { x: "02 Feb", y: 180 },
          { x: "03 Feb", y: 164 },
          { x: "04 Feb", y: 145 },
          { x: "05 Feb", y: 194 },
          { x: "06 Feb", y: 170 },
          { x: "07 Feb", y: 155 },
        ],
      },
    ],
    chart: {
      type: "bar",
      height: "90px",
      fontFamily: "Inter, sans-serif",
      foreColor: "#4B5563",
      parentHeightOffset: 0,
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        columnWidth: "90%",
        barHeight: "100%",
        borderRadius: 3,
      },
    },
    tooltip: {
      shared: false,
      intersect: true,
      style: {
        fontSize: "12px",
        fontFamily: "Inter, sans-serif",
      },
    },
    states: {
      hover: {
        filter: {
          type: "darken",
          value: 1,
        },
      },
    },
    stroke: {
      show: true,
      width: 5,
      colors: ["transparent"],
    },
    grid: {
      show: false,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
      }
    },
    dataLabels: {
      enabled: false,
    },
    legend: {
      show: false,
    },
    xaxis: {
      floating: true,
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
    fill: {
      opacity: 1,
    },
  };
};

if (
  document.getElementById("products-small-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#products-small-chart"),
    getSmallProductsChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSmallProductsChartOptions());
  });
}

const getSmallUserSignupsChartOptions = () => {
  return {
    chart: {
      type: "area",
      height: "90px",
      sparkline: {
        enabled: true,
      },
    },
    tooltip: {
      followCursor: true,
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
      curve: "smooth",
    },
    grid: {
      show: false,
    },
    series: [
      {
        name: "Users",
        data: [3460, 3418, 3456, 3414, 3446, 3434],
        color: getPrimaryColor(),
      },
    ],
    xaxis: {
      categories: [
        "01 February",
        "02 February",
        "03 February",
        "04 February",
        "05 February",
        "06 February",
        "07 February",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("small-users-signups-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#small-users-signups-chart"),
    getSmallUserSignupsChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getSmallUserSignupsChartOptions());
  });
}

const getWebsiteTrafficChartOptions = () => {
  return {
    series: [52.8, 26.8, 20.4],
    colors: [getPrimaryColor(), getSecondaryColor(), getQuaternaryColor()],
    chart: {
      height: 320,
      width: "100%",
      type: "pie",
    },
    stroke: {
      colors: [getWhiteAndDark900Colors()],
      lineCap: "",
    },
    plotOptions: {
      pie: {
        labels: {
          show: true,
        },
        size: "100%",
        dataLabels: {
          offset: -25,
        },
      },
    },
    grid: {
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
      }
    },
    labels: ["Direct", "Organic search", "Referrals"],
    dataLabels: {
      enabled: true,
      style: {
        fontFamily: "Inter, sans-serif",
      },
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
    },
    xaxis: {
      labels: {
        formatter: function (value) {
          return value + "%";
        },
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
  };
};

if (
  document.getElementById("website-traffic-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.getElementById("website-traffic-chart"),
    getWebsiteTrafficChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getWebsiteTrafficChartOptions());
  });
}

const getCostPerClickChartOptions = () => {
  return {
    chart: {
      height: "286px",
      maxWidth: "100%",
      type: "line",
      fontFamily: "Inter, sans-serif",
      dropShadow: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    tooltip: {
      enabled: true,
      x: {
        show: false,
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 4,
      curve: "smooth",
    },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: {
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
      },
    },
    series: [
      {
        name: "Clicks",
        data: [6500, 6418, 6456, 6526, 6356, 6456],
        color: getPrimaryColor(),
      },
      {
        name: "CPC",
        data: [6456, 6356, 6526, 6332, 6418, 6500],
        color: getSecondaryColor(),
      },
    ],
    legend: {
      show: false,
    },
    xaxis: {
      categories: [
        "01 Feb",
        "02 Feb",
        "03 Feb",
        "04 Feb",
        "05 Feb",
        "06 Feb",
        "07 Feb",
      ],
      labels: {
        show: false,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      show: false,
    },
  };
};

if (
  document.getElementById("cost-per-click-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.getElementById("cost-per-click-chart"),
    getCostPerClickChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getCostPerClickChartOptions());
  });
}

const getUptimeChartOptions = () => {
  return {
    series: [
      {
        name: "Mon",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Tue",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Wed",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Thu",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Fri",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Sat",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
      {
        name: "Sun",
        data: generateData(6, {
          min: 0,
          max: 90,
        }),
      },
    ],
    chart: {
      height: 350,
      type: "heatmap",
      toolbar: {
        show: false,
      },
    },
    dataLabels: {
      enabled: false,
    },
    yaxis: {
      show: true,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
    },
    tooltip: {
      shared: false,
      followCursor: true,
      fillSeriesColor: true,
      style: {
        fontSize: "14px",
        fontFamily: "Inter, sans-serif",
      },
      y: {
        formatter: function (value) {
          return value + " issues";
        },
      },
    },
    xaxis: {
      type: "months",
      categories: ["Jan", "Feb", "Mar", "Apr", "May"],
      floating: false,
      labels: {
        show: true,
        style: {
          fontFamily: "Inter, sans-serif",
          cssClass: legendColors.class
        },
      },
      tooltip: {
        enabled: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    colors: [getPrimaryColor()],
    responsive: [
      {
        breakpoint: 480,
        options: {
          yaxis: {
            labels: {
              show: false,
            },
          },
        },
      },
    ],
  };
};

if (
  document.getElementById("uptime-chart") &&
  typeof ApexCharts !== "undefined"
) {
  const chart = new ApexCharts(
    document.querySelector("#uptime-chart"),
    getUptimeChartOptions(),
  );
  chart.render();

  document.addEventListener("rerender-charts", function () {
    chart.updateOptions(getUptimeChartOptions());
  });
}
