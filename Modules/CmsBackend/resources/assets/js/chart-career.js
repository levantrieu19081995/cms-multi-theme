import ApexCharts from 'apexcharts';

document.addEventListener('DOMContentLoaded', () => {
  const chartDiv = document.querySelector('#careerSurveyChart');
  if (!chartDiv) return;

  const categories = JSON.parse(chartDiv.dataset.categories);
  const count = JSON.parse(chartDiv.dataset.count);

  const options = {
    series: count,
    chart: {
      type: 'bar',
      height: 400,
      stacked: true
    },
    plotOptions: {
      bar: {
        horizontal: true
      }
    },
    xaxis: {
      categories
    },
    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#6366f1']
  };

  new ApexCharts(chartDiv, options).render();
});
