<?php
require_once __DIR__ . '/../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\CourtRepository;
use App\Infrastructure\Repositories\FacilityRepository;
if (!Auth::check()) {header('Location: /pikvero/public/login.php');exit;}
$courtRepo=new CourtRepository(); $cities=(new FacilityRepository())->getAllCities();
$city=trim((string)($_GET['city'] ?? '')); $type=(string)($_GET['court_type'] ?? '');
if (!in_array($type,['','indoor','outdoor','covered'],true)) $type='';
$courts=[];$loadError=false;
try {$courts=array_slice($courtRepo->search(['city'=>$city,'court_type'=>$type]),0,12);} catch (Throwable $e) {$loadError=true;}
$latestBooking=null;
try {
    $bookings=(new \App\Infrastructure\Repositories\BookingRepository())->getCustomerBookingsDataTables(Auth::id(),0,1,'','4','DESC','all','','','upcoming');
    $latestBooking=$bookings['data'][0] ?? null;
} catch (Throwable $e) {}
function homeEsc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function homeLink($type,$city){return '/pikvero/public/customer/dashboard.php?'.http_build_query(['court_type'=>$type,'city'=>$city]);}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Home — Pikvero</title>
<link rel="icon" href="<?= homeEsc(Auth::getLogoUrl()) ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/pikvero/assets/css/streetside-theme.css?v=<?= filemtime(__DIR__.'/../../assets/css/streetside-theme.css') ?>">
<link rel="stylesheet" href="/pikvero/assets/css/player-home.css?v=<?= filemtime(__DIR__.'/../../assets/css/player-home.css') ?>">
<link rel="manifest" href="/pikvero/public/manifest.php">
<meta name="theme-color" content="#003d2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pikvero">
<link rel="apple-touch-icon" href="/pikvero/assets/images/pwa/icon-180.png">
<script defer src="/pikvero/assets/js/components/pwa.js?v=20261010"></script>
</head><body class="customer-portal player-home">
<header class="home-top"><div class="home-location-row"><form method="get" id="home-location-form"><i class="bi bi-geo-alt"></i><input type="hidden" name="court_type" value="<?= homeEsc($type) ?>"><label class="sr-only" for="home-city">Court location</label><select id="home-city" name="city" onchange="this.form.submit()"><option value="">All locations</option><?php foreach($cities as $c): $name=is_array($c)?($c['city'] ?? ''):$c; ?><option value="<?= homeEsc($name) ?>" <?= $city===$name?'selected':'' ?>><?= homeEsc($name) ?></option><?php endforeach; ?></select></form><a class="home-bell" href="/pikvero/public/notifications.php" aria-label="Your notifications"><i class="bi bi-bell"></i></a></div>
<form action="/pikvero/public/customer/search.php" class="home-search"><div><i class="bi bi-search"></i><label class="sr-only" for="home-query">Search courts or facilities</label><input id="home-query" name="q" placeholder="Search courts, locations, or facilities…"><input type="hidden" name="city" value="<?= homeEsc($city) ?>"><button type="submit" class="sr-only">Search</button></div><a href="/pikvero/public/customer/search.php?<?= homeEsc(http_build_query(['city'=>$city])) ?>" aria-label="Search and filter courts"><i class="bi bi-sliders"></i></a></form></header>
<main class="home-main"><a class="home-banner" href="/pikvero/public/customer/search.php"><div><h1>Find. Book. Rally.</h1><p>Your next game is just a tap away.</p></div></a>
<nav class="home-categories" aria-label="Court categories"><a href="<?= homeEsc(homeLink('',$city)) ?>" <?= $type===''?'aria-current="page"':'' ?>><i class="bi bi-globe2"></i><span>All</span></a><a href="<?= homeEsc(homeLink('indoor',$city)) ?>" <?= $type==='indoor'?'aria-current="page"':'' ?>><i class="bi bi-house"></i><span>Indoor</span></a><a href="<?= homeEsc(homeLink('outdoor',$city)) ?>" <?= $type==='outdoor'?'aria-current="page"':'' ?>><i class="bi bi-sun"></i><span>Outdoor</span></a><a href="/pikvero/public/customer/open-play.php"><i class="bi bi-dribbble"></i><span>Open play</span></a></nav>
<?php if($latestBooking):
$bookingStatus=$latestBooking['booking_status']==='awaiting_payment'?'Pending Payment':ucwords(str_replace('_',' ',$latestBooking['booking_status']));
?>
<section class="home-upcoming-booking" aria-label="Latest upcoming booking">
<div class="home-section-heading"><h2>Upcoming Booking</h2><a href="/pikvero/public/customer/bookings.php">See All <i class="bi bi-chevron-right"></i></a></div>
<a class="home-booking-card" href="/pikvero/public/customer/bookings.php"><img loading="lazy" src="<?= homeEsc($latestBooking['facility_image'] ?: '/pikvero/assets/images/logo.png') ?>" alt="<?= homeEsc($latestBooking['facility_name']) ?>" onerror="this.onerror=null;this.src='/pikvero/assets/images/logo.png'"><div><span class="home-booking-status <?= homeEsc($latestBooking['booking_status']) ?>"><?= homeEsc($bookingStatus) ?></span><strong><?= homeEsc($latestBooking['facility_name']) ?></strong><small><?= homeEsc($latestBooking['court_name']) ?></small><small><i class="bi bi-calendar-event"></i> <?= homeEsc(date('D, M j, Y',strtotime($latestBooking['booking_date']))) ?></small><small><i class="bi bi-clock"></i> <?= homeEsc(date('g:i A',strtotime($latestBooking['start_time'])).' – '.date('g:i A',strtotime($latestBooking['end_time']))) ?></small></div><i class="bi bi-chevron-right"></i></a>
</section>
<?php endif; ?>
<div class="home-section-heading"><h2><?= $city ? 'Courts in '.homeEsc($city) : 'Discover courts' ?></h2><a href="/pikvero/public/customer/search.php?<?= homeEsc(http_build_query(['city'=>$city,'court_type'=>$type])) ?>">See all <i class="bi bi-chevron-right"></i></a></div>
<div class="home-court-grid"><?php foreach($courts as $court):
$images=$courtRepo->getCourtImages((int)$court['id']);$image=$images[0]['image_path'] ?? '/pikvero/assets/images/bg-search.png';
if (!str_starts_with($image,'/') && !str_starts_with($image,'http')) $image='/pikvero/'.ltrim($image,'/');
$amenities=$courtRepo->getCourtAmenities((int)$court['id']);
$url='/pikvero/public/facility?'.http_build_query(['id'=>$court['facility_id'],'court_id'=>$court['id']]); ?>
<article class="home-court-card"><a class="home-court-photo" href="<?= homeEsc($url) ?>" aria-label="View <?= homeEsc($court['name'] ?? $court['facility_name']) ?>"><img src="<?= homeEsc($image) ?>" alt="<?= homeEsc($court['facility_name']) ?> court" loading="lazy" onerror="this.onerror=null;this.src='/pikvero/assets/images/bg-search.png'"></a><button class="home-favorite" data-court="<?= (int)$court['id'] ?>" aria-label="Save <?= homeEsc($court['facility_name']) ?>" aria-pressed="false"><i class="bi bi-heart"></i></button>
<a class="home-court-info" href="<?= homeEsc($url) ?>"><div class="home-court-title"><h3><?= homeEsc($court['facility_name']) ?></h3><span><strong>₱<?= number_format((float)($court['base_price_per_hour'] ?? 0),0) ?></strong><small> / hour</small></span></div><p><i class="bi bi-geo-alt"></i> <?= homeEsc($court['city']) ?> <span>· <?= homeEsc($court['name'] ?? 'Court') ?></span></p><div class="home-tags"><span><?= homeEsc(ucfirst($court['court_type'] ?? 'Court')) ?></span><?php foreach(array_slice($amenities,0,2) as $amenity): ?><span><?= homeEsc($amenity['name'] ?? '') ?></span><?php endforeach; ?></div></a></article>
<?php endforeach; ?></div>
<?php if(!$courts): ?><div class="home-empty"><i class="bi bi-search"></i><h2><?= $loadError?'Unable to load courts':'No courts found' ?></h2><p><?= $loadError?'Please try again in a moment.':'Try another location or browse all court types.' ?></p><a href="/pikvero/public/customer/dashboard.php">Browse all courts</a></div><?php endif; ?>
</main><script src="/pikvero/assets/js/components/bottom-nav.js?v=<?= filemtime(__DIR__.'/../../assets/js/components/bottom-nav.js') ?>"></script><script>BottomNavComponent.render('dashboard');
const savedKey='pikvero_saved_courts_<?= (int)Auth::id() ?>';let saved=[];try{saved=JSON.parse(localStorage.getItem(savedKey)||'[]');if(!Array.isArray(saved))saved=[];}catch(e){}
document.querySelectorAll('.home-favorite').forEach(button=>{function refresh(){const active=saved.includes(button.dataset.court);button.setAttribute('aria-pressed',String(active));button.querySelector('i').className='bi '+(active?'bi-heart-fill':'bi-heart');}refresh();button.addEventListener('click',()=>{saved=saved.includes(button.dataset.court)?saved.filter(id=>id!==button.dataset.court):[...saved,button.dataset.court];try{localStorage.setItem(savedKey,JSON.stringify(saved));}catch(e){}refresh();});});</script><script data-notification-badge src="/pikvero/assets/js/components/notification-badge.js?v=20261010"></script></body></html>
