document.addEventListener('DOMContentLoaded', function() {
  const callsCtx = document.getElementById('callsChart');
  if (callsCtx) {
    new Chart(callsCtx, {
      type: 'line',
      data: {
        labels: ['10.3', '11.3', '12.3', '13.3', '14.3', '15.3', '16.3'],
        datasets: [{
          label: 'Anrufe',
          data: [12, 15, 18, 14, 21, 25, 23],
          borderColor: 'rgb(79, 70, 229)'
        }, {
          label: 'Erfolgreich',
          data: [9, 11, 14, 12, 16, 20, 19],
          borderColor: 'rgb(16, 185, 129)'
        }]
      }
    });
  }
});
