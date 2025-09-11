import { DataTable } from "simple-datatables";

if (document.getElementById("filter-table") && typeof DataTable !== "undefined") {
  const dataTable = new DataTable("#filter-table", {
    tableRender: (_data, table, type) => {
      if (type === "print") {
        return table;
      }
      const tHead = table.childNodes[0];
      const filterHeaders = {
        nodeName: "TR",
        attributes: {
          class: "search-filtering-row",
        },
        childNodes: tHead.childNodes[0].childNodes.map((_th, index) => ({
          nodeName: "TH",
          childNodes: [
            {
              nodeName: "INPUT",
              attributes: {
                class: "datatable-input",
                type: "search",
                "data-columns": "[" + index + "]",
              },
            },
          ],
        })),
      };
      tHead.childNodes.push(filterHeaders);
      return table;
    },
    paging: true,
    perPage: 15,
    perPageSelect: [5, 10, 15, 20, 25],
  });
}
