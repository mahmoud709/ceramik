
const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('nav');
menu?.addEventListener('click',()=>{const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',String(open))});
let category='all';function filterProjects(){const term=(document.querySelector('#project-search')?.value||'').trim().toLocaleLowerCase('en');let count=0;document.querySelectorAll('.portfolio-grid .project-card').forEach(card=>{const visible=(category==='all'||card.dataset.category===category)&&card.dataset.search.toLocaleLowerCase('en').includes(term);card.hidden=!visible;if(visible)count++});const empty=document.querySelector('#project-empty');if(empty)empty.hidden=count>0}
document.addEventListener('click',e=>{const button=e.target.closest('[data-page-filter]');if(!button)return;category=button.dataset.pageFilter;document.querySelectorAll('[data-page-filter]').forEach(b=>b.classList.toggle('active',b===button));filterProjects()});document.addEventListener('input',e=>{if(e.target.id==='project-search')filterProjects()});window.CMResetPortfolio=()=>{category='all';filterProjects()};
const items=[...document.querySelectorAll('[data-gallery-image]')],dialog=document.querySelector('#gallery-modal');let imageIndex=0;
function showImage(i){imageIndex=(i+items.length)%items.length;dialog.querySelector('img').src=items[imageIndex].dataset.galleryImage;dialog.querySelector('img').alt=items[imageIndex].querySelector('img').alt;document.querySelector('#gallery-count').textContent=(imageIndex+1)+' / '+items.length}
items.forEach((item,i)=>item.onclick=()=>{showImage(i);dialog.showModal()});dialog?.querySelector('.modal-close').addEventListener('click',()=>dialog.close());document.querySelector('[data-gallery-prev]')?.addEventListener('click',()=>{if(items.length)showImage(imageIndex-1)});document.querySelector('[data-gallery-next]')?.addEventListener('click',()=>{if(items.length)showImage(imageIndex+1)});dialog?.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'){e.preventDefault();showImage(imageIndex+1)}if(e.key==='ArrowRight'){e.preventDefault();showImage(imageIndex-1)}});



window.CMOpenDetails=function(item,kind='project',start=0){
const details=document.querySelector('#details-modal');if(!details||!item)return;
details.querySelector('img').src=item.image;details.querySelector('img').alt=item.title;
details.querySelector('h2').textContent=item.title;
details.querySelector('.eyebrow').textContent=kind==='service'?'Service details':item.location||'Project details';
details.querySelector('p').textContent=(kind==='service'?[item.description,...(item.features||[]).map(x=>'• '+x),...(item.steps||[]).map((x,i)=>(i+1)+' / '+x)]:[item.description,item.scope?'Scope of work: '+item.scope:'',item.area?'Area: '+item.area:'',item.year?'Year: '+item.year:'',item.sample?'An inspiration concept, not a documented completed project':'']).filter(Boolean).join('\n\n');
details.querySelector('a').href='quote.html?'+(kind==='service'?'service':'project')+'='+encodeURIComponent(item.id);
window.CMDialogs.gallery(details,[item.image,...(item.gallery||[])],item.title);window.CMDialogs.show(details,start);details.showModal();
};
document.addEventListener('click',e=>{const card=e.target.closest('[data-detail-id]');if(!card)return;const node=document.querySelector('#detail-data');const records=window.CMRecords||(node?JSON.parse(node.textContent):[]),kind=document.body.dataset.catalog==='services'?'service':node?.dataset.kind||'project';const item=records.find(p=>p.id===card.dataset.detailId);if(!item)return;e.preventDefault();window.CMOpenDetails(item,kind)});
const detailDialog=document.querySelector('#details-modal');if(detailDialog){detailDialog.querySelector('.modal-close').onclick=()=>detailDialog.close();detailDialog.addEventListener('click',e=>{const r=detailDialog.getBoundingClientRect();if(e.target===detailDialog&&(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom))detailDialog.close()})}

