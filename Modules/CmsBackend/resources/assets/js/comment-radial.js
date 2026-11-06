import ApexCharts from 'apexcharts';

const timelineDiv = document.querySelector('#commentRadialChart');

if (timelineDiv) {
  const rawData = JSON.parse(timelineDiv.dataset.series); // [{ x: '06-2025', y: 2 }, ...]
  var seriesData = [];
  var categoriesData = [];
  rawData.forEach(item => {
    seriesData.push(item.y);
    categoriesData.push(item.x);
  });
  var options = {
    series: [
      {
        name: window.cmsAdminTranslations.comment_count,
        data: seriesData
      }
    ],
    chart: {
      height: 350,
      type: 'line',
      dropShadow: {
        enabled: true,
        color: '#000',
        top: 18,
        left: 7,
        blur: 10,
        opacity: 0.5
      },
      zoom: {
        enabled: false
      },
      toolbar: {
        show: false
      }
    },
    colors: ['#77B6EA', '#545454'],
    dataLabels: {
      enabled: true
    },
    stroke: {
      curve: 'smooth'
    },
    xaxis: {
      categories: categoriesData
    }
  };

  //   var chart = new ApexCharts(document.querySelector('#chart'), options);
  //   chart.render();

  new ApexCharts(timelineDiv, options).render();
}
