document.addEventListener('DOMContentLoaded',()=>{
  const data=JSON.parse(document.getElementById('page-analytics-data').textContent);
  if(typeof Chart==='undefined'){
    document.querySelectorAll('.visit-chart-wrap').forEach(el=>{el.textContent='Charts could not load. Traffic totals are available in the tables below.';el.className='visit-chart-empty';});
    return;
  }
  const palette=['#0b7545','#ff795f','#3b82b0','#dab157','#9276b8'];
  const format=value=>Number(value).toLocaleString();
  const axes={grid:{color:'#edf1ee'},ticks:{color:'#687c70',precision:0}};
  const common={responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{usePointStyle:true,boxWidth:8,color:'#50645b'}}}};
  new Chart(document.getElementById('traffic-trend'),{
    type:'line',data:{labels:data.trend.map(day=>day.date),datasets:[
      {label:'Page views',data:data.trend.map(day=>day.views),borderColor:palette[0],backgroundColor:'rgba(11,117,69,.08)',fill:true,tension:.2,pointRadius:2},
      {label:'Unique visitors',data:data.trend.map(day=>day.visitors),borderColor:palette[1],backgroundColor:palette[1],tension:.2,pointRadius:2}
    ]},options:{...common,interaction:{mode:'index',intersect:false},scales:{x:{...axes,ticks:{color:'#687c70',maxTicksLimit:10}},y:{...axes,beginAtZero:true}}}
  });
  if(!data.totalViews)return;
  new Chart(document.getElementById('traffic-top-pages'),{
    type:'bar',data:{labels:data.topPages.map(page=>page.page_path),datasets:[{label:'Page views',data:data.topPages.map(page=>Number(page.views)),backgroundColor:palette[0],borderRadius:5}]},
    options:{...common,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{...axes,beginAtZero:true},y:{...axes,ticks:{color:'#50645b',callback:function(value){const path=this.getLabelForValue(value);return path.length>32?path.slice(0,29)+'…':path;}}}}}
  });
  const sections=Object.entries(data.segments).filter(([,views])=>views>0);
  new Chart(document.getElementById('traffic-sections'),{
    type:'doughnut',data:{labels:sections.map(([name])=>name),datasets:[{data:sections.map(([,views])=>views),backgroundColor:palette,borderWidth:3,borderColor:'#fff'}]},
    options:{...common,cutout:'65%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,color:'#50645b'}},tooltip:{callbacks:{label:context=>context.label+': '+format(context.raw)+' views ('+(Number(context.raw)/data.totalViews*100).toFixed(1)+'%)'}}}}
  });
});
