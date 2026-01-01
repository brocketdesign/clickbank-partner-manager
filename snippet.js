(function(){
  // Lightweight snippet for partner ads (simplified)
  var script = document.currentScript || (function(){var s = document.getElementsByTagName('script'); return s[s.length-1];})();
  if(!script) return;

  var partner = script.getAttribute('data-partner');
  if (!partner) return;

  // CMP check (keeps previous behavior but simpler)
  function hasConsent(cb) {
    if (typeof window.__tcfapi === 'function') {
      try {
        window.__tcfapi('getTCData', 2, function(tcData){
          var purposes = (tcData && tcData.purpose && tcData.purpose.consents) || {};
          cb(!!purposes[1]);
        });
      } catch(e) { cb(false); }
    } else cb(false);
  }

  // Fetch config (omit credentials to avoid cookie/CORS preflight issues)
  async function fetchConfig() {
    var url = 'https://adeasynow.com/api/snippet/config.php?partner=' + encodeURIComponent(partner);
    var res = await fetch(url, {method:'GET', credentials:'omit', headers:{'Accept':'application/json'}});
    if (!res.ok) throw new Error('config fetch failed');
    return res.json();
  }

  function chooseCreative(creatives) {
    if (!creatives || creatives.length === 0) return null;
    var total = creatives.reduce(function(sum,c){return sum + (parseInt(c.weight)||0);},0);
    var rand = Math.random()*total;
    var acc = 0;
    for (var i=0;i<creatives.length;i++){
      acc += (parseInt(creatives[i].weight)||0);
      if (rand <= acc) return creatives[i];
    }
    return creatives[0];
  }

  function postImpression(creative_id){
    try{
      var url = 'https://adeasynow.com/api/metrics/impression.php';
      var payload = new URLSearchParams({partner: partner, creative_id: creative_id});
      if (navigator.sendBeacon) {
        navigator.sendBeacon(url, payload);
      } else {
        fetch(url, {method:'POST', credentials:'omit', body: payload}).catch(function(){});
      }
    } catch(e){}
  }

  function attachClickHandler(container, creative) {
    container.addEventListener('click', function(e){
      var el = e.target;
      while (el && el !== container) {
        if (el.tagName && el.tagName.toLowerCase() === 'a') {
          e.preventDefault();
          var r = 'https://adeasynow.com/r?aff_id=' + encodeURIComponent(partner) + '&c=' + encodeURIComponent(creative.id);
          window.location.href = r;
          return;
        }
        el = el.parentNode;
      }
    });
  }

  // Main flow
  hasConsent(function(personal){
    (async function(){
      try {
        var data = await fetchConfig();
        if (!data || !data.success) return;
        var cfg = data.config || {};
        var creatives = cfg.creatives || [];
        var creative = chooseCreative(creatives);
        if (!creative) return;

        var selectors = cfg.selectors || ['body'];
        selectors.forEach(function(sel){
          try{
            var nodes = document.querySelectorAll(sel);
            if (!nodes || nodes.length === 0) return;
            var node = nodes[0];
            var wrapper = document.createElement('div');
            wrapper.className = 'cb-snippet cb-creative-' + creative.id;
            wrapper.innerHTML = creative.html || ('<a href="' + (creative.destination_hoplink||'#') + '" target="_blank">Visit</a>');
            // Rewrite anchors to use redirect and safe attributes
            (function(){
              var anchors = wrapper.getElementsByTagName('a');
              for (var j=0;j<anchors.length;j++){
                try {
                  var a = anchors[j];
                  var orig = a.href;
                  if (!orig) continue;
                  var r = 'https://adeasynow.com/r?aff_id=' + encodeURIComponent(partner) + '&c=' + encodeURIComponent(creative.id) + '&u=' + encodeURIComponent(orig);
                  a.setAttribute('href', r);
                  if (!a.getAttribute('target')) a.setAttribute('target','_blank');
                  var rel = a.getAttribute('rel') || '';
                  if (rel.indexOf('noopener') === -1) rel = rel ? rel + ' noopener' : 'noopener';
                  a.setAttribute('rel', rel.trim());
                } catch(e){}
              }
            })();
            node.appendChild(wrapper);
            postImpression(creative.id);
            attachClickHandler(wrapper, creative);
          }catch(e){}
        });
      } catch(e) {}
    })();
  });
})();