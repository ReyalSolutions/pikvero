<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/bootstrap.php';
use App\Application\Services\CourtPricing;
function priceRule($start,$end,$price,$type='all',$day=null) {
    return ['start_time'=>$start,'end_time'=>$end,'price_per_hour'=>$price,'day_type'=>$type,'day_of_week'=>$day,'is_active'=>1];
}
$cases = [
 ['06:00','07:00',[],200],
 ['06:00','07:00',[priceRule('06:00','08:00',350)],350],
 ['06:00','09:00',[priceRule('06:00','08:00',350)],900],
 ['06:00','07:00',[priceRule('06:30','07:00',400)],300],
 ['06:00','07:00',[priceRule('06:00','08:00',350,'monday,friday')],350],
 ['06:00','07:00',[priceRule('06:00','08:00',350,'weekend')],200],
 ['06:00','07:00',[priceRule('06:00','08:00',350,'all',4)],200],
 ['06:00','07:00',[priceRule('06:00','08:00',0)],0],
];
foreach($cases as [$start,$end,$rules,$expected]) {
 if(CourtPricing::calculate('2030-10-18',$start,$end,200,$rules)!==(float)$expected) throw new RuntimeException('Court pricing mismatch.');
}
echo "PASS: base, rule, mixed-rate, partial-hour, multiple-day, weekday and zero-price calculations.\n";
