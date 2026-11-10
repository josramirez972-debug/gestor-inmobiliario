(function(){
  function initSearch(){
    const form=document.querySelector('[data-gip-search]'),out=document.querySelector('[data-gip-output]');
    if(!form||!out||!window.gipData)return;
    form.addEventListener('submit',async function(e){
      e.preventDefault();
      const data=new FormData();data.append('action','gip_filter_properties');data.append('nonce',gipData.nonce);
      data.append('filters[city]',form.city?.value||'');data.append('filters[type]',form.type?.value||'');data.append('filters[bedrooms]',form.bedrooms?.value||'');data.append('filters[bathrooms]',form.bathrooms?.value||'');
      out.setAttribute('aria-busy','true');
      try{const r=await fetch(gipData.ajaxUrl,{method:'POST',body:data});out.innerHTML=await r.text();}catch(err){out.innerHTML='<p>No fue posible cargar las propiedades.</p>';}finally{out.removeAttribute('aria-busy');}
    });
  }
  function initMap(){
    const map=document.getElementById('gip-map');
    if(!map||map.dataset.initialized==='1')return;
    const lat=parseFloat(map.dataset.lat),lng=parseFloat(map.dataset.lng);
    if(!Number.isFinite(lat)||!Number.isFinite(lng))return;
    if(!window.L){
      map.innerHTML='<div class="gip-map-error">No se pudo cargar el mapa.</div>';
      return;
    }
    map.dataset.initialized='1';
    const m=L.map(map,{scrollWheelZoom:false}).setView([lat,lng],15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap contributors'}).addTo(m);
    L.marker([lat,lng]).addTo(m).bindPopup(map.dataset.title||'Propiedad').openPopup();
    setTimeout(()=>m.invalidateSize(),250);
  }
  document.addEventListener('DOMContentLoaded',function(){initSearch();initMap();});
  window.addEventListener('load',initMap);
})();

(function(){
  function initPropertyGallery(){
    const gallery=document.querySelector('[data-gip-gallery]');
    if(!gallery) return;
    const dataNode=gallery.querySelector('.gip-gallery-data');
    const main=gallery.querySelector('[data-gip-main-image]');
    if(!dataNode||!main) return;
    let images=[]; try{images=JSON.parse(dataNode.textContent)||[];}catch(e){return;}
    let index=0;
    const current=gallery.querySelector('[data-gip-current]');
    const thumbs=[...gallery.querySelectorAll('[data-gip-index]')];
    const show=i=>{ if(!images.length) return; index=(i+images.length)%images.length; main.src=images[index]; if(current) current.textContent=index+1; thumbs.forEach(t=>t.classList.toggle('is-active',Number(t.dataset.gipIndex)===index)); };
    gallery.querySelector('.gip-gallery-prev')?.addEventListener('click',()=>show(index-1));
    gallery.querySelector('.gip-gallery-next')?.addEventListener('click',()=>show(index+1));
    thumbs.forEach(t=>t.addEventListener('click',()=>show(Number(t.dataset.gipIndex))));
  }
  document.addEventListener('DOMContentLoaded',initPropertyGallery);
})();
