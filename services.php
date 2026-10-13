<?php
require __DIR__.'/includes/layout.php';
$c=cm_page_content();$id=$_GET['id']??'';$service=null;
if($id!==''){foreach($c['services']as$s)if($s['id']===$id)$service=$s;if(!$service)cm_missing();}
cm_header($service?$service['title']:'خدماتنا',$service?$service['summary']:'توريد وتركيب البورسلين والسيراميك وتشطيبات الأرضيات والحوائط وحمامات السباحة');
if($service): ?>
<section class="detail-hero"><img src="<?=cm_h(cm_image($service['image']))?>" alt="<?=cm_h($service['title'])?>"><div><a class="breadcrumb" href="services.html">خدماتنا /</a><h1><?=cm_h($service['title'])?></h1><p><?=cm_h($service['summary'])?></p></div></section>
<section class="section detail-layout"><article><span class="eyebrow">تفاصيل الخدمة</span><h2>حلول تبدأ من احتياجك</h2><p class="long-copy"><?=nl2br(cm_h($service['description']))?></p><h3>ما الذي نقدمه؟</h3><ul class="feature-list"><?php foreach($service['features']as$f): ?><li><?=cm_h($f)?></li><?php endforeach;?></ul><h3>كيف نعمل معك؟</h3><div class="work-steps"><?php foreach($service['steps']as$i=>$step):?><div><span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><p><?=cm_h($step)?></p></div><?php endforeach;?></div></article><aside class="detail-aside"><span class="eyebrow">خطوتك القادمة</span><h3>لنعرف أكثر عن مشروعك</h3><p>شاركنا الموقع والمساحة وصور المكان، لنراجع احتياجاتك ونحدد نطاق العمل</p><a class="button navy" href="quote.html?service=<?=rawurlencode($service['id'])?>">اطلب عرض سعر ↖</a><small>عرض السعر يُحدد بعد مراجعة تفاصيل المشروع</small></aside></section>
<?php else:?>
<section class="page-intro section"><span class="eyebrow">خبراتنا / خدماتنا</span><h1>لكل مساحة،<br><span>لمستها الخاصة</span></h1><p>اكتشف حلولنا، واختر الخدمة المناسبة لمشروعك</p></section><section class="section page-cards"><div class="service-grid"><?php foreach($c['services']as$i=>$s):?><a class="service-card" data-detail-id="<?=cm_h($s['id'])?>" href="services.html?id=<?=rawurlencode($s['id'])?>"><img src="<?=cm_h(cm_image($s['image']))?>" alt="<?=cm_h($s['title'])?>" loading="lazy"><span class="service-index"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><div><h3><?=cm_h($s['title'])?></h3><p><?=cm_h($s['summary'])?></p><span class="round-arrow">↖</span></div></a><?php endforeach;?></div><?php if(!$c['services']):?><p>سيتم نشر تفاصيل الخدمات قريبًا</p><?php endif;?></section>
<script type="application/json" id="detail-data" data-kind="service"><?=json_encode($c['services'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?></script>
<?php endif;cm_footer();?>

