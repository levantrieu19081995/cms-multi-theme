import ApexCharts from 'apexcharts';

document.addEventListener('DOMContentLoaded', () => {
  const chartDiv = document.querySelector('#feedbackDonutChart');
  if (!chartDiv) return;

  const series = JSON.parse(chartDiv.dataset.series);
  const labels = JSON.parse(chartDiv.dataset.labels);

  const options = {
    series: series,
    chart: {
      type: 'donut',
      height: 250
    },
    labels: labels,
    colors: ['#3b82f6', '#f87171'],
    dataLabels: {
      enabled: true
    },
    legend: {
      position: 'bottom'
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: { width: 200 },
          legend: { position: 'bottom' }
        }
      }
    ]
  };

  const chart = new ApexCharts(chartDiv, options);
  chart.render();
});
