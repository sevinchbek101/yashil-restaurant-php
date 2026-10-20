(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const page = $('[data-page]')?.dataset.page || '';
  const csrf = $('meta[name="csrf-token"]')?.content || '';
  const state = { products: [], zones: [], checkoutEnabled: false, category: 'all', query: '', lang: localStorage.getItem('yashil:lang') || 'uz' };
  let cart = [];
  try { cart = JSON.parse(localStorage.getItem('yashil:cart') || '[]'); if (!Array.isArray(cart)) cart = []; } catch { cart = []; }

  const copy = {
    uz:{home:{eye:'YANGI MAVSUM TA’MI',hero:'Yengil ye.<br>Yorqin yasha.',heroText:'Mahalliy mahsulotlar, kuchli ta’m va har kuni yangi tayyorlanadigan foydali taomlar.',see:'MENYUNI KO‘RISH',popular:'ENG KO‘P TANLANGAN',popularTitle:'Mashhur<br>taomlar.',sample:'Bosh sahifada sevimli taomlardan namuna. To‘liq tanlov menyuda.',all:'BARCHA MENYU →'},menu:{eye:'HAR KUNI YANGI TAYYORLANADI',title:'Bugun nima<br>tanlaysiz?',intro:'Foydali bowl, yangi salat va tetiklashtiruvchi ichimliklar — barchasi bir joyda.'}},
    ru:{home:{eye:'ВКУС НОВОГО СЕЗОНА',hero:'Ешь легко.<br>Живи ярко.',heroText:'Местные продукты, яркий вкус и полезные блюда, приготовленные свежими каждый день.',see:'СМОТРЕТЬ МЕНЮ',popular:'ЧАЩЕ ВСЕГО ВЫБИРАЮТ',popularTitle:'Популярные<br>блюда.',sample:'Несколько любимых блюд. Полный выбор находится в меню.',all:'ВСЁ МЕНЮ →'},menu:{eye:'ГОТОВИМ СВЕЖИМ КАЖДЫЙ ДЕНЬ',title:'Что выберете<br>сегодня?',intro:'Полезные боулы, свежие салаты и напитки — всё в одном месте.'}},
    en:{home:{eye:'A TASTE OF THE NEW SEASON',hero:'Eat light.<br>Live bright.',heroText:'Local ingredients, bold flavor and healthy meals made fresh every day.',see:'VIEW MENU',popular:'MOST POPULAR',popularTitle:'Favorite<br>dishes.',sample:'A sample of customer favorites. Find the full selection in the menu.',all:'FULL MENU →'},menu:{eye:'FRESHLY MADE EVERY DAY',title:'What will you<br>choose today?',intro:'Healthy bowls, fresh salads and refreshing drinks — all in one place.'}}
  };
  const locale = () => state.lang === 'ru' ? 'ru-RU' : state.lang === 'en' ? 'en-US' : 'uz-UZ';
  const money = value => Number(value).toLocaleString(locale()) + ' so‘m';
  const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
  const icon = name => name === 'trash' ? '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg>' : '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"><path d="M3 4h2l2 11h10l2-7H7M12 7v6M9 10h6M9 20h.01M17 20h.01"/></svg>';

  function applyLanguage(lang) {
    state.lang = ['uz','ru','en'].includes(lang) ? lang : 'uz';
    localStorage.setItem('yashil:lang', state.lang); document.documentElement.lang = state.lang;
    $$('[data-lang]').forEach(button => button.classList.toggle('active', button.dataset.lang === state.lang));
    $$('[data-i18n]').forEach(element => { const [group,key]=element.dataset.i18n.split('.'); element.textContent=copy[state.lang]?.[group]?.[key] || element.textContent; });
    $$('[data-i18n-html]').forEach(element => { const [group,key]=element.dataset.i18nHtml.split('.'); element.innerHTML=copy[state.lang]?.[group]?.[key] || element.innerHTML; });
    renderCart(); if (page === 'menu') renderMenu();
  }
  document.addEventListener('click', event => { const button = event.target.closest('[data-lang]'); if (button) applyLanguage(button.dataset.lang); });

  const mobileToggle = $('#mobile-menu-toggle'), mobilePanel = $('#mobile-navigation');
  mobileToggle?.addEventListener('click', () => { const open=mobilePanel.hidden; mobilePanel.hidden=!open; mobilePanel.classList.toggle('open',open); mobileToggle.setAttribute('aria-expanded',String(open)); });

  function persistCart() { localStorage.setItem('yashil:cart', JSON.stringify(cart)); renderCart(); }
  function addToCart(id) { const product=state.products.find(item=>Number(item.id)===Number(id)); if(product){cart.push(product);persistCart();} }
  function removeFromCart(index) { cart.splice(index,1); persistCart(); }
  function renderCart() {
    const count=$('#cart-count'),list=$('#cart-list'),total=$('#cart-total');
    if(count) count.textContent=String(cart.length);
    if(total) total.textContent=money(cart.reduce((sum,item)=>sum+Number(item.price),0));
    if(!list)return;
    list.innerHTML=cart.length?cart.map((item,index)=>`<div class="cartRow"><span><b>${escapeHtml(item.name)}</b><small>${money(item.price)}</small></span><button class="iconBtn" data-remove="${index}" aria-label="Olib tashlash: ${escapeHtml(item.name)}">${icon('trash')} Olib tashlash</button></div>`).join(''):'<p>Savatingiz hozircha bo‘sh.</p>';
    const checkout=$('#cart-checkout'); if(checkout) checkout.textContent=cart.length?'BUYURTMANI RASMIYLASHTIRISH':'MENYUNI KO‘RISH';
  }
  document.addEventListener('click',event=>{const remove=event.target.closest('[data-remove]');if(remove)removeFromCart(Number(remove.dataset.remove));const add=event.target.closest('[data-add]');if(add)addToCart(add.dataset.add);});
  const cartBackdrop=$('#cart-backdrop'),cartOpen=$('#cart-open'),cartClose=$('#cart-close');
  function setCart(open){if(!cartBackdrop)return;cartBackdrop.classList.toggle('open',open);cartBackdrop.setAttribute('aria-hidden',String(!open));document.body.style.overflow=open?'hidden':'';if(open)$('#cart-dialog')?.focus();else cartOpen?.focus();}
  cartOpen?.addEventListener('click',()=>setCart(true));cartClose?.addEventListener('click',()=>setCart(false));cartBackdrop?.addEventListener('click',e=>{if(e.target===cartBackdrop)setCart(false)});document.addEventListener('keydown',e=>{if(e.key==='Escape')setCart(false)});

  function productCard(product){return `<article class="food"><div class="foodImg"><img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" width="800" height="800" loading="lazy" decoding="async">${product.badge?`<span>${escapeHtml(product.badge)}</span>`:''}</div><h3>${escapeHtml(product.name)}</h3><p>${escapeHtml(product.description)}</p><div><b>${money(product.price)}</b><button class="iconBtn" data-add="${product.id}" aria-label="Savatga qo‘shish: ${escapeHtml(product.name)}">${icon('cart')}</button></div></article>`}
  const skeletons=count=>Array.from({length:count},()=>'<article class="foodSkeleton" aria-hidden="true"><i></i><b></b><span></span><span></span></article>').join('');
  async function loadCatalog(){
    const home=$('#home-products'),menu=$('#menu-products'); if(home)home.innerHTML=skeletons(3);if(menu)menu.innerHTML=skeletons(8);
    try{const response=await fetch('/api/data',{headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok)throw new Error(data.error||'Menyu yuklanmadi');state.products=data.products||[];state.zones=data.zones||[];state.checkoutEnabled=!!data.checkoutEnabled;if(home)home.innerHTML=state.products.slice(0,3).map(productCard).join('');if(menu){renderMenu();prepareCheckout();}}
    catch(error){const target=home||menu;if(target)target.innerHTML=`<div class="stateCard"><p>${escapeHtml(error.message)}</p><button class="outline" onclick="location.reload()">Qayta urinish</button></div>`;}
  }
  function renderMenu(){const target=$('#menu-products');if(!target)return;const q=state.query.trim().toLocaleLowerCase();const shown=state.products.filter(item=>(state.category==='all'||item.category===state.category)&&(!q||`${item.name} ${item.description}`.toLocaleLowerCase().includes(q)));target.innerHTML=shown.map(productCard).join('');$('#result-count').innerHTML=`<b>${shown.length}</b> ta taom topildi`;$('#menu-empty').hidden=shown.length>0;}
  $$('.categoryTabs [data-category]').forEach(button=>button.addEventListener('click',()=>{state.category=button.dataset.category;$$('.categoryTabs button').forEach(item=>{const active=item===button;item.classList.toggle('active',active);item.setAttribute('aria-pressed',String(active));});renderMenu();}));
  $('#menu-search')?.addEventListener('input',event=>{state.query=event.target.value;renderMenu()});$('#reset-filters')?.addEventListener('click',()=>{state.query='';state.category='all';$('#menu-search').value='';$('.categoryTabs [data-category="all"]')?.click();});

  const providers=['click','payme','uzcard','humo','plum','paylov','xazna','oson'];
  function prepareCheckout(){const select=$('#zone-select');if(select)select.innerHTML=state.zones.map(zone=>`<option value="${zone.id}">${escapeHtml(zone.name)} · ${money(zone.fee)}</option>`).join('');const grid=$('#provider-grid');if(grid)grid.innerHTML=providers.map((name,index)=>`<label class="providerTile brand-${name}"><input type="radio" name="payment" value="checkout" ${state.checkoutEnabled?'':'disabled'}><span><img src="/assets/payment-logos/${name}.png" alt="${name}" width="120" height="48"></span></label>`).join('');$('#online-payment')?.classList.toggle('disabled',!state.checkoutEnabled);}
  const checkoutModal=$('#checkout-modal');function setCheckout(open){if(!checkoutModal)return;checkoutModal.hidden=!open;document.body.style.overflow=open?'hidden':'';if(open){setCart(false);$('#checkout-count').textContent=`${cart.length} ta`;$('#checkout-total').textContent=money(cart.reduce((s,i)=>s+Number(i.price),0));setTimeout(()=>$('#checkout-form input')?.focus(),0);}}
  if(page==='menu'){$('#cart-checkout')?.addEventListener('click',event=>{if(cart.length){event.preventDefault();setCheckout(true);}});$('#checkout-close')?.addEventListener('click',()=>setCheckout(false));if(new URLSearchParams(location.search).get('checkout')==='1'&&cart.length)setTimeout(()=>setCheckout(true),500);}
  $('#checkout-form')?.addEventListener('submit',async event=>{event.preventDefault();const error=$('#checkout-error'),button=$('#checkout-submit');error.hidden=true;if(!cart.length){error.textContent='Savat bo‘sh';error.hidden=false;return;}button.disabled=true;button.textContent='YUBORILMOQDA...';const values=Object.fromEntries(new FormData(event.currentTarget));try{const response=await fetch('/api/data',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify({...values,_csrf:csrf,action:'create-order',items:cart})});const data=await response.json();if(!response.ok)throw new Error(data.error||'Xatolik');localStorage.setItem('yashil:lastOrder',JSON.stringify({id:data.id,phone:values.phone}));cart=[];persistCart();if(data.paymentUrl){location.assign(data.paymentUrl);return;}event.currentTarget.hidden=true;$('#checkout-success').hidden=false;$('#success-id').textContent=`#${data.id}`;}catch(err){error.textContent=err.message;error.hidden=false;}finally{button.disabled=false;button.textContent='TASDIQLASH';}});

  let trackMode='id';$$('[data-mode]').forEach(button=>button.addEventListener('click',()=>{trackMode=button.dataset.mode;$$('[data-mode]').forEach(x=>{x.classList.toggle('active',x===button);x.setAttribute('aria-pressed',String(x===button));});const input=$('#track-input'),label=$('#track-label');if(trackMode==='id'){label.childNodes[0].textContent='Buyurtma raqami';$('small',label).textContent='Bitta buyurtmani topadi';input.type='text';input.inputMode='numeric';input.placeholder='Masalan: 24';input.value=input.value.replace(/\D/g,'');}else{label.childNodes[0].textContent='Telefon raqami';$('small',label).textContent='Barcha buyurtmalarni topadi';input.type='tel';input.inputMode='tel';input.placeholder='+998 90 123 45 67';}}));
  const statusNames={new:'Qabul qilindi',preparing:'Tayyorlanmoqda',delivery:'Yetkazilmoqda',done:'Yetkazildi',cancelled:'Bekor qilindi'};
  function renderOrders(orders){$('#tracking-results').innerHTML=orders.map(order=>`<article class="trackingCard"><div class="trackingTop"><div><span>BUYURTMA #${order.id}</span><h2>${escapeHtml(order.customer_name)}</h2><small>${new Date(order.created_at).toLocaleString(locale())}</small></div><div><b>${money(order.total)}</b><em class="paymentBadge ${order.payment_status==='paid'?'paid':'unpaid'}">${order.payment_status==='paid'?'To‘lov tasdiqlandi':order.payment==='checkout'?'To‘lov kutilmoqda':'Olganda to‘lanadi'}</em></div></div><div class="timeline"><div class="${order.status!=='new'?'complete':'current'}"><i>✓</i><span><b>Qabul qilindi</b></span></div><div class="${['delivery','done'].includes(order.status)?'complete':order.status==='preparing'?'current':''}"><i>2</i><span><b>Tayyorlanmoqda</b></span></div><div class="${order.status==='done'?'complete':order.status==='delivery'?'current':''}"><i>3</i><span><b>Yetkazilmoqda</b></span></div><div class="${order.status==='done'?'current':''}"><i>✓</i><span><b>Yetkazildi</b></span></div></div><div class="trackingItems"><h3>Buyurtma tarkibi</h3>${order.items.map(item=>`<div><span>${escapeHtml(item.name)}</span><b>${money(item.price)}</b></div>`).join('')}<div class="trackingSum total"><span>Jami</span><b>${money(order.total)}</b></div></div></article>`).join('');}
  $('#track-form')?.addEventListener('submit',async event=>{event.preventDefault();const input=$('#track-input').value.trim(),error=$('#track-error');error.hidden=true;if(!input){error.textContent='Ma’lumot kiriting';error.hidden=false;return;}try{const params=new URLSearchParams({track:'1',[trackMode==='id'?'order':'phone']:input});const response=await fetch('/api/data?'+params);const data=await response.json();if(!response.ok)throw new Error(data.error||'Topilmadi');renderOrders(data.orders||[data.order]);}catch(err){error.textContent=err.message;error.hidden=false;}});

  if(page==='track'){
    try{
      const last=JSON.parse(localStorage.getItem('yashil:lastOrder')||'null');
      if(last?.id){$('#track-input').value=String(last.id);if(new URLSearchParams(location.search).get('payment')==='returned')setTimeout(()=>$('#track-form')?.requestSubmit(),250);}
    }catch{}
  }

  function setupMotion(){if(matchMedia('(prefers-reduced-motion: reduce)').matches||!matchMedia('(hover:hover) and (pointer:fine)').matches)return;let frame=0,lastEvent;document.addEventListener('pointermove',event=>{lastEvent=event;if(frame)return;frame=requestAnimationFrame(()=>{frame=0;const card=lastEvent.target.closest('.food,.trackingCard,.facts>div');if(!card)return;const r=card.getBoundingClientRect(),x=(lastEvent.clientX-r.left)/r.width-.5,y=(lastEvent.clientY-r.top)/r.height-.5;card.style.transform=`perspective(1000px) rotateX(${-y*6}deg) rotateY(${x*6}deg) translateY(-3px)`;card.onpointerleave=()=>card.style.transform='';});},{passive:true});}
  renderCart();applyLanguage(state.lang);loadCatalog();setupMotion();
})();
