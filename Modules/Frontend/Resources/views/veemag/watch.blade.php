@extends('frontend::layouts.master')

@section('title'){{ $issue->title }} — Player @endsection

@push('after-styles')
<style>
  body{background:#000;}
  .vmp{position:fixed;inset:0;background:#000;display:flex;flex-direction:column;z-index:1200;}
  .vmp__bar{display:flex;align-items:center;gap:1rem;padding:.8rem 1.2rem;color:#fff;background:linear-gradient(180deg,rgba(0,0,0,.8),transparent);position:absolute;top:0;left:0;right:0;z-index:5;}
  .vmp__bar a,.vmp__bar button{color:#fff;background:none;border:0;cursor:pointer;font-size:1.4rem;line-height:1;}
  .vmp__issue{font-size:.8rem;letter-spacing:.14em;text-transform:uppercase;color:#c9c6cf;}
  .vmp__issue b{display:block;font-size:1rem;letter-spacing:-.01em;text-transform:none;color:#fff;}
  .vmp__spacer{flex:1;}
  .vmp__stage{flex:1;position:relative;}
  .vmp__stage iframe,.vmp__stage video{position:absolute;inset:0;width:100%;height:100%;border:0;background:#000;}
  .vmp__nav{position:absolute;bottom:0;left:0;right:0;display:flex;align-items:center;gap:1rem;padding:1rem 1.2rem;color:#fff;background:linear-gradient(0deg,rgba(0,0,0,.85),transparent);z-index:5;}
  .vmp__now{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;color:var(--bs-primary);}
  .vmp__now b{display:block;color:#fff;font-size:1rem;letter-spacing:-.01em;text-transform:none;margin-top:.15rem;}
  .vmp__btn{border:1px solid rgba(255,255,255,.3)!important;border-radius:2rem!important;padding:.5rem 1rem!important;font-size:.8rem!important;display:inline-flex;align-items:center;gap:.4rem;color:#fff;background:none;cursor:pointer;}
  .vmp__transition{position:absolute;inset:0;background:#0c0b11;display:flex;flex-direction:column;justify-content:center;padding:0 8vw;z-index:8;opacity:0;pointer-events:none;transition:opacity .35s;}
  .vmp__transition.show{opacity:1;}
  .vmp__transition .n{font-family:ui-monospace,monospace;color:var(--bs-primary);font-size:1.4rem;letter-spacing:.2em;}
  .vmp__transition .t{font-family:ui-monospace,monospace;color:#9a95a3;letter-spacing:.2em;text-transform:uppercase;font-size:.9rem;margin:1rem 0 .6rem;}
  .vmp__transition .h{font-weight:800;text-transform:uppercase;letter-spacing:-.02em;line-height:1.02;font-size:clamp(2rem,6vw,4rem);color:#fff;}
  .vmp__drawer{position:absolute;top:0;right:0;bottom:0;width:min(26rem,90vw);background:#141019;transform:translateX(100%);transition:transform .3s;z-index:10;overflow-y:auto;padding:1.2rem;}
  .vmp__drawer.open{transform:translateX(0);}
  .vmp__drawer h3{font-family:ui-monospace,monospace;letter-spacing:.2em;text-transform:uppercase;font-size:.8rem;color:var(--bs-primary);margin:2.5rem 0 1rem;}
  .vmp__item{display:flex;gap:.8rem;padding:.7rem 0;border-top:1px solid rgba(255,255,255,.07);cursor:pointer;color:#fff;}
  .vmp__item:hover{color:var(--bs-primary);}
  .vmp__item .idx{font-family:ui-monospace,monospace;color:#9a95a3;font-size:.8rem;}
  .vmp__item.active .idx{color:var(--bs-primary);}
  .vmp__item .ty{font-family:ui-monospace,monospace;font-size:.66rem;letter-spacing:.12em;text-transform:uppercase;color:#9a95a3;}
  .vmp__item .ti{font-size:.95rem;font-weight:500;}
  .vmp__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.4);z-index:9;opacity:0;pointer-events:none;transition:opacity .3s;}
  .vmp__backdrop.open{opacity:1;pointer-events:auto;}
</style>
@endpush

@section('content')
<div class="vmp" id="vmp">
  <div class="vmp__bar">
    <a href="{{ route('veemag.detail', $issue->slug) }}" title="Back to issue"><i class="ph ph-arrow-left"></i></a>
    <div class="vmp__issue"><span>{{ $issue->publication->title ?? '' }} • {{ $issue->issue_label }}</span><b>{{ $issue->title }}</b></div>
    <div class="vmp__spacer"></div>
    <button id="vmp-contents-btn" title="Contents"><i class="ph ph-list-bullets"></i></button>
  </div>

  <div class="vmp__stage">
    <div id="vmp-player"></div>
    <div class="vmp__transition" id="vmp-transition">
      <div class="n" id="vmp-tr-num"></div>
      <div class="t" id="vmp-tr-type"></div>
      <div class="h" id="vmp-tr-title"></div>
    </div>
  </div>

  <div class="vmp__nav">
    <div class="vmp__now"><span id="vmp-now-type"></span><b id="vmp-now-title"></b></div>
    <div class="vmp__spacer"></div>
    <button class="vmp__btn" id="vmp-prev"><i class="ph ph-skip-back"></i> Prev</button>
    <button class="vmp__btn" id="vmp-next">Next <i class="ph ph-skip-forward"></i></button>
  </div>

  <div class="vmp__backdrop" id="vmp-backdrop"></div>
  <div class="vmp__drawer" id="vmp-drawer">
    <h3>Contents</h3>
    <div id="vmp-list"></div>
  </div>
</div>

<script>
  window.VM_PLAYLIST = @json($playlist);
  window.VM_START = {{ (int) request('section', 0) }};
</script>
<script src="https://www.youtube.com/iframe_api"></script>
<script>
(function(){
  var pl = window.VM_PLAYLIST || [];
  var idx = 0;
  if (window.VM_START) { var s = pl.findIndex(function(x){return x.id == window.VM_START;}); if (s>=0) idx = s; }
  var ytPlayer = null, ytReady = false, pendingId = null;

  var elNowType=document.getElementById('vmp-now-type'), elNowTitle=document.getElementById('vmp-now-title');
  var tr=document.getElementById('vmp-transition'), trNum=document.getElementById('vmp-tr-num'), trType=document.getElementById('vmp-tr-type'), trTitle=document.getElementById('vmp-tr-title');
  var drawer=document.getElementById('vmp-drawer'), backdrop=document.getElementById('vmp-backdrop'), list=document.getElementById('vmp-list');

  function pad(n){ return String(n).padStart(2,'0'); }

  function buildList(){
    list.innerHTML='';
    pl.forEach(function(sec,i){
      var d=document.createElement('div'); d.className='vmp__item'+(i===idx?' active':'');
      d.innerHTML='<span class="idx">'+pad(i+1)+'</span><div><div class="ty">'+(sec.type_label||'')+'</div><div class="ti">'+(sec.title||'')+'</div></div>';
      d.onclick=function(){ go(i); closeDrawer(); };
      list.appendChild(d);
    });
  }
  function markActive(){ Array.prototype.forEach.call(list.children,function(c,i){c.classList.toggle('active',i===idx);}); }
  function openDrawer(){ drawer.classList.add('open'); backdrop.classList.add('open'); }
  function closeDrawer(){ drawer.classList.remove('open'); backdrop.classList.remove('open'); }
  document.getElementById('vmp-contents-btn').onclick=openDrawer;
  backdrop.onclick=closeDrawer;

  function showTransition(sec,i,cb){
    trNum.textContent=pad(i+1);
    trType.textContent=sec.type_label||'';
    trTitle.textContent=sec.title||'';
    tr.classList.add('show');
    setTimeout(function(){ tr.classList.remove('show'); if(cb) cb(); }, sec.type==='advertisement'?1200:1800);
  }

  function loadCurrent(){
    var sec=pl[idx]; if(!sec) return;
    elNowType.textContent=sec.type_label||''; elNowTitle.textContent=sec.title||'';
    markActive();
    showTransition(sec, idx, function(){
      if(sec.provider==='youtube' && sec.video_id){
        if(ytReady && ytPlayer){ ytPlayer.loadVideoById(sec.video_id); }
        else { pendingId=sec.video_id; }
      }
    });
  }
  function go(i){ if(i<0||i>=pl.length) return; idx=i; loadCurrent(); }
  document.getElementById('vmp-prev').onclick=function(){ go(idx-1); };
  document.getElementById('vmp-next').onclick=function(){ go(idx+1); };

  window.onYouTubeIframeAPIReady=function(){
    var first=pl[idx]||{};
    ytPlayer=new YT.Player('vmp-player',{
      width:'100%',height:'100%',
      videoId:(first.provider==='youtube'?first.video_id:'') || '',
      playerVars:{autoplay:1,rel:0,modestbranding:1,playsinline:1},
      events:{
        onReady:function(){ ytReady=true; if(pendingId){ ytPlayer.loadVideoById(pendingId); pendingId=null; } },
        onStateChange:function(e){ if(e.data===YT.PlayerState.ENDED && idx<pl.length-1){ go(idx+1); } }
      }
    });
  };

  buildList();
  var s0=pl[idx]; if(s0){ elNowType.textContent=s0.type_label||''; elNowTitle.textContent=s0.title||''; showTransition(s0, idx); }
})();
</script>
@endsection
