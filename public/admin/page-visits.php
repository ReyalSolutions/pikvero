<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Core\Database\Connection;
Auth::requireAuth();
if (!in_array(Auth::role(),['super_admin','platform_admin'],true)) \App\Core\Http\Response::forbidden('Only platform administrators can view website traffic.');
$days=(int)($_GET['days']??30);
if(!in_array($days,[7,30,90],true))$days=30;
$start=(new DateTimeImmutable('today'))->modify('-'.($days-1).' days')->format('Y-m-d');
$db=Connection::getInstance();
$summary=$db->selectOne('SELECT COALESCE(SUM(views),0) AS views,COUNT(DISTINCT visitor_hash) AS visitors,COUNT(DISTINCT user_id) AS users FROM page_visits WHERE visit_date>=?',[$start],'s');
$pages=$db->select('SELECT page_path,SUM(views) AS views,COUNT(DISTINCT visitor_hash) AS visitors,COUNT(DISTINCT user_id) AS users,MAX(last_seen) AS last_seen FROM page_visits WHERE visit_date>=? GROUP BY page_path ORDER BY views DESC',[$start],'s');
$daily=$db->select('SELECT visit_date,SUM(views) AS views,COUNT(DISTINCT visitor_hash) AS visitors FROM page_visits WHERE visit_date>=? GROUP BY visit_date ORDER BY visit_date DESC',[$start],'s');
$dailyMap=array_column($daily,null,'visit_date');
$trend=[];
for($i=0;$i<$days;$i++){
    $date=(new DateTimeImmutable($start))->modify('+'.$i.' days')->format('Y-m-d');
    $trend[]=['date'=>$date,'views'=>(int)($dailyMap[$date]['views']??0),'visitors'=>(int)($dailyMap[$date]['visitors']??0)];
}
$totalViews=(int)$summary['views'];
$viewsPerVisitor=(int)$summary['visitors']>0?$totalViews/(int)$summary['visitors']:0;
$peak=null;
foreach($daily as $day){if(!$peak || (int)$day['views']>(int)$peak['views'])$peak=$day;}
$segments=['Landing page'=>0,'Public pages'=>0,'Player pages'=>0,'Owner pages'=>0,'Admin pages'=>0];
foreach($pages as $page){
    $path=$page['page_path'];
    $group=$path==='/'?'Landing page':(str_starts_with($path,'/public/customer/')?'Player pages':(str_starts_with($path,'/public/owner/')?'Owner pages':(str_starts_with($path,'/public/admin/')?'Admin pages':'Public pages')));
    $segments[$group]+=(int)$page['views'];
}
$chartData=['trend'=>$trend,'topPages'=>array_slice($pages,0,10),'segments'=>$segments,'totalViews'=>$totalViews];
$pageTitle='Pikvero — Page Analytics';$headExtras=['datatables','chartjs'];require __DIR__.'/../../includes/head.php';
?>
<style>
.visit-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:20px 0}.visit-panel{padding:20px;border:1px solid #dce5df!important;box-shadow:none!important;border-radius:14px!important;margin-bottom:20px}.visit-panel h2{font-size:1.15rem!important;margin:0 0 12px}.visit-summary strong{display:block;font-size:1.7rem;color:#0b7545;margin-top:8px}.visit-table-wrap{overflow-x:auto}.visit-table{width:100%;font-size:.8rem}.visit-table th,.visit-table td{padding:12px!important;text-align:left}.visit-header{display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap}.visit-header select{padding:10px;border:1px solid #dce5df;border-radius:8px}.visit-note{color:#687c70;font-size:.8rem;line-height:1.6}@media(max-width:600px){.visit-summary{gap:8px}.visit-summary .visit-panel{padding:12px;font-size:.75rem}.visit-summary strong{font-size:1.2rem}}
.visit-chart-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:18px}.visit-chart-wide{grid-column:1/-1}.visit-chart-wrap{position:relative;height:300px}.visit-insights{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.visit-insights .visit-panel{margin:0;font-size:.78rem;color:#687c70}.visit-insights strong{display:block;color:#19372a;font-size:1.05rem;margin-top:8px;overflow-wrap:anywhere}.visit-chart-empty{height:220px;display:grid;place-items:center;color:#687c70;font-size:.85rem}.visit-chart-caption{font-size:.75rem;color:#687c70;margin:0 0 14px}@media(max-width:900px){.visit-chart-grid{grid-template-columns:1fr}.visit-insights{grid-template-columns:repeat(2,minmax(0,1fr))}.visit-chart-wrap{height:260px}}
</style></head><body><aside id="sidebar-container"></aside><header id="navbar-container"></header>
<main class="portal-main"><div class="visit-header"><div><div class="eyebrow">WEBSITE TRAFFIC</div><h1>Page Visits</h1></div><form><label>Period <select name="days" onchange="this.form.submit()"><?php foreach([7,30,90] as $value):?><option value="<?= $value ?>" <?= $days===$value?'selected':'' ?>>Last <?= $value ?> days</option><?php endforeach;?></select></label></form></div>
<p class="visit-note">Counts start when tracking is enabled. Unique visitors are identified by a browser cookie; signed-in users are counted by account. Rapid reloads within 10 seconds, redirects, API calls, and recognized bots are excluded. Dates use Asia/Manila.</p>
<div class="visit-summary"><?php foreach(['views'=>'Page views','visitors'=>'Unique visitors','users'=>'Signed-in users'] as $key=>$label):?><div class="card-streetside visit-panel"><?= $label ?><strong><?= number_format((int)$summary[$key]) ?></strong></div><?php endforeach;?></div>
<div class="visit-insights">
<div class="card-streetside visit-panel">Average views / day<strong><?= number_format($totalViews/$days,1) ?></strong></div>
<div class="card-streetside visit-panel">Views / unique visitor<strong><?= number_format($viewsPerVisitor,1) ?></strong></div>
<div class="card-streetside visit-panel">Busiest day<strong><?= $peak?htmlspecialchars(date('M j',strtotime($peak['visit_date']))).' · '.number_format((int)$peak['views']).' views':'No visits yet' ?></strong></div>
<div class="card-streetside visit-panel">Most visited page<strong><?= htmlspecialchars($pages[0]['page_path']??'No visits yet') ?></strong></div>
</div>
<div class="visit-chart-grid">
<section class="card-streetside visit-panel visit-chart-wide"><h2>Traffic over time</h2><p class="visit-chart-caption">Daily page views and unique visitors. Days without recorded visits appear as zero.</p><div class="visit-chart-wrap"><canvas id="traffic-trend" role="img" aria-label="Daily page views and unique visitors for the selected period"></canvas></div></section>
<section class="card-streetside visit-panel"><h2>Top pages</h2><p class="visit-chart-caption">The ten pages with the most recorded views.</p><?php if($totalViews):?><div class="visit-chart-wrap"><canvas id="traffic-top-pages" role="img" aria-label="Most visited pages ranked by page views"></canvas></div><?php else:?><div class="visit-chart-empty">No page visits recorded yet.</div><?php endif;?></section>
<section class="card-streetside visit-panel"><h2>Traffic by page section</h2><p class="visit-chart-caption">Share of views across landing, public, player, owner, and admin pages.</p><?php if($totalViews):?><div class="visit-chart-wrap"><canvas id="traffic-sections" role="img" aria-label="Page views by website section"></canvas></div><?php else:?><div class="visit-chart-empty">No page visits recorded yet.</div><?php endif;?></section>
</div>
<section class="card-streetside visit-panel"><h2>Visits by page</h2><div class="visit-table-wrap"><table id="visits-by-page" class="visit-table"><thead><tr><th>Page</th><th>Views</th><th>Unique visitors</th><th>Signed-in users</th><th>Last visit</th></tr></thead><tbody><?php foreach($pages as $page):?><tr><td><?= htmlspecialchars($page['page_path']) ?></td><td><?= (int)$page['views'] ?></td><td><?= (int)$page['visitors'] ?></td><td><?= (int)$page['users'] ?></td><td><?= htmlspecialchars($page['last_seen']) ?></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="card-streetside visit-panel"><h2>Daily traffic</h2><div class="visit-table-wrap"><table id="visits-by-day" class="visit-table"><thead><tr><th>Date</th><th>Views</th><th>Unique visitors</th></tr></thead><tbody><?php foreach($daily as $day):?><tr><td><?= htmlspecialchars($day['visit_date']) ?></td><td><?= (int)$day['views'] ?></td><td><?= (int)$day['visitors'] ?></td></tr><?php endforeach;?></tbody></table></div></section><footer id="footer-container"></footer></main>
<?php require __DIR__.'/../../includes/scripts.php'; ?>
<script type="application/json" id="page-analytics-data"><?= json_encode($chartData,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<script src="/pikvero/assets/js/components/page-analytics.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/page-analytics.js') ?>"></script>
<script>document.addEventListener('DOMContentLoaded',async()=>{await NavbarComponent.render('#navbar-container',true);await AuthHelper.checkSession();SidebarComponent.render('page-visits','admin');FooterComponent.render('#footer-container',true);$('#visits-by-page').DataTable({order:[[1,'desc']],pageLength:10});$('#visits-by-day').DataTable({order:[[0,'desc']],pageLength:10});});</script></body></html>
